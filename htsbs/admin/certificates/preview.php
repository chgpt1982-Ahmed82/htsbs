<?php

session_start();

require_once '../../config/config.php';
require_once '../../app/models/Certificate.php';

$certificateModel =
new Certificate();

$id =
$_GET['id'] ?? 0;

$certificate =
$certificateModel->find($id);

if(!$certificate)
{
    die('Certificate Not Found');
}

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<title>

Certificate Preview

</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

body{
    background:#f5f7fa;
    font-family:'Tahoma',sans-serif;
}

.certificate{
    background:#fff;
    border:12px solid #0d6efd;
    border-radius:20px;
    padding:60px;
    margin:30px auto;
    max-width:1100px;
    min-height:750px;
    box-shadow:0 0 30px rgba(0,0,0,.15);
}

.header{
    text-align:center;
    margin-bottom:40px;
}

.logo{
    width:120px;
    margin-bottom:15px;
}

.ministry{
    font-size:28px;
    font-weight:bold;
    color:#0d6efd;
}

.school{
    font-size:24px;
    color:#444;
}

.title{
    text-align:center;
    font-size:42px;
    font-weight:bold;
    color:#198754;
    margin-top:40px;
    margin-bottom:40px;
}

.content{
    text-align:center;
    line-height:2.5;
}

.student-name{
    font-size:40px;
    color:#dc3545;
    font-weight:bold;
}

.grade{
    font-size:30px;
    color:#0d6efd;
    font-weight:bold;
}

.footer{
    margin-top:60px;
}

.signature{
    text-align:center;
    margin-top:50px;
}

.print-btn{
    text-align:center;
    margin:20px;
}

@media print{

.print-btn{
display:none;
}

body{
background:white;
}

.certificate{
box-shadow:none;
margin:0;
max-width:100%;
}

}

</style>

</head>

<body>

<div class="print-btn">

<button
onclick="window.print()"
class="btn btn-success">

🖨 Print Certificate

</button>

<a
href="generate.php"
class="btn btn-secondary">

Back

</a>
<a
href="download.php?id=<?= $certificate['id']; ?>"
class="btn btn-danger">

📄 Download PDF

</a>
</div>

<div class="certificate">

<div class="header">

<img
src="<?= BASE_URL ?>/assets/images/moe-bahrain.png"
class="logo"
alt="MOE Logo">

<div class="ministry">

وزارة التربية والتعليم

</div>

<div class="school">

مدرسة مدينة حمد الثانوية للبنين

</div>

</div>

<div class="title">

شهادة إنجاز أكاديمي

</div>

<div class="content">

<p style="font-size:24px;">

تشهد إدارة المدرسة بأن الطالب

</p>

<div class="student-name">

<?= htmlspecialchars(
$certificate['full_name']
); ?>

</div>

<p style="font-size:22px;">

الرقم الأكاديمي

<br>

<strong>

<?= htmlspecialchars(
$certificate['student_number']
); ?>

</strong>

</p>

<p style="font-size:24px;">

قد أتم متطلبات البرنامج الدراسي بنجاح

</p>

<p class="grade">

Final Grade :

<?= $certificate['final_grade']; ?>

</p>

<p class="grade">

GPA :

<?= $certificate['gpa']; ?>

</p>

<p style="font-size:20px;">

Certificate Number

<br>

<strong>

<?= $certificate['certificate_no']; ?>

</strong>

</p>

<p style="font-size:20px;">

Issue Date

<br>

<strong>

<?= $certificate['issue_date']; ?>

</strong>

</p>

</div>

<div class="footer row">

<div class="col-6 text-center">

---

<br>

مدير المدرسة

</div>

<div class="col-6 text-center">

---

<br>

ختم المدرسة

</div>

</div>

</div>

</body>

</html>
