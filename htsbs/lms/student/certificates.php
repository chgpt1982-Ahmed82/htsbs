<?php
/*
=====================================================================
LMS - شهاداتي (قائمة الشهادات لجميع المقررات)
صفحة وسيطة: تعرض كل مقررات الطالب وحالة الشهادة في كل مقرر
- إذا استحقها: أزرار عرض / تحميل PDF
- إذا لم يستحقها: 🔒 مقفلة + نسبة الإنجاز المتبقية
(صفحة certificate.php تتطلب course_id ولا يمكن فتحها مباشرة)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];
$courses   = $lms->getStudentCourses((int)$student['class_id']);

// النسبة المطلوبة لاستحقاق الشهادة (من جدول الإعدادات)
$minPercent = (float)$lms->getSetting('certificate_min_percent', 100);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1"><i class="bi bi-patch-check text-warning"></i> شهاداتي</h4>
<p class="text-muted small mb-4">
  تُمنح الشهادة بعد إكمال جميع الدروس والأنشطة وتحقيق نسبة إنجاز
  <strong><?= round($minPercent) ?>%</strong>
</p>

<div class="row g-3">

  <?php if (!$courses): ?>
  <div class="col-12">
    <div class="alert alert-info">لا توجد مقررات مسندة لصفك حالياً</div>
  </div>
  <?php endif; ?>

  <?php foreach ($courses as $c): ?>
  <?php
      $courseId = (int)$c['id'];

      // نسبة إنجاز الطالب في هذا المقرر
      $stmt = $db->prepare("
          SELECT progress_percent, avg_grade
          FROM lms_student_progress
          WHERE student_id = ? AND course_id = ?
      ");
      $stmt->execute([$studentId, $courseId]);
      $prog = $stmt->fetch(PDO::FETCH_ASSOC);

      $percent = $prog ? round((float)$prog['progress_percent']) : 0;
      $avg     = $prog ? round((float)$prog['avg_grade'], 1) : 0;

      // هل الشهادة مُصدرة فعلاً؟
      $stmt = $db->prepare("
          SELECT certificate_no, issue_date
          FROM lms_certificates
          WHERE student_id = ? AND course_id = ?
      ");
      $stmt->execute([$studentId, $courseId]);
      $cert = $stmt->fetch(PDO::FETCH_ASSOC);

      // مستحق؟ (مُصدرة سابقاً أو بلغ النسبة المطلوبة الآن)
      $eligible = $cert || ($percent >= $minPercent);
  ?>

  <div class="col-md-6 col-xl-4">
    <div class="card border-0 shadow-sm h-100 <?= $eligible ? 'border-start border-4 border-warning' : '' ?>">
      <div class="card-body text-center">

        <div style="font-size: 2.5rem;"><?= $eligible ? '🎓' : '🔒' ?></div>

        <h6 class="fw-bold mt-2 mb-3"><?= e($c['course_name']) ?></h6>

        <div class="progress mb-2" style="height: 18px;">
          <div class="progress-bar bg-<?= $eligible ? 'success' : 'warning' ?>"
               style="width: <?= $percent ?>%;">
            <?= $percent ?>%
          </div>
        </div>

        <p class="small text-muted mb-3">
          نسبة الإنجاز: <strong><?= $percent ?>%</strong>
          | متوسط الدرجات: <strong><?= $avg ?>%</strong>
        </p>

        <?php if ($eligible): ?>

          <?php if ($cert): ?>
          <p class="small mb-2">
            <span class="badge bg-success">مُصدرة ✔</span><br>
            <span class="text-muted">
              رقم الشهادة: <span dir="ltr"><?= e($cert['certificate_no']) ?></span><br>
              التاريخ: <?= e(date('d/m/Y', strtotime($cert['issue_date']))) ?>
            </span>
          </p>
          <?php else: ?>
          <p class="small text-success mb-2">🎉 مبروك! أصبحت مستحقاً للشهادة</p>
          <?php endif; ?>

          <div class="d-grid gap-2">
            <a href="certificate.php?course_id=<?= $courseId ?>" class="btn btn-warning">
              <i class="bi bi-eye"></i> عرض الشهادة
            </a>
            <a href="certificate_pdf.php?course_id=<?= $courseId ?>" class="btn btn-outline-danger btn-sm">
              <i class="bi bi-file-earmark-pdf"></i> تحميل PDF
            </a>
          </div>

        <?php else: ?>

          <p class="small text-muted mb-2">
            🔒 أكمل باقي الدروس والأنشطة للحصول على الشهادة
            <br>
            المتبقي: <strong><?= max(0, round($minPercent - $percent)) ?>%</strong>
          </p>

          <a href="lessons.php?course_id=<?= $courseId ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-play-circle"></i> متابعة التعلم
          </a>

        <?php endif; ?>

      </div>
    </div>
  </div>

  <?php endforeach; ?>

</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
