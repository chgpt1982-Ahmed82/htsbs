<?php
/*
=====================================================================
LMS - لوحة إحصائيات الطالب (Chart.js)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];

// إجمالي دروس مقررات الطالب
$stmt = $db->prepare("
    SELECT COUNT(*) FROM lms_lessons l
    INNER JOIN course_assignments ca ON ca.course_id = l.course_id
    WHERE ca.class_id = ? AND l.is_published = 1
");
$stmt->execute([(int)$student['class_id']]);
$totalLessons = (int)$stmt->fetchColumn();

// الدروس المكتملة
$stmt = $db->prepare("
    SELECT COUNT(*) FROM lms_lesson_progress
    WHERE student_id = ? AND status = 'completed'
");
$stmt->execute([$studentId]);
$completedLessons = (int)$stmt->fetchColumn();

$remainingLessons = max(0, $totalLessons - $completedLessons);
$progressPercent  = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : 0;

// إحصائيات عامة
$stmt = $db->prepare("
    SELECT COALESCE(AVG(avg_grade),0) AS avg_grade,
           COALESCE(SUM(total_time_seconds),0) AS total_time
    FROM lms_student_progress WHERE student_id = ?
");
$stmt->execute([$studentId]);
$overall = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) FROM lms_stars WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalStars = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM lms_student_badges WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalBadges = (int)$stmt->fetchColumn();

$rank = $lms->getStudentRank($studentId);

// أفضل مادة
$stmt = $db->prepare("
    SELECT c.course_name, sp.avg_grade
    FROM lms_student_progress sp
    INNER JOIN courses c ON sp.course_id = c.id
    WHERE sp.student_id = ? AND sp.avg_grade > 0
    ORDER BY sp.avg_grade DESC LIMIT 1
");
$stmt->execute([$studentId]);
$bestCourse = $stmt->fetch(PDO::FETCH_ASSOC);

// أكثر نشاط استغرق وقتاً
$stmt = $db->prepare("
    SELECT a.title, MAX(att.duration_seconds) AS max_dur
    FROM lms_student_activity_attempts att
    INNER JOIN lms_activities a ON att.activity_id = a.id
    WHERE att.student_id = ?
    GROUP BY a.id, a.title
    ORDER BY max_dur DESC LIMIT 1
");
$stmt->execute([$studentId]);
$longestActivity = $stmt->fetch(PDO::FETCH_ASSOC);

// بيانات الرسوم: تقدم كل مقرر
$stmt = $db->prepare("
    SELECT c.course_name, sp.progress_percent, sp.avg_grade
    FROM lms_student_progress sp
    INNER JOIN courses c ON sp.course_id = c.id
    WHERE sp.student_id = ?
");
$stmt->execute([$studentId]);
$courseData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// نشاط آخر 14 يوماً
$stmt = $db->prepare("
    SELECT DATE(created_at) AS d, COUNT(*) AS cnt
    FROM lms_student_activity_attempts
    WHERE student_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(created_at)
");
$stmt->execute([$studentId]);
$dailyRaw = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'cnt', 'd');

$dailyLabels = []; $dailyValues = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('d/m', strtotime($d));
    $dailyValues[] = (int)($dailyRaw[$d] ?? 0);
}

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-4"><i class="bi bi-graph-up text-success"></i> إحصائياتي</h4>

<div class="row g-3 mb-4">
  <?php
  $cards = [
      ['✅', $completedLessons, 'دروس مكتملة', 'success'],
      ['⏳', $remainingLessons, 'دروس متبقية', 'secondary'],
      ['📈', $progressPercent . '%', 'نسبة الإنجاز', 'primary'],
      ['📊', round((float)$overall['avg_grade'], 1), 'متوسط الدرجات', 'info'],
      ['⭐', $totalStars, 'النجوم', 'warning'],
      ['🏅', $totalBadges, 'الشارات', 'danger'],
      ['🏆', $rank > 0 ? $rank : '—', 'ترتيبي', 'dark'],
      ['⏱️', lms_format_seconds((int)$overall['total_time']), 'وقت التعلم', 'success'],
  ];
  foreach ($cards as [$icon, $val, $label, $color]): ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body py-3">
        <div style="font-size:1.6rem;"><?= $icon ?></div>
        <h5 class="fw-bold text-<?= $color ?> mb-0"><?= is_string($val) ? e($val) : $val ?></h5>
        <small class="text-muted"><?= $label ?></small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold"><i class="bi bi-trophy text-warning"></i> أفضل مادة</h6>
        <?php if ($bestCourse): ?>
          <p class="mb-0"><?= e($bestCourse['course_name']) ?>
             <span class="badge bg-success"><?= e($bestCourse['avg_grade']) ?>%</span></p>
        <?php else: ?><p class="text-muted mb-0">لا توجد بيانات بعد</p><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h6 class="fw-bold"><i class="bi bi-hourglass-split text-danger"></i> أكثر نشاط استغرق وقتاً</h6>
        <?php if ($longestActivity): ?>
          <p class="mb-0"><?= e($longestActivity['title']) ?>
             <span class="badge bg-secondary"><?= lms_format_seconds((int)$longestActivity['max_dur']) ?></span></p>
        <?php else: ?><p class="text-muted mb-0">لا توجد بيانات بعد</p><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold">نسبة الإنجاز لكل مقرر</div>
      <div class="card-body"><canvas id="chartProgress" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold">متوسط الدرجات لكل مقرر</div>
      <div class="card-body"><canvas id="chartGrades" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-bold">نشاطي خلال آخر 14 يوماً</div>
      <div class="card-body"><canvas id="chartDaily" height="90"></canvas></div>
    </div>
  </div>
</div>

</div>
</div>
</div>

<script>
const courseNames = <?= json_encode(array_column($courseData, 'course_name'), JSON_UNESCAPED_UNICODE) ?>;

new Chart(document.getElementById('chartProgress'), {
    type: 'doughnut',
    data: {
        labels: courseNames,
        datasets: [{
            data: <?= json_encode(array_map('floatval', array_column($courseData, 'progress_percent'))) ?>,
            backgroundColor: ['#4e73df','#1cc88a','#f6c23e','#e74a3b','#36b9cc','#858796']
        }]
    }
});

new Chart(document.getElementById('chartGrades'), {
    type: 'bar',
    data: {
        labels: courseNames,
        datasets: [{
            label: 'متوسط الدرجات %',
            data: <?= json_encode(array_map('floatval', array_column($courseData, 'avg_grade'))) ?>,
            backgroundColor: '#1cc88a'
        }]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
});

new Chart(document.getElementById('chartDaily'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dailyLabels) ?>,
        datasets: [{
            label: 'عدد الأنشطة المحلولة',
            data: <?= json_encode($dailyValues) ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78,115,223,.15)',
            fill: true,
            tension: .3
        }]
    },
    options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
