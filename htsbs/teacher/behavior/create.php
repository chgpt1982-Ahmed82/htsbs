<?php
/*
=====================================================================
teacher/behavior/create.php — إضافة ملاحظة سلوكية
=====================================================================
⚠️ حساسية عالية: الملاحظة السلبية وثيقة تُلصق بسمعة الطالب،
   ويطّلع عليها الأدمن وولي الأمر. لذلك:
   - تُسجَّل السلبية والتنبيه بخطورة "danger" (صف أحمر)
   - يُسجَّل نص الملاحظة كاملاً في السجل — للرجوع عند أي نزاع

التعديلات:
  1. تسجيل إضافة الملاحظة (مع النوع والعنوان والتفاصيل)
  2. 🔴 حماية: التأكد أن الطالب من صفوف هذا المعلم فعلاً
     (كان أي معلم يضيف ملاحظة لأي طالب في المدرسة!)
  3. التحقق من صحة النوع (ENUM) والتاريخ
  4. إشعار ولي الأمر (كان مفقوداً — يُشعَر الطالب والأدمن فقط)
  5. Transaction — لا ملاحظة بلا إشعارات ولا العكس
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/BehaviorNote.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    exit('Unauthorized Access');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$model = new BehaviorNote();

/*
=================================
سجل المعلم
=================================
*/
$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

/*
=================================
قائمة طلاب المعلم (صفوفه فقط)
=================================
*/
$stmt = $db->prepare("
    SELECT DISTINCT
        s.id, s.user_id, s.student_number,
        u.full_name,
        c.class_name
    FROM students s
    INNER JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    INNER JOIN course_assignments ca ON s.class_id = ca.class_id
    WHERE ca.teacher_id = ?
    ORDER BY c.class_name ASC,
             s.student_number ASC,
             u.full_name ASC
");
$stmt->execute([$teacherId]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* أنواع الملاحظات — مطابقة لـ ENUM */
$allowedTypes = ['positive', 'negative', 'warning'];

$typeLabels = [
    'positive' => 'إيجابية',
    'negative' => 'سلبية',
    'warning'  => 'تنبيه',
];

$error = null;

/*
=================================
الحفظ
=================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = (int)($_POST['student_id'] ?? 0);
    $type      = trim((string)($_POST['note_type'] ?? ''));
    $title     = trim((string)($_POST['title'] ?? ''));
    $details   = trim((string)($_POST['details'] ?? ''));
    $noteDate  = trim((string)($_POST['note_date'] ?? ''));

    /* ==================== التحقق من المدخلات ==================== */
    if ($studentId <= 0) {
        die('يرجى اختيار الطالب');
    }

    if (!in_array($type, $allowedTypes, true)) {
        die('نوع الملاحظة غير صالح');
    }

    if ($title === '' || $details === '') {
        die('العنوان والتفاصيل مطلوبان');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $noteDate)) {
        $noteDate = date('Y-m-d');
    }

    /* لا ملاحظات بتاريخ مستقبلي */
    if (strtotime($noteDate) > strtotime(date('Y-m-d'))) {
        die('لا يمكن إضافة ملاحظة بتاريخ مستقبلي');
    }

    /*
    ================================================================
    🔴 حماية جوهرية: الطالب يجب أن يكون من صفوف هذا المعلم
    النسخة السابقة كانت تجلب الطالب بـ WHERE s.id = ? فقط،
    فيستطيع أي معلم إضافة ملاحظة سلبية لأي طالب في المدرسة
    (بتغيير student_id في المتصفح) — وهذه وثيقة تمس سمعته!
    ================================================================
    */
    $stmt = $db->prepare("
        SELECT s.id, s.user_id, s.student_number, u.full_name, c.class_name
        FROM students s
        INNER JOIN users u ON s.user_id = u.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE s.id = ?
          AND EXISTS (
              SELECT 1 FROM course_assignments ca
              WHERE ca.teacher_id = ?
                AND ca.class_id = s.class_id
          )
    ");
    $stmt->execute([$studentId, $teacherId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {

        Logger::log(
            'behavior',
            'create_denied',
            "محاولة إضافة ملاحظة سلوكية لطالب ليس من صفوف المعلم (student_id=$studentId)",
            'student',
            $studentId,
            'danger'
        );

        die('غير مصرح لك بإضافة ملاحظة لهذا الطالب');
    }

    /* ==================== الحفظ ==================== */
    try {

        $db->beginTransaction();

        $model->addNote(
            $studentId,
            $teacherId,
            $type,
            $title,
            $details,
            $noteDate
        );

        $notifyStmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");

        $typeAr = $typeLabels[$type] ?? $type;

        /* ============ إشعار الطالب ============ */
        $notifyStmt->execute([
            (int)$student['user_id'],
            'ملاحظة سلوكية ' . $typeAr,
            "أُضيفت لك ملاحظة سلوكية ($typeAr) بتاريخ $noteDate: $title",
            'announcement',
        ]);

        /*
        ============ إشعار أولياء الأمور ============
        (كان مفقوداً تماماً — يُشعَر الطالب والأدمن فقط!
         ولي الأمر أولى الناس بمعرفة ملاحظة سلوكية عن ابنه)
        */
        $parentStmt = $db->prepare("
            SELECT p.user_id
            FROM parent_student ps
            INNER JOIN parents p ON ps.parent_id = p.id
            WHERE ps.student_id = ?
        ");
        $parentStmt->execute([$studentId]);

        foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

            $notifyStmt->execute([
                (int)$parent['user_id'],
                'ملاحظة سلوكية للطالب',
                $student['full_name'] . " — ملاحظة ($typeAr) بتاريخ $noteDate: $title",
                'announcement',
            ]);
        }

        /* ============ إشعار الأدمن ============ */
        $admins = $db->query("SELECT id FROM users WHERE role_id = 1")
                     ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($admins as $admin) {

            $notifyStmt->execute([
                (int)$admin['id'],
                'ملاحظة سلوكية ' . $typeAr,
                'الطالب ' . $student['full_name']
                . ' (' . ($student['class_name'] ?? '—') . ') — '
                . $title,
                'announcement',
            ]);
        }

        $db->commit();

    } catch (Throwable $ex) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        Logger::log(
            'behavior',
            'create_failed',
            "فشل حفظ ملاحظة سلوكية للطالب {$student['full_name']}",
            'student',
            $studentId,
            'danger'
        );

        die('تعذر حفظ الملاحظة');
    }

    /*
    ================================================================
    التسجيل
    الملاحظة السلبية/التنبيه = danger (صف أحمر في اللوحة)
    الملاحظة الإيجابية = info (صف عادي)
    نسجّل نص التفاصيل كاملاً — فهو محل أي نزاع لاحق
    ================================================================
    */
    Logger::log(
        'behavior',
        'create_note',
        "ملاحظة سلوكية ($typeAr) — الطالب: {$student['full_name']}"
        . ' (' . ($student['student_number'] ?? '—') . ')'
        . ' - صف: ' . ($student['class_name'] ?? '—')
        . " | التاريخ: $noteDate"
        . " | العنوان: $title"
        . " | التفاصيل: " . mb_substr($details, 0, 300),
        'student',
        $studentId,
        ($type === 'positive') ? 'info' : 'danger'
    );

    $_SESSION['success'] = 'تم حفظ الملاحظة السلوكية بنجاح وإشعار الطالب وولي أمره والإدارة';

    header('Location: index.php');
    exit;
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="card shadow border-0">

    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="bi bi-journal-plus"></i> إضافة ملاحظة سلوكية
        </h5>
    </div>

    <div class="card-body">

        <!-- تحذير المسؤولية -->
        <div class="alert alert-warning small">
            <i class="bi bi-exclamation-triangle"></i>
            <strong>تنبيه:</strong>
            الملاحظة السلوكية وثيقة رسمية تُرسل إلى
            <strong>الطالب وولي أمره والإدارة</strong>،
            وتُسجَّل باسمك في سجل النشاط. تحرَّ الدقة والموضوعية.
        </div>

        <form method="POST"
              onsubmit="return confirm('سيتم إشعار الطالب وولي أمره والإدارة بهذه الملاحظة. هل تريد المتابعة؟');">

            <!-- ==================== الطالب ==================== -->

            <div class="mb-3">

                <label class="form-label fw-bold">
                    الطالب <span class="text-danger">*</span>
                </label>

                <select name="student_id" class="form-select" required>

    <option value="">— اختر الطالب —</option>

    <?php
    $currentClass = null;

    foreach ($students as $student):

        $className = $student['class_name'] ?? 'غير محدد';

        /* بداية مجموعة صف جديد */
        if ($className !== $currentClass):

            /* إغلاق المجموعة السابقة */
            if ($currentClass !== null): ?>
                </optgroup>
            <?php endif; ?>

            <optgroup label="<?= e($className); ?>">

            <?php $currentClass = $className;

        endif; ?>

        <option value="<?= (int)$student['id']; ?>"
                data-name="<?= e($student['full_name']); ?>"
                data-number="<?= e($student['student_number']); ?>"
                data-class="<?= e($className); ?>">
            <?= e($className); ?>
            —
            <?= e($student['student_number']); ?>
            —
            <?= e($student['full_name']); ?>
        </option>

    <?php endforeach; ?>

    <?php if ($currentClass !== null): ?>
        </optgroup>
    <?php endif; ?>

</select>

                <?php if (!$students): ?>
                    <small class="text-danger">
                        لا يوجد طلاب في صفوفك — تحقق من إسناد المقررات
                    </small>
                <?php endif; ?>

            </div>

            <!-- بطاقة بيانات الطالب -->

            <div id="studentInfoCard" class="card border-primary shadow-sm mb-3 d-none">

                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-badge"></i> بيانات الطالب
                </div>

                <div class="card-body">
                    <div class="row text-center">

                        <div class="col-md-4">
                            <h6 class="text-muted">اسم الطالب</h6>
                            <h6 id="studentName" class="fw-bold">-</h6>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted">الرقم الأكاديمي</h6>
                            <h6 id="studentNumber" class="fw-bold">-</h6>
                        </div>

                        <div class="col-md-4">
                            <h6 class="text-muted">الصف</h6>
                            <h6 id="studentClass" class="fw-bold">-</h6>
                        </div>

                    </div>
                </div>

            </div>

            <!-- ==================== نوع الملاحظة ==================== -->

            <div class="mb-3">

                <label class="form-label fw-bold">
                    نوع الملاحظة <span class="text-danger">*</span>
                </label>

                <select name="note_type" id="noteType" class="form-select" required
                        onchange="toggleWarning()">
                    <option value="positive">✅ إيجابية</option>
                    <option value="negative">⛔ سلبية</option>
                    <option value="warning">⚠️ تنبيه</option>
                </select>

                <div id="negativeWarning" class="alert alert-danger small mt-2 d-none">
                    <i class="bi bi-exclamation-octagon"></i>
                    الملاحظة السلبية تبقى في سجل الطالب وتؤثر على تقاريره.
                </div>

            </div>

            <!-- ==================== العنوان ==================== -->

            <div class="mb-3">
                <label class="form-label fw-bold">
                    عنوان الملاحظة <span class="text-danger">*</span>
                </label>
                <input type="text" name="title" class="form-control"
                       maxlength="255" required>
            </div>

            <!-- ==================== التفاصيل ==================== -->

            <div class="mb-3">
                <label class="form-label fw-bold">
                    تفاصيل الملاحظة <span class="text-danger">*</span>
                </label>
                <textarea name="details" rows="5" class="form-control"
                          placeholder="اذكر الواقعة بموضوعية: ماذا حدث؟ متى؟ ما الإجراء المتخذ؟"
                          required></textarea>
            </div>

            <!-- ==================== التاريخ ==================== -->

            <div class="row g-3 mb-3">

                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        تاريخ الملاحظة <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="note_date" class="form-control"
                           value="<?= date('Y-m-d'); ?>"
                           max="<?= date('Y-m-d'); ?>"
                           required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">المعلم</label>
                    <input type="text" class="form-control"
                           value="<?= e($_SESSION['name'] ?? ''); ?>" readonly>
                </div>

            </div>

            <hr class="my-4">

            <button type="submit" class="btn btn-success">
                <i class="bi bi-save"></i> حفظ الملاحظة
            </button>

            <a href="index.php" class="btn btn-secondary">رجوع</a>

        </form>

    </div>

</div>

</div>
</div>
</div>

<script>

/* بطاقة بيانات الطالب */
document
.querySelector('select[name="student_id"]')
.addEventListener('change', function () {

    const card = document.getElementById('studentInfoCard');

    if (!this.value) {
        card.classList.add('d-none');
        return;
    }

    const option = this.options[this.selectedIndex];

    card.classList.remove('d-none');

    document.getElementById('studentName').innerText   = option.dataset.name;
    document.getElementById('studentNumber').innerText = option.dataset.number;
    document.getElementById('studentClass').innerText  = option.dataset.class;
});

/* تحذير عند اختيار ملاحظة سلبية */
function toggleWarning() {

    const type = document.getElementById('noteType').value;
    const box  = document.getElementById('negativeWarning');

    box.classList.toggle('d-none', type === 'positive');
}

toggleWarning();

</script>

<?php include '../../app/views/layouts/footer.php'; ?>