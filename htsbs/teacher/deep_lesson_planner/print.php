<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$db = (new Database())->connect();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("رقم التخطيط غير صحيح.");

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

if (!$lesson) die("التخطيط غير موجود.");

// Update print count
$db->prepare("UPDATE deep_lesson_plans SET printed_count = printed_count + 1 WHERE id = ?")->execute([$id]);

$planData   = json_decode($lesson['lesson_plan_json'] ?? '{}', true) ?: [];
$resources  = json_decode($lesson['resources']  ?? '[]', true) ?: [];
$facilities = json_decode($lesson['facilities'] ?? '[]', true) ?: [];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>طباعة التخطيط - <?= htmlspecialchars($lesson['lesson_title']); ?></title>
<style>

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Arial', 'Tahoma', sans-serif;
    font-size: 13px;
    line-height: 1.8;
    direction: rtl;
    text-align: right;
    color: #000;
    background: #fff;
    padding: 20px;
}

.print-btn {
    position: fixed;
    top: 15px;
    left: 15px;
    background: #0d6efd;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    z-index: 999;
    font-family: Arial;
}

.print-btn:hover { background: #0b5ed7; }

@media print {
    .print-btn, .no-print { display: none !important; }
    body { padding: 0; }
    .page { page-break-inside: avoid; }
}

/* ===========================
Header
=========================== */

.lesson-header {
    border: 2px solid #000;
    border-radius: 5px;
    margin-bottom: 16px;
    overflow: hidden;
}

.lesson-header-title {
    background: #1a3a6b;
    color: #fff;
    text-align: center;
    padding: 10px;
    font-size: 16px;
    font-weight: bold;
}

.lesson-header table {
    width: 100%;
    border-collapse: collapse;
}

.lesson-header table td,
.lesson-header table th {
    border: 1px solid #999;
    padding: 6px 10px;
    font-size: 12px;
}

.lesson-header table th {
    background: #f0f4ff;
    font-weight: bold;
    width: 15%;
}

/* ===========================
Sections
=========================== */

.section {
    border: 1px solid #999;
    border-radius: 5px;
    margin-bottom: 14px;
    overflow: hidden;
    page-break-inside: avoid;
}

.section-header {
    background: #1a3a6b;
    color: #fff;
    padding: 7px 14px;
    font-weight: bold;
    font-size: 13px;
}

.section-header.green  { background: #157347; }
.section-header.red    { background: #b02a37; }
.section-header.orange { background: #c35a00; }
.section-header.gray   { background: #495057; }
.section-header.purple { background: #6f42c1; }
.section-header.teal   { background: #0d6e6e; }

.section-body {
    padding: 12px 16px;
}

.section-sub-header {
    background: #6c757d;
    color: #fff;
    padding: 5px 14px;
    font-size: 12px;
    font-weight: bold;
}

ul.plan-list {
    margin: 0;
    padding-right: 22px;
}

ul.plan-list li {
    margin-bottom: 5px;
    font-size: 13px;
}

/* ===========================
Differentiation
=========================== */

.diff-table {
    width: 100%;
    border-collapse: collapse;
}

.diff-table td {
    width: 50%;
    border: 2px solid #999;
    padding: 10px;
    vertical-align: top;
    font-size: 12px;
}

.diff-green-head { background: #d1e7dd; font-weight: bold; border-bottom: 2px solid #157347; }
.diff-yellow-head { background: #fff3cd; font-weight: bold; border-bottom: 2px solid #e0a800; }

/* ===========================
Student categories
=========================== */

.cat-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.cat-table th, .cat-table td {
    border: 1px solid #999;
    padding: 6px 8px;
    vertical-align: top;
}

.cat-table th {
    background: #f0f4ff;
    font-weight: bold;
    text-align: center;
}

/* ===========================
Eval box
=========================== */

.eval-box {
    border: 2px dashed #dc3545;
    border-radius: 5px;
    padding: 10px 14px;
    margin: 8px 0;
    background: #fff5f5;
}

.eval-box strong {
    color: #dc3545;
}

/* ===========================
Checkboxes grid
=========================== */

.check-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    font-size: 12px;
}

.check-item::before {
    content: "☑ ";
    color: #157347;
}

/* ===========================
Signature
=========================== */

.signature-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.signature-table td {
    border: 1px solid #999;
    padding: 10px;
    text-align: center;
    width: 33.33%;
}

</style>
</head>
<body>

<button onclick="window.print()" class="print-btn no-print">🖨️ طباعة</button>

<!-- ===========================
Header
=========================== -->

<div class="lesson-header">
<div class="lesson-header-title">التخطيط العميق للدروس الفائقة</div>
<table>
    <tr>
        <th>المدرسة</th><td><?= htmlspecialchars($lesson['full_name'] ?? ''); ?></td>
        <th>رمز المقرر</th><td><?= htmlspecialchars($lesson['course_name']); ?></td>
    </tr>
    <tr>
        <th>عنوان الدرس</th><td><?= htmlspecialchars($lesson['lesson_title']); ?></td>
        <th>التاريخ</th><td><?= htmlspecialchars($lesson['lesson_date'] ?? ''); ?></td>
    </tr>
    <tr>
        <th>الصف</th><td><?= htmlspecialchars($lesson['class_name']); ?></td>
        <th>الوحدة</th><td><?= htmlspecialchars($lesson['unit_name']); ?></td>
    </tr>
    <tr>
        <th>زمن الحصة</th><td><?= htmlspecialchars((string)$lesson['lesson_duration']); ?> دقيقة</td>
        <th>مستوى الطلبة</th><td><?= htmlspecialchars($lesson['student_level']); ?></td>
    </tr>
</table>
</div>

<!-- ===========================
Objectives & Skills
=========================== -->

<div class="section">
<div class="section-header">الأهداف السّلوكيّة: (الهدف الثاني في مستوى التحليل أو التركيب أو التقويم)</div>
<div class="section-body">
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="border:1px solid #ccc; padding:6px; width:5%;">1</td>
        <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($lesson['objective_1'] ?? ''); ?></td>
    </tr>
    <tr>
        <td style="border:1px solid #ccc; padding:6px;">2</td>
        <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($lesson['objective_2'] ?? ''); ?></td>
    </tr>
</table>
</div>
</div>

<div class="section">
<div class="section-header">المهارات الأساسية اللازمة (في ضوء التقويم التشخيصي)</div>
<div class="section-body">
<table style="width:100%; border-collapse:collapse;">
    <tr>
        <td style="border:1px solid #ccc; padding:6px; width:5%;">1</td>
        <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($lesson['skill_1'] ?? ''); ?></td>
    </tr>
    <tr>
        <td style="border:1px solid #ccc; padding:6px;">2</td>
        <td style="border:1px solid #ccc; padding:6px;"><?= htmlspecialchars($lesson['skill_2'] ?? ''); ?></td>
    </tr>
</table>
</div>
</div>

<div class="section">
<div class="section-header gray">طريقة التدريس والوسائل</div>
<div class="section-body">
<table style="width:100%;border-collapse:collapse;">
    <tr>
        <td style="border:1px solid #ccc;padding:6px;width:25%;"><strong>طريقة التدريس:</strong></td>
        <td style="border:1px solid #ccc;padding:6px;"><?= htmlspecialchars($lesson['teaching_method']); ?></td>
        <td style="border:1px solid #ccc;padding:6px;width:25%;"><strong>التعزيز:</strong></td>
        <td style="border:1px solid #ccc;padding:6px;"><?= htmlspecialchars($lesson['reinforcement'] ?? ''); ?></td>
    </tr>
    <tr>
        <td style="border:1px solid #ccc;padding:6px;"><strong>التكنولوجيا:</strong></td>
        <td style="border:1px solid #ccc;padding:6px;"><?= htmlspecialchars($lesson['technology'] ?? ''); ?></td>
        <td style="border:1px solid #ccc;padding:6px;"><strong>الوسائل:</strong></td>
        <td style="border:1px solid #ccc;padding:6px;"><?= implode('، ', array_map('htmlspecialchars', $resources)); ?></td>
    </tr>
</table>
</div>
</div>

<!-- ===========================
Introduction
=========================== -->

<?php if (!empty($planData['introduction'])): ?>
<div class="section">
<div class="section-header green">تمهيد:</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['introduction'])); ?></div>
</div>
<?php endif; ?>

<!-- ===========================
Goal 1
=========================== -->

<div class="section">
<div class="section-header">إجراءات الهدف الأوّل (المدّة: 15 دقيقة)</div>
<div class="section-body">
<ul class="plan-list">
<?php foreach (($planData['goal_1_procedures'] ?? []) as $item): ?>
    <li><?= htmlspecialchars($item); ?></li>
<?php endforeach; ?>
</ul>
</div>
</div>

<div class="section">
<div class="section-header red">تقويم الهدف الأوّل (المدّة: 5 دقائق)</div>
<div class="section-body">
<div class="eval-box">
    <strong>نص الوقفة التقويمية:</strong><br>
    <?= nl2br(htmlspecialchars($planData['goal_1_evaluation']['question'] ?? '')); ?>
</div>
<?php if (!empty($planData['goal_1_evaluation']['model_answer'])): ?>
<p style="margin-top:8px;"><strong>الإجابة النموذجية:</strong> <?= nl2br(htmlspecialchars($planData['goal_1_evaluation']['model_answer'])); ?></p>
<?php endif; ?>
</div>
<div class="section-sub-header">التغذية الراجعة (المدّة: 5 دقائق)</div>
<div class="section-body" style="font-size:12px;">
    <?= nl2br(htmlspecialchars($planData['goal_1_feedback'] ?? 'سيعرض المعلم الإجابة النموذجية، ويطلب من الطلبة التقويم الذاتي.')); ?>
</div>
</div>

<!-- ===========================
Goal 2
=========================== -->

<div class="section">
<div class="section-header purple">إجراءات الهدف الثاني (المدّة: 15 دقيقة)</div>
<div class="section-body">
<ul class="plan-list">
<?php foreach (($planData['goal_2_procedures'] ?? []) as $item): ?>
    <li><?= htmlspecialchars($item); ?></li>
<?php endforeach; ?>
</ul>
</div>
</div>

<div class="section">
<div class="section-header">سياسة التمايز 6G6Y</div>
<div class="section-body" style="padding:0;">
<table class="diff-table">
    <tr>
        <td class="diff-green-head">🟩 بطاقة التحدي (الورقة الخضراء)</td>
        <td class="diff-yellow-head">🟨 بطاقة المساعدة (الورقة الصفراء)</td>
    </tr>
    <tr>
        <td><?= nl2br(htmlspecialchars($planData['goal_2_differentiation']['green_card'] ?? $lesson['challenge_card'] ?? '')); ?></td>
        <td><?= nl2br(htmlspecialchars($planData['goal_2_differentiation']['yellow_card'] ?? $lesson['support_card'] ?? '')); ?></td>
    </tr>
</table>
</div>
</div>

<div class="section">
<div class="section-header red">تقويم الهدف الثاني (المدّة: 5 دقائق)</div>
<div class="section-body">
<div class="eval-box">
    <strong>نص الوقفة التقويمية:</strong><br>
    <?= nl2br(htmlspecialchars($planData['goal_2_evaluation']['question'] ?? '')); ?>
</div>
<?php if (!empty($planData['goal_2_evaluation']['model_answer'])): ?>
<p style="margin-top:8px;"><strong>الإجابة النموذجية:</strong> <?= nl2br(htmlspecialchars($planData['goal_2_evaluation']['model_answer'])); ?></p>
<?php endif; ?>
</div>
<div class="section-sub-header">التغذية الراجعة بالأقران (المدّة: 5 دقائق)</div>
<div class="section-body" style="font-size:12px;">
    <?= nl2br(htmlspecialchars($planData['goal_2_feedback'] ?? 'سيعرض المعلم الإجابة النموذجية، ويطلب من الطلبة التقويم بالأقران.')); ?>
</div>
</div>

<!-- ===========================
Conclusion
=========================== -->

<?php if (!empty($planData['conclusion'])): ?>
<div class="section">
<div class="section-header orange">الخاتمة (تنفّذ في حال اتّساع الوقت فقط)</div>
<div class="section-body"><?= nl2br(htmlspecialchars($planData['conclusion'])); ?></div>
</div>
<?php endif; ?>

<!-- ===========================
21st Century Skills
=========================== -->

<?php if (!empty($planData['21st_century_skills'])): ?>
<div class="section">
<div class="section-header teal">مهارات القرن الحادي والعشرين</div>
<div class="section-body">
<div class="check-grid">
<?php foreach ($planData['21st_century_skills'] as $skill): ?>
    <div class="check-item"><?= htmlspecialchars($skill); ?></div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>

<!-- ===========================
Bahrain Link & Homework
=========================== -->

<div class="section">
<div class="section-header gray">الربط بتراث مملكة البحرين والامتحانات الوطنية</div>
<div class="section-body">
<table style="width:100%;border-collapse:collapse;">
    <tr>
        <td style="border:1px solid #ccc;padding:6px;width:50%;vertical-align:top;"><strong>الربط بالبحرين:</strong><br><?= nl2br(htmlspecialchars($lesson['bahrain_link'] ?? '')); ?></td>
        <td style="border:1px solid #ccc;padding:6px;width:50%;vertical-align:top;"><strong>الامتحانات الوطنية:</strong><br><?= nl2br(htmlspecialchars($lesson['national_exams_link'] ?? '')); ?></td>
    </tr>
</table>
</div>
</div>

<div class="section">
<div class="section-header green">الإثراء المنزلي</div>
<div class="section-body"><?= nl2br(htmlspecialchars($lesson['homework'] ?? '')); ?></div>
</div>

<!-- ===========================
Student Categories
=========================== -->

<?php if (!empty($planData['student_categories'])): ?>
<?php $sc = $planData['student_categories']; ?>
<div class="section">
<div class="section-header">الإجراءات المتخذة مع فئات الطلبة المختلفة</div>
<div class="section-body" style="padding:0;">
<table class="cat-table">
    <tr>
        <th style="background:#d1e7dd;">المتفوقون</th>
        <th style="background:#cfe2ff;">الموهوبون</th>
        <th style="background:#fff3cd;">ذوو التحصيل المنخفض</th>
    </tr>
    <tr>
        <td><?= nl2br(htmlspecialchars($sc['gifted'] ?? '-')); ?></td>
        <td><?= nl2br(htmlspecialchars($sc['talented'] ?? '-')); ?></td>
        <td><?= nl2br(htmlspecialchars($sc['low_achievers'] ?? '-')); ?></td>
    </tr>
    <tr>
        <th>ذوو الأمراض المزمنة</th>
        <th>صعوبات التعلم</th>
        <th>غير الناطقين بالعربية</th>
    </tr>
    <tr>
        <td><?= nl2br(htmlspecialchars($sc['chronic_illness'] ?? '-')); ?></td>
        <td><?= nl2br(htmlspecialchars($sc['learning_difficulties'] ?? '-')); ?></td>
        <td><?= nl2br(htmlspecialchars($sc['non_arabic'] ?? '-')); ?></td>
    </tr>
</table>
</div>
</div>
<?php endif; ?>

<!-- ===========================
Take My Hand + Signature
=========================== -->

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

<script>
window.onload = function() {
    // Auto print after brief delay
    // window.print();
};
</script>

</body>
</html>
