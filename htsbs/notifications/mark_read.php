<?php

session_start();

require_once '../app/models/Notification.php';

$model = new Notification();

if(isset($_GET['id']))
{
    $model->markRead(
        $_GET['id']
    );
}

header(
"Location: index.php"
);

exit;
?>
