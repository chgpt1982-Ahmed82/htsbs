<?php

$currentPage = $_SERVER['PHP_SELF'];

function isAdminActive($match, $currentPage)
{
    return strpos($currentPage, $match) !== false;
}

function renderAdminMenu($currentPage)
{
?>

<style>
#adminMenu {
    direction: rtl;
    text-align: right;
}

/* كل الروابط + أزرار الأكورديون: الأيقونة أولاً من اليمين */
#adminMenu .accordion-button,
#adminMenu > a.nav-link,
#adminMenu .accordion-body a.nav-link {
    display: flex !important;
    flex-direction: row !important;
    justify-content: flex-start !important;
    align-items: center !important;
    gap: .65rem !important;
    direction: rtl !important;
    text-align: right !important;
    white-space: nowrap;
}

/* الأيقونة: دائماً أول عنصر (أقصى اليمين) */
#adminMenu .menu-icon {
    order: -1 !important;
    flex-shrink: 0;
    width: 1.5rem;
    text-align: center;
    font-size: 1.05rem;
    line-height: 1;
}

/* سهم الأكورديون: يبقى في أقصى اليسار */
#adminMenu .accordion-button::after {
    margin-inline-start: auto !important;
    margin-inline-end: 0 !important;
    order: 1;
}
</style>

<div class="accordion border-0" id="adminMenu">

    <a class="nav-link p-3 border-bottom <?= isAdminActive('admin/dashboard.php',$currentPage) ? 'active fw-bold' : ''; ?>"
       href="<?= BASE_URL ?>/admin/dashboard.php">

        <span class="menu-icon">🏠</span>
        لوحة التحكم

    </a>

    <!-- الإدارة الأكاديمية -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#academicMenu">

                <span class="menu-icon">📚</span>
                الإدارة الأكاديمية

            </button>

        </h2>

        <div id="academicMenu" class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2 <?= isAdminActive('admin/courses/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/courses/index.php">

                    <span class="menu-icon">📘</span>
                    المقررات الدراسية

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/course_assignments/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/course_assignments/index.php">

                    <span class="menu-icon">🔗</span>
                    إسناد المقررات

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/departments/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/departments/index.php">

                    <span class="menu-icon">🏢</span>
                    الأقسام

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/classes/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/classes/index.php">

                    <span class="menu-icon">🏫</span>
                    الصفوف الدراسية

                </a>

            </div>

        </div>

    </div>

    <!-- إدارة المستخدمين -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#usersMenu">

                <span class="menu-icon">👥</span>
                إدارة المستخدمين

            </button>

        </h2>

        <div id="usersMenu" class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2 <?= isAdminActive('admin/teachers/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/teachers/index.php">

                    <span class="menu-icon">👨‍🏫</span>
                    المعلمون

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/students/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/students/index.php">

                    <span class="menu-icon">👨‍🎓</span>
                    الطلاب

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/parents/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/parents/index.php">

                    <span class="menu-icon">👨‍👩‍👧</span>
                    أولياء الأمور

                </a>

            </div>

        </div>

    </div>

    <!-- التقارير والسجلات -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#reportsMenu">

                <span class="menu-icon">📊</span>
                التقارير والسجلات

            </button>

        </h2>

        <div id="reportsMenu" class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2 <?= isAdminActive('admin/reports.php',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/reports.php">

                    <span class="menu-icon">📈</span>
                    التقارير

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/logs/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/logs/index.php">

                    <span class="menu-icon">📜</span>
                    سجل النشاط

                </a>

                <a class="nav-link p-2 <?= isAdminActive('messages/inbox.php',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/messages/inbox.php">

                    <span class="menu-icon">✉️</span>
                    الرسائل

                </a>

            </div>

        </div>

    </div>

    <!-- الشهادات والسجل الأكاديمي -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#certificateMenu">

                <span class="menu-icon">🎓</span>
                الشهادات والسجل الأكاديمي

            </button>

        </h2>

        <div id="certificateMenu" class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2 <?= isAdminActive('admin/certificates/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/certificates/generate.php">

                    <span class="menu-icon">🏆</span>
                    الشهادات

                </a>

                <a class="nav-link p-2 <?= isAdminActive('admin/transcript/',$currentPage) ? 'active fw-bold' : ''; ?>"
                   href="<?= BASE_URL ?>/admin/transcript/settings.php">

                    <span class="menu-icon">🗂️</span>
                    إعدادات السجل الأكاديمي

                </a>

            </div>

        </div>

    </div>

    <a class="nav-link p-3 text-danger border-top"
       href="<?= BASE_URL ?>/core/logout.php">

        <span class="menu-icon">🚪</span>
        تسجيل الخروج

    </a>

</div>

<?php
}
?>

<!-- Mobile Button -->

<button
class="btn btn-primary d-lg-none m-2"
type="button"
data-bs-toggle="offcanvas"
data-bs-target="#adminSidebar">

    <i class="bi bi-list"></i>
    القائمة

</button>

<!-- Desktop Sidebar -->

<div class="sidebar-fixed d-none d-lg-block">

    <div class="student-sidebar h-100">

        <?php renderAdminMenu($currentPage); ?>

    </div>

</div>

<!-- Mobile Sidebar -->

<div
class="offcanvas offcanvas-end student-mobile-sidebar"
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

        <?php renderAdminMenu($currentPage); ?>

    </div>

</div>
