<?php
/*
=====================================================================
LMS - التحقق من صحة الشهادة عبر رمز QR (صفحة عامة)
=====================================================================
*/
require_once __DIR__ . '/includes/lms_init.php';

$certNo = trim((string)($_GET['no'] ?? ''));
$cert   = null;

if ($certNo !== '') {
    $stmt = $db->prepare("
        SELECT cert.*, u.full_name, s.student_number, c.course_name
        FROM lms_certificates cert
        INNER JOIN students s ON cert.student_id = s.id
        INNER JOIN users u ON s.user_id = u.id
        INNER JOIN courses c ON cert.course_id = c.id
        WHERE cert.certificate_no = ?
        LIMIT 1
    ");
    $stmt->execute([$certNo]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>التحقق من الشهادة</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:560px;">
  <div class="card shadow border-0">
    <div class="card-body text-center p-4">
      <?php if ($cert): ?>
        <div style="font-size:3rem;">✅</div>
        <h4 class="fw-bold text-success">شهادة صحيحة وموثقة</h4>
        <hr>
        <p class="mb-1"><strong>الطالب:</strong> <?= e($cert['full_name']) ?></p>
        <p class="mb-1"><strong>الرقم الأكاديمي:</strong> <?= e($cert['student_number'] ?? '—') ?></p>
        <p class="mb-1"><strong>المقرر:</strong> <?= e($cert['course_name']) ?></p>
        <p class="mb-1"><strong>رقم الشهادة:</strong> <span dir="ltr"><?= e($cert['certificate_no']) ?></span></p>
        <p class="mb-0"><strong>تاريخ الإصدار:</strong> <?= date('d/m/Y', strtotime($cert['issue_date'])) ?></p>
      <?php else: ?>
        <div style="font-size:3rem;">❌</div>
        <h4 class="fw-bold text-danger">الشهادة غير موجودة</h4>
        <p class="text-muted">تأكد من رقم الشهادة وحاول مجدداً</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
