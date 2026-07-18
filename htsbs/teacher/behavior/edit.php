<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/BehaviorNote.php';

if(
    !isset($_SESSION['user_id']) ||
    $_SESSION['role_id'] != 2
){
    exit('Unauthorized Access');
}

$model = new BehaviorNote();

$id = (int)($_GET['id'] ?? 0);

$note = $model->getById($id);

if(!$note){
    die('الملاحظة غير موجودة');
}

if($_SERVER['REQUEST_METHOD']=='POST')
{
    $model->updateNote(
        $id,
        $_POST['note_type'],
        $_POST['title'],
        $_POST['details'],
        $_POST['note_date']
    );

    $_SESSION['success'] =
    'تم تحديث الملاحظة';

    header(
        'Location: index.php'
    );
    exit;
}

include '../../app/views/layouts/header.php';
?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">
<div class="card shadow">

<div class="card-header bg-warning">

<h4>

تعديل الملاحظة السلوكية

</h4>

</div>

<div class="card-body">

<form method="POST">

<div class="mb-3">

<label>نوع الملاحظة</label>

<select
name="note_type"
class="form-select">

<option
value="positive"
<?= $note['note_type']=='positive' ? 'selected':''; ?>>

إيجابية

</option>

<option
value="negative"
<?= $note['note_type']=='negative' ? 'selected':''; ?>>

سلبية

</option>

<option
value="warning"
<?= $note['note_type']=='warning' ? 'selected':''; ?>>

تنبيه

</option>

</select>

</div>

<div class="mb-3">

<label>العنوان</label>

<input
type="text"
name="title"
class="form-control"
value="<?= htmlspecialchars($note['title']); ?>">

</div>

<div class="mb-3">

<label>التفاصيل</label>

<textarea
name="details"
rows="5"
class="form-control"><?= htmlspecialchars($note['details']); ?></textarea>

</div>

<div class="mb-3">

<label>التاريخ</label>

<input
type="date"
name="note_date"
class="form-control"
value="<?= $note['note_date']; ?>">

</div>

<button
type="submit"
class="btn btn-success">

حفظ التعديلات

</button>

<a
href="index.php"
class="btn btn-secondary">

رجوع

</a>

</form>

</div>

</div>

</div>

</div>
</div>

<?php include '../../app/views/layouts/footer.php'; ?>
