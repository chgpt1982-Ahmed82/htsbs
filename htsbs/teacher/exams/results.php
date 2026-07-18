<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if(
    !isset($_SESSION['user_id'])
    || $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$db = (new Database())->connect();

/*
====================================
Teacher
====================================
*/

$stmt = $db->prepare("
SELECT id
FROM teachers
WHERE user_id=?
");

$stmt->execute([
    $_SESSION['user_id']
]);

$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$teacherId = $teacher['id'] ?? 0;

/*
====================================
Exam
====================================
*/

$examId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
SELECT *
FROM exams
WHERE id=?
AND teacher_id=?
");

$stmt->execute([
    $examId,
    $teacherId
]);

$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$exam)
{
    die('Exam Not Found');
}

/*
====================================
Save Result
====================================
*/

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
    $studentId = (int)$_POST['student_id'];

    $marks = $_POST['marks'];

    $remarks = trim($_POST['remarks']);

    $stmt = $db->prepare("
    SELECT id
    FROM exam_results
    WHERE exam_id=?
    AND student_id=?
    ");

    $stmt->execute([
        $examId,
        $studentId
    ]);

    $exists = $stmt->fetch();

    if($exists)
    {
        $stmt = $db->prepare("
        UPDATE exam_results
        SET
            marks=?,
            remarks=?
        WHERE exam_id=?
        AND student_id=?
        ");

        $stmt->execute([
            $marks,
            $remarks,
            $examId,
            $studentId
        ]);
    }
    else
    {
        $stmt = $db->prepare("
        INSERT INTO exam_results
        (
            exam_id,
            student_id,
            marks,
            remarks
        )
        VALUES
        (
            ?,?,?,?
        )
        ");

        $stmt->execute([
            $examId,
            $studentId,
            $marks,
            $remarks
        ]);
    }

    $stmtNotify = $db->prepare("
SELECT user_id
FROM students
WHERE id=?
");

$stmtNotify->execute([
    $studentId
]);

$userId =
$stmtNotify->fetchColumn();

$notify = $db->prepare("
INSERT INTO notifications
(
    user_id,
    title,
    message,
    type
)
VALUES
(
    ?,?,?,?
)
");

$notify->execute([
    $userId,
    'تم نشر نتيجة اختبار',
    'تم اعتماد نتيجة الاختبار: ' .
    $exam['exam_name'],
    'grade'
]);



    header(
        "Location: results.php?id=".$examId
    );

    exit;
}

/*
====================================
Students
====================================
*/

$stmt = $db->prepare("
SELECT

    s.id,

    u.full_name,

    c.class_name,

    er.marks,

    er.remarks

FROM exam_assignments ea

INNER JOIN classes c
ON ea.class_id = c.id

INNER JOIN students s
ON c.id = s.class_id

INNER JOIN users u
ON s.user_id = u.id

LEFT JOIN exam_results er
ON er.student_id = s.id
AND er.exam_id = ?

WHERE ea.exam_id = ?

ORDER BY u.full_name
");

$stmt->execute([
    $examId,
    $examId
]);

$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card shadow">

<div class="card-header bg-info text-white">

<h4>

نتائج الاختبار

-

<?= htmlspecialchars($exam['exam_name']); ?>

</h4>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>الطالب</th>

<th>الصف</th>

<th>الدرجة</th>

<th>ملاحظات</th>

<th>حفظ</th>

</tr>

</thead>

<tbody>

<?php foreach($students as $student): ?>

<tr>

<form method="POST">

<td>

<?= htmlspecialchars(
$student['full_name']
); ?>

<input
type="hidden"
name="student_id"
value="<?= $student['id']; ?>">

</td>

<td>

<?= htmlspecialchars(
$student['class_name']
); ?>

</td>

<td>

<input
type="number"
step="0.01"
max="<?= $exam['max_marks']; ?>"
name="marks"
value="<?= $student['marks']; ?>"
class="form-control">

</td>

<td>

<input
type="text"
name="remarks"
value="<?= htmlspecialchars(
$student['remarks'] ?? ''
); ?>"
class="form-control">

</td>

<td>

<button
type="submit"
class="btn btn-success btn-sm">

حفظ

</button>

</td>

</form>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>