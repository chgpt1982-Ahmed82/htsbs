<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

/*
|--------------------------------------------------------------------------
| Config
|--------------------------------------------------------------------------
*/

require_once '../../config/config.php';
require_once '../../config/database.php';

/*
|--------------------------------------------------------------------------
| Model
|--------------------------------------------------------------------------
*/

require_once '../../app/models/LessonPlanner.php';

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: " . BASE_URL . "/login.php");

    exit;

}

if ((int)$_SESSION['role_id'] !== 2) {

    die("Access Denied");

}

/*
|--------------------------------------------------------------------------
| Lesson ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET['id'])

    ? (int)$_GET['id']

    : 0;

if ($id <= 0) {

    $_SESSION['error'] =

        "رقم التحضير غير صحيح.";

    header("Location: index.php");

    exit;

}

/*
|--------------------------------------------------------------------------
| Toggle Favorite
|--------------------------------------------------------------------------
*/

try {

    $lessonPlanner = new LessonPlanner();

    $lesson = $lessonPlanner->find($id);

    if (!$lesson) {

        $_SESSION['error'] =

            "التحضير غير موجود.";

        header("Location: index.php");

        exit;

    }

    /*
    التأكد أن التحضير يخص المعلم الحالي
    */

    if (

        (int)$lesson['teacher_id']

        !==

        (int)$_SESSION['user_id']

    ) {

        $_SESSION['error'] =

            "ليس لديك صلاحية.";

        header("Location: index.php");

        exit;

    }

    $lessonPlanner->toggleFavorite($id);

    $_SESSION['success_message'] =

        "تم تحديث المفضلة بنجاح.";

} catch (Throwable $e) {

    error_log(

        $e->getMessage()

    );

    $_SESSION['error'] =

        "حدث خطأ أثناء تحديث المفضلة.";

}

/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

if (!empty($_SERVER['HTTP_REFERER'])) {

    header(

        "Location: " .

        $_SERVER['HTTP_REFERER']

    );

} else {

    header(

        "Location: view.php?id=" . $id

    );

}

exit;