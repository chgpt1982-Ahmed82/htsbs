<?php

$currentPage = $_SERVER['PHP_SELF'];

function isActive($match, $currentPage)
{
    return !empty($match) &&
    strpos($currentPage, $match) !== false;
}

?>

<!-- زر الجوال -->

<button
class="btn btn-primary d-lg-none m-2"
type="button"
data-bs-toggle="offcanvas"
data-bs-target="#studentSidebar">

<i class="bi bi-list"></i>
القائمة

</button>

<?php
function renderStudentMenu($currentPage)
{
?>

<style>
#studentMenu {
    direction: rtl;
    text-align: right;
}
#studentMenu .accordion-button,
#studentMenu > a.nav-link,
#studentMenu .accordion-body a.nav-link {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: .5rem;
    white-space: nowrap;
    direction: rtl;
    text-align: right;
}
#studentMenu .menu-icon {
    flex-shrink: 0;
    font-size: 1rem;
    line-height: 1;
    order: -1;
}
#studentMenu .accordion-button::after {
    margin-inline-start: auto;
    margin-inline-end: 0;
}
</style>

<div class="accordion" id="studentMenu">

    <!-- الرئيسية -->

    <a class="nav-link p-3 border-bottom <?= isActive('student/dashboard.php',$currentPage) ? 'active' : ''; ?>"
       href="<?= BASE_URL ?>/student/dashboard.php">

        <span class="menu-icon">🏠</span>

        الرئيسية

    </a>

  <!-- التعلم التفاعلي -->

    <div class="accordion-item">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#interactiveMenu">

                <span class="menu-icon">🚀</span>
                التعلم التفاعلي

            </button>

        </h2>

        <div
        id="interactiveMenu"
        class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/student/index.php">

                    <span class="menu-icon">🏠</span>
                    لوحتي التفاعلية

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/student/courses.php">

                    <span class="menu-icon">📘</span>
                    مقرراتي التفاعلية

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/student/stats.php">

                    <span class="menu-icon">📊</span>
                    إحصائياتي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/student/badges.php">

                    <span class="menu-icon">🏅</span>
                    شاراتي ونجومي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/student/leaderboard.php">

                    <span class="menu-icon">🏆</span>
                    لوحة الصدارة

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/lms/student/certificates.php">

                    <span class="menu-icon">🎓</span>
                    شهاداتي

                </a>

            </div>

        </div>

    </div>

    <!-- التعلم -->

    <div class="accordion-item">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#learningMenu">

                <span class="menu-icon">📚</span>
                التعلم

            </button>

        </h2>

        <div
        id="learningMenu"
        class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/lessons/index.php">

                    <span class="menu-icon">📖</span>
                    دروسي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/courses.php">

                    <span class="menu-icon">📘</span>
                    مقرراتي

                </a>

            </div>

        </div>

    </div>

    <!-- التقييمات -->

    <div class="accordion-item">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#evaluationMenu">

                <span class="menu-icon">📝</span>
                التقييمات

            </button>

        </h2>

        <div
        id="evaluationMenu"
        class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/activities/index.php">

                    <span class="menu-icon">🗃️</span>
                    الأنشطة

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/activities/results.php">

                    <span class="menu-icon">✅</span>
                    نتائج الأنشطة

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/assignments/index.php">

                    <span class="menu-icon">📓</span>
                    الواجبات

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/assignments/my_submissions.php">

                    <span class="menu-icon">📥</span>
                    واجباتي المرسلة

                </a>
                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/quizzes/index.php">

                    <span class="menu-icon">📋</span>
                    الاختبارات القصيرة

                </a>
                 <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/quizzes/results.php">

                    <span class="menu-icon">🏆</span>
                    نتائج الاختبارات القصيرة
                </a>
                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/exams/index.php">

                    <span class="menu-icon">📄</span>
                    الاختبارات

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/exams/results.php">

                    <span class="menu-icon">🎯</span>
                    نتائج الاختبارات

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/grades.php">

                    <span class="menu-icon">📊</span>
                    درجاتي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/results.php">

                    <span class="menu-icon">📈</span>
                    النتائج

                </a>

            </div>

        </div>

    </div>

    <!-- التواصل -->

    <div class="accordion-item">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#communicationMenu">

                <span class="menu-icon">📢</span>
                التواصل

            </button>

        </h2>

        <div
        id="communicationMenu"
        class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/messages/inbox.php">

                    <span class="menu-icon">✉️</span>
                    الرسائل

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/announcements/index.php">

                    <span class="menu-icon">📣</span>
                    الإعلانات

                </a>
                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/behavior/index.php">

                    <span class="menu-icon">⚖️</span>
                    السلوك والانضباط

                </a>


            </div>

        </div>

    </div>

  

    <!-- الخدمات -->

    <div class="accordion-item">

        <h2 class="accordion-header">

            <button
            class="accordion-button collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#servicesMenu">

                <span class="menu-icon">📅</span>
                الخدمات الأكاديمية

            </button>

        </h2>

        <div
        id="servicesMenu"
        class="accordion-collapse collapse">

            <div class="accordion-body p-0">

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/calendar/index.php">

                    <span class="menu-icon">🗓️</span>
                    التقويم الأكاديمي

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/attendance/index.php">

                    <span class="menu-icon">✅</span>
                    الحضور والغياب

                </a>

                <a class="nav-link p-2"
                   href="<?= BASE_URL ?>/student/transcript/index.php">

                    <span class="menu-icon">🗂️</span>
                    السجل الأكاديمي

                </a>

            </div>

        </div>

    </div>

    <!-- تسجيل الخروج -->

    <a class="nav-link p-3 text-danger"
       href="<?= BASE_URL ?>/core/logout.php">

        <span class="menu-icon">🚪</span>

        تسجيل الخروج

    </a>

</div>

<?php
}
?>

<!-- Desktop + Tablet -->

<div class="col-lg-2 d-none d-lg-block p-0 m-0 sidebar-container">
    <div class="student-sidebar sidebar-fixed">

        <?php renderStudentMenu($currentPage); ?>

    </div>

</div>

<!-- Mobile -->

<div
class="offcanvas offcanvas-end student-mobile-sidebar"
tabindex="-1"
id="studentSidebar">

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

        <?php renderStudentMenu($currentPage); ?>

    </div>

</div>