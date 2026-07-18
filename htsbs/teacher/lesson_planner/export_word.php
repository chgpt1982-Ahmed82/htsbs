<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();


require_once '../../app/Documents/Word/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

require_once '../../config/config.php';
require_once '../../config/database.php';

/*
|--------------------------------------------------------------------------
| Composer
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Models
|--------------------------------------------------------------------------
*/

require_once '../../app/models/LessonPlanner.php';

/*
|--------------------------------------------------------------------------
| Word Engine
|-------------------------------------------------------------------
require_once '../../app/Documents/Word/WordConfig.php';
require_once '../../app/Documents/Word/WordStyle.php';
require_once '../../app/Documents/Word/WordHeader.php';
require_once '../../app/Documents/Word/WordFooter.php';
require_once '../../app/Documents/Word/WordLessonBuilder.php';
require_once '../../app/Documents/Word/WordExporter.php';
-------
*/



/*
|--------------------------------------------------------------------------
| Components
|-------------------------------------------------------------------

require_once '../../app/Documents/Word/Components/SectionCard.php';
-------
*/


/*
|--------------------------------------------------------------------------
| Sections
|--------------------------------------------------------------------------
*/

require_once '../../app/Documents/Word/Sections/ObjectivesSection.php';
require_once '../../app/Documents/Word/Sections/WarmupSection.php';
require_once '../../app/Documents/Word/Sections/IntroductionSection.php';
require_once '../../app/Documents/Word/Sections/ActivitiesSection.php';

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ' . BASE_URL . '/login.php'
    );

    exit;

}

if ((int)$_SESSION['role_id'] !== 2) {

    exit('Access Denied');

}

/*
|--------------------------------------------------------------------------
| Lesson ID
|--------------------------------------------------------------------------
*/

$lessonId = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$lessonId) {

    exit('رقم التحضير غير صحيح.');

}

/*
|--------------------------------------------------------------------------
| Load Lesson
|--------------------------------------------------------------------------
*/

$model = new LessonPlanner();

$lesson = $model->getLessonForExport(

    $lessonId

);

if (empty($lesson)) {

    exit('التحضير غير موجود.');

}

/*
|--------------------------------------------------------------------------
| Security
|--------------------------------------------------------------------------
*/

if (

    (int)$lesson['teacher_id']

    !==

    (int)$_SESSION['user_id']

) {

    exit('ليس لديك صلاحية تصدير هذا التحضير.');

}


/*
|--------------------------------------------------------------------------
| Increase Export Counter
|--------------------------------------------------------------------------
*/

$model->increaseWordExport($lessonId);

require_once __DIR__ . '/../../core/Logger.php';

Logger::log(
    'lesson_planner',
    'export_word',
    "تصدير Word لتحضير (id=$lessonId)",
    'lesson_plan',
    $lessonId,
    'info'
);

/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$lesson['school_name'] =

    $lesson['school_name']

    ??

    'مدارس البحرين';

$lesson['ministry_logo'] =

    $lesson['ministry_logo']

    ??

    '';

$lesson['school_logo'] =

    $lesson['school_logo']

    ??

    '';

$lesson['teacher_name'] =    $_SESSION['full_name']    ??$_SESSION['user_name']    ??    $_SESSION['name']    ??    '';

$lesson['export_date'] =

    date('Y-m-d');

$lesson['export_time'] =

    date('H:i');

/*
|--------------------------------------------------------------------------
| File Name
|--------------------------------------------------------------------------
*/

$fileName = trim(

    $lesson['lesson_title']

);

if ($fileName === '') {

    $fileName =

        'Lesson_Plan_' . $lessonId;

}

$fileName = preg_replace(

    '/[^\p{Arabic}\p{L}\p{N}\-_ ]+/u',

    '',

    $fileName

);

$fileName = str_replace(

    ' ',

    '_',

    $fileName

);

/*
|--------------------------------------------------------------------------
| Word Exporter
|--------------------------------------------------------------------------
*/

$exporter = new WordExporter();

/*
|--------------------------------------------------------------------------
| Export Data
|--------------------------------------------------------------------------
*/

$lesson['document_title'] =

    'تحضير درس';

$lesson['system_name'] =

    'Learning Management System';

$lesson['generated_by'] =

    'AI Lesson Planner';

$lesson['document_type'] =

    'lesson_plan';

/*
|--------------------------------------------------------------------------
| Optional Images
|--------------------------------------------------------------------------
*/

if (

    !isset($lesson['school_logo'])

) {

    $lesson['school_logo'] =
    BASE_PATH .
    '/public/assets/images/moe.png';

}

if (

    !isset($lesson['ministry_logo'])

) {

    $lesson['ministry_logo'] =
    BASE_PATH .
    '/public/assets/images/moe.png';

}

/*
|--------------------------------------------------------------------------
| Ready For Export
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Export Document
|--------------------------------------------------------------------------
*/

try {

    $exporter->download(
    $lesson,
    $fileName
);

} catch (\Throwable $e) {

    error_log(

        '[WORD EXPORT] ' .

        $e->getMessage()

    );

    http_response_code(500);

    ?>
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">

    <head>

        <meta charset="UTF-8">

        <title>خطأ</title>

        <style>

            body{

                font-family:Cairo,Tahoma,Arial,sans-serif;

                background:#f5f5f5;

                margin:40px;

            }

            .card{

                max-width:700px;

                margin:auto;

                background:#fff;

                border-radius:10px;

                padding:30px;

                box-shadow:0 5px 20px rgba(0,0,0,.15);

                border-right:6px solid #dc3545;

            }

            h2{

                color:#dc3545;

                margin-bottom:20px;

            }

            p{

                font-size:15px;

                line-height:1.9;

            }

            pre{

                background:#f8f9fa;

                padding:15px;

                border-radius:6px;

                overflow:auto;

                direction:ltr;

                text-align:left;

            }

            a{

                display:inline-block;

                margin-top:20px;

                text-decoration:none;

                background:#0d6efd;

                color:#fff;

                padding:10px 20px;

                border-radius:6px;

            }

        </style>

    </head>

    <body>

        <div class="card">

            <h2>

                تعذر إنشاء ملف Word

            </h2>

            <p>

                حدث خطأ أثناء إنشاء مستند Word.

            </p>

            <?php if (ini_get('display_errors')) : ?>

                <pre><?= htmlspecialchars($e->getMessage()) ?></pre>

            <?php endif; ?>

            <a href="javascript:history.back();">

                الرجوع

            </a>

        </div>

    </body>

    </html>
    <?php

    exit;

}