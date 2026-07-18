<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/course.php';
require_once '../../core/Logger.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Course ID Not Found');
}

$courseModel = new Course();

/* ✅ نقرأ الاسم قبل الحذف — بعده يضيع نهائياً */
$row = $courseModel->getById($id);

if (!$row) {
    die('Course Not Found');
}

$name = trim($row['course_name'] ?? '')
      . (!empty($row['course_code']) ? ' (' . $row['course_code'] . ')' : '');

$result = $courseModel->delete($id);

if ($result) {

    Logger::deleted('courses', $name !== '' ? $name : ('#' . $id), $id);

    header("Location: " . BASE_URL . "/admin/courses/index.php");
    exit;

} else {
    die('Delete Failed');
}