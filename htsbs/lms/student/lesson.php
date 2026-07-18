<?php
/*
=====================================================================
LMS - عرض الدرس + الأنشطة الخمسة + تتبع وقت التعلم تلقائياً
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];
$lessonId  = (int)($_GET['id'] ?? 0);

// جلب الدرس مع التحقق من صف الطالب
$stmt = $db->prepare("
    SELECT l.*, c.course_name
    FROM lms_lessons l
    INNER JOIN courses c ON l.course_id = c.id
    INNER JOIN course_assignments ca ON ca.course_id = c.id AND ca.class_id = ?
    WHERE l.id = ? AND l.is_published = 1
    LIMIT 1
");
$stmt->execute([(int)$student['class_id'], $lessonId]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) exit('الدرس غير موجود أو غير متاح');

// حماية القفل التسلسلي من الوصول المباشر
if (!$lms->isLessonUnlocked($lessonId, $studentId)) {
    exit('🔒 هذا الدرس مقفل - أكمل الدروس السابقة أولاً');
}

// فتح سجل التقدم + حفظ آخر درس
$lms->touchLessonProgress($lessonId, $studentId);
$lms->log((int)$_SESSION['user_id'], 'view_lesson', 'lesson_id=' . $lessonId);

// الأنشطة مع أفضل نتيجة للطالب
$stmt = $db->prepare("
    SELECT a.*,
           (SELECT MAX(score) FROM lms_student_activity_attempts att
             WHERE att.activity_id = a.id AND att.student_id = ?) AS best_score,
           (SELECT COUNT(*) FROM lms_student_activity_attempts att
             WHERE att.activity_id = a.id AND att.student_id = ?) AS attempts,
           (SELECT MAX(is_passed) FROM lms_student_activity_attempts att
             WHERE att.activity_id = a.id AND att.student_id = ?) AS is_passed
    FROM lms_activities a
    WHERE a.lesson_id = ?
    ORDER BY a.activity_order ASC
");
$stmt->execute([$studentId, $studentId, $studentId, $lessonId]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ملفات إضافية
$stmt = $db->prepare("SELECT * FROM lms_lesson_files WHERE lesson_id = ? ORDER BY id");
$stmt->execute([$lessonId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تحويل رابط يوتيوب إلى embed
function lms_youtube_embed(?string $url): ?string
{
    if (!$url) return null;
    if (preg_match('~(?:youtu\.be/|v=|embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return null;
}
$embed = lms_youtube_embed($lesson['video_url']);

$typeLabels = [
    'mcq' => 'اختيار من متعدد', 'true_false' => 'صح أو خطأ',
    'ordering' => 'ترتيب', 'matching' => 'توصيل',
    'short_answer' => 'سؤال قصير', 'project' => 'مشروع / رفع ملف'
];

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="courses.php">مقرراتي</a></li>
    <li class="breadcrumb-item"><a href="lessons.php?course_id=<?= (int)$lesson['course_id'] ?>"><?= e($lesson['course_name']) ?></a></li>
    <li class="breadcrumb-item active"><?= e($lesson['title']) ?></li>
  </ol>
</nav>

<div class="card border-0 shadow-sm mb-4">
  <?php if (!empty($lesson['image'])): ?>
    <img src="<?= BASE_URL ?>/lms/uploads/<?= e($lesson['image']) ?>" class="card-img-top"
         style="max-height:260px;object-fit:cover;" alt="صورة الدرس">
  <?php endif; ?>
  <div class="card-body">
    <h4 class="fw-bold"><?= e($lesson['title']) ?></h4>
    <p class="text-muted"><?= nl2br(e($lesson['description'] ?? '')) ?></p>

    <div class="row g-3">
      <?php if (!empty($lesson['objectives'])): ?>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100 bg-light">
          <h6 class="fw-bold"><i class="bi bi-bullseye text-danger"></i> أهداف التعلم</h6>
          <div class="small"><?= nl2br(e($lesson['objectives'])) ?></div>
        </div>
      </div>
      <?php endif; ?>
      <?php if (!empty($lesson['outcomes'])): ?>
      <div class="col-md-6">
        <div class="border rounded p-3 h-100 bg-light">
          <h6 class="fw-bold"><i class="bi bi-check2-circle text-success"></i> مخرجات التعلم</h6>
          <div class="small"><?= nl2br(e($lesson['outcomes'])) ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- الفيديو -->
<?php if ($embed || !empty($lesson['video_url'])): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="bi bi-camera-video text-danger"></i> فيديو الدرس</div>
  <div class="card-body">
    <?php if ($embed): ?>
      <div class="ratio ratio-16x9">
        <iframe src="<?= e($embed) ?>" allowfullscreen loading="lazy"></iframe>
      </div>
    <?php else: ?>
      <video controls class="w-100" style="max-height:480px;">
        <source src="<?= BASE_URL ?>/lms/uploads/<?= e($lesson['video_url']) ?>">
      </video>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- المواد التعليمية -->
<div class="row g-3 mb-4">
  <?php if (!empty($lesson['pdf_file'])): ?>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center justify-content-between">
        <span><i class="bi bi-file-pdf text-danger fs-3"></i> ملف PDF</span>
        <a href="<?= BASE_URL ?>/lms/uploads/<?= e($lesson['pdf_file']) ?>" target="_blank"
           class="btn btn-outline-danger btn-sm">فتح / تحميل</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php if (!empty($lesson['ppt_file'])): ?>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center justify-content-between">
        <span><i class="bi bi-file-ppt text-warning fs-3"></i> عرض PowerPoint</span>
        <a href="<?= BASE_URL ?>/lms/uploads/<?= e($lesson['ppt_file']) ?>"
           class="btn btn-outline-warning btn-sm" download>تحميل</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if ($files): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="bi bi-paperclip"></i> ملفات إضافية</div>
  <ul class="list-group list-group-flush">
    <?php foreach ($files as $f): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
      <span><i class="bi bi-file-earmark"></i> <?= e($f['file_title']) ?></span>
      <a href="<?= BASE_URL ?>/lms/uploads/<?= e($f['file_path']) ?>" download
         class="btn btn-sm btn-outline-primary">تحميل</a>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if (!empty($lesson['external_links'])): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="bi bi-link-45deg text-primary"></i> روابط خارجية</div>
  <ul class="list-group list-group-flush">
    <?php foreach (preg_split('/\r\n|\n/', $lesson['external_links']) as $link):
        $link = trim($link);
        if (!$link || !filter_var($link, FILTER_VALIDATE_URL)) continue; ?>
      <li class="list-group-item">
        <a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer"><?= e($link) ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if (!empty($lesson['references_text'])): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="bi bi-bookmarks text-secondary"></i> المراجع</div>
  <div class="card-body small"><?= nl2br(e($lesson['references_text'])) ?></div>
</div>
<?php endif; ?>

<!-- الأنشطة الخمسة -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold">
    <i class="bi bi-pencil-square text-success"></i> الأنشطة
    <small class="text-muted">- درجة النجاح المطلوبة: <?= e($lesson['pass_grade']) ?>%</small>
  </div>
  <div class="list-group list-group-flush">
    <?php if (!$activities): ?>
      <div class="list-group-item text-muted">لم يضف المعلم أنشطة بعد</div>
    <?php endif; ?>

    <?php
    $previousPassed = true; // النشاط الأول متاح دائماً
    foreach ($activities as $a):
        $passed = ((int)$a['is_passed'] === 1);
        $lockedAct = !$previousPassed;
    ?>
    <div class="list-group-item d-flex align-items-center py-3 <?= $lockedAct ? 'bg-light text-muted' : '' ?>">
      <div class="me-3" style="min-width:100px;">
        <?= str_repeat('⭐', (int)$a['activity_order']) ?>
      </div>
      <div class="flex-grow-1">
        <strong><?= e($a['title']) ?></strong>
        <br><small class="text-muted"><?= e($typeLabels[$a['activity_type']] ?? $a['activity_type']) ?></small>
        <?php if ($a['attempts'] > 0): ?>
          <br><small>
            المحاولات: <?= (int)$a['attempts'] ?> |
            أفضل نتيجة:
            <span class="badge bg-<?= $passed ? 'success' : 'danger' ?>"><?= e($a['best_score']) ?>%</span>
          </small>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($lockedAct): ?>
          <button class="btn btn-secondary btn-sm" disabled>🔒</button>
        <?php elseif ($passed): ?>
          <span class="badge bg-success me-1"><i class="bi bi-check-lg"></i> مجتاز</span>
          <a href="activity.php?id=<?= (int)$a['id'] ?>" class="btn btn-outline-secondary btn-sm">إعادة</a>
        <?php else: ?>
          <a href="activity.php?id=<?= (int)$a['id'] ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-play-fill"></i> ابدأ
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php $previousPassed = $passed; endforeach; ?>
  </div>
</div>

</div>
</div>
</div>

<!-- تتبع وقت التعلم تلقائياً كل 30 ثانية -->
<script>
(function () {
    const LESSON_ID = <?= (int)$lessonId ?>;
    setInterval(function () {
        if (document.hidden) return; // لا يحتسب وقت التبويب المخفي
        fetch('ajax/save_time.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'lesson_id=' + LESSON_ID + '&seconds=30&csrf_token=<?= e(lms_csrf_token()) ?>'
        }).catch(() => {});
    }, 30000);
})();
</script>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
