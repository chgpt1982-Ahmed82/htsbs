<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/openai.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ((int)$_SESSION['role_id'] !== 2) {
    die("Access Denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: create.php");
    exit;
}

require_once '../../app/models/DeepLessonPlanner.php';
require_once '../../app/Services/AI/OpenAIClient.php';

/*
|--------------------------------------------------------------------------
| Read Form Data
|--------------------------------------------------------------------------
*/

$data = [
    'teacher_id'          => (int)$_SESSION['user_id'],
    'subject_id'          => (int)($_POST['subject_id'] ?? 0),
    'class_id'            => (int)($_POST['class_id'] ?? 0),
    'unit_name'           => trim($_POST['unit_name'] ?? ''),
    'lesson_title'        => trim($_POST['lesson_title'] ?? ''),
    'lesson_date'         => trim($_POST['lesson_date'] ?? date('Y-m-d')),
    'lesson_duration'     => (int)($_POST['lesson_duration'] ?? 45),
    'student_level'       => trim($_POST['student_level'] ?? 'متوسط'),
    'objective_1'         => trim($_POST['objective_1'] ?? ''),
    'objective_2'         => trim($_POST['objective_2'] ?? ''),
    'skill_1'             => trim($_POST['skill_1'] ?? ''),
    'skill_2'             => trim($_POST['skill_2'] ?? ''),
    'teaching_method'     => trim($_POST['teaching_method'] ?? ''),
    'reinforcement'       => trim($_POST['reinforcement'] ?? ''),
    'technology'          => trim($_POST['technology'] ?? ''),
    'resources'           => $_POST['resources'] ?? [],
    'facilities'          => $_POST['facilities'] ?? [],
    'lesson_description'  => trim($_POST['lesson_description'] ?? ''),
    'learning_outcomes'   => trim($_POST['learning_outcomes'] ?? ''),
    'keywords'            => trim($_POST['keywords'] ?? ''),
    'challenge_card'      => trim($_POST['challenge_card'] ?? ''),
    'support_card'        => trim($_POST['support_card'] ?? ''),
    'national_exams_link' => trim($_POST['national_exams_link'] ?? ''),
    'homework'            => trim($_POST['homework'] ?? ''),
    'bahrain_link'        => trim($_POST['bahrain_link'] ?? ''),
    'ai_prompt'           => trim($_POST['ai_prompt'] ?? ''),
    'generator'           => trim($_POST['generator'] ?? 'ai'),
    'status'              => trim($_POST['status'] ?? 'draft'),
];

$data['resources']  = array_filter(array_map('trim', (array)$data['resources']));
$data['facilities'] = array_filter(array_map('trim', (array)$data['facilities']));

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

$errors = [];
if ($data['subject_id'] <= 0) $errors[] = 'يرجى اختيار المادة.';
if ($data['class_id'] <= 0)   $errors[] = 'يرجى اختيار الصف.';
if ($data['unit_name'] === '') $errors[] = 'يرجى إدخال اسم الوحدة.';
if ($data['lesson_title'] === '') $errors[] = 'يرجى إدخال عنوان الدرس.';
if ($data['objective_1'] === '') $errors[] = 'يرجى إدخال الهدف الأول.';

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    $_SESSION['old']   = $_POST;
    header("Location: create.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Build AI Prompt for Deep Lesson Plan
|--------------------------------------------------------------------------
*/

$resourcesList  = implode('، ', $data['resources']);
$facilitiesList = implode('، ', $data['facilities']);
$duration       = $data['lesson_duration'];
$obj1Time       = 15;
$obj2Time       = 15;
$evalTime       = 5;
$feedbackTime   = 5;

$systemPrompt = "أنت خبير تربوي متخصص في بناء تخطيطات الدروس العميقة وفق نموذج التخطيط العميق للدروس الفائقة المعتمد في مملكة البحرين.
يجب أن يتضمن التخطيط:
1. أهداف سلوكية في مستويات التحليل أو التركيب أو التقويم (تاكسونوميا بلوم).
2. تمهيد واضح يحدد فيه المعلم معايير المراقبة السلوكية للطلبة.
3. إجراءات الهدف الأول ({$obj1Time} دقيقة): سلسلة من الأنشطة التكوينية التدرجية.
4. وقفة تقويمية مكتوبة للهدف الأول ({$evalTime} دقائق): سياق مختلف عن الأنشطة التكوينية.
5. تغذية راجعة للهدف الأول ({$feedbackTime} دقائق): عرض الإجابة النموذجية وتصحيح ذاتي.
6. إجراءات الهدف الثاني ({$obj2Time} دقيقة) مع سياسة التمايز 6G6Y: بطاقات خضراء للمتفوقين وصفراء للمتعثرين.
7. وقفة تقويمية مكتوبة للهدف الثاني ({$evalTime} دقائق).
8. تغذية راجعة للهدف الثاني ({$feedbackTime} دقائق): بالأقران.
9. الخاتمة والتطبيق الواقعي (إن اتسع الوقت).
10. مهارات القرن الحادي والعشرين ومؤشرات الانهماك الطلابي.
11. مهارات التعلم المتضمنة.
12. الربط بتراث مملكة البحرين والمواطنة.
13. الإجراءات مع فئات الطلبة المختلفة (متفوقون، موهوبون، متدنو التحصيل، ذوو أمراض مزمنة، صعوبات التعلم، ذوو إعاقات، غير ناطقين بالعربية).
14. مبادرة 'خذ بيدي' (ربط الطلبة المتميزين بالمتدنيين).

أعد الرد بصيغة JSON فقط، بدون أي مقدمة أو إغلاق أو علامات markdown.";

$userPrompt = "المادة: {$data['lesson_title']}
الوحدة: {$data['unit_name']}
الهدف الأول: {$data['objective_1']}
الهدف الثاني: {$data['objective_2']}
المحتوى: {$data['lesson_description']}
نواتج التعلم: {$data['learning_outcomes']}
مستوى الطلبة: {$data['student_level']}
زمن الحصة: {$duration} دقيقة
طريقة التدريس: {$data['teaching_method']}
الوسائل: {$resourcesList}
بطاقة التحدي الخضراء: {$data['challenge_card']}
بطاقة المساعدة الصفراء: {$data['support_card']}
الإثراء المنزلي: {$data['homework']}
الربط بالبحرين: {$data['bahrain_link']}
الربط بالامتحانات: {$data['national_exams_link']}
{$data['ai_prompt']}

أعد JSON بالهيكل التالي:
{
  \"introduction\": \"\",
  \"goal_1_procedures\": [],
  \"goal_1_evaluation\": { \"question\": \"\", \"model_answer\": \"\" },
  \"goal_1_feedback\": \"\",
  \"goal_2_procedures\": [],
  \"goal_2_differentiation\": { \"green_card\": \"\", \"yellow_card\": \"\" },
  \"goal_2_evaluation\": { \"question\": \"\", \"model_answer\": \"\" },
  \"goal_2_feedback\": \"\",
  \"conclusion\": \"\",
  \"21st_century_skills\": [],
  \"engagement_indicators\": [],
  \"learning_skills\": [],
  \"bahrain_heritage\": { \"local_citizenship\": \"\", \"global_citizenship\": \"\" },
  \"student_categories\": {
    \"gifted\": \"\", \"talented\": \"\", \"low_achievers\": \"\",
    \"chronic_illness\": \"\", \"learning_difficulties\": \"\",
    \"disabilities\": \"\", \"non_arabic\": \"\"
  },
  \"take_my_hand\": \"\"
}";

/*
|--------------------------------------------------------------------------
| Generate or Manual
|--------------------------------------------------------------------------
*/

$startedAt = microtime(true);
$lessonPlanJson = '';
$lessonPlanHtml = '';
$lessonPlanText = '';
$tokensUsed = 0;

if ($data['generator'] === 'ai') {

    try {

        $client = new OpenAIClient(OPENAI_API_KEY, OPENAI_MODEL);

        $response = $client->generate($systemPrompt, $userPrompt);

        $rawJson = $response['text'];

        // Strip markdown fences if present
        $rawJson = preg_replace('/^```json\s*/i', '', trim($rawJson));
        $rawJson = preg_replace('/```\s*$/i', '', $rawJson);

        $lessonPlanJson = $rawJson;

        $tokensUsed = $response['tokens_used'] ?? 0;

        // Build HTML from JSON
        $planData = json_decode($rawJson, true);

        if (!is_array($planData)) {
            $planData = [];
        }

        $lessonPlanHtml = buildDeepLessonHtml($planData, $data);
        $lessonPlanText = buildDeepLessonText($planData, $data);

    } catch (Throwable $e) {

        $_SESSION['error'] = 'خطأ في الذكاء الاصطناعي: ' . htmlspecialchars($e->getMessage());
        $_SESSION['old']   = $_POST;
        header("Location: create.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Helper: Build HTML
|--------------------------------------------------------------------------
*/

function buildDeepLessonHtml(array $p, array $d): string
{
    $h  = '<div class="deep-lesson-plan" dir="rtl">';

    // Header table
    $h .= '<table class="table table-bordered table-sm mb-4 lesson-header-table">';
    $h .= '<tr><th>المادة</th><td>' . htmlspecialchars($d['lesson_title']) . '</td><th>الوحدة</th><td>' . htmlspecialchars($d['unit_name']) . '</td></tr>';
    $h .= '<tr><th>الهدف الأول</th><td colspan="3">' . htmlspecialchars($d['objective_1']) . '</td></tr>';
    $h .= '<tr><th>الهدف الثاني</th><td colspan="3">' . htmlspecialchars($d['objective_2']) . '</td></tr>';
    $h .= '<tr><th>طريقة التدريس</th><td>' . htmlspecialchars($d['teaching_method']) . '</td><th>زمن الحصة</th><td>' . htmlspecialchars((string)$d['lesson_duration']) . ' دقيقة</td></tr>';
    $h .= '</table>';

    // Introduction
    if (!empty($p['introduction'])) {
        $h .= '<div class="section-box section-intro"><div class="section-title"><i class="bi bi-play-circle-fill"></i> التمهيد</div><div class="section-body">' . nl2br(htmlspecialchars($p['introduction'])) . '</div></div>';
    }

    // Goal 1 Procedures
    if (!empty($p['goal_1_procedures'])) {
        $h .= '<div class="section-box section-goal1"><div class="section-title"><i class="bi bi-1-circle-fill"></i> إجراءات الهدف الأول (المدة: 15 دقيقة)</div><div class="section-body"><ul>';
        foreach ($p['goal_1_procedures'] as $item) {
            $h .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $h .= '</ul></div></div>';
    }

    // Goal 1 Evaluation
    if (!empty($p['goal_1_evaluation'])) {
        $h .= '<div class="section-box section-eval"><div class="section-title"><i class="bi bi-pencil-square"></i> تقويم الهدف الأول (المدة: 5 دقائق)</div>';
        $h .= '<div class="section-body"><p><strong>نص الوقفة التقويمية:</strong><br>' . nl2br(htmlspecialchars($p['goal_1_evaluation']['question'] ?? '')) . '</p>';
        $h .= '<p><strong>الإجابة النموذجية:</strong><br>' . nl2br(htmlspecialchars($p['goal_1_evaluation']['model_answer'] ?? '')) . '</p></div>';
        if (!empty($p['goal_1_feedback'])) {
            $h .= '<div class="section-title-sub"><i class="bi bi-arrow-repeat"></i> التغذية الراجعة (المدة: 5 دقائق)</div><div class="section-body">' . nl2br(htmlspecialchars($p['goal_1_feedback'])) . '</div>';
        }
        $h .= '</div>';
    }

    // Goal 2 Procedures
    if (!empty($p['goal_2_procedures'])) {
        $h .= '<div class="section-box section-goal2"><div class="section-title"><i class="bi bi-2-circle-fill"></i> إجراءات الهدف الثاني (المدة: 15 دقيقة)</div><div class="section-body"><ul>';
        foreach ($p['goal_2_procedures'] as $item) {
            $h .= '<li>' . htmlspecialchars($item) . '</li>';
        }
        $h .= '</ul>';
        // Differentiation 6G6Y
        if (!empty($p['goal_2_differentiation'])) {
            $h .= '<div class="diff-section mt-3"><h6 class="text-primary">سياسة التمايز 6G6Y</h6><div class="row"><div class="col-md-6"><div class="diff-card diff-green"><strong>🟩 بطاقة التحدي (الورقة الخضراء)</strong><br>' . nl2br(htmlspecialchars($p['goal_2_differentiation']['green_card'] ?? '')) . '</div></div><div class="col-md-6"><div class="diff-card diff-yellow"><strong>🟨 بطاقة المساعدة (الورقة الصفراء)</strong><br>' . nl2br(htmlspecialchars($p['goal_2_differentiation']['yellow_card'] ?? '')) . '</div></div></div></div>';
        }
        $h .= '</div></div>';
    }

    // Goal 2 Evaluation
    if (!empty($p['goal_2_evaluation'])) {
        $h .= '<div class="section-box section-eval"><div class="section-title"><i class="bi bi-pencil-square"></i> تقويم الهدف الثاني (المدة: 5 دقائق)</div>';
        $h .= '<div class="section-body"><p><strong>نص الوقفة التقويمية:</strong><br>' . nl2br(htmlspecialchars($p['goal_2_evaluation']['question'] ?? '')) . '</p>';
        $h .= '<p><strong>الإجابة النموذجية:</strong><br>' . nl2br(htmlspecialchars($p['goal_2_evaluation']['model_answer'] ?? '')) . '</p></div>';
        if (!empty($p['goal_2_feedback'])) {
            $h .= '<div class="section-title-sub"><i class="bi bi-arrow-repeat"></i> التغذية الراجعة (بالأقران - 5 دقائق)</div><div class="section-body">' . nl2br(htmlspecialchars($p['goal_2_feedback'])) . '</div>';
        }
        $h .= '</div>';
    }

    // Conclusion
    if (!empty($p['conclusion'])) {
        $h .= '<div class="section-box section-conclusion"><div class="section-title"><i class="bi bi-flag-fill"></i> الخاتمة (تنفّذ في حال اتساع الوقت)</div><div class="section-body">' . nl2br(htmlspecialchars($p['conclusion'])) . '</div></div>';
    }

    // 21st Century Skills
    if (!empty($p['21st_century_skills'])) {
        $h .= '<div class="section-box section-skills"><div class="section-title"><i class="bi bi-stars"></i> مهارات القرن الحادي والعشرين</div><div class="section-body"><div class="row">';
        foreach ($p['21st_century_skills'] as $skill) {
            $h .= '<div class="col-md-6"><div class="form-check"><input type="checkbox" class="form-check-input" checked disabled><label class="form-check-label">' . htmlspecialchars($skill) . '</label></div></div>';
        }
        $h .= '</div></div></div>';
    }

    // Student Categories
    if (!empty($p['student_categories'])) {
        $sc = $p['student_categories'];
        $h .= '<div class="section-box section-diff"><div class="section-title"><i class="bi bi-people-fill"></i> الإجراءات مع فئات الطلبة المختلفة</div><div class="section-body">';
        $h .= '<table class="table table-bordered table-sm">';
        $h .= '<tr><th class="bg-success text-white">المتفوقون</th><th class="bg-info text-white">الموهوبون</th><th class="bg-warning text-dark">ذوو التحصيل المنخفض</th></tr>';
        $h .= '<tr><td>' . nl2br(htmlspecialchars($sc['gifted'] ?? '-')) . '</td><td>' . nl2br(htmlspecialchars($sc['talented'] ?? '-')) . '</td><td>' . nl2br(htmlspecialchars($sc['low_achievers'] ?? '-')) . '</td></tr>';
        $h .= '<tr><th>ذوو الأمراض المزمنة</th><th>صعوبات التعلم</th><th>غير الناطقين بالعربية</th></tr>';
        $h .= '<tr><td>' . nl2br(htmlspecialchars($sc['chronic_illness'] ?? '-')) . '</td><td>' . nl2br(htmlspecialchars($sc['learning_difficulties'] ?? '-')) . '</td><td>' . nl2br(htmlspecialchars($sc['non_arabic'] ?? '-')) . '</td></tr>';
        $h .= '</table>';
        if (!empty($p['take_my_hand'])) {
            $h .= '<p class="mt-2"><strong>مبادرة خذ بيدي:</strong> ' . htmlspecialchars($p['take_my_hand']) . '</p>';
        }
        $h .= '</div></div>';
    }

    $h .= '</div>'; // end deep-lesson-plan

    return $h;
}

function buildDeepLessonText(array $p, array $d): string
{
    $t = "التخطيط العميق للدروس الفائقة\n";
    $t .= "================================\n";
    $t .= "المادة: {$d['lesson_title']} | الوحدة: {$d['unit_name']}\n";
    $t .= "الهدف الأول: {$d['objective_1']}\n";
    $t .= "الهدف الثاني: {$d['objective_2']}\n\n";

    if (!empty($p['introduction'])) {
        $t .= "التمهيد:\n{$p['introduction']}\n\n";
    }

    if (!empty($p['goal_1_procedures'])) {
        $t .= "إجراءات الهدف الأول:\n";
        foreach ($p['goal_1_procedures'] as $item) {
            $t .= "- {$item}\n";
        }
        $t .= "\n";
    }

    if (!empty($p['goal_1_evaluation'])) {
        $t .= "الوقفة التقويمية الأولى:\n{$p['goal_1_evaluation']['question']}\n\n";
    }

    if (!empty($p['goal_2_procedures'])) {
        $t .= "إجراءات الهدف الثاني:\n";
        foreach ($p['goal_2_procedures'] as $item) {
            $t .= "- {$item}\n";
        }
        $t .= "\n";
    }

    if (!empty($p['goal_2_evaluation'])) {
        $t .= "الوقفة التقويمية الثانية:\n{$p['goal_2_evaluation']['question']}\n\n";
    }

    return $t;
}

/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

$data['resources']  = json_encode($data['resources'],  JSON_UNESCAPED_UNICODE);
$data['facilities'] = json_encode($data['facilities'],  JSON_UNESCAPED_UNICODE);

$data['lesson_plan_json'] = $lessonPlanJson;
$data['lesson_plan_html'] = $lessonPlanHtml;
$data['lesson_plan']      = $lessonPlanText;
$data['version_no']       = 1;
$data['is_favorite']      = 0;
$data['ai_model']         = OPENAI_MODEL ?? 'gpt-5.5';
$data['generation_time']  = round(microtime(true) - $startedAt, 2);
$data['tokens_used']      = $tokensUsed;
$data['exported_pdf']     = 0;
$data['exported_word']    = 0;
$data['printed_count']    = 0;

$model = new DeepLessonPlanner();

try {

    $lessonId = $model->create($data);

    $_SESSION['success'] = 'تم إنشاء التخطيط العميق بنجاح.';

    unset($_SESSION['old']);

    header("Location: view.php?id=" . $lessonId);
    exit;

} catch (Throwable $e) {

    error_log('[Deep Lesson Planner] ' . $e->getMessage());

    $_SESSION['old']   = $_POST;
    $_SESSION['error'] = 'حدث خطأ أثناء الحفظ: ' . htmlspecialchars($e->getMessage());

    header("Location: create.php");
    exit;
}
?>
