<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

if(session_status() == PHP_SESSION_NONE)
{
    session_start();
}

if(!isset($count))
{
    $count = 0;
}

require_once dirname(__DIR__,2) . '/models/Message.php';

$messageModel = new Message();

$messageCount = 0;

if(isset($_SESSION['user_id']))
{
    $messageCount =
    $messageModel->unreadCount(
        $_SESSION['user_id']
    );
}

/*
====================================
معلومات المستخدم
====================================
*/

$userInfo = '';

if(isset($_SESSION['user_id']))
{
    require_once dirname(__DIR__,3) . '/config/database.php';

    $db = (new Database())->connect();

    // الطالب
    if($_SESSION['role_id'] == 3)
    {
        $stmt = $db->prepare("
        SELECT
            s.student_number,
            c.class_name
        FROM students s
        LEFT JOIN classes c
            ON s.class_id = c.id
        WHERE s.user_id = ?
        ");

        $stmt->execute([
            $_SESSION['user_id']
        ]);

        $student =
        $stmt->fetch(PDO::FETCH_ASSOC);

        if($student)
        {
            $userInfo =
            '<i class="bi bi-building"></i> '
            .$student['class_name'].
            ' &nbsp;&nbsp; | &nbsp;&nbsp;
            <i class="bi bi-person-vcard"></i> '
            .$student['student_number'];
        }
    }

    // المعلم
    elseif($_SESSION['role_id'] == 2)
    {
        $stmt = $db->prepare("
        SELECT
            d.department_name,
            t.specialization
        FROM teachers t
        LEFT JOIN departments d
            ON t.department_id = d.id
        WHERE t.user_id = ?
        ");

        $stmt->execute([
            $_SESSION['user_id']
        ]);

        $teacher =
        $stmt->fetch(PDO::FETCH_ASSOC);

        if($teacher)
        {
            $userInfo =
            '<i class="bi bi-building"></i> '
            .$teacher['department_name'];

            if(!empty($teacher['specialization']))
            {
                $userInfo .=
                ' &nbsp;&nbsp; | &nbsp;&nbsp;
                <i class="bi bi-book"></i> '
                .$teacher['specialization'];
            }
        }
    }

    // الأدمن
    elseif($_SESSION['role_id'] == 1)
    {
        $userInfo =
        '<i class="bi bi-shield-lock-fill"></i> مدير النظام';
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Computer Science LMS</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
rel="stylesheet">

<link
rel="stylesheet"
href="<?= BASE_URL ?>/assets/css/theme.css">

<link
rel="stylesheet"
href="<?= BASE_URL ?>/assets/css/lms.css">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap"
rel="stylesheet">

<style>

body{
    font-family:'Cairo',sans-serif;
    padding-top:60px;
}

.form-label{
    display:block;
    text-align:right;
    font-weight:600;
}

.form-control,
.form-select{
    text-align:right;
}

.card-body{
    direction:rtl;
}

.user-info{
    font-size:14px;
    opacity:.95;
}

</style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center w-100">



    <!-- اسم النظام والمستخدم -->

    <div class="text-white text-end">

        <div class="d-flex align-items-center justify-content-end">
    <!-- 
            <span class="fw-bold fs-4 ms-4">

                قسم الحاسب الآلي

            </span>
    
    -->
            <span class="fw-semibold">

                <?php if($_SESSION['role_id'] == 3): ?>

                    <i class="bi bi-mortarboard-fill"></i>

                <?php elseif($_SESSION['role_id'] == 2): ?>

                    <i class="bi bi-person-workspace"></i>

                <?php else: ?>

                    <i class="bi bi-shield-lock-fill"></i>

                <?php endif; ?>

                مرحباً
                <?= htmlspecialchars($_SESSION['name']); ?>

            </span>

        </div>

        <?php if(!empty($userInfo)): ?>

        <div class="user-info mt-1">

            <?= $userInfo; ?>

        </div>

        <?php endif; ?>

    </div>
    
    
    
    
        <!-- الرسائل والتنبيهات -->

    <div class="d-flex align-items-center">

        <a
        href="<?= BASE_URL ?>/notifications/index.php"
        class="btn btn-light rounded-circle position-relative me-2">

            <i class="bi bi-bell-fill"></i>

            <?php if($count > 0): ?>

            <span
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">

                <?= $count; ?>

            </span>

            <?php endif; ?>

        </a>

        <a
        href="<?= BASE_URL ?>/messages/inbox.php"
        class="btn btn-light rounded-circle position-relative">

            <i class="bi bi-envelope-fill"></i>

            <?php if($messageCount > 0): ?>

            <span
            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">

                <?= $messageCount; ?>

            </span>

            <?php endif; ?>

        </a>

    </div>

</div>

</div>

</nav>