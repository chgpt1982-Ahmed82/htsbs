<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$db = (new Database())->connect();

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'add';

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$value = ($action === 'add') ? 1 : 0;

$stmt = $db->prepare("
UPDATE deep_lesson_plans
SET is_favorite = ?
WHERE id = ? AND teacher_id = ?
");

$stmt->execute([$value, $id, $_SESSION['user_id']]);

$_SESSION['success'] = ($action === 'add')
    ? 'تمت الإضافة إلى المفضلة.'
    : 'تمت الإزالة من المفضلة.';

header("Location: view.php?id=" . $id);
exit;
?>
