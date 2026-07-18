<?php
/*
=====================================================================
LMS - الشهادة (عرض + طباعة + تحميل PDF عبر نافذة الطباعة)
لا تظهر إلا بعد: كل الدروس + كل الأنشطة + نسبة الإنجاز المطلوبة
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';
require_once dirname(__DIR__, 2) . '/config/school.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];
$courseId  = (int)($_GET['course_id'] ?? 0);

// فحص/إصدار الشهادة
$cert = $lms->checkCertificate($studentId, $courseId);

if (!$cert) {
    exit('🔒 لم تستحق الشهادة بعد - أكمل جميع الدروس والأنشطة بنسبة الإنجاز المطلوبة');
}

$stmt = $db->prepare("SELECT course_name FROM courses WHERE id = ?");
$stmt->execute([$courseId]);
$courseName = (string)$stmt->fetchColumn();

// اسم المعلم
$stmt = $db->prepare("
    SELECT u.full_name FROM course_assignments ca
    INNER JOIN teachers t ON ca.teacher_id = t.id
    INNER JOIN users u ON t.user_id = u.id
    WHERE ca.course_id = ? AND ca.class_id = ?
    LIMIT 1
");
$stmt->execute([$courseId, (int)$student['class_id']]);
$teacherName = (string)$stmt->fetchColumn();

$verifyUrl = BASE_URL . '/lms/verify.php?no=' . urlencode($cert['certificate_no']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>شهادة إتمام - <?= e($courseName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
<style>
    body { background: #eef1f6; font-family: 'Cairo', sans-serif; }
    .certificate {
        max-width: 950px; margin: 30px auto; background: #fff;
        border: 14px double #c9a227; border-radius: 8px;
        padding: 50px 60px; position: relative;
    }
    .certificate::before {
        content: '🎓'; position: absolute; font-size: 200px;
        opacity: .05; top: 50%; left: 50%; transform: translate(-50%,-50%);
    }
    .cert-title { color: #c9a227; font-weight: 900; letter-spacing: 1px; }
    .cert-name { font-size: 2.2rem; font-weight: 900; color: #1a3c6e; }
    .sig-line { border-top: 2px solid #333; width: 180px; margin: 40px auto 5px; }
    @media print {
        body { background: #fff; }
        .no-print { display: none !important; }
        .certificate { margin: 0; border-width: 10px; box-shadow: none; }
    }
</style>
</head>
<body>

<div class="text-center my-3 no-print">
  <button onclick="window.print()" class="btn btn-primary">
    🖨️ طباعة / تحميل PDF
  </button>
  <a href="index.php" class="btn btn-outline-secondary">العودة للوحة</a>
  <div class="small text-muted mt-1">لتحميل PDF: اختر "حفظ كـ PDF" من نافذة الطباعة</div>
</div>

<div class="certificate text-center">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <img src="<?= BASE_URL ?>/assets/images/moe.png" alt="شعار الوزارة" height="80"
         onerror="this.style.display='none'">
    <div>
      <div class="fw-bold"><?= e(MINISTRY_NAME) ?></div>
      <div class="fw-bold"><?= e(SCHOOL_NAME) ?></div>
    </div>
    <img src="<?= BASE_URL ?>/assets/images/school.png" alt="شعار المدرسة" height="80"
         onerror="this.style.display='none'">
  </div>

  <hr style="border-color:#c9a227;opacity:.6;">

  <h1 class="cert-title my-3">شهادة إتمام مقرر</h1>
  <p class="text-muted">تشهد إدارة المنصة التعليمية بأن الطالب</p>

  <div class="cert-name my-2"><?= e($student['full_name']) ?></div>
  <p class="mb-1">الرقم الأكاديمي: <strong><?= e($student['student_number'] ?? '—') ?></strong></p>

  <p class="fs-5 mt-3">قد أتمّ بنجاح جميع دروس وأنشطة مقرر</p>
  <h3 class="fw-bold text-primary">«<?= e($courseName) ?>»</h3>

  <div class="row justify-content-center my-4 g-3">
    <div class="col-auto"><div class="border rounded px-3 py-2">⭐ النجوم<br><strong><?= (int)$cert['stars'] ?></strong></div></div>
    <div class="col-auto"><div class="border rounded px-3 py-2">🏅 الشارات<br><strong><?= (int)$cert['badges'] ?></strong></div></div>
    <div class="col-auto"><div class="border rounded px-3 py-2">📈 الإنجاز<br><strong><?= round((float)$cert['progress_percent']) ?>%</strong></div></div>
    <div class="col-auto"><div class="border rounded px-3 py-2">📊 المعدل<br><strong><?= round((float)$cert['final_grade'], 1) ?>%</strong></div></div>
  </div>

  <p class="mb-1">تاريخ الإنجاز: <strong><?= date('d/m/Y', strtotime($cert['issue_date'])) ?></strong></p>
  <p>رقم الشهادة: <strong dir="ltr"><?= e($cert['certificate_no']) ?></strong></p>

  <div class="row align-items-end mt-4">
    <div class="col-4">
      <div class="sig-line"></div>
      <div>توقيع المعلم</div>
      <small class="text-muted"><?= e($teacherName ?: '—') ?></small>
    </div>
    <div class="col-4">
      <div id="qrcode" class="d-inline-block bg-white p-2 border rounded"></div>
      <div class="small text-muted mt-1">امسح للتحقق</div>
    </div>
    <div class="col-4">
      <div class="sig-line"></div>
      <div>توقيع الإدارة</div>
      <small class="text-muted"><?= e(SCHOOL_NAME) ?></small>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qrcode'), {
    text: <?= json_encode($verifyUrl) ?>,
    width: 110, height: 110
});
</script>
</body>
</html>
