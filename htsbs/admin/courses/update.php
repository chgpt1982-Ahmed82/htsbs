<?php
session_start();

require_once '../../config/config.php';
require_once '../../app/models/course.php';
require_once '../../core/Logger.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Course ID Not Found');

$courseModel = new Course();
$courseModel->update($id, $_POST);          // ننفّذ أولاً

Logger::updated('courses', $_POST['course_name'] ?? '', $id);   // ✅ هنا

header("Location: " . BASE_URL . "/admin/courses/index.php");
exit;