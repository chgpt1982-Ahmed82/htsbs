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
#teacherMenu .accordion-button,
#teacherMenu > a.nav-link {
    display: flex;
    align-items: center;
    gap: .5rem;
    white-space: nowrap;
}
#teacherMenu .accordion-button i,
#teacherMenu > a.nav-link i {
    flex-shrink: 0;
    font-size: 1rem;
    line-height: 1;
}
#teacherMenu .accordion-body a.nav-link {
    display: flex;
    align-items: center;
    gap: .5rem;
    white-space: nowrap;
}
</style>

<div class="accordion border-0" id="teacherMenu">

    <a class="nav-link p-3 border-bottom <?= isTeacherActive('teacher/dashboard.php',$currentPage) ? 'active fw-bold' : ''; ?>"
       href="<?= BASE_URL ?>/teacher/dashboard.php">

        <i class="bi bi-speedometer2 "></i>
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

                <i class="bi bi-megaphone-fill "></i>
                التواصل

            </button>

        </h2>

        <div id="teacherCommunication"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/messages/inbox.php">

                    الرسائل

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/announcements/index.php">

                    الإعلانات

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/behavior/index.php">

                    السلوك والانضباط

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

            <i class="bi bi-robot "></i>
            التحضير الذكي

        </button>

    </h2>

    <div
        id="lessonPlanner"
        class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/lesson_planner/index.php">

                <i class="bi bi-journal-richtext "></i>

                تحضيراتي

            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/lesson_planner/create.php">

                <i class="bi bi-plus-circle "></i>

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

            <i class="bi bi-diagram-3 "></i>
            التخطيط العميق

        </button>

    </h2>

    <div
        id="lessonPlanner2"
        class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/deep_lesson_planner/index.php">

                <i class="bi bi-diagram-3 "></i>

                جميع المخططات

            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/deep_lesson_planner/create.php">

                <i class="bi bi-magic "></i>

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

                <i class="bi bi-person-workspace "></i>
                التدريس

            </button>

        </h2>

        <div id="teacherTeaching"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/courses.php">

                    مقرراتي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/classes.php">

                    صفوفي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/attendance/index.php">

                    الحضور والغياب

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/gradebook/index.php">

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

                <i class="bi bi-book-half "></i>
                الدروس

            </button>

        </h2>

        <div id="teacherLessons"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/lessons/index.php">

                    بنك الدروس

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/lessons/create.php">

                    إضافة درس

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/lessons/assign.php">

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

            <i class="bi bi-pencil-square "></i>
            الأنشطة

        </button>

    </h2>

    <div id="teacherActivities"
         class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/index.php">
                بنك الأنشطة
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/create.php">
                إضافة نشاط
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/assign.php">
                تعيين نشاط
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/activities/submissions.php">
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

            <i class="bi bi-journal-check "></i>
            الواجبات

        </button>

    </h2>

    <div id="teacherAssignments"
         class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/assignments/index.php">
               بنك الواجبات
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/assignments/create.php">
                إضافة واجب
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/assignments/submissions.php">
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

            <i class="bi bi-clipboard-check "></i>
            الاختبارات

        </button>

    </h2>

    <div id="teacherQuizzes"
         class="accordion-collapse collapse">

        <div class="accordion-body p-0">

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/index.php">
                بنك الاختبارات القصيرة
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/create.php">
                إضافة اختبار قصير
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/assign.php">
                تعيين اختبار قصير
            </a>

            <a class="nav-link p-2"
               href="<?= BASE_URL ?>/teacher/quizzes/results.php">
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

                <i class="bi bi-gear-fill "></i>
                الخدمات

            </button>

        </h2>

        <div id="teacherServices"
             class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/reports.php">

                    التقارير

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/calendar/index.php">

                    التقويم الأكاديمي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/teacher/question_bank/index.php">

                    بنك الأسئلة

                </a>

            </div>

        </div>

    </div>

    <a class="nav-link p-3 text-danger border-top"
       href="<?= BASE_URL ?>/core/logout.php">

        <i class="bi bi-box-arrow-right "></i>
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
