<?php
/*
=====================================================================
LMS - مقررات الطالب (بطاقات مع نسبة الإكمال)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];
$courses   = $lms->getStudentCourses((int)$student['class_id']);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-4"><i class="bi bi-journal-bookmark text-primary"></i> مقرراتي التفاعلية</h4>

<div class="row g-3">
  <?php if (!$courses): ?>
    <div class="col-12"><div class="alert alert-info">لا توجد مقررات مسندة لصفك حالياً</div></div>
  <?php endif; ?>

  <?php foreach ($courses as $c):
      // نسبة إكمال الطالب لهذا المقرر
      $stmt = $db->prepare("
          SELECT progress_percent, completed_lessons
          FROM lms_student_progress
          WHERE student_id = ? AND course_id = ?
      ");
      $stmt->execute([$studentId, (int)$c['id']]);
      $prog = $stmt->fetch(PDO::FETCH_ASSOC);
      $percent = $prog ? round((float)$prog['progress_percent']) : 0;
  ?>
  <div class="col-md-6 col-xl-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex align-items-center mb-2">
          <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2"
               style="width:48px;height:48px;">
            <i class="bi bi-book text-primary fs-4"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-0"><?= e($c['course_name']) ?></h5>
            <small class="text-muted"><?= e($c['course_code'] ?? '') ?></small>
          </div>
        </div>

        <p class="text-muted small"><?= e(mb_substr($c['description'] ?? '', 0, 100)) ?></p>

        <p class="small mb-2"><i class="bi bi-person-badge text-secondary"></i>
          المعلم: <strong><?= e($c['teacher_name']) ?></strong></p>

        <div class="d-flex justify-content-between small text-muted mb-2">
          <span><i class="bi bi-collection"></i> <?= (int)$c['lessons_count'] ?> درس</span>
          <span><i class="bi bi-pencil-square"></i> <?= (int)$c['activities_count'] ?> نشاط</span>
          <span><i class="bi bi-people"></i> <?= (int)$c['students_count'] ?> طالب</span>
        </div>

        <div class="d-flex justify-content-between small mb-1">
          <span>نسبة الإكمال</span><span class="fw-bold"><?= $percent ?>%</span>
        </div>
        <div class="progress mb-3" style="height:8px;">
          <div class="progress-bar <?= $percent >= 100 ? 'bg-success' : '' ?>" style="width:<?= $percent ?>%"></div>
        </div>

        <a href="lessons.php?course_id=<?= (int)$c['id'] ?>" class="btn btn-primary w-100">
          <i class="bi bi-play-circle"></i>
          <?= $percent > 0 ? 'متابعة التعلم' : 'ابدأ التعلم' ?>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
