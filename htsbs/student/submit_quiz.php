<?php

session_start();

require_once '../config/database.php';
require_once '../app/models/Student.php';

$db = (new Database())->connect();

$studentModel = new Student();

$studentId =
$studentModel->getStudentId(
$_SESSION['user_id']
);

$quizId = $_POST['quiz_id'];

$answers =
$_POST['answers'] ?? [];

$stmt = $db->prepare(
"SELECT *
 FROM quiz_questions
 WHERE quiz_id=?"
);

$stmt->execute([$quizId]);

$questions =
$stmt->fetchAll(PDO::FETCH_ASSOC);

$correct = 0;

$total =
count($questions);

foreach($questions as $question)
{
    $selected =
    $answers[$question['id']]
    ?? '';

    if(
        $selected ==
        $question['correct_answer']
    ){
        $correct++;
    }
}

$score =
$total > 0
?
round(
($correct / $total) * 100,
2
)
:
0;

$stmt = $db->prepare(
"INSERT INTO quiz_attempts
(
quiz_id,
student_id,
score,
total_questions
)
VALUES
(
?,?,?,?
)"
);

$stmt->execute([
$quizId,
$studentId,
$score,
$total
]);

header(
"Location: results.php"
);

exit;
?>
