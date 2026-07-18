<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = (new Database())->connect();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) exit('رقم التخطيط غير صحيح.');

$stmt = $db->prepare("
SELECT dlp.*, c.course_name, cl.class_name, u.full_name
FROM deep_lesson_plans dlp
LEFT JOIN courses c  ON c.id  = dlp.subject_id
LEFT JOIN classes cl ON cl.id = dlp.class_id
LEFT JOIN users   u  ON u.id  = dlp.teacher_id
WHERE dlp.id = ? AND dlp.teacher_id = ?
LIMIT 1
");

$stmt->execute([$id, $_SESSION['user_id']]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) exit('التخطيط غير موجود.');

// Update export count
$db->prepare("UPDATE deep_lesson_plans SET exported_pdf = exported_pdf + 1 WHERE id = ?")->execute([$id]);

$planData   = json_decode($lesson['lesson_plan_json'] ?? '{}', true) ?: [];
$resources  = json_decode($lesson['resources']  ?? '[]', true) ?: [];
$facilities = json_decode($lesson['facilities'] ?? '[]', true) ?: [];

/*
|--------------------------------------------------------------------------
| Build HTML for PDF (Dompdf)
|--------------------------------------------------------------------------
*/

ob_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: 'DejaVu Sans', 'Arial', sans-serif;
    font-size: 11px;
    line-height: 1.7;
    direction: rtl;
    text-align: right;
    color: #111;
}

.page-title {
    text-align: center;
    font-size: 15px;
    font-weight: bold;
    color: #1a3a6b;
    border-bottom: 3px solid #1a3a6b;
    padding-bottom: 8px;
    margin-bottom: 14px;
}

table.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

table.info-table td, table.info-table th {
    border: 1px solid #999;
    padding: 5px 8px;
    font-size: 11px;
}

table.info-table th {
    background: #e8edf5;
    font-weight: bold;
    width: 15%;
}

.section {
    border: 1px solid #aaa;
    margin-bottom: 12px;
    border-radius: 3px;
}

.section-header {
    background: #1a3a6b;
    color: #fff;
    padding: 6px 12px;
    font-weight: bold;
    font-size: 12px;
}

.section-header.green  { background: #157347; }
.section-header.red    { background: #b02a37; }
.section-header.purple { background: #6f42c1; }
.section-header.orange { background: #c35a00; }
.section-header.gray   { background: #495057; }
.section-header.teal   { background: #0d6e6e; }

.section-sub { background: #6c757d; color: #fff; padding: 4px 12px; font-size: 11px; font-weight: bold; }

.section-body { padding: 8px 12px; }

.eval-box {
    border: 2px dashed #b02a37;
    padding: 8px 10px;
    background: #fff5f5;
    margin: 6px 0;
    border-radius: 3px;
}

.eval-box strong { color: #b02a37; }

ul { padding-right: 18px; margin: 0; }
ul li { margin-bottom: 3px; }

table.diff-table { width: 100%; border-collapse: collapse; }
table.diff-table td { width: 50%; border: 1px solid #999; padding: 7px; vertical-align: top; font-size: 11px; }
.diff-green { background: #d1e7dd; }
.diff-yellow { background: #fff3cd; }

table.cat-table { width: 100%; border-collapse: collapse; font-size: 10px; }
table.cat-table th, table.cat-table td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
table.cat-table th { background: #f0f4ff; text-align: center; }

.signature-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
.signature-table td { border: 1px solid #999; padding: 10px; text-align: center; width: 33.33%; }

.check-grid { column-count: 3; font-size: 11px; }
.check-item::before { content: "✔ "; color: #157347; }
</style>
</head>
<body>

<div class="page-title">التخطيط العميق للدروس الفائقة</div>

<table class="info-table">
    <tr>
        <th>المعلم</th><td><?= htmlspecialchars($lesson['full_name'] ?? ''); ?></td>
        <th>المادة</th><td><?= htmlspecialchars($lesson['course_name']); ?></td>
    </tr>
    <tr>
        <th>عنوان الدرس</th><td><?= htmlspecialchars($lesson['lesson_title']); ?></td>
        <th>الصف</th><td><?= htmlspecialchars($lesson['class_name']); ?></td>
    </tr>
    <tr>
        <th>الوحدة</th><td><?= htmlspecialchars($lesson['unit_name']); ?></td>
        <th>التاريخ</th><td><?= htmlspecialchars($lesson['lesson_date'] ?? ''); ?></td>
    </tr>
    <tr>
        <th>زمن الحصة</th><td><?= $lesson['lesson_duration']; ?> دقيقة</td>
        <th>مستوى الطلبة</th><td><?= htmlspecialchars($lesson['student_level']); ?></td>
    </tr>
</table>

<!-- Objectives -->
<div class="section">
<div class="section-header">الأهداف السلوكية والمهارات الأساسية</div>
<div class="section-body">
<table style="width:100%;border-collapse:collapse;">
    <tr><td style="border:1px solid #ccc;padding:5px;width:5%;"><strong>1</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['objective_1'] ?? ''); ?></td></tr>
    <tr><td style="border:1px solid #ccc;padding:5px;"><strong>2</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['objective_2'] ?? ''); ?></td></tr>
</table>
<br>
<table style="width:100%;border-collapse:collapse;">
    <tr><td style="border:1px solid #ccc;padding:5px;width:20%;background:#f5f5f5;"><strong>المهارة 1</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['skill_1'] ?? ''); ?></td></tr>
    <tr><td style="border:1px solid #ccc;padding:5px;background:#f5f5f5;"><strong>المهارة 2</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['skill_2'] ?? ''); ?></td></tr>
</table>
</div>
</div>

<!-- Teaching methods -->
<div class="section">
<div class="section-header gray">طريقة التدريس والوسائل</div>
<div class="section-body">
<table style="width:100%;border-collapse:collapse;">
    <tr>
        <td style="border:1px solid #ccc;padding:5px;width:25%;"><strong>طريقة التدريس:</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['teaching_method']); ?></td>
        <td style="border:1px solid #ccc;padding:5px;width:20%;"><strong>التعزيز:</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['reinforcement'] ?? ''); ?></td>
    </tr>
    <tr>
        <td style="border:1px solid #ccc;padding:5px;"><strong>التكنولوجيا:</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= htmlspecialchars($lesson['technology'] ?? ''); ?></td>
        <td style="border:1px solid #ccc;padding:5px;"><strong>الوسائل:</strong></td><td style="border:1px solid #ccc;padding:5px;"><?= implode('، ', array_map('htmlspecialchars', $resources)); ?></td>
    </tr>
</table>
</div>
</div>

<!-- Intro -->
<?php if (!empty($planData['introduction'])): ?>
<div class="section">
<div class="section-header green">التمهيد</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['introduction'])); ?></div>
</div>
<?php endif; ?>

<!-- Goal 1 -->
<div class="section">
<div class="section-header">إجراءات الهدف الأوّل (المدّة: 15 دقيقة)</div>
<div class="section-body"><ul><?php foreach ($planData['goal_1_procedures'] ?? [] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul></div>
</div>

<div class="section">
<div class="section-header red">تقويم الهدف الأوّل (5 دقائق)</div>
<div class="section-body">
<div class="eval-box"><strong>نص الوقفة التقويمية:</strong><br><?= nl2br(htmlspecialchars($planData['goal_1_evaluation']['question'] ?? '')); ?></div>
<?php if (!empty($planData['goal_1_evaluation']['model_answer'])): ?><p><strong>الإجابة النموذجية:</strong> <?= nl2br(htmlspecialchars($planData['goal_1_evaluation']['model_answer'])); ?></p><?php endif; ?>
</div>
<div class="section-sub">التغذية الراجعة (5 دقائق)</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['goal_1_feedback'] ?? 'سيعرض المعلم الإجابة النموذجية وتقويم ذاتي.')); ?></div>
</div>

<!-- Goal 2 -->
<div class="section">
<div class="section-header purple">إجراءات الهدف الثاني (المدّة: 15 دقيقة)</div>
<div class="section-body"><ul><?php foreach ($planData['goal_2_procedures'] ?? [] as $p): ?><li><?= htmlspecialchars($p); ?></li><?php endforeach; ?></ul></div>
</div>

<div class="section">
<div class="section-header">سياسة التمايز 6G6Y</div>
<div class="section-body" style="padding:0;">
<table class="diff-table">
    <tr>
        <td class="diff-green"><strong>🟩 بطاقة التحدي (الخضراء)</strong><br><?= nl2br(htmlspecialchars($planData['goal_2_differentiation']['green_card'] ?? $lesson['challenge_card'] ?? '')); ?></td>
        <td class="diff-yellow"><strong>🟨 بطاقة المساعدة (الصفراء)</strong><br><?= nl2br(htmlspecialchars($planData['goal_2_differentiation']['yellow_card'] ?? $lesson['support_card'] ?? '')); ?></td>
    </tr>
</table>
</div>
</div>

<div class="section">
<div class="section-header red">تقويم الهدف الثاني (5 دقائق)</div>
<div class="section-body">
<div class="eval-box"><strong>نص الوقفة التقويمية:</strong><br><?= nl2br(htmlspecialchars($planData['goal_2_evaluation']['question'] ?? '')); ?></div>
<?php if (!empty($planData['goal_2_evaluation']['model_answer'])): ?><p><strong>الإجابة النموذجية:</strong> <?= nl2br(htmlspecialchars($planData['goal_2_evaluation']['model_answer'])); ?></p><?php endif; ?>
</div>
<div class="section-sub">التغذية الراجعة بالأقران (5 دقائق)</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['goal_2_feedback'] ?? 'تقويم بالأقران وتصحيح جماعي.')); ?></div>
</div>

<!-- Conclusion -->
<?php if (!empty($planData['conclusion'])): ?>
<div class="section">
<div class="section-header orange">الخاتمة (إن اتسع الوقت)</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['conclusion'])); ?></div>
</div>
<?php endif; ?>

<!-- 21st skills -->
<?php if (!empty($planData['21st_century_skills'])): ?>
<div class="section">
<div class="section-header teal">مهارات القرن الحادي والعشرين</div>
<div class="section-body">
<div class="check-grid">
<?php foreach ($planData['21st_century_skills'] as $s): ?>
<div class="check-item"><?= htmlspecialchars($s); ?></div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>

<!-- Bahrain + Homework -->
<div class="section">
<div class="section-header gray">الربط وتراث البحرين والإثراء</div>
<div class="section-body">
<table style="width:100%;border-collapse:collapse;">
<tr>
    <td style="border:1px solid #ccc;padding:5px;width:50%;vertical-align:top;"><strong>الربط بالبحرين:</strong><br><?= nl2br(htmlspecialchars($lesson['bahrain_link'] ?? '')); ?></td>
    <td style="border:1px solid #ccc;padding:5px;vertical-align:top;"><strong>الامتحانات الوطنية:</strong><br><?= nl2br(htmlspecialchars($lesson['national_exams_link'] ?? '')); ?></td>
</tr>
<tr><td colspan="2" style="border:1px solid #ccc;padding:5px;"><strong>الإثراء المنزلي:</strong> <?= nl2br(htmlspecialchars($lesson['homework'] ?? '')); ?></td></tr>
</table>
</div>
</div>

<!-- Student Categories -->
<?php if (!empty($planData['student_categories'])): ?>
<?php $sc = $planData['student_categories']; ?>
<div class="section">
<div class="section-header">الإجراءات مع فئات الطلبة المختلفة</div>
<div class="section-body" style="padding:0;">
<table class="cat-table">
    <tr><th style="background:#d1e7dd;">المتفوقون</th><th style="background:#cfe2ff;">الموهوبون</th><th style="background:#fff3cd;">التحصيل المنخفض</th></tr>
    <tr><td><?= nl2br(htmlspecialchars($sc['gifted'] ?? '-')); ?></td><td><?= nl2br(htmlspecialchars($sc['talented'] ?? '-')); ?></td><td><?= nl2br(htmlspecialchars($sc['low_achievers'] ?? '-')); ?></td></tr>
    <tr><th>ذوو الأمراض المزمنة</th><th>صعوبات التعلم</th><th>غير الناطقين بالعربية</th></tr>
    <tr><td><?= nl2br(htmlspecialchars($sc['chronic_illness'] ?? '-')); ?></td><td><?= nl2br(htmlspecialchars($sc['learning_difficulties'] ?? '-')); ?></td><td><?= nl2br(htmlspecialchars($sc['non_arabic'] ?? '-')); ?></td></tr>
</table>
</div>
</div>
<?php endif; ?>

<?php if (!empty($planData['take_my_hand'])): ?>
<div class="section">
<div class="section-header teal">مبادرة خذ بيدي</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['take_my_hand'])); ?></div>
</div>
<?php endif; ?>

<table class="signature-table">
<tr>
    <td><strong>الأستاذ / رئيس القسم</strong><br><br>&nbsp;</td>
    <td><strong>مدير المدرسة</strong><br><br>&nbsp;</td>
    <td><strong>التاريخ</strong><br><br><?= date('d/m/Y'); ?></td>
</tr>
</table>

</body>
</html>
<?php

$html = ob_get_clean();

/*
|--------------------------------------------------------------------------
| Render with Dompdf
|--------------------------------------------------------------------------
*/

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'تخطيط_درس_' . preg_replace('/[^\w\x{0600}-\x{06FF}]/u', '_', $lesson['lesson_title']) . '_' . date('Ymd') . '.pdf';

$dompdf->stream($filename, ['Attachment' => true]);
exit;
?>
