<?php
/*
=====================================================================
admin/certificates/generate.php — إصدار الشهادات الأكاديمية
=====================================================================
التعديلات:
  1. تسجيل إصدار الشهادة — وثيقة رسمية تستحق أعلى توثيق
  2. تسجيل محاولات الإصدار المرفوضة (طالب غير مستحق / شهادة مكررة)
  3. حماية صلاحيات صحيحة: Auth::check() + role_id (كان يفحص role_id فقط)
  4. التحقق من وجود الطالب فعلاً
  5. تعريب الواجهة والإشعارات
  6. دالة e() — تعالج NULL في جدول الشهادات (كل الحقول DEFAULT NULL)
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Certificate.php';
require_once '../../app/models/Notification.php';

/*
====================================================================
الصلاحية: أدمن فقط
النسخة السابقة كانت تفحص $_SESSION['role_id'] مباشرة بلا Auth::check()
فلو لم تكن الجلسة مفتوحة أصلاً → تحذير Undefined index
====================================================================
*/
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    die('Access Denied');
}

if (!function_exists('e')) {
    function e($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$certificateModel  = new Certificate();
$notificationModel = new Notification();

$count = $notificationModel->unreadCount((int)$_SESSION['user_id']);

$error   = null;
$success = null;

/* ==================== قائمة الطلاب ==================== */
$students = $db->query("
    SELECT s.id, s.student_number, u.full_name
    FROM students s
    INNER JOIN users u ON s.user_id = u.id
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

/* ==================== إصدار الشهادة ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = (int)($_POST['student_id'] ?? 0);

    if ($studentId <= 0) {

        $error = 'يرجى اختيار الطالب';

    } else {

        /* بيانات الطالب — نحتاجها للسجل والإشعار */
        $stmt = $db->prepare("
            SELECT s.id, s.user_id, s.student_number, u.full_name
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {

            $error = 'الطالب غير موجود';

            Logger::log(
                'certificates',
                'generate_denied',
                "محاولة إصدار شهادة لطالب غير موجود (student_id=$studentId)",
                null, null, 'danger'
            );

        } else {

            $label = $student['full_name']
                   . ' (' . ($student['student_number'] ?? '—') . ')';

            /* ============ (1) هل الطالب مستحق؟ ============ */
            if (!$certificateModel->isEligible($studentId)) {

                $error = 'الطالب غير مستحق للشهادة — لم يستوفِ شروط التخرج';

                /* محاولة مرفوضة — تُسجَّل (قد تتكرر من نفس المستخدم) */
                Logger::log(
                    'certificates',
                    'generate_blocked',
                    "محاولة إصدار شهادة لطالب غير مستحق: $label",
                    'student',
                    $studentId,
                    'warning'
                );

            } else {

                /* ============ (2) هل الشهادة موجودة مسبقاً؟ ============ */
                $existing = $certificateModel->getByStudent($studentId);

                if ($existing) {

                    $error = 'الشهادة صادرة مسبقاً لهذا الطالب — الرقم: '
                           . e($existing['certificate_no'] ?? '—');

                    Logger::log(
                        'certificates',
                        'generate_duplicate',
                        "محاولة إصدار شهادة مكررة: $label"
                        . " | الشهادة الحالية: " . ($existing['certificate_no'] ?? '—'),
                        'student',
                        $studentId,
                        'warning'
                    );

                } else {

                    /* ============ (3) الإصدار ============ */
                    try {

                        $certificateModel->createCertificate($studentId);

                    } catch (Throwable $ex) {

                        Logger::log(
                            'certificates',
                            'generate_failed',
                            "فشل إصدار شهادة: $label",
                            'student',
                            $studentId,
                            'danger'
                        );

                        die('تعذر إصدار الشهادة');
                    }

                    /* بيانات الشهادة الصادرة — للسجل */
                    $cert = $certificateModel->getByStudent($studentId);

                    /*
                    ============================================
                    التسجيل: الشهادة وثيقة رسمية
                    نوثّق رقمها ودرجتها ومعدلها — للرجوع عند أي نزاع
                    ============================================
                    */
                    Logger::log(
                        'certificates',
                        'generate_certificate',
                        "إصدار شهادة: $label"
                        . " | رقم الشهادة: " . ($cert['certificate_no'] ?? '—')
                        . " | الدرجة النهائية: " . ($cert['final_grade'] ?? '—')
                        . " | المعدل: " . ($cert['gpa'] ?? '—')
                        . " | التاريخ: " . ($cert['issue_date'] ?? date('Y-m-d')),
                        'student',
                        $studentId,
                        'warning'
                    );

                    /* ============ إشعار الطالب ============ */
                    $notificationModel->create(
                        (int)$student['user_id'],
                        'صدرت شهادتك 🎓',
                        'تم إصدار شهادتك الأكاديمية'
                        . (!empty($cert['certificate_no'])
                            ? ' برقم: ' . $cert['certificate_no']
                            : '')
                        . '. يمكنك عرضها وتحميلها من صفحة السجل الأكاديمي.',
                        'certificate'
                    );

                    /* ============ إشعار أولياء الأمور ============ */
                    $parentStmt = $db->prepare("
                        SELECT p.user_id
                        FROM parent_student ps
                        INNER JOIN parents p ON ps.parent_id = p.id
                        WHERE ps.student_id = ?
                    ");
                    $parentStmt->execute([$studentId]);

                    foreach ($parentStmt->fetchAll(PDO::FETCH_ASSOC) as $parent) {

                        $notificationModel->create(
                            (int)$parent['user_id'],
                            'صدرت شهادة الطالب 🎓',
                            'تم إصدار الشهادة الأكاديمية للطالب '
                            . $student['full_name'],
                            'certificate'
                        );
                    }

                    header("Location: generate.php?success=1");
                    exit;
                }
            }
        }
    }
}

/* الشهادات الصادرة */
$certificates = $certificateModel->getAll();

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/sidebar.php'; ?>

<div class="col-md-10 p-4">

<h4 class="fw-bold mb-1">
    <i class="bi bi-award-fill text-warning"></i> إصدار الشهادات
</h4>
<p class="text-muted small mb-4">
    الشهادة وثيقة رسمية — كل إصدار يُسجَّل في سجل النشاط
</p>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> تم إصدار الشهادة بنجاح وإشعار الطالب
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?= $error; ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ==================== نموذج الإصدار ==================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-body">

        <form method="POST"
              onsubmit="return confirm('سيتم إصدار شهادة رسمية لهذا الطالب. هل تريد المتابعة؟');">

            <div class="row g-3 align-items-end">

                <div class="col-md-6">

                    <label class="form-label fw-bold">
                        الطالب <span class="text-danger">*</span>
                    </label>

                    <select name="student_id" class="form-select" required>

                        <option value="">— اختر الطالب —</option>

                        <?php foreach ($students as $student): ?>
                            <option value="<?= (int)$student['id']; ?>">
                                <?= e($student['full_name']); ?>
                                — <?= e($student['student_number']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-patch-check"></i> إصدار الشهادة
                    </button>
                </div>

            </div>

        </form>

        <small class="text-muted d-block mt-3">
            <i class="bi bi-info-circle"></i>
            لا تُصدَر الشهادة إلا للطالب المستحق، ولا تتكرر لنفس الطالب.
        </small>

    </div>

</div>

<!-- ==================== الشهادات الصادرة ==================== -->

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold">
            <i class="bi bi-collection"></i> الشهادات الصادرة
        </span>
        <span class="badge bg-secondary"><?= count($certificates); ?></span>
    </div>

    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0">

            <thead class="table-light">
                <tr>
                    <th>رقم الشهادة</th>
                    <th>الطالب</th>
                    <th class="text-center">الدرجة النهائية</th>
                    <th class="text-center">المعدل</th>
                    <th>تاريخ الإصدار</th>
                    <th class="text-center">عرض</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!$certificates): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        لا توجد شهادات صادرة بعد
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($certificates as $certificate): ?>
                <tr>
                    <td>
                        <span dir="ltr" class="fw-bold">
                            <?= e($certificate['certificate_no']); ?>
                        </span>
                    </td>
                    <td><?= e($certificate['full_name']); ?></td>
                    <td class="text-center"><?= e($certificate['final_grade']); ?></td>
                    <td class="text-center"><?= e($certificate['gpa']); ?></td>
                    <td class="small text-muted"><?= e($certificate['issue_date']); ?></td>
                    <td class="text-center">
                        <a href="preview.php?id=<?= (int)$certificate['id']; ?>"
                           class="btn btn-info btn-sm">
                            <i class="bi bi-eye"></i> عرض
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

</div>
</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>