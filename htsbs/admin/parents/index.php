<?php

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$sql = "

SELECT

p.id,

p.user_id,

p.phone,

u.full_name,

u.email,

u.status

FROM parents p

INNER JOIN users u
ON p.user_id=u.id

ORDER BY u.full_name

";

$parents =
$db->query($sql)
->fetchAll(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<div class="d-flex justify-content-between mb-3">

<h2>Parents</h2>

<a href="create.php"
class="btn btn-primary">

Add Parent

</a>

</div>

<table class="table table-bordered table-striped">

<thead>

<tr>

<th>Name</th>
<th>Email</th>
<th>Phone</th>
<th>Status</th>
<th width="220">Actions</th>

</tr>

</thead>

<tbody>

<?php foreach($parents as $parent): ?>

<tr>

<td>

<?= htmlspecialchars(
$parent['full_name']
); ?>

</td>

<td>

<?= htmlspecialchars(
$parent['email']
); ?>

</td>

<td>

<?= htmlspecialchars(
$parent['phone']
); ?>

</td>

<td>

<?= htmlspecialchars(
$parent['status']
); ?>

</td>

<td>

<a
href="edit.php?id=<?= $parent['id']; ?>"
class="btn btn-warning btn-sm">

Edit

</a>

<a
href="link_student.php?id=<?= $parent['id']; ?>"
class="btn btn-info btn-sm">

Link Student

</a>

<a
href="delete.php?id=<?= $parent['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete Parent?')">

Delete

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<?php include '../../app/views/layouts/footer.php'; ?>
