<?php
/*
=====================================================================
teacher/question_bank/delete.php — حذف سؤال من بنك الأسئلة
⚠️ كان مفتوحاً تماماً: لا صلاحيات، لا تحقق ملكية
   أي مستخدم يحذف أي سؤال بتغيير ?id=
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';
require_once '../../app/models/QuestionBank.php';

/* ==================== الصلاحية: معلم فقط ==================== */
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 2) {
    die('Access Denied');
}

$db = (new Database())->connect();

$stmt = $db->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$teacherId = (int)$stmt->fetchColumn();

if ($teacherId <= 0) {
    die('Teacher Not Found');
}

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Question ID Missing');
}

/*
====================================================================
✅ نقرأ بيانات السؤال قبل الحذف — مع التأكد من الملكية
====================================================================
*/
$stmt = $db->prepare("
    SELECT qb.*, c.course_name
    FROM question_bank qb
    LEFT JOIN courses c ON qb.course_id = c.id
    WHERE qb.id = ? AND qb.teacher_id = ?
");
$stmt->execute([$id, $teacherId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {

    Logger::log(
        'question_bank',
        'delete_denied',
        "محاولة حذف سؤال لا يملكه المعلم (question_id=$id)",
        null, null, 'danger'
    );

    die('السؤال غير موجود أو لا تملك صلاحية حذفه');
}

$category   = (string)($row['category'] ?? '');
$courseName = (string)($row['course_name'] ?? '');
$courseId   = (int)($row['course_id'] ?? 0);

/* ==================== الحذف ==================== */
$questionBank = new QuestionBank();
$deleted      = $questionBank->delete($id);

if (!$deleted) {
    die('Delete Failed');
}

/* ==================== التسجيل ==================== */
Logger::log(
    'question_bank',
    'delete_question',
    "حذف سؤال من بنك الأسئلة (id=$id) - مقرر ($courseName)"
    . " - التصنيف ($category)",
    'course',
    $courseId,
    'danger'
);

header('Location: index.php?deleted=1');
exit;