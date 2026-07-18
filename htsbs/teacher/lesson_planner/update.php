<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/LessonPlanner.php';
require_once __DIR__ . '/../../app/helpers/LessonPlanTextBuilder.php';





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

$lessonPlanner = new LessonPlanner();

/*
==================================================
رقم التحضير
==================================================
*/

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {

    die("رقم التحضير غير صحيح.");

}

/*
==================================================
جلب التحضير الحالي
==================================================
*/

$stmt = $db->prepare("

SELECT *

FROM lesson_plans

WHERE id = ? AND teacher_id = ?

LIMIT 1

");

$stmt->execute([$id, (int)$_SESSION['user_id']]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

/*
🔴 حماية: كانت مفقودة تماماً — أي معلم يعدّل تحضير أي معلم آخر
بتغيير id في المتصفح (POST['id'])
*/
if (!$lesson) {

    require_once '../../core/Logger.php';

    Logger::log(
        'lesson_planner',
        'update_denied',
        "محاولة تعديل تحضير لا يملكه المعلم (lesson_id=$id)",
        null, null, 'danger'
    );

    die('التحضير غير موجود أو لا تملك صلاحية تعديله');
}
/*
==================================================
Lesson JSON
==================================================
*/

$lessonJson = [];

if (!empty($lesson['lesson_plan_json'])) {

    $lessonJson = json_decode(

        $lesson['lesson_plan_json'],

        true

    );

}

if (!is_array($lessonJson)) {

    $lessonJson = [];

}

/*
==================================================
Generate Lesson Text
==================================================
*/

$lesson['lesson_plan'] = LessonPlanTextBuilder::build(

    $lessonJson

);

if (!$lesson) {

    die("التحضير غير موجود.");

}

/*
==================================================
استقبال بيانات النموذج
==================================================
*/

$lessonInfo = $_POST['lesson_info'] ?? [];

$objectives = $_POST['objectives'] ?? [];

$warmup = $_POST['warmup'] ?? [];

$introduction = $_POST['introduction'] ?? [];

$objective1 = $_POST['objective1'] ?? [];

$objective2 = $_POST['objective2'] ?? [];

$conclusion = trim($_POST['conclusion'] ?? '');

$homework = trim($_POST['homework'] ?? '');

/*
==================================================
تحويل Textarea إلى Arrays
==================================================
*/

$resources = array_filter(array_map(

    'trim',

    explode(

        "\n",

        $_POST['resources'] ?? ''

    )

));

$skills = array_filter(array_map(

    'trim',

    explode(

        "\n",

        $_POST['skills'] ?? ''

    )

));

$values = array_filter(array_map(

    'trim',

    explode(

        "\n",

        $_POST['values'] ?? ''

    )

));

$differentiation = $_POST['differentiation'] ?? [];

$finalAssessment = $_POST['final_assessment'] ?? [];

/*
==================================================
تحويل الأسئلة إلى Arrays
==================================================
*/

$finalAssessment['oral'] = array_filter(

    array_map(

        'trim',

        explode(

            "\n",

            $finalAssessment['oral'] ?? ''

        )

    )

);

$finalAssessment['written'] = array_filter(

    array_map(

        'trim',

        explode(

            "\n",

            $finalAssessment['written'] ?? ''

        )

    )

);


/*
==================================================
إنشاء JSON الجديد
==================================================
*/

$lessonJson = [

    "lesson_info" => [

        "subject" => trim($lessonInfo['subject'] ?? ''),

        "grade" => trim($lessonInfo['grade'] ?? ''),

        "unit" => trim($lessonInfo['unit'] ?? ''),

        "lesson_title" => trim($lessonInfo['lesson_title'] ?? ''),

        "duration" => trim($lessonInfo['duration'] ?? '')

    ],

    "objectives" => array_values($objectives),

    "warmup" => [

        "title" => trim($warmup['title'] ?? ''),

        "teacher_role" => trim($warmup['teacher_role'] ?? ''),

        "student_role" => trim($warmup['student_role'] ?? ''),

        "resources" => trim($warmup['resources'] ?? ''),

        "time" => trim($warmup['time'] ?? '')

    ],

    "introduction" => [

        "content" => trim($introduction['content'] ?? '')

    ],

    "objective1" => [

        "goal" => trim($objective1['goal'] ?? ''),

        "strategy" => trim($objective1['strategy'] ?? ''),

        "activity1" => trim($objective1['activity1'] ?? ''),

        "activity2" => trim($objective1['activity2'] ?? ''),

        "assessment" => trim($objective1['assessment'] ?? '')

    ],

    "objective2" => [

        "goal" => trim($objective2['goal'] ?? ''),

        "strategy" => trim($objective2['strategy'] ?? ''),

        "activity1" => trim($objective2['activity1'] ?? ''),

        "activity2" => trim($objective2['activity2'] ?? ''),

        "assessment" => trim($objective2['assessment'] ?? '')

    ],

    "conclusion" => $conclusion,

    "homework" => $homework,

    "resources" => array_values($resources),

    "skills" => array_values($skills),

    "values" => array_values($values),

    "differentiation" => [

        "advanced" => trim($differentiation['advanced'] ?? ''),

        "average" => trim($differentiation['average'] ?? ''),

        "support" => trim($differentiation['support'] ?? '')

    ],

    "final_assessment" => [

        "oral" => array_values($finalAssessment['oral']),

        "written" => array_values($finalAssessment['written']),

        "performance_task" => trim(

            $finalAssessment['performance_task'] ?? ''

        )

    ]

];

/*
==================================================
تحويل JSON إلى نص
==================================================
*/

$lessonPlanJson = json_encode(

    $lessonJson,

    JSON_UNESCAPED_UNICODE |
    JSON_PRETTY_PRINT

);

/*
==================================================
نسخة نصية احتياطية
==================================================
*/

$lessonPlanText = '';

$lessonPlanText .= "معلومات الدرس\n";

$lessonPlanText .= "-----------------------------\n";

foreach ($lessonJson['lesson_info'] as $key => $value) {

    $lessonPlanText .= $key . " : " . $value . "\n";

}

$lessonPlanText .= "\n";

$lessonPlanText .= "أهداف التعلم\n";

$lessonPlanText .= "-----------------------------\n";

foreach ($lessonJson['objectives'] as $item) {

    $lessonPlanText .= "- " . $item . "\n";

}

$lessonPlanText .= "\n";

$lessonPlanText .= "النشاط الاستهلالي\n";

$lessonPlanText .= $lessonJson['warmup']['title'] . "\n\n";

$lessonPlanText .= "مقدمة الدرس\n";

$lessonPlanText .= $lessonJson['introduction']['content'] . "\n\n";

$lessonPlanText .= "الهدف الأول\n";

$lessonPlanText .= $lessonJson['objective1']['goal'] . "\n";

$lessonPlanText .= "الاستراتيجية: "

    . $lessonJson['objective1']['strategy'] . "\n";

$lessonPlanText .= "النشاط الأول: "

    . $lessonJson['objective1']['activity1'] . "\n";

$lessonPlanText .= "النشاط الثاني: "

    . $lessonJson['objective1']['activity2'] . "\n";

$lessonPlanText .= "التقويم: "

    . $lessonJson['objective1']['assessment'] . "\n\n";

$lessonPlanText .= "الهدف الثاني\n";

$lessonPlanText .= $lessonJson['objective2']['goal'] . "\n";

$lessonPlanText .= "الاستراتيجية: "

    . $lessonJson['objective2']['strategy'] . "\n";

$lessonPlanText .= "النشاط الأول: "

    . $lessonJson['objective2']['activity1'] . "\n";

$lessonPlanText .= "النشاط الثاني: "

    . $lessonJson['objective2']['activity2'] . "\n";

$lessonPlanText .= "التقويم: "

    . $lessonJson['objective2']['assessment'] . "\n\n";

$lessonPlanText .= "الخاتمة\n";

$lessonPlanText .= $conclusion . "\n\n";

$lessonPlanText .= "الواجب\n";

$lessonPlanText .= $homework . "\n";


/*
==================================================
زيادة رقم الإصدار
==================================================
*/

$versionNo = (int)$lesson['version_no'] + 1;

/*
==================================================
بدء Transaction
==================================================
*/

try {

    $db->beginTransaction();

    /*
    ==============================================
    تحديث التحضير
    ==============================================
    */

    $stmt = $db->prepare("

    UPDATE lesson_plans

    SET

        lesson_plan = :lesson_plan,

        lesson_plan_json = :lesson_plan_json,

        version_no = :version_no,

        student_level = :student_level,

        updated_at = NOW()

    WHERE id = :id

    ");

    $stmt->execute([

        ':lesson_plan'       => $lessonPlanText,

        ':lesson_plan_json'  => $lessonPlanJson,

        ':version_no'        => $versionNo,

        ':student_level'     => $_POST['student_level'] ?? 'متوسط',

        ':id'                => $id

    ]);

    /*
    ==============================================
    نجاح العملية
    ==============================================
    */

    $db->commit();

    $_SESSION['success_message'] = "تم تحديث التحضير بنجاح.";
    require_once '../../core/Logger.php';

    Logger::log(
    'lesson_planner',
    'update_plan',
    "تعديل تحضير الدرس (id=$id)"
    . (!empty($_POST['student_level']) ? " - المستوى: {$_POST['student_level']}" : ''),
    'lesson_plan',
    $id,
    'warning'
    );


    /*
    ==============================================
    إعادة التوجيه
    ==============================================
    */

    header(

        "Location: view.php?id=" . $id

    );

    exit;

}

/*
==================================================
خطأ في قاعدة البيانات
==================================================
*/

catch (PDOException $e) {

    $db->rollBack();

    error_log(

        "Lesson Planner Update Error : "

        . $e->getMessage()

    );

    $_SESSION['error'] =

        "حدث خطأ أثناء حفظ التعديلات.";

    header(

        "Location: edit.php?id=" . $id

    );

    exit;

}

/*
==================================================
أي خطأ آخر
==================================================
*/

catch (Exception $e) {

    if ($db->inTransaction()) {

        $db->rollBack();

    }

    error_log(

        "Lesson Planner Exception : "

        . $e->getMessage()

    );

    $_SESSION['error'] =

        "حدث خطأ غير متوقع.";

    header(

        "Location: edit.php?id=" . $id

    );

    exit;

}

