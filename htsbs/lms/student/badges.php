<?php
/*
=====================================================================
LMS - شارات الطالب (المكتسبة وغير المكتسبة)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];

$stmt = $db->prepare("
    SELECT b.*, sb.awarded_at
    FROM lms_badges b
    LEFT JOIN lms_student_badges sb
           ON sb.badge_id = b.id AND sb.student_id = ?
    ORDER BY b.id
");
$stmt->execute([$studentId]);
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// النجوم لكل مقرر
$stmt = $db->prepare("
    SELECT c.course_name, COUNT(*) AS stars
    FROM lms_stars st
    INNER JOIN courses c ON st.course_id = c.id
    WHERE st.student_id = ?
    GROUP BY c.id, c.course_name
");
$stmt->execute([$studentId]);
$starsByCourse = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalStars = array_sum(array_column($starsByCourse, 'stars'));

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-4"><i class="bi bi-patch-check-fill text-warning"></i> نجومي وشاراتي</h4>

<!-- النجوم -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold">⭐ مجموع النجوم: <?= $totalStars ?></div>
  <div class="card-body">
    <?php if (!$starsByCourse): ?>
      <p class="text-muted mb-0">أكمل درساً كاملاً للحصول على أول نجمة ذهبية!</p>
    <?php endif; ?>
    <?php foreach ($starsByCourse as $s): ?>
      <div class="d-flex justify-content-between border-bottom py-2">
        <span><?= e($s['course_name']) ?></span>
        <span><?= str_repeat('⭐', min((int)$s['stars'], 15)) ?>
          <strong>(<?= (int)$s['stars'] ?>)</strong></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- الشارات -->
<div class="row g-3">
  <?php foreach ($badges as $b):
      $earned = !empty($b['awarded_at']); ?>
  <div class="col-6 col-md-4 col-xl-3">
    <div class="card border-0 shadow-sm text-center h-100 <?= $earned ? '' : 'opacity-50' ?>">
      <div class="card-body">
        <div style="font-size:3rem;<?= $earned ? '' : 'filter:grayscale(1);' ?>"><?= e($b['icon']) ?></div>
        <h6 class="fw-bold mb-1"><?= e($b['title']) ?></h6>
        <small class="text-muted d-block"><?= e($b['description']) ?></small>
        <?php if ($earned): ?>
          <span class="badge bg-success mt-2">
            <?= date('d/m/Y', strtotime($b['awarded_at'])) ?>
          </span>
        <?php else: ?>
          <span class="badge bg-secondary mt-2">🔒 لم تُكتسب بعد</span>
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
