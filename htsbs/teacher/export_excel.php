<?php

session_start();

require_once '../config/database.php';

$db = (new Database())->connect();

/*
|--------------------------------------------------------------------------
| Excel UTF-8 Headers
|--------------------------------------------------------------------------
*/

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="gradebook.csv"');
header('Pragma: no-cache');
header('Expires: 0');

/*
|--------------------------------------------------------------------------
| UTF-8 BOM
|--------------------------------------------------------------------------
| مهم جداً لإظهار العربية بشكل صحيح في Excel
*/

echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

/*
|--------------------------------------------------------------------------
| CSV Header
|--------------------------------------------------------------------------
*/

fputcsv($output, [

    'Student',
    'Student Number',
    'Quiz',
    'Score',
    'Questions',
    'Date'

]);

/*
|--------------------------------------------------------------------------
| Report Query
|--------------------------------------------------------------------------
*/

$sql = "

SELECT

u.full_name,

s.student_number,

q.title,

qa.score,

qa.total_questions,

qa.submitted_at

FROM quiz_attempts qa

INNER JOIN students s
    ON qa.student_id = s.id

INNER JOIN users u
    ON s.user_id = u.id

INNER JOIN quizzes q
    ON qa.quiz_id = q.id

ORDER BY qa.submitted_at DESC

";

$stmt = $db->query($sql);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
{
    fputcsv($output, [

        $row['full_name'],
        $row['student_number'],
        $row['title'],
        $row['score'],
        $row['total_questions'],
        $row['submitted_at']

    ]);
}

fclose($output);

exit;
?>
