<?php

session_start();

require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

if(
    !isset($_GET['student_id'])
){
    die('Student ID Missing');
}

$db = (new Database())->connect();

$studentId = (int)$_GET['student_id'];

/*
=========================
Student Info
=========================
*/

$stmt = $db->prepare("
SELECT

u.full_name,
s.student_number,
c.class_name

FROM students s

INNER JOIN users u
ON s.user_id=u.id

LEFT JOIN classes c
ON s.class_id=c.id

WHERE s.id=?
");

$stmt->execute([$studentId]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$student){
    die('Student Not Found');
}

/*
=========================
Notes
=========================
*/

$stmt = $db->prepare("
SELECT

b.*,

tu.full_name AS teacher_name

FROM behavior_notes b

INNER JOIN teachers t
ON b.teacher_id=t.id

INNER JOIN users tu
ON t.user_id=tu.id

WHERE b.student_id=?

ORDER BY b.note_date DESC
");

$stmt->execute([$studentId]);

$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
=========================
Statistics
=========================
*/

$positive = 0;
$negative = 0;
$warning  = 0;

foreach($notes as $n)
{
    if($n['note_type']=='positive') $positive++;
    if($n['note_type']=='negative') $negative++;
    if($n['note_type']=='warning') $warning++;
}

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'xbriyaz'
]);

$mpdf->SetDirectionality('rtl');

$html = '

<style>

body{
font-family:xbriyaz;
direction:rtl;
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
border:1px solid #ccc;
padding:8px;
text-align:center;
}

th{
background:#0d6efd;
color:#fff;
}

.title{
text-align:center;
font-size:22px;
font-weight:bold;
margin-bottom:20px;
}

.info{
margin-bottom:15px;
font-size:14px;
}

.stats{
margin-bottom:15px;
}

</style>

<div class="title">
تقرير السلوك الطلابي
</div>

<div class="info">

<b>اسم الطالب:</b>
'.$student['full_name'].'<br>

<b>الرقم الأكاديمي:</b>
'.$student['student_number'].'<br>

<b>الصف:</b>
'.$student['class_name'].'

</div>

<div class="stats">

<b>إيجابية:</b> '.$positive.'
&nbsp;&nbsp;&nbsp;

<b>سلبية:</b> '.$negative.'
&nbsp;&nbsp;&nbsp;

<b>تنبيهات:</b> '.$warning.'

</div>

<table>

<tr>

<th>التاريخ</th>
<th>النوع</th>
<th>العنوان</th>
<th>التفاصيل</th>
<th>المعلم</th>

</tr>
';

foreach($notes as $note)
{
    $html .= '

    <tr>

    <td>'.$note['note_date'].'</td>

    <td>'.$note['note_type'].'</td>

    <td>'.$note['title'].'</td>

    <td>'.$note['details'].'</td>

    <td>'.$note['teacher_name'].'</td>

    </tr>
    ';
}

$html .= '</table>';

$mpdf->WriteHTML($html);

$mpdf->Output(
'Student_Behavior_Report.pdf',
'I'
);