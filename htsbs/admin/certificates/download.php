<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Certificate.php';

require_once '../../vendor/tcpdf/tcpdf.php';

$model =
new Certificate();

$id =
$_GET['id'] ?? 0;

$certificate =
$model->find($id);

if(!$certificate)
{
    die('Certificate Not Found');
}

$pdf = new TCPDF(

    'L',
    PDF_UNIT,
    'A4',
    true,
    'UTF-8',
    false

);

$pdf->SetCreator('LMS');

$pdf->SetAuthor(
'Ministry of Education Bahrain'
);

$pdf->SetTitle(
'Academic Certificate'
);

$pdf->SetMargins(
10,
10,
10
);

$pdf->AddPage();

$pdf->setRTL(true);

$pdf->SetFont(
'dejavusans',
'',
14
);

/*
شعار الوزارة
*/

$logo =
$_SERVER['DOCUMENT_ROOT'] .
'/assets/images/moe-bahrain.png';

if(file_exists($logo))
{
    $pdf->Image(
        $logo,
        130,
        10,
        30
    );
}

$html = '

<div style="text-align:center;">

<h2>

مملكة البحرين

</h2>

<h3>

وزارة التربية والتعليم

</h3>

<h3>

مدرسة مدينة حمد الثانوية للبنين

</h3>

<br><br>

<h1>

شهادة إنجاز أكاديمي

</h1>

<br>

<p>

تشهد إدارة المدرسة بأن الطالب

</p>

<h2 style="color:blue;">

'.$certificate['full_name'].'

</h2>

<p>

الرقم الأكاديمي

</p>

<h3>

'.$certificate['student_number'].'

</h3>

<br>

<p>

قد أتم بنجاح جميع المتطلبات الأكاديمية

</p>

<br>

<h3>

الدرجة النهائية :

'.$certificate['final_grade'].'

</h3>

<h3>

GPA :

'.$certificate['gpa'].'

</h3>

<br>

<p>

رقم الشهادة

</p>

<strong>

'.$certificate['certificate_no'].'

</strong>

<br><br>

<p>

تاريخ الإصدار

</p>

<strong>

'.$certificate['issue_date'].'

</strong>

<br><br><br>

<table width="100%">

<tr>

<td align="center">

____________________

<br>

مدير المدرسة

</td>

<td align="center">

____________________

<br>

ختم المدرسة

</td>

</tr>

</table>

</div>

';

$pdf->writeHTML(
$html,
true,
false,
true,
false,
''
);

$pdf->Output(

'Certificate_' .
$certificate['certificate_no'] .
'.pdf',

'D'

);

exit;

?>
