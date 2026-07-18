<?php

require_once '../../config/config.php';

include '../../app/views/layouts/header.php';

?>


<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

<div class="container">

<h2>Add Parent</h2>

<form
method="POST"
action="store.php">

<div class="mb-3">

<label class="form-label">

Full Name

</label>

<input
type="text"
name="full_name"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Email

</label>

<input
type="email"
name="email"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Phone

</label>

<input
type="text"
name="phone"
class="form-control">

</div>

<div class="mb-3">

<label class="form-label">

Password

</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<button
type="submit"
class="btn btn-success">

Save Parent

</button>

<a
href="index.php"
class="btn btn-secondary">

Cancel

</a>

</form>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
