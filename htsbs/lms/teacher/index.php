<?php
/*
=====================================================================
LMS - لوحة المعلم الرئيسية
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2); // معلم فقط

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];

// مقررات المعلم
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.course_name
    FROM course_assignments ca
    INNER JOIN courses c ON ca.course_id = c.id
    WHERE ca.teacher_id = ?
");
$stmt->execute([$teacherId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// إحصائيات
$stmt = $db->prepare("SELECT COUNT(*) FROM lms_lessons WHERE teacher_id = ?");
$stmt->execute([$teacherId]);
$lessonsCount = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) FROM lms_activities a
    INNER JOIN lms_lessons l ON a.lesson_id = l.id
    WHERE l.teacher_id = ?
");
$stmt->execute([$teacherId]);
$activitiesCount = (int)$stmt->fetchColumn();

// مشاريع بانتظار التصحيح
$stmt = $db->prepare("
    SELECT COUNT(*) FROM lms_student_activity_attempts att
    INNER JOIN lms_activities a ON att.activity_id = a.id
    INNER JOIN lms_lessons l ON a.lesson_id = l.id
    WHERE l.teacher_id = ? AND a.activity_type = 'project' AND att.graded_by IS NULL
");
$stmt->execute([$teacherId]);
$pendingProjects = (int)$stmt->fetchColumn();

// عدد طلاب المعلم
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT s.id) FROM students s
    INNER JOIN course_assignments ca ON ca.class_id = s.class_id
    WHERE ca.teacher_id = ?
");
$stmt->execute([$teacherId]);
$studentsCount = (int)$stmt->fetchColumn();

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-4"><i class="bi bi-mortarboard text-primary"></i> منصة التعلم التفاعلي - لوحة المعلم</h4>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['bi-journal-bookmark', count($courses), 'مقرراتي', 'primary'],
      ['bi-collection-play', $lessonsCount, 'الدروس', 'success'],
      ['bi-pencil-square', $activitiesCount, 'الأنشطة', 'info'],
      ['bi-people', $studentsCount, 'الطلاب', 'warning'],
  ];
  foreach ($cards as [$icon, $val, $label, $color]): ?>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi <?= $icon ?> text-<?= $color ?> fs-1"></i>
        <h3 class="fw-bold mb-0"><?= $val ?></h3>
        <small class="text-muted"><?= $label ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($pendingProjects > 0): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center">
  <span><i class="bi bi-exclamation-triangle"></i>
    لديك <strong><?= $pendingProjects ?></strong> مشروع بانتظار التصحيح</span>
  <a href="projects.php" class="btn btn-warning btn-sm">تصحيح الآن</a>
</div>
<?php endif; ?>

<div class="row g-3">
  <?php
  $links = [
      ['lessons.php',  'bi-collection-play', 'إدارة الدروس', 'إنشاء الدروس ورفع الفيديو وPDF وPowerPoint والملفات', 'primary'],
      ['projects.php', 'bi-clipboard-check', 'تصحيح المشاريع', 'تصحيح مشاريع النشاط الخامس ⭐⭐⭐⭐⭐', 'warning'],
      ['progress.php', 'bi-graph-up-arrow', 'متابعة تقدم الطلاب', 'النجوم والشارات ونسب الإنجاز لكل طالب', 'success'],
      ['leaderboard.php', 'bi-bar-chart-fill', 'لوحة الصدارة', 'ترتيب الطلاب حسب النجوم والشارات والإنجاز', 'danger'],
      ['reports.php',  'bi-file-earmark-spreadsheet', 'التقارير', 'استخراج تقارير Excel وطباعة PDF', 'info'],
  ];
  foreach ($links as [$href, $icon, $title, $desc, $color]): ?>
  <div class="col-md-6 col-xl-4">
    <a href="<?= $href ?>" class="text-decoration-none">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <i class="bi <?= $icon ?> text-<?= $color ?> fs-2"></i>
          <h6 class="fw-bold mt-2 mb-1 text-dark"><?= $title ?></h6>
          <small class="text-muted"><?= $desc ?></small>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
