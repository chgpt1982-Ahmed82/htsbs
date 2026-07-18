<?php
/*
=====================================================================
LMS - لوحة الصدارة (نسخة المعلم)
- منصة تتويج لأفضل 3 طلاب 🥇🥈🥉
- جدول كامل بالترتيب: النجوم ثم الشارات ثم الإنجاز ثم المتوسط ثم السرعة
- يُحدَّث الترتيب تلقائياً بعد كل نشاط يحله أي طالب
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

/* ==================== أفضل 50 طالباً ==================== */
$stmt = $db->prepare("
    SELECT lb.*, u.full_name, u.profile_image, s.student_number, c.class_name
    FROM lms_leaderboard lb
    INNER JOIN students s ON lb.student_id = s.id
    INNER JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY lb.rank_position ASC
    LIMIT 50
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-fill text-danger"></i> لوحة الصدارة</h4>
<p class="text-muted small mb-4">
  الترتيب حسب: النجوم ⭐ ثم الشارات 🏅 ثم نسبة الإنجاز ثم متوسط الدرجات ثم سرعة الإنجاز
  - يتحدث تلقائياً بعد كل نشاط
</p>

<?php if (!$rows): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle"></i> لا توجد بيانات بعد - ستظهر لوحة الصدارة بعد أن يبدأ الطلاب حل الأنشطة
</div>
<?php endif; ?>

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
        <div class="fw-bold small text-truncate"><?= e($p['full_name']) ?></div>
        <small class="text-muted d-block"><?= e($p['class_name'] ?? '') ?></small>
        <span class="badge bg-warning text-dark">⭐ <?= (int)$p['total_stars'] ?></span>
        <span class="badge bg-info text-dark">🏅 <?= (int)$p['total_badges'] ?></span>
      </div>
      <div class="bg-<?= $color ?> bg-opacity-25 rounded-bottom" style="height:<?= $h ?>px;"></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- الجدول الكامل -->
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th class="text-center">الترتيب</th>
          <th>الطالب</th>
          <th class="text-center">⭐ النجوم</th>
          <th class="text-center">🏅 الشارات</th>
          <th class="text-center">الدروس المكتملة</th>
          <th class="text-center">نسبة الإنجاز</th>
          <th class="text-center">متوسط الدرجات</th>
          <th class="text-center">وقت التعلم</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <?php $rank = (int)$r['rank_position']; ?>
        <tr class="<?= $rank <= 3 ? 'table-warning' : '' ?>">
          <td class="text-center fw-bold">
            <?php if ($rank === 1): ?>🥇
            <?php elseif ($rank === 2): ?>🥈
            <?php elseif ($rank === 3): ?>🥉
            <?php else: ?><?= $rank ?>
            <?php endif; ?>
          </td>
          <td>
            <span class="fw-bold"><?= e($r['full_name']) ?></span>
            <small class="text-muted d-block">
              <?= e($r['student_number'] ?? '—') ?><?= $r['class_name'] ? ' - ' . e($r['class_name']) : '' ?>
            </small>
          </td>
          <td class="text-center"><span class="badge bg-warning text-dark">⭐ <?= (int)$r['total_stars'] ?></span></td>
          <td class="text-center"><span class="badge bg-info text-dark">🏅 <?= (int)$r['total_badges'] ?></span></td>
          <td class="text-center"><?= (int)$r['completed_lessons'] ?></td>
          <td class="text-center">
            <div class="progress" style="height: 16px; min-width: 110px;">
              <div class="progress-bar bg-success" style="width: <?= round((float)$r['progress_percent']) ?>%;">
                <?= round((float)$r['progress_percent']) ?>%
              </div>
            </div>
          </td>
          <td class="text-center fw-bold"><?= round((float)$r['avg_grade'], 1) ?>%</td>
          <td class="text-center small"><?= e(lms_format_seconds((int)$r['total_time_seconds'])) ?></td>
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
