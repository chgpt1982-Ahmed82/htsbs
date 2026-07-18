<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ((int)$_SESSION['role_id'] !== 2) {
    exit('Access Denied');
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
$db->prepare("UPDATE deep_lesson_plans SET exported_word = exported_word + 1 WHERE id = ?")->execute([$id]);

$planData   = json_decode($lesson['lesson_plan_json'] ?? '{}', true) ?: [];
$resources  = json_decode($lesson['resources']  ?? '[]', true) ?: [];
$facilities = json_decode($lesson['facilities'] ?? '[]', true) ?: [];

/*
|--------------------------------------------------------------------------
| Build Word Document with PhpWord
|--------------------------------------------------------------------------
*/

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\TablePosition;
use PhpOffice\PhpWord\IOFactory;

$phpWord = new PhpWord();

// RTL document settings
$phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\ComplexType\ProofState());
$phpWord->addFontStyle('titleStyle',   ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => '1a3a6b', 'rtl' => true]);
$phpWord->addFontStyle('sectionStyle', ['name' => 'Arial', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true]);
$phpWord->addFontStyle('bodyStyle',    ['name' => 'Arial', 'size' => 11, 'rtl' => true]);
$phpWord->addFontStyle('boldStyle',    ['name' => 'Arial', 'size' => 11, 'bold' => true, 'rtl' => true]);
$phpWord->addFontStyle('evalStyle',    ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => 'b02a37', 'rtl' => true]);

$phpWord->addParagraphStyle('rtlPara',   ['alignment' => 'right', 'bidi' => true]);
$phpWord->addParagraphStyle('centerPara',['alignment' => 'center']);
$phpWord->addParagraphStyle('listPara',  ['alignment' => 'right', 'bidi' => true, 'indent' => 0.5]);

$section = $phpWord->addSection([
    'orientation' => 'portrait',
    'marginTop'    => 700,
    'marginBottom' => 700,
    'marginLeft'   => 800,
    'marginRight'  => 800,
    'bidi'         => true,
]);

// ---- Helper closures ----

$addHeader = function(string $text, string $bgColor = '1a3a6b') use ($section, $phpWord) {
    $table = $section->addTable(['bgColor' => $bgColor, 'borderColor' => $bgColor, 'borderSize' => 1, 'cellMargin' => 80, 'width' => 9000, 'unit' => TblWidth::TWIP]);
    $table->addRow();
    $cell = $table->addCell(9000, ['bgColor' => $bgColor]);
    $cell->addText($text, ['name' => 'Arial', 'size' => 12, 'bold' => true, 'color' => 'FFFFFF', 'rtl' => true], ['alignment' => 'right', 'bidi' => true]);
};

$addBody = function(string $text) use ($section) {
    if (empty(trim($text))) return;
    $para = $section->addTextRun(['alignment' => 'right', 'bidi' => true]);
    foreach (explode("\n", $text) as $line) {
        $para->addText(trim($line), ['name' => 'Arial', 'size' => 11, 'rtl' => true]);
        $para->addTextBreak();
    }
};

$addBulletList = function(array $items) use ($section) {
    foreach ($items as $item) {
        if (empty(trim($item))) continue;
        $section->addListItem($item, 0, ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'listPara');
    }
};

// ---- Title ----

$section->addText('التخطيط العميق للدروس الفائقة', 'titleStyle', 'centerPara');
$section->addTextBreak(1);

// ---- Info Table ----

$infoTable = $section->addTable(['borderColor' => '999999', 'borderSize' => 6, 'cellMargin' => 80, 'width' => 9000, 'unit' => TblWidth::TWIP]);

$rowData = [
    ['المعلم', $lesson['full_name'] ?? '', 'المادة', $lesson['course_name']],
    ['عنوان الدرس', $lesson['lesson_title'], 'الصف', $lesson['class_name']],
    ['الوحدة', $lesson['unit_name'], 'التاريخ', $lesson['lesson_date'] ?? ''],
    ['زمن الحصة', $lesson['lesson_duration'] . ' دقيقة', 'مستوى الطلبة', $lesson['student_level']],
    ['طريقة التدريس', $lesson['teaching_method'], 'التعزيز', $lesson['reinforcement'] ?? ''],
    ['الوسائل', implode('، ', $resources), 'التكنولوجيا', $lesson['technology'] ?? ''],
];

foreach ($rowData as $row) {
    $infoTable->addRow();
    $cell = $infoTable->addCell(2000, ['bgColor' => 'e8edf5']);
    $cell->addText($row[0], ['bold' => true, 'name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $cell = $infoTable->addCell(2500);
    $cell->addText($row[1], ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $cell = $infoTable->addCell(2000, ['bgColor' => 'e8edf5']);
    $cell->addText($row[2], ['bold' => true, 'name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $cell = $infoTable->addCell(2500);
    $cell->addText($row[3], ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
}

$section->addTextBreak(1);

// ---- Objectives ----

$addHeader('الأهداف السلوكية (مستوى التحليل أو التركيب أو التقويم)');
$objTable = $section->addTable(['borderColor' => 'cccccc', 'borderSize' => 4, 'cellMargin' => 80, 'width' => 9000, 'unit' => TblWidth::TWIP]);
$objTable->addRow();
$objTable->addCell(500)->addText('1', ['bold' => true, 'name' => 'Arial', 'size' => 11], 'rtlPara');
$objTable->addCell(8500)->addText($lesson['objective_1'] ?? '', ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$objTable->addRow();
$objTable->addCell(500)->addText('2', ['bold' => true, 'name' => 'Arial', 'size' => 11], 'rtlPara');
$objTable->addCell(8500)->addText($lesson['objective_2'] ?? '', ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$section->addTextBreak(1);

// ---- Skills ----

$addHeader('المهارات الأساسية اللازمة', '157347');
$skillTable = $section->addTable(['borderColor' => 'cccccc', 'borderSize' => 4, 'cellMargin' => 80, 'width' => 9000, 'unit' => TblWidth::TWIP]);
$skillTable->addRow();
$skillTable->addCell(500)->addText('1', ['bold' => true, 'name' => 'Arial', 'size' => 11], 'rtlPara');
$skillTable->addCell(8500)->addText($lesson['skill_1'] ?? '', ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$skillTable->addRow();
$skillTable->addCell(500)->addText('2', ['bold' => true, 'name' => 'Arial', 'size' => 11], 'rtlPara');
$skillTable->addCell(8500)->addText($lesson['skill_2'] ?? '', ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$section->addTextBreak(1);

// ---- Introduction ----

if (!empty($planData['introduction'])) {
    $addHeader('التمهيد', '157347');
    $addBody($planData['introduction']);
    $section->addTextBreak(1);
}

// ---- Goal 1 Procedures ----

$addHeader('إجراءات الهدف الأوّل (المدّة: 15 دقيقة)', '0d6efd');
$addBulletList($planData['goal_1_procedures'] ?? []);
$section->addTextBreak(1);

// ---- Goal 1 Evaluation ----

$addHeader('تقويم الهدف الأوّل (5 دقائق)', 'b02a37');
$section->addText('نص الوقفة التقويمية:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'color' => 'b02a37', 'rtl' => true], 'rtlPara');
$addBody($planData['goal_1_evaluation']['question'] ?? '');
if (!empty($planData['goal_1_evaluation']['model_answer'])) {
    $section->addText('الإجابة النموذجية:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
    $addBody($planData['goal_1_evaluation']['model_answer']);
}
$addHeader('التغذية الراجعة (5 دقائق)', '6c757d');
$addBody($planData['goal_1_feedback'] ?? 'سيعرض المعلم الإجابة النموذجية وتقويم ذاتي.');
$section->addTextBreak(1);

// ---- Goal 2 Procedures ----

$addHeader('إجراءات الهدف الثاني (المدّة: 15 دقيقة)', '6f42c1');
$addBulletList($planData['goal_2_procedures'] ?? []);
$section->addTextBreak(1);

// ---- Differentiation 6G6Y ----

$addHeader('سياسة التمايز 6G6Y');
$diffTable = $section->addTable(['borderColor' => '999999', 'borderSize' => 6, 'cellMargin' => 100, 'width' => 9000, 'unit' => TblWidth::TWIP]);
$diffTable->addRow();
$greenCell  = $diffTable->addCell(4500, ['bgColor' => 'd1e7dd', 'borderColor' => '157347', 'borderSize' => 12]);
$yellowCell = $diffTable->addCell(4500, ['bgColor' => 'fff3cd', 'borderColor' => 'e0a800', 'borderSize' => 12]);
$greenCell->addText('بطاقة التحدي (الورقة الخضراء)', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$greenCell->addText($planData['goal_2_differentiation']['green_card'] ?? $lesson['challenge_card'] ?? '', ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$yellowCell->addText('بطاقة المساعدة (الورقة الصفراء)', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$yellowCell->addText($planData['goal_2_differentiation']['yellow_card'] ?? $lesson['support_card'] ?? '', ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
$section->addTextBreak(1);

// ---- Goal 2 Evaluation ----

$addHeader('تقويم الهدف الثاني (5 دقائق)', 'b02a37');
$section->addText('نص الوقفة التقويمية:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'color' => 'b02a37', 'rtl' => true], 'rtlPara');
$addBody($planData['goal_2_evaluation']['question'] ?? '');
if (!empty($planData['goal_2_evaluation']['model_answer'])) {
    $section->addText('الإجابة النموذجية:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
    $addBody($planData['goal_2_evaluation']['model_answer']);
}
$addHeader('التغذية الراجعة بالأقران (5 دقائق)', '6c757d');
$addBody($planData['goal_2_feedback'] ?? 'تقويم بالأقران وتصحيح جماعي.');
$section->addTextBreak(1);

// ---- Conclusion ----

if (!empty($planData['conclusion'])) {
    $addHeader('الخاتمة (تنفّذ في حال اتّساع الوقت)', 'c35a00');
    $addBody($planData['conclusion']);
    $section->addTextBreak(1);
}

// ---- 21st Century Skills ----

if (!empty($planData['21st_century_skills'])) {
    $addHeader('مهارات القرن الحادي والعشرين', '0d6e6e');
    foreach ($planData['21st_century_skills'] as $skill) {
        $section->addListItem($skill, 0, ['name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
    }
    $section->addTextBreak(1);
}

// ---- Bahrain link + Homework ----

$addHeader('الربط بتراث البحرين والامتحانات الوطنية والإثراء', '495057');
if (!empty($lesson['bahrain_link'])) {
    $section->addText('الربط بتراث مملكة البحرين:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
    $addBody($lesson['bahrain_link']);
}
if (!empty($lesson['national_exams_link'])) {
    $section->addText('الربط بالامتحانات الوطنية:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
    $addBody($lesson['national_exams_link']);
}
if (!empty($lesson['homework'])) {
    $section->addText('الإثراء المنزلي:', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'rtlPara');
    $addBody($lesson['homework']);
}
$section->addTextBreak(1);

// ---- Student Categories ----

if (!empty($planData['student_categories'])) {
    $sc = $planData['student_categories'];
    $addHeader('الإجراءات مع فئات الطلبة المختلفة');
    $catTable = $section->addTable(['borderColor' => '999999', 'borderSize' => 4, 'cellMargin' => 80, 'width' => 9000, 'unit' => TblWidth::TWIP]);
    $catTable->addRow();
    foreach (['d1e7dd' => 'المتفوقون', 'cfe2ff' => 'الموهوبون', 'fff3cd' => 'التحصيل المنخفض'] as $bg => $label) {
        $catTable->addCell(3000, ['bgColor' => $bg])->addText($label, ['bold' => true, 'name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    }
    $catTable->addRow();
    $catTable->addCell(3000)->addText($sc['gifted'] ?? '-', ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $catTable->addCell(3000)->addText($sc['talented'] ?? '-', ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $catTable->addCell(3000)->addText($sc['low_achievers'] ?? '-', ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');

    $catTable->addRow();
    foreach (['الأمراض المزمنة', 'صعوبات التعلم', 'غير الناطقين بالعربية'] as $label) {
        $catTable->addCell(3000, ['bgColor' => 'f0f4ff'])->addText($label, ['bold' => true, 'name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    }
    $catTable->addRow();
    $catTable->addCell(3000)->addText($sc['chronic_illness'] ?? '-', ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $catTable->addCell(3000)->addText($sc['learning_difficulties'] ?? '-', ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');
    $catTable->addCell(3000)->addText($sc['non_arabic'] ?? '-', ['name' => 'Arial', 'size' => 10, 'rtl' => true], 'rtlPara');

    $section->addTextBreak(1);
}

// ---- Take My Hand ----

if (!empty($planData['take_my_hand'])) {
    $addHeader('مبادرة خذ بيدي', '0d6e6e');
    $addBody($planData['take_my_hand']);
    $section->addTextBreak(1);
}

// ---- Signature ----

$sigTable = $section->addTable(['borderColor' => '999999', 'borderSize' => 6, 'cellMargin' => 150, 'width' => 9000, 'unit' => TblWidth::TWIP]);
$sigTable->addRow(800);
$sigTable->addCell(3000)->addText('الأستاذ / رئيس القسم', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'centerPara');
$sigTable->addCell(3000)->addText('مدير المدرسة', ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'centerPara');
$sigTable->addCell(3000)->addText('التاريخ: ' . date('d/m/Y'), ['bold' => true, 'name' => 'Arial', 'size' => 11, 'rtl' => true], 'centerPara');
$sigTable->addRow(600);
$sigTable->addCell(3000)->addText(' ', ['name' => 'Arial', 'size' => 11], 'centerPara');
$sigTable->addCell(3000)->addText(' ', ['name' => 'Arial', 'size' => 11], 'centerPara');
$sigTable->addCell(3000)->addText(' ', ['name' => 'Arial', 'size' => 11], 'centerPara');

/*
|--------------------------------------------------------------------------
| Stream as .docx
|--------------------------------------------------------------------------
*/

$filename = 'تخطيط_عميق_' . preg_replace('/[^\w\x{0600}-\x{06FF}]/u', '_', $lesson['lesson_title']) . '_' . date('Ymd') . '.docx';

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($phpWord, 'Word2007');
$writer->save('php://output');
exit;
?>
