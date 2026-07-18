<?php
/*
=====================================================================
LMS - اختيار الدرس لإدارة أنشطته (معلم)
صفحة وسيطة: تعرض جميع دروس المعلم مع حالة أنشطتها (0/5)
ومنها ينتقل إلى activities.php?lesson_id=X
(صفحة activities.php تتطلب lesson_id ولا يمكن فتحها مباشرة)
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(2);

$teacher = $lms->getTeacherByUserId((int)$_SESSION['user_id']);
if (!$teacher) exit('Teacher Not Found');

$teacherId = (int)$teacher['id'];

/* ==================== دروس المعلم مع عدد الأنشطة ==================== */
$stmt = $db->prepare("
    SELECT l.id, l.title, l.lesson_order, l.is_published,
           c.course_name,
           (SELECT COUNT(*) FROM lms_activities a WHERE a.lesson_id = l.id) AS activities_count
    FROM lms_lessons l
    INNER JOIN courses c ON l.course_id = c.id
    WHERE l.teacher_id = ?
    ORDER BY c.course_name, l.lesson_order, l.id
");
$stmt->execute([$teacherId]);
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<h4 class="fw-bold mb-1"><i class="bi bi-puzzle text-primary"></i> الأنشطة التفاعلية</h4>
<p class="text-muted small mb-4">
  اختر الدرس لإدارة أنشطته الخمسة المتدرجة في الصعوبة
  ⭐ اختيار من متعدد | ⭐⭐ صح أو خطأ | ⭐⭐⭐ ترتيب أو توصيل | ⭐⭐⭐⭐ سؤال قصير | ⭐⭐⭐⭐⭐ مشروع
</p>

<?php if (!$lessons): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle"></i> لا توجد دروس بعد.
  <a href="lessons.php" class="alert-link">أنشئ درساً أولاً من صفحة إدارة الدروس</a>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>المقرر</th>
          <th>الدرس</th>
          <th class="text-center">الأنشطة</th>
          <th class="text-center">الحالة</th>
          <th class="text-center">إدارة</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lessons as $i => $l): ?>
        <?php
            $count = (int)$l['activities_count'];
            $badge = $count >= 5 ? 'success' : ($count > 0 ? 'warning' : 'secondary');
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td class="small"><?= e($l['course_name']) ?></td>
          <td>
            <span class="fw-bold"><?= e($l['title']) ?></span>
            <small class="text-muted d-block">الترتيب: <?= (int)$l['lesson_order'] ?></small>
          </td>
          <td class="text-center">
            <span class="badge bg-<?= $badge ?>"><?= $count ?> / 5</span>
          </td>
          <td class="text-center">
            <?php if ($count >= 5): ?>
              <span class="badge bg-success">مكتملة ✔</span>
            <?php elseif ($count > 0): ?>
              <span class="badge bg-warning text-dark">ناقصة</span>
            <?php else: ?>
              <span class="badge bg-light text-muted border">لم تُنشأ بعد</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <a href="activities.php?lesson_id=<?= (int)$l['id'] ?>" class="btn btn-sm btn-success">
              <i class="bi bi-pencil-square"></i> إدارة الأنشطة
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<p class="text-muted small mt-3">
  <i class="bi bi-lightbulb"></i> ملاحظة: الدرس لا يُعتبر مكتملاً للطالب إلا بعد اجتياز
  <strong>جميع أنشطته</strong>، لذا احرص على إنشاء الأنشطة الخمسة لكل درس.
</p>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
