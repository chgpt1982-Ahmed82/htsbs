<?php
/*
=====================================================================
LMS - تصحيح المشاريع (معلم)
تصحيح النشاط الخامس ⭐⭐⭐⭐⭐ (مشروع / رفع ملف / سؤال برمجي)
- عرض المشاريع بانتظار التصحيح + المشاريع المصححة
- إدخال الدرجة + ملاحظات المعلم (Feedback)
- عند الحفظ: تحديث المحاولة ثم إعادة احتساب التقدم
  (اكتمال الدرس + النجمة + الشارات + الشهادة + لوحة الصدارة)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];
$flash = null;

/* ==================== معالجة التصحيح ==================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'grade_project') {

    lms_csrf_check();

    try {
        $attemptId = (int)($_POST['attempt_id'] ?? 0);
        $score     = (float)($_POST['score'] ?? -1);
        $feedback  = trim((string)($_POST['feedback'] ?? ''));

        if ($score < 0 || $score > 100) {
            throw new Exception('الدرجة يجب أن تكون بين 0 و 100');
        }

        /*
        التحقق من ملكية المحاولة:
        المحاولة -> النشاط -> الدرس -> المعلم الحالي
        (حماية من التلاعب بالمعرفات - IDOR Protection)
        */
        $stmt = $db->prepare("
            SELECT att.id, att.activity_id, att.student_id,
                   a.activity_type, l.pass_grade
            FROM lms_student_activity_attempts att
            INNER JOIN lms_activities a ON att.activity_id = a.id
            INNER JOIN lms_lessons l ON a.lesson_id = l.id
            WHERE att.id = ?
              AND l.teacher_id = ?
              AND a.activity_type = 'project'
        ");
        $stmt->execute([$attemptId, $teacherId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            throw new Exception('المحاولة غير موجودة أو لا تملك صلاحية تصحيحها');
        }

        $isPassed = ($score >= (float)$attempt['pass_grade']) ? 1 : 0;

        // حفظ نتيجة التصحيح
        $stmt = $db->prepare("
            UPDATE lms_student_activity_attempts
            SET score = ?,
                is_passed = ?,
                teacher_feedback = ?,
                graded_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$score, $isPassed, $feedback ?: null, $teacherId, $attemptId]);

        /*
        إعادة احتساب التقدم بعد التصحيح:
        قد يكتمل الدرس الآن -> نجمة + فتح الدرس التالي + شارات + شهادة + صدارة
        */
        $lms->recalculateAfterActivity(
            (int)$attempt['activity_id'],
            (int)$attempt['student_id']
        );

        // إشعار الطالب بنتيجة التصحيح
        $lms->notifyStudent(
            (int)$attempt['student_id'],
            $isPassed ? 'تم تصحيح مشروعك 🎉' : 'تم تصحيح مشروعك 📝',
            'حصلت على درجة ' . $score . '% في المشروع'
            . ($isPassed ? ' - مبروك النجاح!' : ' - راجع ملاحظات المعلم وأعد المحاولة')
            . ($feedback !== '' ? ' | ملاحظات المعلم: ' . $feedback : '')
        );

        // سجل التدقيق
        $lms->log((int)$_SESSION['user_id'], 'grade_project',
            "attempt=$attemptId score=$score passed=$isPassed");

        $flash = ['success', '✅ تم حفظ التصحيح بنجاح وإشعار الطالب'];

    } catch (Exception $ex) {
        $flash = ['danger', $ex->getMessage()];
    }
}

/* ==================== المشاريع بانتظار التصحيح ==================== */
$stmt = $db->prepare("
    SELECT att.*,
           a.title AS activity_title, a.lesson_id,
           l.title AS lesson_title, l.pass_grade,
           c.course_name,
           u.full_name AS student_name, s.student_number,
           cls.class_name
    FROM lms_student_activity_attempts att
    INNER JOIN lms_activities a ON att.activity_id = a.id
    INNER JOIN lms_lessons l ON a.lesson_id = l.id
    INNER JOIN courses c ON l.course_id = c.id
    INNER JOIN students s ON att.student_id = s.id
    INNER JOIN users u ON s.user_id = u.id
    LEFT JOIN classes cls ON s.class_id = cls.id
    WHERE l.teacher_id = ?
      AND a.activity_type = 'project'
      AND att.graded_by IS NULL
    ORDER BY att.created_at ASC
");
$stmt->execute([$teacherId]);
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==================== آخر المشاريع المصححة ==================== */
$stmt = $db->prepare("
    SELECT att.*,
           a.title AS activity_title,
           l.title AS lesson_title,
           c.course_name,
           u.full_name AS student_name, s.student_number
    FROM lms_student_activity_attempts att
    INNER JOIN lms_activities a ON att.activity_id = a.id
    INNER JOIN lms_lessons l ON a.lesson_id = l.id
    INNER JOIN courses c ON l.course_id = c.id
    INNER JOIN students s ON att.student_id = s.id
    INNER JOIN users u ON s.user_id = u.id
    WHERE l.teacher_id = ?
      AND a.activity_type = 'project'
      AND att.graded_by IS NOT NULL
    ORDER BY att.id DESC
    LIMIT 30
");
$stmt->execute([$teacherId]);
$graded = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
====================================
استخراج نص إجابة الطالب من answers_json
====================================
*/
function lms_project_text(?string $json): string
{
    if (!$json) return '';
    $arr = json_decode($json, true);
    if (!is_array($arr)) return '';
    $parts = [];
    foreach ($arr as $v) {
        $v = trim((string)$v);
        if ($v !== '') $parts[] = $v;
    }
    return implode("\n", $parts);
}

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1"><i class="bi bi-clipboard-check text-warning"></i> تصحيح المشاريع</h4>
<p class="text-muted small mb-4">تصحيح النشاط الخامس ⭐⭐⭐⭐⭐ (مشروع / رفع ملف / سؤال برمجي)</p>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show">
  <?= e($flash[1]) ?>
  <button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ==================== بانتظار التصحيح ==================== -->
<div class="d-flex align-items-center gap-2 mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-hourglass-split text-warning"></i> بانتظار التصحيح</h5>
  <span class="badge bg-warning text-dark"><?= count($pending) ?></span>
</div>

<?php if (!$pending): ?>
<div class="alert alert-success">
  <i class="bi bi-check-circle"></i> رائع! لا توجد مشاريع بانتظار التصحيح حالياً
</div>
<?php endif; ?>

<?php foreach ($pending as $p): ?>
<?php $projText = lms_project_text($p['answers_json']); ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <span class="fw-bold"><i class="bi bi-person-circle text-primary"></i> <?= e($p['student_name']) ?></span>
      <small class="text-muted">
        (<?= e($p['student_number'] ?? '—') ?><?= $p['class_name'] ? ' - ' . e($p['class_name']) : '' ?>)
      </small>
    </div>
    <small class="text-muted">
      <i class="bi bi-clock"></i> سُلِّم في: <?= e(date('d/m/Y H:i', strtotime($p['created_at']))) ?>
      | المحاولة رقم: <?= (int)$p['attempt_no'] ?>
      | مدة الحل: <?= e(lms_format_seconds((int)$p['duration_seconds'])) ?>
    </small>
  </div>

  <div class="card-body">
    <div class="mb-2 small">
      <span class="badge bg-primary"><?= e($p['course_name']) ?></span>
      <span class="badge bg-secondary"><?= e($p['lesson_title']) ?></span>
      <span class="badge bg-info text-dark"><?= e($p['activity_title']) ?></span>
      <span class="badge bg-light text-dark border">درجة النجاح المطلوبة: <?= round((float)$p['pass_grade']) ?>%</span>
    </div>

    <?php if ($p['project_file']): ?>
    <p class="mb-2">
      <i class="bi bi-paperclip"></i> ملف المشروع:
      <a href="<?= BASE_URL ?>/lms/uploads/<?= e($p['project_file']) ?>" target="_blank"
         class="btn btn-sm btn-outline-primary">
        <i class="bi bi-download"></i> عرض / تحميل الملف
      </a>
    </p>
    <?php endif; ?>

    <?php if ($projText !== ''): ?>
    <div class="border rounded bg-light p-3 mb-3" style="white-space: pre-wrap;"><?= e($projText) ?></div>
    <?php endif; ?>

    <!-- نموذج التصحيح -->
    <form method="post" class="row g-2 align-items-end">
      <?= lms_csrf_field() ?>
      <input type="hidden" name="action" value="grade_project">
      <input type="hidden" name="attempt_id" value="<?= (int)$p['id'] ?>">

      <div class="col-md-2">
        <label class="form-label fw-bold small">الدرجة (0 - 100) *</label>
        <input type="number" name="score" class="form-control" min="0" max="100" step="0.5" required>
      </div>
      <div class="col-md-8">
        <label class="form-label fw-bold small">ملاحظات للطالب (اختياري)</label>
        <input type="text" name="feedback" class="form-control" maxlength="1000"
               placeholder="مثال: عمل ممتاز، انتبه لتنسيق الكود...">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-success">
          <i class="bi bi-check2-circle"></i> حفظ التصحيح
        </button>
      </div>
    </form>
  </div>
</div>
<?php endforeach; ?>

<!-- ==================== آخر المشاريع المصححة ==================== -->
<h5 class="fw-bold mt-4 mb-3"><i class="bi bi-check2-all text-success"></i> آخر المشاريع المصححة</h5>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>الطالب</th>
          <th>المقرر</th>
          <th>الدرس</th>
          <th>النشاط</th>
          <th class="text-center">الدرجة</th>
          <th class="text-center">النتيجة</th>
          <th>ملاحظات المعلم</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$graded): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">لا توجد مشاريع مصححة بعد</td></tr>
        <?php endif; ?>
        <?php foreach ($graded as $g): ?>
        <tr>
          <td>
            <?= e($g['student_name']) ?>
            <small class="text-muted d-block"><?= e($g['student_number'] ?? '') ?></small>
          </td>
          <td class="small"><?= e($g['course_name']) ?></td>
          <td class="small"><?= e($g['lesson_title']) ?></td>
          <td class="small"><?= e($g['activity_title']) ?></td>
          <td class="text-center fw-bold"><?= round((float)$g['score'], 1) ?>%</td>
          <td class="text-center">
            <?php if ((int)$g['is_passed'] === 1): ?>
              <span class="badge bg-success">ناجح ✔</span>
            <?php else: ?>
              <span class="badge bg-danger">لم يجتز ✘</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?= e($g['teacher_feedback'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
