<?php

$currentPage = $_SERVER['PHP_SELF'];

function isTeacherActive($match, $currentPage)
{
    return strpos($currentPage, $match) !== false;
}

function renderTeacherMenu($currentPage)
{
?>

<style>
#teacherMenu {
    direction: rtl;
    text-align: right;
}
#teacherMenu .accordion-button,
#teacherMenu > a.nav-link,
#teacherMenu .accordion-body a.nav-link {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: .5rem;
    white-space: nowrap;
    direction: rtl;
    text-align: right;
}
#teacherMenu .menu-icon {
    flex-shrink: 0;
    font-size: 1rem;
    line-height: 1;
    order: -1;
}
#teacherMenu .accordion-button::after {
    margin-inline-start: auto;
    margin-inline-end: 0;
}
</style>

<div class="accordion border-0" id="teacherMenu">

    <a class="nav-link p-3 border-bottom <?= isTeacherActive('teacher/dashboard.php',$currentPage) ? 'active fw-bold' : ''; ?>"
       href="<?= BASE_URL ?>/teacher/dashboard.php">

        <span class="menu-icon">📊</span>
        لوحة التحكم

    </a>

    <!-- التواصل -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#teacherCommunication">

                <span class="menu-icon">📢</span>
                التواصل

            </button>

        </h2>

        <div id="teacherCommunication"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/messages/inbox.php">

                    <span class="menu-icon">✉️</span>
                    الرسائل

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/announcements/index.php">

                    <span class="menu-icon">📣</span>
                    الإعلانات

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/behavior/index.php">

                    <span class="menu-icon">⚖️</span>
                    السلوك والانضباط

                </a>

            </div>

        </div>

    </div>


    <!-- ======================================
    التعلم التفاعلي (LMS)
    ====================================== -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#teacherLms">

                <span class="menu-icon">🚀</span>
                التعلم التفاعلي

            </button>

        </h2>

        <div id="teacherLms"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/index.php">

                    <span class="menu-icon">🏠</span>
                    لوحة التعلم التفاعلي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/lessons.php">

                    <span class="menu-icon">🎬</span>
                    إدارة الدروس التفاعلية

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/activities_index.php">

                    <span class="menu-icon">🧩</span>
                    الأنشطة التفاعلية

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/projects.php">

                    <span class="menu-icon">📤</span>
                    تصحيح المشاريع

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/progress.php">

                    <span class="menu-icon">📈</span>
                    متابعة تقدم الطلبة

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/leaderboard.php">

                    <span class="menu-icon">🏅</span>
                    لوحة الصدارة

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/teacher/reports.php">

                    <span class="menu-icon">📑</span>
                    تقارير التعلم التفاعلي

                </a>

            </div>

        </div>

    </div>



<!-- ======================================
AI Lesson Planner
====================================== -->

<div class="accordion-item border-0">

    <h2 class="accordion-header">

        <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#lessonPlanner">

            <span class="menu-icon">🤖</span>
            التحضير الذكي

        </button>

    </h2>

    <div
        id="lessonPlanner"
        class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/lesson_planner/index.php">

                <span class="menu-icon">📄</span>
                تحضيراتي

            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/lesson_planner/create.php">

                <span class="menu-icon">➕</span>
                إنشاء تحضير جديد

            </a>

        </div>

    </div>

</div>

<!-- ======================================
Deep Lesson Planner
====================================== -->

<div class="accordion-item border-0">

    <h2 class="accordion-header">

        <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#lessonPlanner2">

            <span class="menu-icon">🧠</span>
            التخطيط العميق

        </button>

    </h2>

    <div
        id="lessonPlanner2"
        class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/deep_lesson_planner/index.php">

                <span class="menu-icon">🗺️</span>
                جميع المخططات

            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/deep_lesson_planner/create.php">

                <span class="menu-icon">🪄</span>
                إنشاء تخطيط جديد

            </a>

        </div>

    </div>

</div>



    <!-- التدريس -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#teacherTeaching">

                <span class="menu-icon">👨‍🏫</span>
                التدريس

            </button>

        </h2>

        <div id="teacherTeaching"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/courses.php">

                    <span class="menu-icon">📘</span>
                    مقرراتي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/classes.php">

                    <span class="menu-icon">🏫</span>
                    صفوفي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/attendance/index.php">

                    <span class="menu-icon">🗓️</span>
                    الحضور والغياب

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/gradebook/index.php">

                    <span class="menu-icon">📊</span>
                    سجل الدرجات

                </a>

            </div>

        </div>

    </div>

    <!-- الدروس -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#teacherLessons">

                <span class="menu-icon">📚</span>
                الدروس

            </button>

        </h2>

        <div id="teacherLessons"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/lessons/index.php">

                    <span class="menu-icon">📗</span>
                    بنك الدروس

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/lessons/create.php">

                    <span class="menu-icon">➕</span>
                    إضافة درس

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/lessons/assign.php">

                    <span class="menu-icon">📌</span>
                    تعيين الدروس

                </a>

            </div>

        </div>

    </div>


    <!-- الأنشطة -->

<div class="accordion-item border-0">

    <h2 class="accordion-header">

        <button
        class="accordion-button collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#teacherActivities">

            <span class="menu-icon">📝</span>
            الأنشطة

        </button>

    </h2>

    <div id="teacherActivities"
         class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/index.php">
                <span class="menu-icon">🗃️</span>
                بنك الأنشطة
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/create.php">
                <span class="menu-icon">➕</span>
                إضافة نشاط
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/assign.php">
                <span class="menu-icon">📌</span>
                تعيين نشاط
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/submissions.php">
                <span class="menu-icon">✅</span>
                حلول الأنشطة
            </a>

        </div>

    </div>

</div>

<!-- الواجبات -->

<div class="accordion-item border-0">

    <h2 class="accordion-header">

        <button
        class="accordion-button collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#teacherAssignments">

            <span class="menu-icon">📓</span>
            الواجبات

        </button>

    </h2>

    <div id="teacherAssignments"
         class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/assignments/index.php">
               <span class="menu-icon">🗄️</span>
               بنك الواجبات
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/assignments/create.php">
                <span class="menu-icon">➕</span>
                إضافة واجب
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/assignments/submissions.php">
                <span class="menu-icon">📥</span>
                تسليمات الطلاب
            </a>

        </div>

    </div>

</div>


<!-- الاختبارات القصيرة -->

<div class="accordion-item border-0">

    <h2 class="accordion-header">

        <button
        class="accordion-button collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#teacherQuizzes">

            <span class="menu-icon">📋</span>
            الاختبارات

        </button>

    </h2>

    <div id="teacherQuizzes"
         class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/index.php">
                <span class="menu-icon">❓</span>
                بنك الاختبارات القصيرة
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/create.php">
                <span class="menu-icon">➕</span>
                إضافة اختبار قصير
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/assign.php">
                <span class="menu-icon">📌</span>
                تعيين اختبار قصير
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/results.php">
                <span class="menu-icon">🏆</span>
                نتائج الاختبارات القصيرة
            </a>

        </div>

    </div>

</div>



    <!-- الخدمات -->

    <div class="accordion-item border-0">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#teacherServices">

                <span class="menu-icon">⚙️</span>
                الخدمات

            </button>

        </h2>

        <div id="teacherServices"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/reports.php">

                    <span class="menu-icon">📈</span>
                    التقارير

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/calendar/index.php">

                    <span class="menu-icon">📅</span>
                    التقويم الأكاديمي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/question_bank/index.php">

                    <span class="menu-icon">❔</span>
                    بنك الأسئلة

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
data-bs-target="#teacherSidebar">

    <i class="bi bi-list"></i>
    القائمة

</button>

<!-- Desktop Sidebar -->

<div class="sidebar-fixed d-none d-lg-block">

    <div class="student-sidebar h-100">

        <?php renderTeacherMenu($currentPage); ?>

    </div>

</div>

<!-- Mobile Sidebar -->

<div
class="offcanvas offcanvas-end student-mobile-sidebar"
tabindex="-1"
id="teacherSidebar">

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

        <?php renderTeacherMenu($currentPage); ?>

    </div>

</div>
