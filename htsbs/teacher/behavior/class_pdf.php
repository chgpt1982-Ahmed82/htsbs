<?php

session_start();

require_once '../../vendor/autoload.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$classId = (int)($_GET['class_id'] ?? 0);

if(!$classId){
    die('Class Missing');
}

$stmt = $db->prepare("
SELECT class_name
FROM classes
WHERE id=?
");

$stmt->execute([$classId]);

$className = $stmt->fetchColumn();

$stmt = $db->prepare("
SELECT

s.id,

u.full_name,

s.student_number,

COUNT(b.id) total_notes,

SUM(
CASE
WHEN b.note_type='positive'
THEN 1
ELSE 0
END
) positive_count,

SUM(
CASE
WHEN b.note_type='negative'
THEN 1
ELSE 0
END
) negative_count,

SUM(
CASE
WHEN b.note_type='warning'
THEN 1
ELSE 0
END
) warning_count

FROM students s

INNER JOIN users u
ON s.user_id=u.id

LEFT JOIN behavior_notes b
ON s.id=b.student_id

WHERE s.class_id=?

GROUP BY s.id

ORDER BY u.full_name
");

$stmt->execute([$classId]);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mpdf = new \Mpdf\Mpdf([
'mode'=>'utf-8',
'default_font'=>'xbriyaz'
]);

$mpdf->SetDirectionality('rtl');

$html='

<h2 style="text-align:center">
تقرير السلوك للصف
</h2>

<h3 style="text-align:center">
'.$className.'
</h3>

<table border="1" width="100%" cellpadding="8">

<tr style="background:#0d6efd;color:white">

<th>الطالب</th>

<th>الرقم الأكاديمي</th>

<th>إيجابية</th>

<th>سلبية</th>

<th>تنبيهات</th>

<th>الإجمالي</th>

</tr>

';

foreach($students as $s)
{
$html.='

<tr>

<td>'.$s['full_name'].'</td>

<td>'.$s['student_number'].'</td>

<td>'.$s['positive_count'].'</td>

<td>'.$s['negative_count'].'</td>

<td>'.$s['warning_count'].'</td>

<td>'.$s['total_notes'].'</td>

</tr>

';
}

$html.='</table>';

$mpdf->WriteHTML($html);

$mpdf->Output(
'Class_Behavior_Report.pdf',
'I'
);