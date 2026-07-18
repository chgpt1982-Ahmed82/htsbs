<?php
/*
=====================================================================
LMS - لوحة الصدارة (Leaderboard) - تُحدَّث تلقائياً بعد كل نشاط
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];

// أفضل 20 طالباً
$stmt = $db->prepare("
    SELECT lb.*, u.full_name, u.profile_image, c.class_name
    FROM lms_leaderboard lb
    INNER JOIN students s ON lb.student_id = s.id
    INNER JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY lb.rank_position ASC
    LIMIT 20
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$myRank = $lms->getStudentRank($studentId);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-2"><i class="bi bi-bar-chart-fill text-warning"></i> لوحة الصدارة</h4>
<p class="text-muted small mb-4">
  الترتيب حسب: النجوم ⭐ ثم الشارات 🏅 ثم نسبة الإنجاز ثم متوسط الدرجات ثم سرعة الإنجاز
  <?php if ($myRank): ?>
    | ترتيبك الحالي: <span class="badge bg-primary"><?= $myRank ?></span>
  <?php endif; ?>
</p>

<!-- منصة التتويج -->
<?php if (count($rows) >= 3): ?>
<div class="row text-center mb-4 g-2 align-items-end">
  <?php
  $podium = [[1, $rows[1] ?? null, '🥈', 'secondary', 90],
             [0, $rows[0] ?? null, '🥇', 'warning', 120],
             [2, $rows[2] ?? null, '🥉', 'danger', 70]];
  foreach ($podium as [$idx, $p, $medal, $color, $h]): if (!$p) continue; ?>
  <div class="col-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body py-2">
        <div style="font-size:2rem;"><?= $medal ?></div>
        <strong class="d-block text-truncate"><?= e($p['full_name']) ?></strong>
        <small class="text-muted">⭐ <?= (int)$p['total_stars'] ?> | 🏅 <?= (int)$p['total_badges'] ?></small>
      </div>
      <div class="bg-<?= $color ?>" style="height:<?= $h ?>px;border-radius:0 0 .5rem .5rem;"></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>الطالب</th><th>الصف</th>
          <th>⭐ النجوم</th><th>🏅 الشارات</th>
          <th>الدروس المكتملة</th><th>نسبة الإنجاز</th>
          <th>متوسط الدرجات</th><th>وقت التعلم</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">لا توجد بيانات بعد — كن أول المتصدرين!</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $isMe = ((int)$r['student_id'] === $studentId); ?>
        <tr class="<?= $isMe ? 'table-primary' : '' ?>">
          <td class="fw-bold"><?= (int)$r['rank_position'] ?></td>
          <td>
            <?php if (!empty($r['profile_image'])): ?>
              <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($r['profile_image']) ?>"
                   class="rounded-circle me-1" width="32" height="32" style="object-fit:cover;">
            <?php else: ?>
              <i class="bi bi-person-circle me-1"></i>
            <?php endif; ?>
            <?= e($r['full_name']) ?>
            <?= $isMe ? '<span class="badge bg-primary">أنت</span>' : '' ?>
          </td>
          <td><?= e($r['class_name'] ?? '—') ?></td>
          <td>⭐ <?= (int)$r['total_stars'] ?></td>
          <td>🏅 <?= (int)$r['total_badges'] ?></td>
          <td><?= (int)$r['completed_lessons'] ?></td>
          <td>
            <div class="progress" style="height:8px;min-width:80px;">
              <div class="progress-bar bg-success" style="width:<?= (float)$r['progress_percent'] ?>%"></div>
            </div>
            <small><?= round((float)$r['progress_percent']) ?>%</small>
          </td>
          <td><?= round((float)$r['avg_grade'], 1) ?></td>
          <td><small><?= lms_format_seconds((int)$r['total_time_seconds']) ?></small></td>
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
