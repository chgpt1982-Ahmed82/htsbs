<?php
/*
=====================================================================
LMS - لوحة الطالب الرئيسية
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';

lms_require_role(3); // طالب فقط

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];

/* ==================== الإحصائيات العامة ==================== */

// النجوم
$stmt = $db->prepare("SELECT COUNT(*) FROM lms_stars WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalStars = (int)$stmt->fetchColumn();

// الشارات
$stmt = $db->prepare("SELECT COUNT(*) FROM lms_student_badges WHERE student_id = ?");
$stmt->execute([$studentId]);
$totalBadges = (int)$stmt->fetchColumn();

// الترتيب
$rank = $lms->getStudentRank($studentId);

// التقدم الإجمالي
$stmt = $db->prepare("
    SELECT COALESCE(AVG(progress_percent),0) AS progress,
           COALESCE(AVG(avg_grade),0)        AS avg_grade,
           COALESCE(SUM(total_time_seconds),0) AS total_time
    FROM lms_student_progress WHERE student_id = ?
");
$stmt->execute([$studentId]);
$overall = $stmt->fetch(PDO::FETCH_ASSOC);

// عدد المقررات
$courses = $lms->getStudentCourses((int)$student['class_id']);

// آخر درس وصل إليه
$stmt = $db->prepare("
    SELECT l.id, l.title, c.course_name
    FROM lms_student_progress sp
    INNER JOIN lms_lessons l ON sp.last_lesson_id = l.id
    INNER JOIN courses c ON l.course_id = c.id
    WHERE sp.student_id = ? AND sp.last_lesson_id IS NOT NULL
    ORDER BY sp.updated_at DESC LIMIT 1
");
$stmt->execute([$studentId]);
$lastLesson = $stmt->fetch(PDO::FETCH_ASSOC);

// آخر نشاط تم حله
$stmt = $db->prepare("
    SELECT a.title, att.score, att.created_at, a.lesson_id
    FROM lms_student_activity_attempts att
    INNER JOIN lms_activities a ON att.activity_id = a.id
    WHERE att.student_id = ?
    ORDER BY att.created_at DESC LIMIT 1
");
$stmt->execute([$studentId]);
$lastActivity = $stmt->fetch(PDO::FETCH_ASSOC);

// الشهادات
$stmt = $db->prepare("
    SELECT cert.*, c.course_name
    FROM lms_certificates cert
    INNER JOIN courses c ON cert.course_id = c.id
    WHERE cert.student_id = ?
    ORDER BY cert.created_at DESC
");
$stmt->execute([$studentId]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// آخر الإشعارات
$stmt = $db->prepare("
    SELECT title, message, created_at FROM notifications
    WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([(int)$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// آخر الإنجازات (نجوم + شارات)
$stmt = $db->prepare("
    (SELECT CONCAT('⭐ نجمة: ', l.title) AS achievement, st.awarded_at
       FROM lms_stars st
       INNER JOIN lms_lessons l ON st.lesson_id = l.id
       WHERE st.student_id = ?)
    UNION ALL
    (SELECT CONCAT(b.icon, ' شارة: ', b.title), sb.awarded_at
       FROM lms_student_badges sb
       INNER JOIN lms_badges b ON sb.badge_id = b.id
       WHERE sb.student_id = ?)
    ORDER BY awarded_at DESC LIMIT 6
");
$stmt->execute([$studentId, $studentId]);
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$progressPercent = round((float)$overall['progress']);

include dirname(__DIR__, 2) . '/app/views/layouts/header.php';
?>

<div class="container-fluid px-0">
<div class="row flex-lg-row-reverse g-0">

<?php include dirname(__DIR__, 2) . '/app/views/layouts/student_sidebar.php'; ?>

<div class="main-content">

<!-- بطاقة الطالب -->
<div class="card shadow-lg border-0 mb-4" style="background:linear-gradient(135deg,#4e73df,#224abe);color:#fff;">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-2 text-center mb-3 mb-md-0">
        <?php if (!empty($student['profile_image'])): ?>
          <img src="<?= BASE_URL ?>/uploads/profiles/<?= e($student['profile_image']) ?>"
               class="rounded-circle border border-3 border-white" width="110" height="110"
               style="object-fit:cover;" alt="صورة الطالب">
        <?php else: ?>
          <i class="bi bi-person-circle" style="font-size:90px;"></i>
        <?php endif; ?>
      </div>
      <div class="col-md-7">
        <h3 class="fw-bold mb-1"><?= e($student['full_name']) ?></h3>
        <div class="row small mt-2 g-2">
          <div class="col-6 col-md-4"><i class="bi bi-person-vcard"></i> الرقم الشخصي: <?= e($student['national_id'] ?? '—') ?></div>
          <div class="col-6 col-md-4"><i class="bi bi-hash"></i> الرقم الأكاديمي: <?= e($student['student_number'] ?? '—') ?></div>
          <div class="col-6 col-md-4"><i class="bi bi-mortarboard"></i> التخصص: <?= e($student['department_name'] ?? '—') ?></div>
          <div class="col-6 col-md-4"><i class="bi bi-bar-chart-steps"></i> المستوى: <?= e($student['academic_level'] ?? '—') ?></div>
          <div class="col-6 col-md-4"><i class="bi bi-people"></i> الصف: <?= e($student['class_name'] ?? '—') ?></div>
          <div class="col-6 col-md-4"><i class="bi bi-journal-bookmark"></i> المقررات: <?= count($courses) ?></div>
        </div>
      </div>
      <div class="col-md-3 text-center">
        <!-- Progress Circle -->
        <div class="position-relative d-inline-block">
          <svg width="130" height="130" viewBox="0 0 130 130">
            <circle cx="65" cy="65" r="55" fill="none" stroke="rgba(255,255,255,.25)" stroke-width="12"/>
            <circle cx="65" cy="65" r="55" fill="none" stroke="#f6c23e" stroke-width="12"
                    stroke-linecap="round" stroke-dasharray="<?= 345.6 * $progressPercent / 100 ?> 345.6"
                    transform="rotate(-90 65 65)"/>
          </svg>
          <div class="position-absolute top-50 start-50 translate-middle">
            <h3 class="fw-bold mb-0"><?= $progressPercent ?>%</h3>
            <small>الإنجاز</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- بطاقات الإحصائيات -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <div style="font-size:2rem;">⭐</div>
        <h3 class="fw-bold text-warning mb-0"><?= $totalStars ?></h3>
        <small class="text-muted">النجوم</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <div style="font-size:2rem;">🏅</div>
        <h3 class="fw-bold text-primary mb-0"><?= $totalBadges ?></h3>
        <small class="text-muted">الشارات</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <div style="font-size:2rem;">🏆</div>
        <h3 class="fw-bold text-success mb-0"><?= $rank > 0 ? $rank : '—' ?></h3>
        <small class="text-muted">ترتيبك بين زملائك</small>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body">
        <div style="font-size:2rem;">📊</div>
        <h3 class="fw-bold text-info mb-0"><?= round((float)$overall['avg_grade'], 1) ?></h3>
        <small class="text-muted">متوسط الدرجات</small>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- آخر درس / نشاط / وقت -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history text-primary"></i> متابعة التعلم</div>
      <div class="card-body">
        <p class="mb-2"><i class="bi bi-stopwatch text-success"></i>
          <strong>وقت التعلم:</strong> <?= lms_format_seconds((int)$overall['total_time']) ?></p>

        <p class="mb-2"><i class="bi bi-book text-primary"></i>
          <strong>آخر درس:</strong>
          <?php if ($lastLesson): ?>
            <a href="lesson.php?id=<?= (int)$lastLesson['id'] ?>">
              <?= e($lastLesson['title']) ?> (<?= e($lastLesson['course_name']) ?>)
            </a>
          <?php else: ?> لم تبدأ بعد <?php endif; ?>
        </p>

        <p class="mb-3"><i class="bi bi-pencil-square text-warning"></i>
          <strong>آخر نشاط:</strong>
          <?php if ($lastActivity): ?>
            <?= e($lastActivity['title']) ?>
            <span class="badge bg-<?= $lastActivity['score'] >= 60 ? 'success' : 'danger' ?>">
              <?= e($lastActivity['score']) ?>%
            </span>
          <?php else: ?> لا يوجد <?php endif; ?>
        </p>

        <?php if ($lastLesson): ?>
        <a href="lesson.php?id=<?= (int)$lastLesson['id'] ?>" class="btn btn-primary btn-sm">
          <i class="bi bi-play-fill"></i> متابعة من حيث توقفت
        </a>
        <?php else: ?>
        <a href="courses.php" class="btn btn-primary btn-sm">
          <i class="bi bi-play-fill"></i> ابدأ التعلم
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- الشهادات -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold"><i class="bi bi-award text-warning"></i> شهاداتي</div>
      <div class="card-body">
        <?php if ($certificates): ?>
          <?php foreach ($certificates as $cert): ?>
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
              <div>
                🎓 <strong><?= e($cert['course_name']) ?></strong>
                <br><small class="text-muted"><?= e($cert['certificate_no']) ?></small>
              </div>
              <a href="certificate.php?course_id=<?= (int)$cert['course_id'] ?>"
                 class="btn btn-outline-primary btn-sm">عرض</a>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted mb-0">أكمل جميع دروس وأنشطة أي مقرر للحصول على شهادتك 🎓</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- تقدم المقررات (Progress Bars) -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-bold"><i class="bi bi-journal-check text-success"></i> تقدمي في المقررات</div>
  <div class="card-body">
    <?php if (!$courses): ?>
      <p class="text-muted mb-0">لا توجد مقررات مسندة لصفك حالياً</p>
    <?php endif; ?>
    <?php foreach ($courses as $c):
        $stmt = $db->prepare("SELECT progress_percent FROM lms_student_progress WHERE student_id=? AND course_id=?");
        $stmt->execute([$studentId, (int)$c['id']]);
        $p = round((float)$stmt->fetchColumn());
    ?>
    <div class="mb-3">
      <div class="d-flex justify-content-between">
        <a href="lessons.php?course_id=<?= (int)$c['id'] ?>" class="fw-bold text-decoration-none">
          <?= e($c['course_name']) ?>
        </a>
        <span class="badge bg-<?= $p >= 100 ? 'success' : 'primary' ?>"><?= $p ?>%</span>
      </div>
      <div class="progress" style="height:10px;">
        <div class="progress-bar <?= $p >= 100 ? 'bg-success' : '' ?>" style="width:<?= $p ?>%"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- آخر الإنجازات -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold"><i class="bi bi-trophy text-warning"></i> آخر الإنجازات</div>
      <ul class="list-group list-group-flush">
        <?php if (!$achievements): ?>
          <li class="list-group-item text-muted">لا توجد إنجازات بعد — ابدأ أول درس!</li>
        <?php endif; ?>
        <?php foreach ($achievements as $a): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e($a['achievement']) ?></span>
            <small class="text-muted"><?= date('d/m/Y', strtotime($a['awarded_at'])) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- آخر الإشعارات -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-bold"><i class="bi bi-bell text-danger"></i> آخر الإشعارات</div>
      <ul class="list-group list-group-flush">
        <?php if (!$notifications): ?>
          <li class="list-group-item text-muted">لا توجد إشعارات</li>
        <?php endif; ?>
        <?php foreach ($notifications as $n): ?>
          <li class="list-group-item">
            <strong><?= e($n['title']) ?></strong>
            <br><small><?= e($n['message']) ?></small>
            <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<div class="text-center mb-4">
  <a href="leaderboard.php" class="btn btn-outline-primary"><i class="bi bi-bar-chart"></i> لوحة الصدارة</a>
  <a href="stats.php" class="btn btn-outline-success"><i class="bi bi-graph-up"></i> إحصائياتي</a>
  <a href="badges.php" class="btn btn-outline-warning"><i class="bi bi-patch-check"></i> شاراتي</a>
</div>

</div>
</div>
</div>

<?php include dirname(__DIR__, 2) . '/app/views/layouts/footer.php'; ?>
