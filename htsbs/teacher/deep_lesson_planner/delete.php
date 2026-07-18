<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$db = (new Database())->connect();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = $db->prepare("DELETE FROM deep_lesson_plans WHERE id = ? AND teacher_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

$_SESSION['success'] = 'تم حذف التخطيط بنجاح.';

header("Location: index.php");
exit;
?>
