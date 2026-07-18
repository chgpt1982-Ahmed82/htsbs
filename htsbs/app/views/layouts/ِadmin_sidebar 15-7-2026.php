<style>

/* =========================================================
   Admin Sidebar
   نفس تنسيق student_sidebar.php
   الأيقونة أولاً من اليمين ثم النص
========================================================= */

#adminMenu{
    direction:rtl;
    text-align:right;
}

/* جميع الروابط */

#adminMenu .accordion-button,
#adminMenu>.nav-link,
#adminMenu .accordion-body .nav-link{

    display:flex !important;

    flex-direction:row !important;

    justify-content:flex-start !important;

    align-items:center !important;

    gap:.65rem !important;

    direction:rtl !important;

    text-align:right !important;

    white-space:nowrap;

    font-weight:500;

}

/* الأيقونة */

#adminMenu .menu-icon{

    order:-1 !important;

    width:1.6rem;

    min-width:1.6rem;

    text-align:center;

    font-size:1.1rem;

    line-height:1;

    flex-shrink:0;

}

/* سهم الـ Accordion */

#adminMenu .accordion-button::after{

    margin-inline-start:auto;

    margin-inline-end:0;

}

/* إزالة خلفية Bootstrap */

#adminMenu .accordion-button{

    background:#fff;

    color:#333;

    box-shadow:none;

}

/* عند الفتح */

#adminMenu .accordion-button:not(.collapsed){

    background:#eef4ff;

    color:#0d6efd;

    box-shadow:none;

}

/* الروابط */

#adminMenu .nav-link{

    color:#444;

    border-radius:8px;

    transition:.25s;

}

/* Hover */

#adminMenu .nav-link:hover{

    background:#f5f8ff;

    color:#0d6efd;

}

/* الرابط النشط */

#adminMenu .nav-link.active{

    background:#0d6efd;

    color:#fff;

    font-weight:700;

}

/* الأيقونة داخل الرابط النشط */

#adminMenu .nav-link.active .menu-icon{

    color:#fff !important;

}

</style>
<?php

function isActive($match, $currentPage)
{
    return !empty($match) &&
           strpos($currentPage, $match) !== false;
}

function renderAdminMenu($currentPage)
{
?>

<div class="accordion" id="adminMenu">

    <!-- الرئيسية -->

    <a
    class="nav-link p-3 border-bottom <?= isActive('admin/dashboard.php',$currentPage) ? 'active' : ''; ?>"
    href="<?= BASE_URL ?>/admin/dashboard.php">

        <span class="menu-icon">🏠</span>

        لوحة التحكم

    </a>

    <!-- الإدارة الأكاديمية -->

    <div class="accordion-item">

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

        <div
        id="academicMenu"
        class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a
                class="nav-link p-2 <?= isActive('admin/courses/',$currentPage) ? 'active' : ''; ?>"
                href="<?= BASE_URL ?>/admin/courses/index.php">

                    <span class="menu-icon">📘</span>

                    المقررات الدراسية

                </a>

                <a
                class="nav-link p-2 <?= isActive('admin/course_assignments/',$currentPage) ? 'active' : ''; ?>"
                href="<?= BASE_URL ?>/admin/course_assignments/index.php">

                    <span class="menu-icon">🔗</span>

                    إسناد المقررات

                </a>

                <a
                class="nav-link p-2 <?= isActive('admin/departments/',$currentPage) ? 'active' : ''; ?>"
                href="<?= BASE_URL ?>/admin/departments/index.php">

                    <span class="menu-icon">🏢</span>

                    الأقسام

                </a>

                <a
                class="nav-link p-2 <?= isActive('admin/classes/',$currentPage) ? 'active' : ''; ?>"
                href="<?= BASE_URL ?>/admin/classes/index.php">

                    <span class="menu-icon">🏫</span>

                    الصفوف الدراسية

                </a>

            </div>

        </div>

    </div
    <!-- =========================================================
     إدارة المستخدمين
========================================================= -->

<div class="accordion-item">

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

    <div
    id="usersMenu"
    class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <!-- المعلمون -->

            <a
            class="nav-link p-2 <?= isActive('admin/teachers/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/teachers/index.php">

                <span class="menu-icon">👨‍🏫</span>

                المعلمون

            </a>

            <!-- الطلاب -->

            <a
            class="nav-link p-2 <?= isActive('admin/students/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/students/index.php">

                <span class="menu-icon">👨‍🎓</span>

                الطلاب

            </a>

            <!-- أولياء الأمور -->

            <a
            class="nav-link p-2 <?= isActive('admin/parents/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/parents/index.php">

                <span class="menu-icon">👨‍👩‍👧</span>

                أولياء الأمور

            </a>

        </div>

    </div>

</div>
<!-- =========================================================
     التقارير والسجلات
========================================================= -->

<div class="accordion-item">

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

    <div
    id="reportsMenu"
    class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <!-- التقارير -->

            <a
            class="nav-link p-2 <?= isActive('admin/reports.php',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/reports.php">

                <span class="menu-icon">📈</span>

                التقارير

            </a>

            <!-- سجل النشاط -->

            <a
            class="nav-link p-2 <?= isActive('admin/logs/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/logs/index.php">

                <span class="menu-icon">📜</span>

                سجل النشاط

            </a>

            <!-- الرسائل -->

            <a
            class="nav-link p-2 <?= isActive('messages/inbox.php',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/messages/inbox.php">

                <span class="menu-icon">✉️</span>

                الرسائل

            </a>

        </div>

    </div>

</div>
<!-- =========================================================
     الشهادات والسجل الأكاديمي
========================================================= -->

<div class="accordion-item">

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

    <div
    id="certificateMenu"
    class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <!-- الشهادات -->

            <a
            class="nav-link p-2 <?= isActive('admin/certificates/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/certificates/generate.php">

                <span class="menu-icon">🏆</span>

                الشهادات

            </a>

            <!-- السجل الأكاديمي -->

            <a
            class="nav-link p-2 <?= isActive('admin/transcript/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/transcript/settings.php">

                <span class="menu-icon">🗂️</span>

                إعدادات السجل الأكاديمي

            </a>

        </div>

    </div>

</div>
<!-- =========================================================
     إدارة النظام
========================================================= -->

<div class="accordion-item">

    <h2 class="accordion-header">

        <button
        class="accordion-button collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#systemMenu">

            <span class="menu-icon">⚙️</span>

            إدارة النظام

        </button>

    </h2>

    <div
    id="systemMenu"
    class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <!-- المستخدمون -->

            <a
            class="nav-link p-2 <?= isActive('admin/users/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/users/index.php">

                <span class="menu-icon">👤</span>

                المستخدمون

            </a>

            <!-- الأدوار والصلاحيات -->

            <a
            class="nav-link p-2 <?= isActive('admin/roles/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/roles/index.php">

                <span class="menu-icon">🔐</span>

                الأدوار والصلاحيات

            </a>

            <!-- النسخ الاحتياطي -->

            <a
            class="nav-link p-2 <?= isActive('admin/backup/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/backup/index.php">

                <span class="menu-icon">💾</span>

                النسخ الاحتياطي

            </a>

            <!-- إعدادات البريد -->

            <a
            class="nav-link p-2 <?= isActive('admin/mail/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/mail/index.php">

                <span class="menu-icon">📧</span>

                إعدادات البريد

            </a>

            <!-- إعدادات النظام -->

            <a
            class="nav-link p-2 <?= isActive('admin/settings/',$currentPage) ? 'active' : ''; ?>"
            href="<?= BASE_URL ?>/admin/settings/index.php">

                <span class="menu-icon">🛠️</span>

                إعدادات النظام

            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     تسجيل الخروج
========================================================= -->

<a
class="nav-link p-3 text-danger"
href="<?= BASE_URL ?>/core/logout.php">

    <span class="menu-icon">🚪</span>

    تسجيل الخروج

</a>
<?php
}
?>
