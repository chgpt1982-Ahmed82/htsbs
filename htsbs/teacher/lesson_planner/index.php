<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../app/models/LessonPlanner.php';
require_once '../../app/models/Notification.php';

/*
==================================================
التحقق من تسجيل الدخول
==================================================
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: " . BASE_URL . "/login.php");
    exit;

}

if ($_SESSION['role_id'] != 2) {

    die("ليس لديك صلاحية الوصول.");

}

/*
==================================================
تهيئة الكائنات
==================================================
*/

$db = (new Database())->connect();

$model = new LessonPlanner();

$notificationModel = new Notification();

$count = $notificationModel->unreadCount(
    $_SESSION['user_id']
);

/*
==================================================
الإحصائيات
==================================================
*/

$totalPlans = $model->countTeacherPlans(
    $_SESSION['user_id']
);

$draftPlans = $model->countByStatus(
    $_SESSION['user_id'],
    'draft'
);

$publishedPlans = $model->countByStatus(
    $_SESSION['user_id'],
    'published'
);

$favoritePlans = count(
    $model->favorites(
        $_SESSION['user_id']
    )
);

/*
==================================================
جلب التحاضير
==================================================
*/

$plans = $model->getTeacherPlans(
    $_SESSION['user_id']
);

include '../../app/views/layouts/header.php';

?>

<style>

.dashboard-stat-card{

    border:none;
    border-radius:18px;
    transition:.3s;

}

.dashboard-stat-card:hover{

    transform:translateY(-5px);

}

.dashboard-stat-card .card-body{

    min-height:150px;

    display:flex;

    flex-direction:column;

    justify-content:center;

    align-items:center;

}

.dashboard-stat-card i{

    font-size:48px;

}

</style>

<div class="container-fluid">

<div class="row">

<?php include '../../app/views/layouts/teacher_sidebar.php'; ?>

<div class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2 class="fw-bold">

            <i class="bi bi-journal-richtext text-primary"></i>

            بنك التحاضير الذكية

        </h2>

        <p class="text-muted">

            إنشاء وإدارة جميع تحاضير الدروس باستخدام الذكاء الاصطناعي

        </p>

    </div>

    <a
    href="create.php"
    class="btn btn-primary btn-lg">

        <i class="bi bi-plus-circle"></i>

        تحضير جديد

    </a>

</div>

<!-- ==========================
الإحصائيات
========================== -->

<div class="row g-3 mb-4">

    <div class="col-lg-3 col-md-6">

        <div class="card dashboard-stat-card shadow">

            <div class="card-body">

                <i class="bi bi-journal-bookmark-fill text-primary"></i>

                <h2 class="mt-3">

                    <?= $totalPlans ?>

                </h2>

                <h6>

                    إجمالي التحاضير

                </h6>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card dashboard-stat-card shadow">

            <div class="card-body">

                <i class="bi bi-pencil-square text-warning"></i>

                <h2 class="mt-3">

                    <?= $draftPlans ?>

                </h2>

                <h6>

                    المسودات

                </h6>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card dashboard-stat-card shadow">

            <div class="card-body">

                <i class="bi bi-check-circle-fill text-success"></i>

                <h2 class="mt-3">

                    <?= $publishedPlans ?>

                </h2>

                <h6>

                    المنشورة

                </h6>

            </div>

        </div>

    </div>

    <div class="col-lg-3 col-md-6">

        <div class="card dashboard-stat-card shadow">

            <div class="card-body">

                <i class="bi bi-star-fill text-danger"></i>

                <h2 class="mt-3">

                    <?= $favoritePlans ?>

                </h2>

                <h6>

                    المفضلة

                </h6>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
البحث والفلترة
========================================== -->

<div class="card shadow border-0 mb-4">

    <div class="card-header bg-primary text-white">

        <i class="bi bi-search"></i>

        البحث في التحاضير

    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-4 mb-3">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control"
                    placeholder="ابحث بعنوان الدرس...">

            </div>

            <div class="col-md-3 mb-3">

                <select
                    id="statusFilter"
                    class="form-select">

                    <option value="">جميع الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="published">منشور</option>
                    <option value="archived">مؤرشف</option>

                </select>

            </div>

            <div class="col-md-3 mb-3">

                <select
                    id="favoriteFilter"
                    class="form-select">

                    <option value="">الكل</option>
                    <option value="1">المفضلة فقط</option>
                    <option value="0">غير المفضلة</option>

                </select>

            </div>

            <div class="col-md-2 mb-3">

                <button
                    class="btn btn-secondary w-100"
                    onclick="clearFilters()">

                    <i class="bi bi-arrow-clockwise"></i>

                    إعادة ضبط

                </button>

            </div>

        </div>

    </div>

</div>

<!-- ==========================================
جدول التحاضير
========================================== -->

<div class="card shadow border-0">

    <div class="card-header bg-success text-white d-flex justify-content-between">

        <span>

            <i class="bi bi-table"></i>

            قائمة التحاضير

        </span>

        <span class="badge bg-light text-dark">

            <?= count($plans); ?>

            تحضير

        </span>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                id="plansTable"
                class="table table-hover table-bordered align-middle mb-0">

                <thead class="table-dark">

                    <tr>

                        <th width="60">#</th>

                        <th>عنوان الدرس</th>

                        <th>المادة</th>

                        <th>الصف</th>

                        <th>الوحدة</th>

                        <th>الحالة</th>

                        <th>المفضلة</th>

                        <th>التاريخ</th>

                        <th width="260">

                            الإجراءات

                        </th>

                    </tr>

                </thead>

                <tbody>

<?php if(empty($plans)): ?>

<tr>

    <td colspan="9" class="text-center p-5">

        <i class="bi bi-journal-x fs-1 text-muted"></i>

        <br><br>

        لا توجد تحاضير حتى الآن.

        <br><br>

        <a
        href="create.php"
        class="btn btn-primary">

            إنشاء أول تحضير

        </a>

    </td>

</tr>

<?php else: ?>

<?php foreach($plans as $plan): ?>

<tr>

    <td>

        <?= $plan['id']; ?>

    </td>

    <td>

        <strong>

            <?= htmlspecialchars($plan['lesson_title']); ?>

        </strong>

    </td>

    <td>

        <?= htmlspecialchars($plan['course_name']); ?>

    </td>

    <td>

        <?= htmlspecialchars($plan['class_name']); ?>

    </td>

    <td>

        <?= htmlspecialchars($plan['unit_name']); ?>

    </td>

    <td>

<?php

switch($plan['status']){

case 'published':

echo '<span class="badge bg-success">منشور</span>';

break;

case 'archived':

echo '<span class="badge bg-secondary">مؤرشف</span>';

break;

default:

echo '<span class="badge bg-warning text-dark">مسودة</span>';

}

?>

    </td>

    <td class="text-center">

        <?= $plan['is_favorite']
            ? '⭐'
            : '-'; ?>

    </td>

    <td>

        <?= date(

            'Y-m-d',

            strtotime(

                $plan['created_at']

            )

        ); ?>

    </td>

    <td>
    <!-- ==========================
أزرار العمليات
========================== -->

<div class="btn-group" role="group">

    <!-- عرض -->

    <a
        href="view.php?id=<?= $plan['id']; ?>"
        class="btn btn-sm btn-primary"
        title="عرض">

        <i class="bi bi-eye-fill"></i>

    </a>

    <!-- تعديل -->

    <a
        href="edit.php?id=<?= $plan['id']; ?>"
        class="btn btn-sm btn-warning"
        title="تعديل">

        <i class="bi bi-pencil-fill"></i>

    </a>

    <!-- إعادة التوليد -->

    <a
        href="generate.php?id=<?= $plan['id']; ?>&regenerate=1"
        class="btn btn-sm btn-info text-white"
        title="إعادة التوليد">

        <i class="bi bi-stars"></i>

    </a>

    <!-- PDF -->

    <a
        href="export_pdf.php?id=<?= $plan['id']; ?>"
        target="_blank"
        class="btn btn-sm btn-danger"
        title="PDF">

        <i class="bi bi-file-earmark-pdf-fill"></i>

    </a>

    <!-- Word -->

    <a
        href="export_word.php?id=<?= $plan['id']; ?>"
        class="btn btn-sm btn-primary"
        title="Word">

        <i class="bi bi-file-earmark-word-fill"></i>

    </a>

    <!-- طباعة -->

    <a
        href="print.php?id=<?= $plan['id']; ?>"
        target="_blank"
        class="btn btn-sm btn-success"
        title="طباعة">

        <i class="bi bi-printer-fill"></i>

    </a>

    <!-- مفضلة -->

    <?php if($plan['is_favorite']){ ?>

        <a
            href="favorite.php?id=<?= $plan['id']; ?>&action=remove"
            class="btn btn-sm btn-warning"
            title="إزالة من المفضلة">

            <i class="bi bi-star-fill"></i>

        </a>

    <?php }else{ ?>

        <a
            href="favorite.php?id=<?= $plan['id']; ?>&action=add"
            class="btn btn-sm btn-outline-warning"
            title="إضافة للمفضلة">

            <i class="bi bi-star"></i>

        </a>

    <?php } ?>

    <!-- حذف -->

    <a
        href="delete.php?id=<?= $plan['id']; ?>"
        class="btn btn-sm btn-outline-danger"
        onclick="return confirm('هل تريد حذف هذا التحضير؟');"
        title="حذف">

        <i class="bi bi-trash-fill"></i>

    </a>

</div>

</td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>    

<!-- ==========================================
Footer
========================================== -->

</div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const table = document.getElementById("plansTable");

    const rows = table.querySelectorAll("tbody tr");

    const searchInput = document.getElementById("searchInput");

    const statusFilter = document.getElementById("statusFilter");

    const favoriteFilter = document.getElementById("favoriteFilter");

    function filterTable(){

        const keyword =
        searchInput.value.toLowerCase();

        const status =
        statusFilter.value.toLowerCase();

        const favorite =
        favoriteFilter.value;

        rows.forEach(function(row){

            const title =
            row.cells[1].innerText.toLowerCase();

            const course =
            row.cells[2].innerText.toLowerCase();

            const className =
            row.cells[3].innerText.toLowerCase();

            const unit =
            row.cells[4].innerText.toLowerCase();

            const rowStatus =
            row.cells[5].innerText.toLowerCase();

            const rowFavorite =
            row.cells[6].innerText.trim() == "⭐"
            ? "1"
            : "0";

            let show = true;

            if(keyword != ""){

                show =
                    title.includes(keyword)
                    ||
                    course.includes(keyword)
                    ||
                    className.includes(keyword)
                    ||
                    unit.includes(keyword);

            }

            if(show && status != ""){

                if(!rowStatus.includes(status)){

                    show = false;

                }

            }

            if(show && favorite != ""){

                if(rowFavorite != favorite){

                    show = false;

                }

            }

            row.style.display =
            show ? "" : "none";

        });

    }

    searchInput.addEventListener(

        "keyup",

        filterTable

    );

    statusFilter.addEventListener(

        "change",

        filterTable

    );

    favoriteFilter.addEventListener(

        "change",

        filterTable

    );

});

function clearFilters(){

    document.getElementById("searchInput").value="";

    document.getElementById("statusFilter").value="";

    document.getElementById("favoriteFilter").value="";

    const rows =
    document.querySelectorAll(
        "#plansTable tbody tr"
    );

    rows.forEach(function(row){

        row.style.display="";

    });

}

</script>

<style>

.btn-group .btn{

    margin-left:3px;

}

.table td{

    vertical-align:middle;

}

.table tbody tr:hover{

    background:#f8fbff;

}

.badge{

    font-size:13px;

}

.dashboard-stat-card{

    transition:.3s;

}

.dashboard-stat-card:hover{

    transform:translateY(-5px);

}

#plansTable th{

    white-space:nowrap;

}

#plansTable td{

    white-space:nowrap;

}

</style>

<?php

include '../../app/views/layouts/footer.php';

?>
        