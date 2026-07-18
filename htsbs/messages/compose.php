<?php

session_start();

require_once '../config/config.php';
require_once '../config/database.php';

$db = (new Database())->connect();

$roleId = $_SESSION['role_id'];
$userId = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| بيانات القوائم
|--------------------------------------------------------------------------
*/

$teachers = [];
$students = [];
$parents  = [];
$admins   = [];
$classes  = [];

/*
|--------------------------------------------------------------------------
| الطالب
|--------------------------------------------------------------------------
*/

if($roleId == 3)
{
    $stmt = $db->prepare("
    SELECT class_id
    FROM students
    WHERE user_id=?
    ");

    $stmt->execute([$userId]);

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if($student)
    {
        $stmt = $db->prepare("
        SELECT DISTINCT

            u.id,
            u.full_name,
            c.course_name

        FROM course_assignments ca

        INNER JOIN teachers t
            ON ca.teacher_id=t.id

        INNER JOIN users u
            ON t.user_id=u.id

        INNER JOIN courses c
            ON ca.course_id=c.id

        WHERE ca.class_id=?

        ORDER BY c.course_name
        ");

        $stmt->execute([
            $student['class_id']
        ]);

        $teachers =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

/*
|--------------------------------------------------------------------------
| المعلم
|--------------------------------------------------------------------------
*/

if($roleId == 2)
{
    $stmt = $db->prepare("
    SELECT id
    FROM teachers
    WHERE user_id=?
    ");

    $stmt->execute([$userId]);

    $teacher =
    $stmt->fetch(PDO::FETCH_ASSOC);

    if($teacher)
    {
        /*
        طلاب صفوف المعلم
        */

              $stmt = $db->prepare("
        SELECT DISTINCT
        
        
        u.id,
        u.full_name,
        s.student_number,
        c.class_name
        
        
        FROM students s
        
        INNER JOIN users u
        ON s.user_id = u.id
        
        INNER JOIN classes c
        ON s.class_id = c.id
        
        INNER JOIN course_assignments ca
        ON s.class_id = ca.class_id
        
        WHERE ca.teacher_id = ?
        
        ORDER BY
        c.class_name ASC,
        CAST(s.student_number AS UNSIGNED) ASC
        ");

        $stmt->execute([
            $teacher['id']
        ]);

        $students =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

        /*
        صفوف المعلم
        */

        $stmt = $db->prepare("
        SELECT DISTINCT

            cl.id,
            cl.class_name

        FROM classes cl

        INNER JOIN course_assignments ca
            ON cl.id=ca.class_id

        WHERE ca.teacher_id=?

        ORDER BY cl.class_name
        ");

        $stmt->execute([
            $teacher['id']
        ]);

        $classes =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    جميع المعلمين
    */

          $stmt = $db->prepare("
        SELECT DISTINCT
        
            u.id,
            u.full_name
        
        FROM teachers t
        
        INNER JOIN users u
            ON u.id = t.user_id
        
        WHERE t.user_id != ?
        
        ORDER BY u.full_name
        ");
        
        $stmt->execute([
            $userId
        ]);
        
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    الإدارة
    */

    $stmt = $db->prepare("
    SELECT

        id,
        full_name

    FROM users

    WHERE role_id=1

    ORDER BY full_name
    ");

    $stmt->execute();

    $admins =
    $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| الإدارة
|--------------------------------------------------------------------------
*/

if($roleId == 1)
{
    $students =
    $db->query("
    SELECT id,full_name
    FROM users
    WHERE role_id=3
    ORDER BY full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $teachers =
    $db->query("
    SELECT id,full_name
    FROM users
    WHERE role_id=2
    ORDER BY full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $parents =
    $db->query("
    SELECT id,full_name
    FROM users
    WHERE role_id=4
    ORDER BY full_name
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| ولي الأمر
|--------------------------------------------------------------------------
*/

if($roleId == 4)
{
    $stmt = $db->prepare("
    SELECT p.id
    FROM parents p
    WHERE p.user_id=?
    ");

    $stmt->execute([$userId]);

    $parent =
    $stmt->fetch(PDO::FETCH_ASSOC);

    if($parent)
    {
        $stmt = $db->prepare("
        SELECT DISTINCT

            u.id,
            u.full_name

        FROM parent_student ps

        INNER JOIN students s
            ON ps.student_id=s.id

        INNER JOIN course_assignments ca
            ON s.class_id=ca.class_id

        INNER JOIN teachers t
            ON ca.teacher_id=t.id

        INNER JOIN users u
            ON t.user_id=u.id

        WHERE ps.parent_id=?

        ORDER BY u.full_name
        ");

        $stmt->execute([
            $parent['id']
        ]);

        $teachers =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmt = $db->query("
    SELECT id,full_name
    FROM users
    WHERE role_id=1
    ");

    $admins =
    $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../app/views/layouts/header.php';
?>
<div class="container-fluid">

<div class="row flex-lg-row-reverse">



   

<?php

switch($_SESSION['role_id'])
{
    case 1:
        include '../app/views/layouts/sidebar.php';
    break;

    case 2:
        include '../app/views/layouts/teacher_sidebar.php';
    break;

    case 3:
         include '../app/views/layouts/student_sidebar.php';
    break;

    case 4:
        include '../app/views/layouts/parent_sidebar.php';
    break;
}

?>
<div class="main-content">

<div class="card shadow" dir="rtl">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">
<i class="bi bi-envelope-fill"></i>
إرسال رسالة
</h4>

</div>

<div class="card-body">

<form method="POST" action="send.php">

<?php if($roleId != 3): ?>

<div class="mb-3">

<label class="form-label">

نوع الإرسال

</label>

<select
name="send_type"
id="send_type"
class="form-select">

<?php if($roleId == 2): ?>

<option value="student">
طالب محدد
</option>

<option value="class">
جميع طلاب صف
</option>

<option value="all_my_students">
جميع طلاب صفوفي
</option>

<option value="teacher">
معلم محدد
</option>

<option value="teachers">
جميع المعلمين
</option>
<option value="admin">
إداري محدد
</option>

<option value="admins">
جميع الإدارة
</option>
<?php elseif($roleId == 1): ?>

<option value="student">
طالب محدد
</option>

<option value="teacher">
معلم محدد
</option>

<option value="parent">
ولي أمر محدد
</option>

<option value="admin">
إداري محدد
</option>

<option value="all_students">
جميع الطلاب
</option>

<option value="all_teachers">
جميع المعلمين
</option>

<option value="all_parents">
جميع أولياء الأمور
</option>

<option value="admins">
جميع الإدارة
</option>

<?php endif; ?>

</select>

</div>

<?php endif; ?>

<!-- قائمة المستلمين -->

<div
class="mb-3"
id="receiver_container">

<label class="form-label">

المستلم

</label>

<select
name="receiver_id"
id="receiver_id"
class="form-select">

<option value="">

اختر المستلم

</option>

<?php if($roleId == 3): ?>

<?php foreach($teachers as $teacher): ?>

<option value="<?= $teacher['id']; ?>">

<?= htmlspecialchars($teacher['course_name']); ?>

-

<?= htmlspecialchars($teacher['full_name']); ?>

</option>

<?php endforeach; ?>

<?php endif; ?>

</select>

</div>

<!-- قائمة الصفوف -->

<div
class="mb-3 d-none"
id="class_container">

<label class="form-label">

الصف

</label>

<select
name="class_id"
class="form-select">

<option value="">

اختر الصف

</option>

<?php foreach($classes as $class): ?>

<option value="<?= $class['id']; ?>">

<?= htmlspecialchars(
$class['class_name']
); ?>

</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

الموضوع

</label>

<input
type="text"
name="subject"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

الرسالة

</label>

<textarea
name="message"
rows="8"
class="form-control"
required></textarea>

</div>

<button
type="submit"
class="btn btn-primary">

<i class="bi bi-send-fill"></i>

إرسال

</button>

<a
href="inbox.php"
class="btn btn-secondary">

رجوع

</a>

</form>

</div>

</div>

</div>

</div>

</div>


<script>

const studentsData =
<?= json_encode($students, JSON_UNESCAPED_UNICODE); ?>;

const teachersData =
<?= json_encode($teachers, JSON_UNESCAPED_UNICODE); ?>;

const parentsData =
<?= json_encode($parents, JSON_UNESCAPED_UNICODE); ?>;

const adminsData =
<?= json_encode($admins, JSON_UNESCAPED_UNICODE); ?>;

const receiverSelect =
document.getElementById('receiver_id');

const receiverContainer =
document.getElementById('receiver_container');

const classContainer =
document.getElementById('class_container');

function fillReceivers(data)
{
receiverSelect.innerHTML =
'<option value="">اختر المستلم</option>';


data.forEach(item =>
{
    let text = '';

    if(item.class_name)
    {
        text += item.class_name + ' | ';
    }

    if(item.student_number)
    {
        text += item.student_number + ' | ';
    }

    text += item.full_name;

    receiverSelect.innerHTML += `
    <option value="${item.id}">
        ${text}
    </option>`;
});


}

const sendType =
document.getElementById('send_type');

if(sendType)
{
    function updateReceivers()
    {
        const type = sendType.value;

        classContainer.classList.add('d-none');
        receiverContainer.classList.remove('d-none');

        switch(type)
        {
            case 'student':
                fillReceivers(studentsData);
            break;

            case 'teacher':
                fillReceivers(teachersData);
            break;
            case 'teachers':
                receiverContainer.classList.add('d-none');
            break;

            case 'parent':
                fillReceivers(parentsData);
            break;

            case 'admin':
                fillReceivers(adminsData);
            break;
            case 'admins':
                receiverContainer.classList.add('d-none');
            break;

            case 'class':
                receiverContainer.classList.add('d-none');
                classContainer.classList.remove('d-none');
            break;

            case 'all_students':
            case 'all_teachers':
            case 'all_parents':
            case 'all_my_students':
            case 'teachers':
            case 'admins':

                receiverContainer.classList.add('d-none');
            break;
        }
    }

    updateReceivers();

    sendType.addEventListener(
        'change',
        updateReceivers
    );
}

</script>


<?php include '../app/views/layouts/footer.php'; ?>