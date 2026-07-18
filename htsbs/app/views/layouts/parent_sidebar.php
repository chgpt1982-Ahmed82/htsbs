<div class="col-md-2 bg-dark min-vh-100">

<div class="text-center text-white py-3 border-bottom">

    <h5>

        <?= htmlspecialchars($_SESSION['name']); ?>

    </h5>

    <small>

        Parent Portal

    </small>

</div>
<ul class="nav flex-column pt-3">

    <li class="nav-item">

        <a class="nav-link text-white"
           href="<?= BASE_URL ?>/parent/dashboard.php">

            🏠 Dashboard

        </a>

    </li>
    <li class="nav-item">

        <a
        class="nav-link text-white"
        href="<?= BASE_URL ?>/messages/inbox.php">
    
        📩 Messages
    
        </a>
    
    </li>
    <li class="nav-item">

        <a class="nav-link text-white"
           href="<?= BASE_URL ?>/parent/attendance.php">

            📅 Attendance

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link text-white"
           href="<?= BASE_URL ?>/parent/grades.php">

            📊 Grades

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link text-white"
           href="<?= BASE_URL ?>/parent/results.php">

            📝 Quiz Results

        </a>

    </li>

    <li class="nav-item">

        <a class="nav-link text-white"
           href="<?= BASE_URL ?>/parent/profile.php">

            👤 Student Profile

        </a>

    </li>
    <li class="nav-item">

        <a
        class="nav-link text-white"
        href="<?= BASE_URL ?>/parent/exams/results.php">
    
        📝 Exam Results
    
        </a>
    
    </li>
    
    <li class="nav-item">

        <a
        class="nav-link text-white"
        href="<?= BASE_URL ?>/parent/transcript/index.php">
    
        🎓 Academic Transcript
    
        </a>
    
    </li>
    <li class="nav-item">

        <a
        class="nav-link text-white"
        href="<?= BASE_URL ?>/parent/announcements/index.php">
    
        📢 Announcements
    
        </a>
    
    </li>
    <li class="nav-item">

        <a
        class="nav-link text-white"
        href="<?= BASE_URL ?>/parent/calendar/index.php">
    
        📅 Academic Calendar
    
        </a>
    
    </li>
    <li class="nav-item">

            <a
            class="nav-link text-white"
            href="<?= BASE_URL ?>/parent/quizzes/results.php">
        
            📊 Quiz Results
        
            </a>
        
        </li>
    <li class="nav-item">

        <a class="nav-link text-white"
           href="<?= BASE_URL ?>/core/logout.php">

            🚪 Logout

        </a>

    </li>

</ul>


</div>
