<?php
/*
=====================================================================
admin/transcript/settings.php — إعدادات أوزان السجل الأكاديمي
=====================================================================
⚠️ خطورة هذه الصفحة: تغيير الأوزان يعيد حساب معدلات
   **جميع الطلاب** في النظام دفعة واحدة.
   طالب ناجح قد يصبح راسباً بتغيير رقم واحد هنا.
   لذلك: التسجيل هنا من أهم ما في النظام.

التعديلات:
  1. تسجيل تغيير الأوزان — القديمة ← الجديدة (لكل وزن على حدة)
  2. حماية صلاحيات صحيحة: Auth::check() + role_id
  3. التحقق من نطاق كل وزن (0 - 100) — كان يقبل سالباً!
  4. إصلاح خطأ خطير: UPDATE بلا WHERE يحدّث كل الصفوف
  5. تعريب الواجهة + شريط بصري لتوزيع الأوزان
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/Notification.php';

/*
====================================================================
الصلاحية: أدمن فقط
(كانت تفحص role_id مباشرة بلا Auth::check())
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

$notificationModel = new Notification();
$count = $notificationModel->unreadCount((int)$_SESSION['user_id']);

$error = null;

/* ==================== جلب الإعدادات الحالية ==================== */
$settings = $db->query("SELECT * FROM transcript_settings LIMIT 1")
               ->fetch(PDO::FETCH_ASSOC);

/* أول تشغيل — إنشاء الإعدادات الافتراضية */
if (!$settings) {

    $db->exec("
        INSERT INTO transcript_settings
            (attendance_weight, assignment_weight, exam_weight)
        VALUES (10, 20, 70)
    ");

    Logger::log(
        'transcript',
        'init_settings',
        'إنشاء إعدادات السجل الأكاديمي الافتراضية (حضور 10% | واجبات 20% | اختبارات 70%)',
        null,
        null,
        'info'
    );

    $settings = $db->query("SELECT * FROM transcript_settings LIMIT 1")
                   ->fetch(PDO::FETCH_ASSOC);
}

/*
====================================================================
✅ الأوزان القديمة — تُقرأ قبل الحفظ
بلا القيم القديمة يصبح السجل عديم القيمة:
لن تعرف أن معدلات الطلاب تغيّرت لأن الوزن نُقل من 70 إلى 50
====================================================================
*/
$oldAttendance = (float)($settings['attendance_weight'] ?? 0);
$oldAssignment = (float)($settings['assignment_weight'] ?? 0);
$oldExam       = (float)($settings['exam_weight'] ?? 0);

$settingsId = (int)($settings['id'] ?? 0);

/* ==================== حفظ التعديلات ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $attendanceWeight = (float)($_POST['attendance_weight'] ?? -1);
    $assignmentWeight = (float)($_POST['assignment_weight'] ?? -1);
    $examWeight       = (float)($_POST['exam_weight'] ?? -1);

    /*
    التحقق من نطاق كل وزن
    (النسخة السابقة كانت تفحص المجموع = 100 فقط،
     فيمكن إدخال: حضور -50 | واجبات 100 | اختبارات 50 = 100 ✓ !)
    */
    if ($attendanceWeight < 0 || $attendanceWeight > 100
        || $assignmentWeight < 0 || $assignmentWeight > 100
        || $examWeight < 0 || $examWeight > 100) {

        $error = 'كل وزن يجب أن يكون بين 0 و 100';

    } elseif (abs(($attendanceWeight + $assignmentWeight + $examWeight) - 100) > 0.001) {

        $total = $attendanceWeight + $assignmentWeight + $examWeight;
        $error = "مجموع الأوزان يجب أن يساوي 100% — المجموع الحالي: $total%";

    } else {

        /* هل تغيّر شيء فعلاً؟ */
        $changed =
            (abs($oldAttendance - $attendanceWeight) > 0.001) ||
            (abs($oldAssignment - $assignmentWeight) > 0.001) ||
            (abs($oldExam - $examWeight) > 0.001);

        try {

            /*
            ⚠️ إصلاح خطير: النسخة السابقة كانت:
                UPDATE transcript_settings SET ... (بلا WHERE!)
            وهذا يحدّث **كل الصفوف** في الجدول.
            الآن نحدّد الصف بالمعرّف.
            */
            $update = $db->prepare("
                UPDATE transcript_settings
                SET attendance_weight = ?,
                    assignment_weight = ?,
                    exam_weight = ?
                WHERE id = ?
            ");
            $update->execute([
                $attendanceWeight,
                $assignmentWeight,
                $examWeight,
                $settingsId,
            ]);

        } catch (Throwable $ex) {

            Logger::log(
                'transcript',
                'update_settings_failed',
                'فشل حفظ إعدادات السجل الأكاديمي',
                null, null, 'danger'
            );

            die('تعذر حفظ الإعدادات');
        }

        /*
        ================================================================
        التسجيل: نوثّق كل وزن تغيّر (القديم ← الجديد)
        هذا التغيير يعيد حساب معدلات كل الطلاب — لذلك severity = danger
        ================================================================
        */
        if ($changed) {

            $changes = [];

            if (abs($oldAttendance - $attendanceWeight) > 0.001) {
                $changes[] = "الحضور: $oldAttendance% ← $attendanceWeight%";
            }

            if (abs($oldAssignment - $assignmentWeight) > 0.001) {
                $changes[] = "الواجبات: $oldAssignment% ← $assignmentWeight%";
            }

            if (abs($oldExam - $examWeight) > 0.001) {
                $changes[] = "الاختبارات: $oldExam% ← $examWeight%";
            }

            Logger::log(
                'transcript',
                'update_weights',
                'تغيير أوزان السجل الأكاديمي — يؤثر على معدلات جميع الطلاب | '
                . implode(' | ', $changes),
                null,
                null,
                'danger'   /* أحمر: تغيير جذري يمس كل الطلاب */
            );

        } else {

            /* حفظ بلا تغيير فعلي — نسجّله بهدوء */
            Logger::log(
                'transcript',
                'save_settings',
                'حفظ إعدادات السجل الأكاديمي بلا تغيير في الأوزان',
                null,
                null,
                'info'
            );
        }

        header("Location: settings.php?success=1");
        exit;
    }
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/sidebar.php'; ?>

<div class="col-md-10 p-4">

<h4 class="fw-bold mb-1">
    <i class="bi bi-sliders text-warning"></i> إعدادات السجل الأكاديمي
</h4>
<p class="text-muted small mb-4">
    أوزان احتساب المعدل النهائي للطالب
</p>

<!-- ==================== تحذير ==================== -->

<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>تنبيه مهم:</strong>
    تغيير هذه الأوزان يعيد حساب المعدل النهائي
    <strong>لجميع الطلاب في النظام</strong> — بما فيهم من صدرت شهاداتهم.
    كل تغيير يُسجَّل في سجل النشاط.
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> تم حفظ الإعدادات بنجاح
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-x-circle"></i> <?= e($error); ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ==================== التوزيع الحالي ==================== -->

<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white fw-bold">
        <i class="bi bi-pie-chart"></i> التوزيع الحالي
    </div>

    <div class="card-body">

        <div class="progress" style="height: 28px;">

            <div class="progress-bar bg-info"
                 style="width: <?= (float)$oldAttendance; ?>%;">
                الحضور <?= (float)$oldAttendance; ?>%
            </div>

            <div class="progress-bar bg-success"
                 style="width: <?= (float)$oldAssignment; ?>%;">
                الواجبات <?= (float)$oldAssignment; ?>%
            </div>

            <div class="progress-bar bg-warning text-dark"
                 style="width: <?= (float)$oldExam; ?>%;">
                الاختبارات <?= (float)$oldExam; ?>%
            </div>

        </div>

    </div>

</div>

<!-- ==================== النموذج ==================== -->

<form method="POST"
      onsubmit="return confirm('سيتم إعادة حساب معدلات جميع الطلاب بناءً على الأوزان الجديدة. هل تريد المتابعة؟');">

<div class="card border-0 shadow-sm">

    <div class="card-body">

        <div class="row g-3">

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    <i class="bi bi-calendar-check text-info"></i>
                    وزن الحضور %
                </label>
                <input type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       name="attendance_weight"
                       id="w1"
                       value="<?= e($oldAttendance); ?>"
                       class="form-control"
                       required
                       oninput="calcTotal()">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    <i class="bi bi-journal-text text-success"></i>
                    وزن الواجبات %
                </label>
                <input type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       name="assignment_weight"
                       id="w2"
                       value="<?= e($oldAssignment); ?>"
                       class="form-control"
                       required
                       oninput="calcTotal()">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">
                    <i class="bi bi-file-earmark-text text-warning"></i>
                    وزن الاختبارات %
                </label>
                <input type="number"
                       step="0.01"
                       min="0"
                       max="100"
                       name="exam_weight"
                       id="w3"
                       value="<?= e($oldExam); ?>"
                       class="form-control"
                       required
                       oninput="calcTotal()">
            </div>

        </div>

        <!-- المجموع الحي -->
        <div id="totalBox" class="alert alert-info mt-4 mb-3">
            <i class="bi bi-calculator"></i>
            المجموع: <strong id="totalValue">0</strong>%
            — يجب أن يساوي <strong>100%</strong> بالضبط
        </div>

        <button type="submit" class="btn btn-primary" id="saveBtn">
            <i class="bi bi-save"></i> حفظ الإعدادات
        </button>

    </div>

</div>

</form>

</div>
</div>
</div>

<script>
/* حساب المجموع فورياً — يمنع الإرسال إن لم يكن 100 */
function calcTotal() {

    const w1 = parseFloat(document.getElementById('w1').value) || 0;
    const w2 = parseFloat(document.getElementById('w2').value) || 0;
    const w3 = parseFloat(document.getElementById('w3').value) || 0;

    const total = Math.round((w1 + w2 + w3) * 100) / 100;

    const box  = document.getElementById('totalBox');
    const val  = document.getElementById('totalValue');
    const btn  = document.getElementById('saveBtn');

    val.textContent = total;

    const ok = Math.abs(total - 100) < 0.001;

    box.className = 'alert mt-4 mb-3 ' + (ok ? 'alert-success' : 'alert-danger');
    btn.disabled  = !ok;
}

calcTotal();
</script>

<?php include '../../app/views/layouts/footer.php'; ?>