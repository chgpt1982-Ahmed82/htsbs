<?php
/*
=====================================================================
LMS - AJAX: حفظ وقت التعلم دورياً (كل 30 ثانية من صفحة الدرس)
=====================================================================
*/
require_once dirname(__DIR__, 2) . '/includes/lms_init.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 3) {
    http_response_code(403);
    exit(json_encode(['success' => false]));
}

lms_csrf_check();

$student = $lms->getStudentByUserId((int)$_SESSION['user_id']);
if (!$student) exit(json_encode(['success' => false]));

$lessonId = (int)($_POST['lesson_id'] ?? 0);
$seconds  = (int)($_POST['seconds'] ?? 0);

$lms->addTimeSpent($lessonId, (int)$student['id'], $seconds);

echo json_encode(['success' => true]);
