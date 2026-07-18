<?php

session_start();

require_once '../../config/config.php';

include '../../app/views/layouts/header.php';

?>

<div class="container-fluid">
<div class="row flex-lg-row-reverse">
<?php include '../../app/views/layouts/sidebar.php'; ?>
<div class="col-md-10 p-4">

    
<div class="container mt-4">

<div class="card">

    <div class="card-header">

        <h3>استيراد الطلاب من CSV</h3>

    </div>

    <div class="card-body">

        <?php if(isset($_SESSION['success'])): ?>

            <div class="alert alert-success">

                <?= $_SESSION['success']; ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php endif; ?>

        <div class="alert alert-info">

            ترتيب الأعمدة في الملف:

            <hr>

            الرقم الأكاديمي |
            الرقم الشخصي |
            اسم الطالب |
            الشعبة |
            المعدل التراكمي |
            هاتف ولي الأمر |
            الهاتف

        </div>

        <form
            method="POST"
            action="import_process.php"
            enctype="multipart/form-data">

            <div class="mb-3">

                <label>اختر ملف CSV</label>

                <input
                    type="file"
                    name="excel_file"
                    accept=".csv"
                    class="form-control"
                    required>

            </div>

            <button
                type="submit"
                class="btn btn-success">

                استيراد الطلاب

            </button>

            <a
                href="index.php"
                class="btn btn-secondary">

                رجوع

            </a>

        </form>

    </div>

</div>


</div>

<?php include '../../app/views/layouts/footer.php'; ?>
