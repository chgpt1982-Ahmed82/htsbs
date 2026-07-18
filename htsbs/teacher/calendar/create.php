<?php

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';

require_once '../../app/models/Calendar.php';
require_once '../../app/models/Notification.php';

$db = (new Database())->connect();

$calendarModel =
new Calendar();

$notificationModel =
new Notification();

$count =
$notificationModel->unreadCount(
$_SESSION['user_id']
);

/*
جلب المعلم الحالي
*/

$stmt = $db->prepare(

"SELECT id

 FROM teachers

 WHERE user_id=?"

);

$stmt->execute([
$_SESSION['user_id']
]);

$teacher =
$stmt->fetch(PDO::FETCH_ASSOC);

if(!$teacher)
{
    die('Teacher Not Found');
}

/*
المقررات المسندة للمعلم
*/

$stmt = $db->prepare(

"SELECT DISTINCT

c.id,
c.course_name

FROM course_assignments ca

INNER JOIN courses c
ON ca.course_id=c.id

WHERE ca.teacher_id=?

ORDER BY c.course_name"

);

$stmt->execute([
$teacher['id']
]);

$courses =
$stmt->fetchAll(PDO::FETCH_ASSOC);

/*
المقرر المختار
*/

$selectedCourse =
$_GET['course_id'] ?? '';

$classes = [];

if(!empty($selectedCourse))
{
    $stmt = $db->prepare(

    "SELECT DISTINCT

    cl.id,
    cl.class_name

    FROM course_assignments ca

    INNER JOIN classes cl
    ON ca.class_id=cl.id

    WHERE ca.teacher_id=?
    AND ca.course_id=?

    ORDER BY cl.class_name"

    );

    $stmt->execute([

        $teacher['id'],
        $selectedCourse

    ]);

    $classes =
    $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../../app/views/layouts/header.php';

?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">   
<h2>

📅 Create Calendar Event

</h2>

<div class="card">

<div class="card-body">

<form method="GET">

<div class="mb-3">

<label class="form-label">

Course

</label>

<select
name="course_id"
class="form-select"
onchange="this.form.submit()">

<option value="">

Select Course

</option>

<?php foreach($courses as $course): ?>

<option
value="<?= $course['id']; ?>"

<?= ($selectedCourse == $course['id'])
? 'selected'
: ''; ?>>

<?= htmlspecialchars(
$course['course_name']
); ?>

</option>

<?php endforeach; ?>

</select>

</div>

</form>

<hr>

<form
method="POST"
action="store.php">

<input
type="hidden"
name="course_id"
value="<?= $selectedCourse; ?>">

<div class="mb-3">

<label class="form-label">

Event Title

</label>

<input
type="text"
name="title"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Event Description

</label>

<textarea
name="description"
rows="5"
class="form-control"></textarea>

</div>

<div class="mb-3">

<label class="form-label">

Event Type

</label>

<select
name="event_type"
class="form-select"
required>

<option value="Exam">

🧪 Exam

</option>

<option value="Assignment">

📝 Assignment

</option>

<option value="Announcement">

📢 Announcement

</option>

<option value="Event">

🎉 School Event

</option>

<option value="Holiday">

🏖 Holiday

</option>

</select>

</div>

<div class="row">

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

Start Date

</label>

<input
type="date"
name="start_date"
class="form-control"
required>

</div>

</div>

<div class="col-md-6">

<div class="mb-3">

<label class="form-label">

End Date

</label>

<input
type="date"
name="end_date"
class="form-control"
required>

</div>

</div>

</div>

<div class="mb-3">

<label class="form-label">

Target Classes

</label>

<select
name="class_ids[]"
multiple
class="form-select"
size="8"
required>

<?php foreach($classes as $class): ?>

<option
value="<?= $class['id']; ?>">

<?= htmlspecialchars(
$class['class_name']
); ?>

</option>

<?php endforeach; ?>

</select>

<small class="text-muted">

Hold CTRL to select multiple classes

</small>

</div>

<button
type="submit"
class="btn btn-primary">

Publish Event

</button>

<a
href="index.php"
class="btn btn-secondary">

Back

</a>

</form>

</div>

</div>

</div>

<?php include '../../app/views/layouts/footer.php'; ?>
