<?php
/*
=====================================================================
LMS - دروس المقرر (فتح تسلسلي Sequential Learning)
🔒 الدرس التالي لا يفتح إلا بعد إكمال الحالي بنجاح
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];
$courseId  = (int)($_GET['course_id'] ?? 0);

// التحقق أن المقرر مسند لصف الطالب (RBAC)
$stmt = $db->prepare("
    SELECT c.course_name FROM courses c
    INNER JOIN course_assignments ca ON ca.course_id = c.id
    WHERE c.id = ? AND ca.class_id = ?
    LIMIT 1
");
$stmt->execute([$courseId, (int)$student['class_id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) exit('غير مصرح لك بالوصول لهذا المقرر');

$lessons = $lms->getCourseLessonsWithStatus($courseId, $studentId);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="courses.php">مقرراتي</a></li>
    <li class="breadcrumb-item active"><?= e($course['course_name']) ?></li>
  </ol>
</nav>

<h4 class="fw-bold mb-1"><i class="bi bi-collection-play text-primary"></i> دروس: <?= e($course['course_name']) ?></h4>
<p class="text-muted small mb-4">
  <i class="bi bi-info-circle"></i>
  أكمل الأنشطة الخمسة لكل درس بدرجة النجاح المطلوبة ليفتح الدرس التالي تلقائياً
</p>

<div class="list-group shadow-sm">
  <?php if (!$lessons): ?>
    <div class="alert alert-info">لم يضف المعلم دروساً لهذا المقرر بعد</div>
  <?php endif; ?>

  <?php foreach ($lessons as $i => $l):
      $locked    = $l['is_locked'];
      $completed = ($l['display_status'] === 'completed');
  ?>
  <div class="list-group-item d-flex align-items-center py-3 <?= $locked ? 'bg-light text-muted' : '' ?>">

    <!-- رقم / حالة الدرس -->
    <div class="me-3 text-center" style="width:52px;">
      <?php if ($completed): ?>
        <span style="font-size:1.8rem;">⭐</span>
      <?php elseif ($locked): ?>
        <span style="font-size:1.8rem;">🔒</span>
      <?php else: ?>
        <span class="badge rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
              style="width:42px;height:42px;font-size:1.1rem;"><?= $i + 1 ?></span>
      <?php endif; ?>
    </div>

    <div class="flex-grow-1">
      <h6 class="fw-bold mb-1"><?= e($l['title']) ?></h6>
      <small class="text-muted"><?= e(mb_substr($l['description'] ?? '', 0, 90)) ?></small>
      <div class="mt-1 small">
        <span class="badge bg-secondary bg-opacity-25 text-dark">
          <?= (int)($l['completed_activities'] ?? 0) ?> / <?= (int)$l['total_activities'] ?> نشاط
        </span>
        <?php if ($completed): ?>
          <span class="badge bg-success">⭐ مكتمل</span>
        <?php elseif ($locked): ?>
          <span class="badge bg-secondary">🔒 غير مكتمل</span>
        <?php else: ?>
          <span class="badge bg-warning text-dark">قيد التعلم</span>
        <?php endif; ?>
        <?php if (!empty($l['time_spent_seconds'])): ?>
          <span class="badge bg-info bg-opacity-25 text-dark">
            <i class="bi bi-stopwatch"></i> <?= lms_format_seconds((int)$l['time_spent_seconds']) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <?php if ($locked): ?>
        <button class="btn btn-secondary btn-sm" disabled><i class="bi bi-lock-fill"></i> مقفل</button>
      <?php else: ?>
        <a href="lesson.php?id=<?= (int)$l['id'] ?>"
           class="btn btn-<?= $completed ? 'outline-success' : 'primary' ?> btn-sm">
          <i class="bi bi-<?= $completed ? 'arrow-repeat' : 'play-fill' ?>"></i>
          <?= $completed ? 'مراجعة' : 'ابدأ الدرس' ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
