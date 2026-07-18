<?php
/*
=====================================================================
LMS - متابعة تقدم الطلاب (معلم)
- اختيار المقرر ثم عرض جميع طلابه مع:
  الدروس المكتملة | نسبة الإنجاز | متوسط الدرجات | النجوم | الشارات
  وقت التعلم | آخر درس وصل إليه | استحقاق الشهادة
- بطاقات ملخصة إجمالية للمقرر
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];

/* ==================== مقررات المعلم ==================== */
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.course_name
    FROM course_assignments ca
    INNER JOIN courses c ON ca.course_id = c.id
    WHERE ca.teacher_id = ?
    ORDER BY c.course_name
");
$stmt->execute([$teacherId]);
$courses   = $stmt->fetchAll(PDO::FETCH_ASSOC);
$courseIds = array_map('intval', array_column($courses, 'id'));

// المقرر المختار (مع التحقق أنه من مقررات المعلم فقط)
$courseId = (int)($_GET['course_id'] ?? 0);
if ($courseId && !in_array($courseId, $courseIds, true)) {
    $courseId = 0;
}
if (!$courseId && $courseIds) {
    $courseId = $courseIds[0]; // أول مقرر افتراضياً
}

$students     = [];
$totalLessons = 0;
$summary      = ['avg_progress' => 0, 'completed_all' => 0, 'certificates' => 0];

if ($courseId) {

    // عدد دروس المقرر المنشورة
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM lms_lessons
        WHERE course_id = ? AND is_published = 1
    ");
    $stmt->execute([$courseId]);
    $totalLessons = (int)$stmt->fetchColumn();

    /*
    طلاب المقرر (عبر الصفوف المرتبطة بالمعلم في course_assignments)
    مع كل بيانات التقدم والنجوم والشارات وآخر درس
    */
    $stmt = $db->prepare("
        SELECT DISTINCT s.id AS student_id, s.student_number,
               u.full_name, u.profile_image,
               cls.class_name,
               COALESCE(sp.completed_lessons, 0)  AS completed_lessons,
               COALESCE(sp.progress_percent, 0)   AS progress_percent,
               COALESCE(sp.avg_grade, 0)          AS avg_grade,
               COALESCE(sp.total_time_seconds, 0) AS total_time_seconds,
               (SELECT COUNT(*) FROM lms_stars st
                 WHERE st.student_id = s.id AND st.course_id = ?)       AS stars,
               (SELECT COUNT(*) FROM lms_student_badges sb
                 WHERE sb.student_id = s.id)                             AS badges,
               (SELECT l2.title FROM lms_lessons l2
                 WHERE l2.id = sp.last_lesson_id)                        AS last_lesson_title,
               (SELECT COUNT(*) FROM lms_certificates cert
                 WHERE cert.student_id = s.id AND cert.course_id = ?)    AS has_certificate
        FROM course_assignments ca
        INNER JOIN students s ON s.class_id = ca.class_id
        INNER JOIN users u ON s.user_id = u.id
        LEFT JOIN classes cls ON s.class_id = cls.id
        LEFT JOIN lms_student_progress sp
               ON sp.student_id = s.id AND sp.course_id = ca.course_id
        WHERE ca.course_id = ? AND ca.teacher_id = ?
        ORDER BY progress_percent DESC, avg_grade DESC, u.full_name
    ");
    $stmt->execute([$courseId, $courseId, $courseId, $teacherId]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ملخص إجمالي
    if ($students) {
        $sumProgress = 0;
        foreach ($students as $s) {
            $sumProgress += (float)$s['progress_percent'];
            if ((float)$s['progress_percent'] >= 100) $summary['completed_all']++;
            if ((int)$s['has_certificate'] > 0)       $summary['certificates']++;
        }
        $summary['avg_progress'] = round($sumProgress / count($students), 1);
    }
}

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-success"></i> متابعة تقدم الطلاب</h4>
<p class="text-muted small mb-4">النجوم والشارات ونسب الإنجاز وآخر درس وصل إليه كل طالب</p>

<!-- اختيار المقرر -->
<form method="get" class="row g-2 mb-4">
  <div class="col-md-5">
    <select name="course_id" class="form-select" onchange="this.form.submit()">
      <?php if (!$courses): ?>
        <option value="">لا توجد مقررات مسندة إليك</option>
      <?php endif; ?>
      <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $courseId === (int)$c['id'] ? 'selected' : '' ?>>
          <?= e($c['course_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php if ($courseId): ?>

<!-- بطاقات ملخصة -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-people-fill text-primary fs-3"></i>
        <div class="fs-4 fw-bold"><?= count($students) ?></div>
        <small class="text-muted">عدد الطلاب</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-bar-chart-line-fill text-success fs-3"></i>
        <div class="fs-4 fw-bold"><?= $summary['avg_progress'] ?>%</div>
        <small class="text-muted">متوسط الإنجاز العام</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-trophy-fill text-warning fs-3"></i>
        <div class="fs-4 fw-bold"><?= $summary['completed_all'] ?></div>
        <small class="text-muted">أكملوا المقرر 100%</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <i class="bi bi-award-fill text-danger fs-3"></i>
        <div class="fs-4 fw-bold"><?= $summary['certificates'] ?></div>
        <small class="text-muted">شهادات مُصدرة</small>
      </div>
    </div>
  </div>
</div>

<!-- جدول تقدم الطلاب -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-bold">
    <i class="bi bi-table"></i> تفاصيل تقدم الطلاب
    <span class="badge bg-secondary">عدد دروس المقرر: <?= $totalLessons ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>الطالب</th>
          <th class="text-center">الدروس المكتملة</th>
          <th style="min-width:160px;">نسبة الإنجاز</th>
          <th class="text-center">متوسط الدرجات</th>
          <th class="text-center">⭐ النجوم</th>
          <th class="text-center">🏅 الشارات</th>
          <th class="text-center">وقت التعلم</th>
          <th>آخر درس وصل إليه</th>
          <th class="text-center">الشهادة</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$students): ?>
        <tr><td colspan="10" class="text-center text-muted py-4">لا يوجد طلاب في هذا المقرر</td></tr>
        <?php endif; ?>

        <?php foreach ($students as $i => $s): ?>
        <?php
            $percent = round((float)$s['progress_percent']);
            $barColor = $percent >= 100 ? 'success' : ($percent >= 50 ? 'primary' : 'warning');
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td>
            <span class="fw-bold"><?= e($s['full_name']) ?></span>
            <small class="text-muted d-block">
              <?= e($s['student_number'] ?? '—') ?><?= $s['class_name'] ? ' - ' . e($s['class_name']) : '' ?>
            </small>
          </td>
          <td class="text-center"><?= (int)$s['completed_lessons'] ?> / <?= $totalLessons ?></td>
          <td>
            <div class="progress" style="height: 18px;">
              <div class="progress-bar bg-<?= $barColor ?>" role="progressbar"
                   style="width: <?= $percent ?>%;"
                   aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                <?= $percent ?>%
              </div>
            </div>
          </td>
          <td class="text-center fw-bold"><?= round((float)$s['avg_grade'], 1) ?>%</td>
          <td class="text-center"><span class="badge bg-warning text-dark">⭐ <?= (int)$s['stars'] ?></span></td>
          <td class="text-center"><span class="badge bg-info text-dark">🏅 <?= (int)$s['badges'] ?></span></td>
          <td class="text-center small"><?= e(lms_format_seconds((int)$s['total_time_seconds'])) ?></td>
          <td class="small text-muted"><?= e($s['last_lesson_title'] ?? 'لم يبدأ بعد') ?></td>
          <td class="text-center">
            <?php if ((int)$s['has_certificate'] > 0): ?>
              <span class="badge bg-success">🎓 مُصدرة</span>
            <?php else: ?>
              <span class="badge bg-light text-muted border">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
