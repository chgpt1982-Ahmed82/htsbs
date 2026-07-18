<?php

$currentPage = $_SERVER['PHP_SELF'];

/*
=====================================================================
قائمة لوحة الأدمن الجانبية
- الأيقونة أولاً (يمين) ثم النص — مطابق لقائمتي المعلم والطالب
- تعمل على سطح المكتب والتابلت والجوال (offcanvas)
=====================================================================
*/

$menuItems = [

[
'url' => BASE_URL . '/admin/dashboard.php',
'icon' => 'bi-speedometer2',
'color' => 'text-info',
'title' => 'لوحة التحكم',
'match' => 'admin/dashboard.php'
],

[
'url' => BASE_URL . '/messages/inbox.php',
'icon' => 'bi-envelope-fill',
'color' => 'text-warning',
'title' => 'الرسائل',
'match' => 'messages/inbox.php'
],

[
'url' => BASE_URL . '/admin/courses/index.php',
'icon' => 'bi-book-fill',
'color' => 'text-primary',
'title' => 'المقررات الدراسية',
'match' => 'admin/courses/'
],

[
'url' => BASE_URL . '/admin/course_assignments/index.php',
'icon' => 'bi-link-45deg',
'color' => 'text-success',
'title' => 'إسناد المقررات',
'match' => 'admin/course_assignments/'
],

[
'url' => BASE_URL . '/admin/departments/index.php',
'icon' => 'bi-diagram-3-fill',
'color' => 'text-info',
'title' => 'الأقسام',
'match' => 'admin/departments/'
],

[
'url' => BASE_URL . '/admin/classes/index.php',
'icon' => 'bi-building',
'color' => 'text-warning',
'title' => 'الصفوف الدراسية',
'match' => 'admin/classes/'
],

[
'url' => BASE_URL . '/admin/teachers/index.php',
'icon' => 'bi-person-workspace',
'color' => 'text-success',
'title' => 'المعلمون',
'match' => 'admin/teachers/'
],

[
'url' => BASE_URL . '/admin/students/index.php',
'icon' => 'bi-people-fill',
'color' => 'text-primary',
'title' => 'الطلاب',
'match' => 'admin/students/'
],

[
'url' => BASE_URL . '/admin/parents/index.php',
'icon' => 'bi-person-hearts',
'color' => 'text-danger',
'title' => 'أولياء الأمور',
'match' => 'admin/parents/'
],

[
'url' => BASE_URL . '/admin/reports.php',
'icon' => 'bi-bar-chart-line-fill',
'color' => 'text-success',
'title' => 'التقارير',
'match' => 'admin/reports.php'
],

[
'url' => BASE_URL . '/admin/transcript/settings.php',
'icon' => 'bi-mortarboard-fill',
'color' => 'text-warning',
'title' => 'إعدادات السجل الأكاديمي',
'match' => 'admin/transcript/'
],

[
'url' => BASE_URL . '/admin/certificates/generate.php',
'icon' => 'bi-award-fill',
'color' => 'text-info',
'title' => 'الشهادات',
'match' => 'admin/certificates/'
],

[
'url' => BASE_URL . '/admin/logs/index.php',
'icon' => 'bi-journal-text',
'color' => 'text-secondary',
'title' => 'سجل النشاط',
'match' => 'admin/logs/'
],

[
'url' => BASE_URL . '/core/logout.php',
'icon' => 'bi-box-arrow-right',
'color' => 'text-danger',
'title' => 'تسجيل الخروج',
'match' => ''
]

];

?>

<style>
/*
====================================
اتجاه عناصر القائمة: الأيقونة أولاً ثم العنوان
نستخدم !important للتغلب على قاعدة flex-direction: row-reverse
الموجودة في assets/css/lms.css والتي تدفع الأيقونة لنهاية السطر
تعمل على: سطح المكتب + التابلت + الجوال (offcanvas)
====================================
*/
.admin-menu {
    direction: rtl;
    text-align: right;
}
.admin-menu .nav-link {
    display: flex !important;
    flex-direction: row !important;      /* وليس row-reverse */
    justify-content: flex-start !important;
    align-items: center !important;
    gap: .5rem !important;
    white-space: nowrap;
    direction: rtl !important;
    text-align: right !important;
}
.admin-menu .nav-link .menu-icon {
    order: -1 !important;                /* الأيقونة دائماً في بداية السطر (اليمين) */
    flex-shrink: 0;
    font-size: 1.05rem;
    line-height: 1;
    width: 1.4rem;                       /* عرض ثابت = محاذاة النصوص عمودياً */
    text-align: center;
}
</style>

<!-- زر القائمة للجوال -->

<button
class="btn btn-primary d-lg-none m-2"
type="button"
data-bs-toggle="offcanvas"
data-bs-target="#adminSidebar">

☰ القائمة

</button>

<!-- Sidebar Desktop -->

<div class="col-lg-2 d-none d-lg-block p-0 sidebar-container">

<div class="student-sidebar">

<ul class="nav flex-column admin-menu">

<?php foreach($menuItems as $item): ?>

<?php

$isActive =
!empty($item['match']) &&
strpos($currentPage,$item['match']) !== false;

?>

<li class="nav-item">

<a
class="nav-link <?= $isActive ? 'active fw-bold' : ''; ?>"
href="<?= $item['url']; ?>">

<i class="bi <?= $item['icon']; ?> <?= $item['color']; ?> menu-icon"></i>

<span>
<?= $item['title']; ?>
</span>

</a>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>

<!-- Sidebar Mobile -->

<div
class="offcanvas offcanvas-end text-bg-dark"
tabindex="-1"
id="adminSidebar">

<div class="offcanvas-header">

<h5 class="offcanvas-title">

القائمة الرئيسية

</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="offcanvas"></button>

</div>

<div class="offcanvas-body">

<ul class="nav flex-column admin-menu">

<?php foreach($menuItems as $item): ?>

<?php

$isActive =
!empty($item['match']) &&
strpos($currentPage,$item['match']) !== false;

?>

<li class="nav-item mb-1">

<a
class="nav-link <?= $isActive ? 'active fw-bold' : ''; ?>"
href="<?= $item['url']; ?>">

<i class="bi <?= $item['icon']; ?> <?= $item['color']; ?> menu-icon"></i>

<span>
<?= $item['title']; ?>
</span>

</a>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>
