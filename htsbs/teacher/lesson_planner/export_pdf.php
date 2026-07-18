<?php

declare(strict_types=1);

/*
==================================================
Session
==================================================
*/

session_start();

/*
==================================================
Composer
==================================================
*/

require_once __DIR__ . '/../../vendor/autoload.php';

/*
==================================================
Configuration
==================================================
*/

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/school.php';

/*
==================================================
Helpers
==================================================
*/

require_once __DIR__ . '/../../app/helpers/PdfRenderer.php';
require_once __DIR__ . '/../../app/helpers/LessonPlanRenderer.php';

/*
==================================================
Authentication
==================================================
*/

if (!isset($_SESSION['user_id'])) {

    header('Location: ' . BASE_URL . '/login.php');

    exit;

}

/*
==================================================
Database
==================================================
*/

$db = (new Database())->connect();

/*
==================================================
Lesson ID
==================================================
*/

$id = filter_input(

    INPUT_GET,

    'id',

    FILTER_VALIDATE_INT

);

if (!$id) {

    exit('Invalid Lesson ID.');

}

/*
==================================================
Load Lesson
==================================================
*/

$sql = "

SELECT

    lp.*,

    u.full_name,

    c.course_name,

    cl.class_name

FROM lesson_plans lp

LEFT JOIN users u

ON u.id = lp.teacher_id

LEFT JOIN courses c

ON c.id = lp.subject_id

LEFT JOIN classes cl

ON cl.id = lp.class_id

WHERE

    lp.id = ?

AND

    lp.teacher_id = ?

LIMIT 1

";

$stmt = $db->prepare($sql);

$stmt->execute([

    $id,

    $_SESSION['user_id']

]);

$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {

    exit('Lesson Plan Not Found.');

}

/*
==================================================
Lesson JSON
==================================================
*/

if (empty($lesson['lesson_plan_json'])) {

    exit('لا يوجد تحضير محفوظ بصيغة JSON.');

}

$lessonJson = json_decode(

    $lesson['lesson_plan_json'],

    true

);

if (

    json_last_error() !== JSON_ERROR_NONE ||

    !is_array($lessonJson)

) {

    exit('JSON غير صالح.');

}

/*
==================================================
School Information
==================================================
*/

$school = [

    'school_name'   => SCHOOL_NAME,

    'ministry_name' => MINISTRY_NAME,

    'school_logo'   => SCHOOL_LOGO,

    'moe_logo'      => MOE_LOGO,

    'academic_year' => ACADEMIC_YEAR,

    'address'       => SCHOOL_ADDRESS,

    'phone'         => SCHOOL_PHONE

];

/*
==================================================
Generate HTML
==================================================
*/

$html = PdfRenderer::render(

    $lesson,

    $lessonJson,

    $school

);

if (trim($html) === '') {

    exit('فشل إنشاء محتوى PDF.');

}

/*
==================================================
mPDF Configuration
==================================================
*/

$defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();

$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();

$fontData = $defaultFontConfig['fontdata'];

/*
==================================================
Fonts Path
==================================================
*/

$fontsPath = realpath(

    __DIR__ . '/../../fonts'

);

if (!$fontsPath) {

    exit('Fonts directory not found.');

}

/*
==================================================
Create mPDF
==================================================
*/

$mpdf = new \Mpdf\Mpdf([

    'mode' => 'utf-8',

    'format' => 'A4',

    'orientation' => 'P',

    'margin_left' => 10,

    'margin_right' => 10,

    'margin_top' => 28,

    'margin_bottom' => 20,

    'margin_header' => 8,

    'margin_footer' => 8,

    'fontDir' => array_merge(

        $fontDirs,

        [

            $fontsPath

        ]

    ),

    'fontdata' => $fontData + [

        'cairo' => [

            'R'  => 'Cairo-Regular.ttf',

            'B'  => 'Cairo-Bold.ttf',

            'I'  => 'Cairo-Light.ttf',

            'BI' => 'Cairo-SemiBold.ttf'

        ]

    ],

    'default_font' => 'cairo'

]);

/*
==================================================
Arabic Support
==================================================
*/

$mpdf->SetDirectionality('rtl');

$mpdf->autoScriptToLang = true;

$mpdf->autoLangToFont = true;

$mpdf->useSubstitutions = true;

/*
==================================================
Document Information
==================================================
*/

$mpdf->SetTitle(

    'تحضير درس - ' .

    ($lesson['lesson_title'] ?? '')

);

$mpdf->SetAuthor(

    $lesson['full_name'] ?? 'AI Lesson Planner'

);

$mpdf->SetCreator(

    'AI Lesson Planner'

);

$mpdf->SetSubject(

    $lesson['course_name'] ?? ''

);

$mpdf->SetDisplayMode('fullpage');

/*
==================================================
Header
==================================================
*/

$header = '

<table width="100%" dir="rtl" style="border-bottom:1px solid #999;padding-bottom:6px;">

<tr>

<td width="15%" align="center">';

if (file_exists(MOE_LOGO)) {

    $header .= '

<img src="' . MOE_LOGO . '" width="55">

';

}

$header .= '

</td>

<td width="70%" align="center">

<div style="font-size:18pt;font-weight:bold;">

' . htmlspecialchars(SCHOOL_NAME) . '

</div>

<div style="font-size:11pt;color:#666;">

' . htmlspecialchars(MINISTRY_NAME) . '

</div>

<div style="font-size:14pt;font-weight:bold;margin-top:6px;">

تحضير درس باستخدام الذكاء الاصطناعي

</div>

</td>

<td width="15%" align="center">';

if (file_exists(SCHOOL_LOGO)) {

    $header .= '

<img src="' . SCHOOL_LOGO . '" width="55">

';

}

$header .= '

</td>

</tr>

</table>

';

$mpdf->SetHTMLHeader($header);

/*
==================================================
Footer
==================================================
*/

$footer = '

<table width="100%" style="border-top:1px solid #999;font-size:9pt;">

<tr>

<td width="35%" align="right">

' . htmlspecialchars(SCHOOL_NAME) . '

</td>

<td width="30%" align="center">

صفحة {PAGENO} من {nbpg}

</td>

<td width="35%" align="left">

' . date('Y-m-d H:i') . '

</td>

</tr>

</table>

';

$mpdf->SetHTMLFooter($footer);

/*
==================================================
Watermark (Optional)
==================================================
*/

$mpdf->showWatermarkText = false;

$mpdf->showWatermarkImage = false;

/*
==================================================
PDF Properties
==================================================
*/

$mpdf->mirrorMargins = false;

$mpdf->use_kwt = true;

$mpdf->shrink_tables_to_fit = 1;

$mpdf->keep_table_proportions = true;

/*
==================================================
Generate PDF
==================================================
*/

try {

    /*
    ==============================================
    Write HTML
    ==============================================
    */

    $mpdf->WriteHTML($html);

    /*
    ==============================================
    Update Statistics
    ==============================================
    */

    $stmt = $db->prepare("

        UPDATE lesson_plans

        SET

            exported_pdf = COALESCE(exported_pdf,0) + 1,

            printed_count = COALESCE(printed_count,0) + 1,

            updated_at = NOW()

        WHERE id = ?

    ");

    $stmt->execute([$lesson['id'] ]);
    
    require_once __DIR__ . '/../../core/Logger.php';

Logger::log(
    'lesson_planner',
    'export_pdf',
    "تصدير PDF لتحضير (id=$id)",
    'lesson_plan',
    $id,
    'info'
);
    /*
    ==============================================
    File Name
    ==============================================
    */

    $lessonTitle = trim(

        $lesson['lesson_title'] ??

        'Lesson'

    );

    $lessonTitle = preg_replace(

        '/[^A-Za-z0-9\-_أ-ي ]/u',

        '',

        $lessonTitle

    );

    $lessonTitle = str_replace(

        ' ',

        '_',

        $lessonTitle

    );

    $fileName =

        'LessonPlan_' .

        $lessonTitle .

        '_' .

        date('Ymd_His') .

        '.pdf';

    /*
    ==============================================
    Output PDF
    ==============================================
    */

    $mpdf->Output(

        $fileName,

        \Mpdf\Output\Destination::INLINE

    );

    exit;

}

/*
==================================================
mPDF Error
==================================================
*/

catch (\Mpdf\MpdfException $e) {

    error_log(

        'mPDF Error : '

        .

        $e->getMessage()

    );

    die(

        '<h3>حدث خطأ أثناء إنشاء ملف PDF.</h3><hr>' .

        htmlspecialchars(

            $e->getMessage()

        )

    );

}

/*
==================================================
General Error
==================================================
*/

catch (Throwable $e) {

    error_log(

        'Export PDF Error : '

        .

        $e->getMessage()

    );

    die(

        '<h3>حدث خطأ غير متوقع.</h3><hr>' .

        htmlspecialchars(

            $e->getMessage()

        )

    );

}

/*
==================================================
End of File
==================================================
*/

/*
لا تضع أي echo أو var_dump أو HTML بعد هذا السطر.

أي مخرجات إضافية ستؤدي إلى تلف ملف PDF أو ظهور رسالة مثل:

Output has already been sent

أو

Cannot modify header information
*/

exit;

