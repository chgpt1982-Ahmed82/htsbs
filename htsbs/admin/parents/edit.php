<?php

require_once '../../config/config.php';
require_once '../../config/database.php';

$db = (new Database())->connect();

$id = $_GET['id'];

$stmt = $db->prepare(

"SELECT

p.id,
p.user_id,
p.phone,

u.full_name,
u.email

FROM parents p

INNER JOIN users u
ON p.user_id=u.id

WHERE p.id=?"

);

$stmt->execute([
$id
]);

$parent =
$stmt->fetch(PDO::FETCH_ASSOC);

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<div class="container">

<h2>Edit Parent</h2>

<form
method="POST"
action="update.php">

<input
type="hidden"
name="id"
value="<?= $parent['id']; ?>">

<input
type="hidden"
name="user_id"
value="<?= $parent['user_id']; ?>">

<div class="mb-3">

<label>Full Name</label>

<input
type="text"
name="full_name"
class="form-control"
value="<?= htmlspecialchars($parent['full_name']); ?>"
required>

</div>

<div class="mb-3">

<label>Email</label>

<input
type="email"
name="email"
class="form-control"
value="<?= htmlspecialchars($parent['email']); ?>"
required>

</div>

<div class="mb-3">

<label>Phone</label>

<input
type="text"
name="phone"
class="form-control"
value="<?= htmlspecialchars($parent['phone']); ?>">

</div>

<div class="mb-3">

<label>New Password</label>

<input
type="password"
name="password"
class="form-control">

<small class="text-muted">

Leave blank to keep current password

</small>

</div>

<button
class="btn btn-success">

Update Parent

</button>

<a
href="index.php"
class="btn btn-secondary">

Cancel

</a>

</form>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
