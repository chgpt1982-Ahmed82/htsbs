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

if(!$teacher)
{
    die('Teacher Not Found');
}

$teacherId = $teacher['id'];

/*
====================================
Submissions
====================================
*/

$stmt = $db->prepare("
SELECT

    s.id,

    u.full_name,

    a.title,

    c.course_name,

    s.file_path,

    s.submission_text,

    s.grade,

    s.feedback,

    s.submitted_at

FROM activity_submissions s

INNER JOIN activities a
ON s.activity_id = a.id

INNER JOIN students st
ON s.student_id = st.id

INNER JOIN users u
ON st.user_id = u.id

INNER JOIN courses c
ON a.course_id = c.id

WHERE a.teacher_id = ?

ORDER BY s.submitted_at DESC
");

$stmt->execute([
    $teacherId
]);

$submissions =
$stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
   
<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="bi bi-upload"></i>

حلول الأنشطة

</h4>

</div>

<div class="card-body">

<?php if(empty($submissions)): ?>

<div class="alert alert-info">

لا توجد أي حلول مرفوعة حتى الآن

</div>

<?php else: ?>

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle">

<thead class="table-primary">

<tr>

<th>الطالب</th>

<th>النشاط</th>

<th>المقرر</th>

<th>تاريخ التسليم</th>

<th>الملف</th>

<th>الدرجة</th>

<th>التغذية الراجعة</th>

<th>إجراء</th>

</tr>

</thead>

<tbody>

<?php foreach($submissions as $submission): ?>

<tr>

<td>

<td>
<?= htmlspecialchars($submission['full_name']); ?>
</td>

</td>

<td>

<?= htmlspecialchars(
$submission['title']
); ?>

</td>

<td>

<?= htmlspecialchars(
$submission['course_name']
); ?>

</td>

<td>

<?= $submission['submitted_at']; ?>

</td>

<td>

<?php if(!empty($submission['file_path'])): ?>

<a
href="<?= BASE_URL . '/' . $submission['file_path']; ?>"
target="_blank"
class="btn btn-info btn-sm">

تحميل

</a>

<?php else: ?>

-

<?php endif; ?>

</td>

<td>

<?php

if($submission['grade'] !== null)
{
    echo $submission['grade'];
}
else
{
    echo '<span class="badge bg-warning">
    لم تصحح
    </span>';
}

?>

</td>

<td>

<?= !empty($submission['feedback'])
? htmlspecialchars($submission['feedback'])
: '-'; ?>

</td>

<td>

<a
href="grade_submission.php?id=<?= $submission['id']; ?>"
class="btn btn-success btn-sm">

تصحيح

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php endif; ?>

</div>

</div>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>