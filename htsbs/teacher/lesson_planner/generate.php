<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

require_once '../../config/config.php';
require_once '../../config/openai.php';

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/

require_once '../../app/models/LessonPlanner.php';

/*
|--------------------------------------------------------------------------
| AI Services
|--------------------------------------------------------------------------
*/

require_once '../../app/Services/AI/PromptBuilder.php';
require_once '../../app/Services/AI/OpenAIClient.php';
require_once '../../app/Services/AI/JsonValidator.php';
require_once '../../app/Services/AI/HtmlBuilder.php';

/*
|--------------------------------------------------------------------------
| Read Form
|--------------------------------------------------------------------------
*/

$data = [

    'teacher_id' => (int)$_SESSION['user_id'],

    'subject_id' => (int)($_POST['subject_id'] ?? 0),

    'class_id' => (int)($_POST['class_id'] ?? 0),

    'unit_name' => trim($_POST['unit_name'] ?? ''),

    'lesson_title' => trim($_POST['lesson_title'] ?? ''),

    'lesson_description' => trim($_POST['lesson_description'] ?? ''),

    'learning_outcomes' => trim($_POST['learning_outcomes'] ?? ''),

    'keywords' => trim($_POST['keywords'] ?? ''),

    'lesson_duration' => (int)($_POST['lesson_duration'] ?? 45),

    'resources' => $_POST['resources'] ?? [],

    'student_level' => trim($_POST['student_level'] ?? 'متوسط'),

    'additional_instructions' => trim(

        $_POST['additional_instructions'] ?? ''

    )

];

/*
|--------------------------------------------------------------------------
| Resources
|--------------------------------------------------------------------------
*/

if (is_array($data['resources'])) {

    $data['resources'] = array_filter(

        array_map(

            'trim',

            $data['resources']

        )

    );

}

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

$errors = [];

if ($data['subject_id'] <= 0)
    $errors[] = 'يرجى اختيار المادة.';

if ($data['class_id'] <= 0)
    $errors[] = 'يرجى اختيار الصف.';

if ($data['unit_name'] === '')
    $errors[] = 'يرجى إدخال اسم الوحدة.';

if ($data['lesson_title'] === '')
    $errors[] = 'يرجى إدخال عنوان الدرس.';

if (!empty($errors)) {

    $_SESSION['error'] = implode('<br>', $errors);

    $_SESSION['old'] = $_POST;

    header("Location:create.php");

    exit;

}

/*
|--------------------------------------------------------------------------
| Start Timer
|--------------------------------------------------------------------------
*/

$startedAt = microtime(true);

/*
|--------------------------------------------------------------------------
| Build Prompts
|--------------------------------------------------------------------------
*/

$promptBuilder = new PromptBuilder();

$prompts = $promptBuilder->build($data);

/*
|--------------------------------------------------------------------------
| OpenAI
|--------------------------------------------------------------------------
*/

$client = new OpenAIClient(

    OPENAI_API_KEY,

    OPENAI_MODEL

);

$response = $client->generate(

    $prompts['system'],

    $prompts['user']

);

/*
|--------------------------------------------------------------------------
| Validate JSON
|--------------------------------------------------------------------------
*/

$validator = new JsonValidator();

$lessonJson = $validator->validate(

    $response['text']

);

/*
|--------------------------------------------------------------------------
| Build Lesson
|--------------------------------------------------------------------------
*/

$htmlBuilder = new HtmlBuilder();

$result = $htmlBuilder->build(

    $lessonJson

);

/*
|--------------------------------------------------------------------------
| Complete Data
|--------------------------------------------------------------------------
*/

$data['resources'] = is_array($data['resources'])

    ? json_encode(

        $data['resources'],

        JSON_UNESCAPED_UNICODE

    )

    : $data['resources'];

$data['ai_prompt'] =

    $prompts['system']

    . "\n\n========================\n\n"

    . $prompts['user'];

$data['lesson_plan'] =

    $result['lesson_plan'];

$data['lesson_plan_json'] =

    $result['lesson_plan_json'];

$data['lesson_plan_html'] =

    $result['lesson_plan_html'];

$data['notes'] = '';

$data['version_no'] = 1;

$data['status'] = 'draft';

$data['is_favorite'] = 0;

$data['ai_model'] =

    OPENAI_MODEL;

$data['generation_time'] = round(

    microtime(true) - $startedAt,

    2

);

$data['tokens_used'] =

    $response['tokens_used'];

$data['exported_pdf'] = 0;

$data['exported_word'] = 0;

$data['printed_count'] = 0;

/*
|--------------------------------------------------------------------------
| Save Lesson
|--------------------------------------------------------------------------
*/

$model = new LessonPlanner();

try {

    $lessonId = $model->create(

        $data

    );

require_once '../../core/Logger.php';

Logger::log(
    'lesson_planner',
    'generate_plan',
    "إنشاء تحضير درس جديد (id=$lessonId)"
    . (!empty($data['tokens_used']) ? " - tokens: {$data['tokens_used']}" : ''),
    'lesson_plan',
    $lessonId,
    'info'
);

    $_SESSION['success'] =

        'تم إنشاء التحضير بنجاح.';

    unset(

        $_SESSION['old']

    );

    header(

        "Location: view.php?id="

        .

        $lessonId

    );

    exit;

} catch (Throwable $e) {

    error_log(

        '[Lesson Planner] '

        .

        $e->getMessage()

    );

    $_SESSION['old'] = $_POST;

    $_SESSION['error'] =

        "حدث خطأ أثناء حفظ التحضير.<br><br>"

        .

        htmlspecialchars(

            $e->getMessage(),

            ENT_QUOTES,

            'UTF-8'

        );

    header(

        "Location: create.php"

    );

    exit;

}