<!DOCTYPE html>
<html lang="ar" >

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>تسجيل الدخول</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap"
rel="stylesheet">

<link rel="stylesheet" href="assets/css/login.css">

</head>

<body>
<div class="login-wrapper">

<div class="login-card">

<div class="login-logo">

<h2>قسم الحاسب الآلي</h2>

<p>نظام إدارة التعلم الإلكتروني</p>

</div>

<form method="POST" action="/login_process.php">
<div class="mb-3">
<?php if (isset($_GET['error'])): ?>

<div class="alert alert-danger alert-dismissible fade show text-center mb-4">
<i class="bi bi-exclamation-triangle-fill me-2"></i>
<?php

switch ($_GET['error']) {

    case 'invalid':
        echo 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        break;

    case 'inactive':
        echo 'تم إيقاف هذا الحساب، يرجى التواصل مع الإدارة.';
        break;

    case 'role':
        echo 'صلاحية المستخدم غير صحيحة.';
        break;

    case 'user':
        echo 'يرجى إدخال البريد الإلكتروني.';
        break;

    default:
        echo 'حدث خطأ أثناء تسجيل الدخول.';
}

?>

<button
    type="button"
    class="btn-close"
    data-bs-dismiss="alert">
</button>

</div>

<?php endif; ?>
<label class="form-label d-block text-center">
البريد الإلكتروني
</label>

<input
type="email"
name="email"
class="form-control text-center"
required>

</div>

<div class="mb-4">

<label class="form-label d-block text-center">

كلمة المرور
</label>

<input
type="password"
name="password"
class="form-control text-center"
required>

</div>

<button
type="submit"
class="btn-login">

تسجيل الدخول

</button>

</form>

<div class="login-footer">

© Ahmed Alsaegh 2026

</div>

</div>

</div>

