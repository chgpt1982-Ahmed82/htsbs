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

$model->deleteNote($id);

$_SESSION['success'] =
'تم حذف الملاحظة بنجاح';

header(
    'Location: index.php'
);

exit;
