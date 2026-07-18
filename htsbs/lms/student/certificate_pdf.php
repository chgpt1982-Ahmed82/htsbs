<?php
/*
=====================================================================
LMS - تحميل الشهادة PDF (تنزيل مباشر من الخادم عبر mpdf)
- مكملة لصفحة certificate.php (العرض والطباعة) دون أي تعديل عليها
- لا تعمل إلا إذا استحق الطالب الشهادة (نفس شروط checkCertificate)
- الرابط: lms/student/certificate_pdf.php?course_id=X
=====================================================================
*/
require_once dirname(__DIR__) . '/includes/lms_init.php';
require_once dirname(__DIR__, 2) . '/config/school.php';

lms_require_role(3);

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit('Student Not Found');

$studentId = (int)$student['id'];
$courseId  = (int)($_GET['course_id'] ?? 0);

// فحص/إصدار الشهادة (نفس منطق صفحة العرض)
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

// صورة QR عبر خدمة خارجية (مع إظهار رابط التحقق نصياً كبديل دائم)
$qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode($verifyUrl);

// سجل التدقيق
$lms->log((int)$_SESSION['user_id'], 'download_certificate_pdf',
    'certificate_no=' . $cert['certificate_no']);

/* ==================== توليد PDF ==================== */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode'             => 'utf-8',
    'format'           => 'A4-L', // الشهادة بالعرض
    'default_font'     => 'xbriyaz',
    'autoScriptToLang' => true,
    'autoLangToFont'   => true,
    'margin_top'       => 10,
    'margin_bottom'    => 10,
    'margin_left'      => 10,
    'margin_right'     => 10,
]);
$mpdf->SetDirectionality('rtl');
$mpdf->SetTitle('شهادة إتمام - ' . $courseName);

// شعارات المؤسسة (تُتجاهل تلقائياً إن لم توجد الملفات)
$logoDir   = dirname(__DIR__, 2) . '/assets/images/';
$moeLogo   = is_file($logoDir . 'moe.png')    ? $logoDir . 'moe.png'    : '';
$schLogo   = is_file($logoDir . 'school.png') ? $logoDir . 'school.png' : '';

$html = '
<html dir="rtl">
<head><meta charset="UTF-8">
<style>
    body { direction: rtl; text-align: center; font-family: xbriyaz; color: #222; }
    .frame {
        border: 6px double #c9a227; padding: 24px 34px;
    }
    .org  { font-size: 12pt; font-weight: bold; }
    .title { color: #c9a227; font-size: 24pt; font-weight: bold; margin: 10px 0 4px; }
    .name  { color: #1a3c6e; font-size: 20pt; font-weight: bold; margin: 6px 0; }
    .course { color: #1a3c6e; font-size: 16pt; font-weight: bold; }
    table.stats { width: 70%; margin: 12px auto; border-collapse: collapse; }
    table.stats td { border: 1px solid #c9a227; padding: 6px 10px; font-size: 11pt; }
    table.footer { width: 100%; margin-top: 22px; }
    table.footer td { width: 33%; vertical-align: bottom; font-size: 10pt; }
    .sig { border-top: 1.5px solid #333; width: 60%; margin: 0 auto 3px; }
    .muted { color: #777; font-size: 9pt; }
</style>
</head>
<body>
<div class="frame">

    <table style="width:100%;">
        <tr>
            <td style="width:20%; text-align:right;">'
                . ($moeLogo ? '<img src="' . $moeLogo . '" height="65">' : '') . '
            </td>
            <td style="width:60%;" class="org">'
                . e(MINISTRY_NAME) . '<br>' . e(SCHOOL_NAME) . '
            </td>
            <td style="width:20%; text-align:left;">'
                . ($schLogo ? '<img src="' . $schLogo . '" height="65">' : '') . '
            </td>
        </tr>
    </table>

    <hr style="border:0; border-top:1px solid #c9a227;">

    <div class="title">شهادة إتمام مقرر</div>
    <div class="muted">تشهد إدارة المنصة التعليمية بأن الطالب</div>

    <div class="name">' . e($student['full_name']) . '</div>
    <div>الرقم الأكاديمي: <b>' . e($student['student_number'] ?? '—') . '</b></div>

    <div style="margin-top:8px;">قد أتمّ بنجاح جميع دروس وأنشطة مقرر</div>
    <div class="course">«' . e($courseName) . '»</div>

    <table class="stats">
        <tr>
            <td>⭐ النجوم<br><b>' . (int)$cert['stars'] . '</b></td>
            <td>🏅 الشارات<br><b>' . (int)$cert['badges'] . '</b></td>
            <td>📈 الإنجاز<br><b>' . round((float)$cert['progress_percent']) . '%</b></td>
            <td>📊 المعدل<br><b>' . round((float)$cert['final_grade'], 1) . '%</b></td>
        </tr>
    </table>

    <div>تاريخ الإنجاز: <b>' . date('d/m/Y', strtotime($cert['issue_date'])) . '</b></div>
    <div>رقم الشهادة: <b><span dir="ltr">' . e($cert['certificate_no']) . '</span></b></div>

    <table class="footer">
        <tr>
            <td>
                <div class="sig"></div>
                توقيع المعلم<br>
                <span class="muted">' . e($teacherName ?: '—') . '</span>
            </td>
            <td style="text-align:center;">
                <img src="' . $qrImg . '" width="90" height="90"><br>
                <span class="muted">امسح للتحقق</span><br>
                <span class="muted" dir="ltr" style="font-size:7pt;">' . e($verifyUrl) . '</span>
            </td>
            <td>
                <div class="sig"></div>
                توقيع الإدارة<br>
                <span class="muted">' . e(SCHOOL_NAME) . '</span>
            </td>
        </tr>
    </table>

</div>
</body>
</html>';

$mpdf->WriteHTML($html);
$mpdf->Output('certificate_' . $cert['certificate_no'] . '.pdf',
    \Mpdf\Output\Destination::DOWNLOAD);
exit;
