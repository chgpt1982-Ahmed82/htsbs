<?php

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$parentId =
$_GET['id'];

$parentStmt = $db->prepare(

"SELECT

p.id,
u.full_name

FROM parents p

INNER JOIN users u
ON p.user_id=u.id

WHERE p.id=?"

);

$parentStmt->execute([
$parentId
]);

$parent =
$parentStmt->fetch(PDO::FETCH_ASSOC);

$students = $db->query(

"SELECT

s.id,
u.full_name,
s.student_number

FROM students s

INNER JOIN users u
ON s.user_id=u.id

ORDER BY u.full_name"

)->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">


<div class="container">

<h2>Link Student To Parent</h2>

<div class="alert alert-info">

Parent:

<strong>

<?= htmlspecialchars(
$parent['full_name']
); ?>

</strong>

</div>

<form
method="POST"
action="save_link.php">

<input
type="hidden"
name="parent_id"
value="<?= $parentId; ?>">

<div class="mb-3">

<label>

Student

</label>

<select
name="student_id"
class="form-control"
required>

<option value="">

Select Student

</option>

<?php foreach($students as $student): ?>

<option
value="<?= $student['id']; ?>">

<?= htmlspecialchars(
$student['full_name']
); ?>

(

<?= $student['student_number']; ?>

)

</option>

<?php endforeach; ?>

</select>

</div>

<button
class="btn btn-primary">

Link Student

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
