<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/DeepLessonPlanner.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ($_SESSION['role_id'] != 2) {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: index.php");
    exit;
}

$db = (new Database())->connect();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) die("رقم التخطيط غير صحيح.");

/*
==================================================
جلب التخطيط الحالي
==================================================
*/

$stmt = $db->prepare("SELECT * FROM deep_lesson_plans WHERE id = ? AND teacher_id = ? LIMIT 1");
$stmt->execute([$id, $_SESSION['user_id']]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) die("التخطيط غير موجود.");

/*
==================================================
إعادة بناء JSON من البيانات المعدّلة
==================================================
*/

$existingJson = [];
if (!empty($lesson['lesson_plan_json'])) {
    $existingJson = json_decode($lesson['lesson_plan_json'], true) ?: [];
}

// Parse multi-line procedure fields
function parseLines(string $text): array {
    return array_values(array_filter(array_map('trim', explode("\n", $text))));
}

$updatedJson = array_merge($existingJson, [
    'introduction'       => trim($_POST['introduction'] ?? ''),
    'goal_1_procedures'  => parseLines($_POST['goal_1_procedures'] ?? ''),
    'goal_1_evaluation'  => [
        'question'     => trim($_POST['goal_1_eval_question'] ?? ''),
        'model_answer' => trim($_POST['goal_1_eval_answer'] ?? ''),
    ],
    'goal_1_feedback'    => $existingJson['goal_1_feedback'] ?? '',
    'goal_2_procedures'  => parseLines($_POST['goal_2_procedures'] ?? ''),
    'goal_2_differentiation' => [
        'green_card'  => trim($_POST['green_card'] ?? ''),
        'yellow_card' => trim($_POST['yellow_card'] ?? ''),
    ],
    'goal_2_evaluation'  => [
        'question'     => trim($_POST['goal_2_eval_question'] ?? ''),
        'model_answer' => trim($_POST['goal_2_eval_answer'] ?? ''),
    ],
    'goal_2_feedback'    => $existingJson['goal_2_feedback'] ?? '',
    'conclusion'         => trim($_POST['conclusion'] ?? ''),
]);

/*
==================================================
إعادة بناء HTML
==================================================
*/

function buildDeepLessonHtml(array $p, array $d): string
{
    $h  = '<div class="deep-lesson-plan" dir="rtl">';

    $h .= '<table class="table table-bordered table-sm mb-4 lesson-header-table">';
    $h .= '<tr><th>عنوان الدرس</th><td>' . htmlspecialchars($d['lesson_title']) . '</td><th>الوحدة</th><td>' . htmlspecialchars($d['unit_name']) . '</td></tr>';
    $h .= '<tr><th>الهدف الأول</th><td colspan="3">' . htmlspecialchars($d['objective_1'] ?? '') . '</td></tr>';
    $h .= '<tr><th>الهدف الثاني</th><td colspan="3">' . htmlspecialchars($d['objective_2'] ?? '') . '</td></tr>';
    $h .= '</table>';

    if (!empty($p['introduction'])) {
        $h .= '<div class="section-box section-intro"><div class="section-title"><i class="bi bi-play-circle-fill"></i> التمهيد</div><div class="section-body">' . nl2br(htmlspecialchars($p['introduction'])) . '</div></div>';
    }

    if (!empty($p['goal_1_procedures'])) {
        $h .= '<div class="section-box section-goal1"><div class="section-title"><i class="bi bi-1-circle-fill"></i> إجراءات الهدف الأول (15 دقيقة)</div><div class="section-body"><ul>';
        foreach ($p['goal_1_procedures'] as $item) {
            $h .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $h .= '</ul></div></div>';
    }

    if (!empty($p['goal_1_evaluation'])) {
        $h .= '<div class="section-box section-eval"><div class="section-title"><i class="bi bi-pencil-square"></i> تقويم الهدف الأول (5 دقائق)</div>';
        $h .= '<div class="section-body"><p><strong>نص الوقفة التقويمية:</strong><br>' . nl2br(htmlspecialchars($p['goal_1_evaluation']['question'] ?? '')) . '</p>';
        $h .= '<p><strong>الإجابة النموذجية:</strong><br>' . nl2br(htmlspecialchars($p['goal_1_evaluation']['model_answer'] ?? '')) . '</p></div>';
        if (!empty($p['goal_1_feedback'])) {
            $h .= '<div class="section-title-sub"><i class="bi bi-arrow-repeat"></i> التغذية الراجعة (5 دقائق)</div><div class="section-body">' . nl2br(htmlspecialchars($p['goal_1_feedback'])) . '</div>';
        }
        $h .= '</div>';
    }

    if (!empty($p['goal_2_procedures'])) {
        $h .= '<div class="section-box section-goal2"><div class="section-title"><i class="bi bi-2-circle-fill"></i> إجراءات الهدف الثاني (15 دقيقة)</div><div class="section-body"><ul>';
        foreach ($p['goal_2_procedures'] as $item) {
            $h .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $h .= '</ul>';
        if (!empty($p['goal_2_differentiation'])) {
            $h .= '<div class="diff-section mt-3"><h6 class="text-primary">سياسة التمايز 6G6Y</h6><div class="row">';
            $h .= '<div class="col-md-6"><div class="diff-card diff-green"><strong>🟩 بطاقة التحدي (الخضراء)</strong><br>' . nl2br(htmlspecialchars($p['goal_2_differentiation']['green_card'] ?? '')) . '</div></div>';
            $h .= '<div class="col-md-6"><div class="diff-card diff-yellow"><strong>🟨 بطاقة المساعدة (الصفراء)</strong><br>' . nl2br(htmlspecialchars($p['goal_2_differentiation']['yellow_card'] ?? '')) . '</div></div>';
            $h .= '</div></div>';
        }
        $h .= '</div></div>';
    }

    if (!empty($p['goal_2_evaluation'])) {
        $h .= '<div class="section-box section-eval"><div class="section-title"><i class="bi bi-pencil-square"></i> تقويم الهدف الثاني (5 دقائق)</div>';
        $h .= '<div class="section-body"><p><strong>نص الوقفة التقويمية:</strong><br>' . nl2br(htmlspecialchars($p['goal_2_evaluation']['question'] ?? '')) . '</p>';
        $h .= '<p><strong>الإجابة النموذجية:</strong><br>' . nl2br(htmlspecialchars($p['goal_2_evaluation']['model_answer'] ?? '')) . '</p></div>';
        if (!empty($p['goal_2_feedback'])) {
            $h .= '<div class="section-title-sub"><i class="bi bi-arrow-repeat"></i> التغذية الراجعة بالأقران (5 دقائق)</div><div class="section-body">' . nl2br(htmlspecialchars($p['goal_2_feedback'])) . '</div>';
        }
        $h .= '</div>';
    }

    if (!empty($p['conclusion'])) {
        $h .= '<div class="section-box section-conclusion"><div class="section-title"><i class="bi bi-flag-fill"></i> الخاتمة (إن اتسع الوقت)</div><div class="section-body">' . nl2br(htmlspecialchars($p['conclusion'])) . '</div></div>';
    }

    // Student categories (from existing json)
    if (!empty($p['student_categories'])) {
        $sc = $p['student_categories'];
        $h .= '<div class="section-box section-diff"><div class="section-title"><i class="bi bi-people-fill"></i> فئات الطلبة</div><div class="section-body">';
        $h .= '<table class="table table-bordered table-sm">';
        $h .= '<tr><th class="bg-success text-white">المتفوقون</th><th class="bg-info text-white">الموهوبون</th><th class="bg-warning text-dark">التحصيل المنخفض</th></tr>';
        $h .= '<tr><td>' . nl2br(htmlspecialchars($sc['gifted'] ?? '-')) . '</td><td>' . nl2br(htmlspecialchars($sc['talented'] ?? '-')) . '</td><td>' . nl2br(htmlspecialchars($sc['low_achievers'] ?? '-')) . '</td></tr>';
        $h .= '</table>';
        if (!empty($p['take_my_hand'])) {
            $h .= '<p><strong>مبادرة خذ بيدي:</strong> ' . htmlspecialchars($p['take_my_hand']) . '</p>';
        }
        $h .= '</div></div>';
    }

    $h .= '</div>';
    return $h;
}

$updatedHtml = buildDeepLessonHtml($updatedJson, [
    'lesson_title' => trim($_POST['lesson_title'] ?? $lesson['lesson_title']),
    'unit_name'    => trim($_POST['unit_name'] ?? $lesson['unit_name']),
    'objective_1'  => trim($_POST['objective_1'] ?? $lesson['objective_1'] ?? ''),
    'objective_2'  => trim($_POST['objective_2'] ?? $lesson['objective_2'] ?? ''),
]);

/*
==================================================
تحديث قاعدة البيانات
==================================================
*/

$stmt = $db->prepare("
UPDATE deep_lesson_plans SET
    subject_id          = ?,
    class_id            = ?,
    unit_name           = ?,
    lesson_title        = ?,
    lesson_date         = ?,
    lesson_duration     = ?,
    student_level       = ?,
    objective_1         = ?,
    objective_2         = ?,
    skill_1             = ?,
    skill_2             = ?,
    teaching_method     = ?,
    reinforcement       = ?,
    technology          = ?,
    homework            = ?,
    national_exams_link = ?,
    bahrain_link        = ?,
    challenge_card      = ?,
    support_card        = ?,
    status              = ?,
    lesson_plan_json    = ?,
    lesson_plan_html    = ?,
    version_no          = version_no + 1,
    updated_at          = NOW()
WHERE id = ?
AND teacher_id = ?
");

$stmt->execute([
    (int)$_POST['subject_id'],
    (int)$_POST['class_id'],
    trim($_POST['unit_name'] ?? ''),
    trim($_POST['lesson_title'] ?? ''),
    trim($_POST['lesson_date'] ?? date('Y-m-d')),
    (int)($_POST['lesson_duration'] ?? 45),
    trim($_POST['student_level'] ?? 'متوسط'),
    trim($_POST['objective_1'] ?? ''),
    trim($_POST['objective_2'] ?? ''),
    trim($_POST['skill_1'] ?? ''),
    trim($_POST['skill_2'] ?? ''),
    trim($_POST['teaching_method'] ?? ''),
    trim($_POST['reinforcement'] ?? ''),
    trim($_POST['technology'] ?? ''),
    trim($_POST['homework'] ?? ''),
    trim($_POST['national_exams_link'] ?? ''),
    trim($_POST['bahrain_link'] ?? ''),
    trim($_POST['green_card'] ?? ''),
    trim($_POST['yellow_card'] ?? ''),
    trim($_POST['status'] ?? 'draft'),
    json_encode($updatedJson, JSON_UNESCAPED_UNICODE),
    $updatedHtml,
    $id,
    $_SESSION['user_id'],
]);

$_SESSION['success'] = 'تم تحديث التخطيط بنجاح.';

header("Location: view.php?id=" . $id);
exit;
?>
