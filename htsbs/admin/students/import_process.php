<?php
/*
=====================================================================
admin/students/import_process.php — استيراد الطلاب من ملف CSV
=====================================================================
التعديلات المدمجة:
  1. حماية صلاحيات (أدمن فقط) — كان الملف مفتوحاً للجميع ⛔
  2. تسجيل العملية في السجلات (Logger) — عملية جماعية تستحق التوثيق
  3. Transaction لكل طالب — لا مستخدمين يتامى عند فشل جزئي
  4. department_id يُقرأ من الملف أو من القائمة، لا يُثبَّت على 1
  5. لا ينشئ صفوفاً جديدة تلقائياً — يتخطى الصفوف المجهولة ويبلّغ عنها
  6. تحقق من نوع الملف وحجمه
  7. تقرير مفصل: نجح / تخطّى / سبب التخطي
=====================================================================
*/

session_start();

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../core/Auth.php';
require_once '../../core/Logger.php';

/*
====================================================================
(1) الصلاحية: أدمن فقط
كان أي زائر يستطيع إرسال CSV وإنشاء حسابات مستخدمين!
====================================================================
*/
if (!Auth::check() || (int)($_SESSION['role_id'] ?? 0) !== 1) {

    Logger::log(
        'students',
        'import_denied',
        'محاولة استيراد طلاب بدون صلاحية',
        null,
        null,
        'danger'
    );

    die('Access Denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: import.php');
    exit;
}

/*
====================================================================
(2) التحقق من الملف
====================================================================
*/
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = 'فشل رفع الملف';
    header('Location: import.php');
    exit;
}

$tmpPath  = $_FILES['excel_file']['tmp_name'];
$origName = $_FILES['excel_file']['name'];
$size     = (int)$_FILES['excel_file']['size'];

/* الحجم: 5 ميجابايت كحد أقصى */
if ($size > 5 * 1024 * 1024) {
    $_SESSION['error'] = 'حجم الملف يتجاوز 5 ميجابايت';
    header('Location: import.php');
    exit;
}

/* الامتداد: CSV فقط */
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($ext, ['csv', 'txt'], true)) {
    $_SESSION['error'] = 'الملف يجب أن يكون بصيغة CSV';
    header('Location: import.php');
    exit;
}

if (!is_uploaded_file($tmpPath)) {
    die('Invalid upload');
}

$db = (new Database())->connect();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/*
====================================================================
(3) القسم الافتراضي
كان مثبتاً على 1 قسراً — الآن يُقرأ من النموذج،
وإن لم يُرسل نأخذ أول قسم موجود فعلاً في قاعدة البيانات
====================================================================
*/
$defaultDepartmentId = (int)($_POST['department_id'] ?? 0);

if ($defaultDepartmentId > 0) {

    $stmt = $db->prepare("SELECT COUNT(*) FROM departments WHERE id = ?");
    $stmt->execute([$defaultDepartmentId]);

    if ((int)$stmt->fetchColumn() === 0) {
        $_SESSION['error'] = 'القسم المحدد غير موجود';
        header('Location: import.php');
        exit;
    }

} else {
    $defaultDepartmentId = (int)$db->query(
        "SELECT id FROM departments ORDER BY id LIMIT 1"
    )->fetchColumn();

    if ($defaultDepartmentId <= 0) {
        $_SESSION['error'] = 'لا توجد أقسام في النظام — أضف قسماً أولاً';
        header('Location: import.php');
        exit;
    }
}

/*
====================================================================
(4) خريطة الصفوف الموجودة
لا ننشئ صفوفاً تلقائياً: خطأ إملائي واحد في CSV كان ينشئ صفاً وهمياً
====================================================================
*/
$classMap = [];

foreach ($db->query("SELECT id, class_name FROM classes")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $classMap[trim($c['class_name'])] = (int)$c['id'];
}

/*
====================================================================
(5) قراءة الملف
====================================================================
*/
$file = fopen($tmpPath, 'r');

if (!$file) {
    $_SESSION['error'] = 'تعذر فتح الملف';
    header('Location: import.php');
    exit;
}

/* تخطي BOM إن وُجد (يفسد أول خلية في ملفات Excel العربية) */
$bom = fread($file, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($file);
}

$rowNumber = 0;   // رقم السطر في الملف
$imported  = 0;   // نجح
$skipped   = 0;   // تخطّي
$errors    = [];  // أسباب التخطي (لأول 20 سطراً)

while (($row = fgetcsv($file, 10000, ',')) !== false) {

    $rowNumber++;

    /* السطر الأول = رؤوس الأعمدة */
    if ($rowNumber === 1) {
        continue;
    }

    /* سطر فارغ تماماً */
    if (count($row) === 1 && trim((string)$row[0]) === '') {
        continue;
    }

    /*
    ترتيب الأعمدة المتوقع:
    0: الرقم الأكاديمي | 1: الرقم الشخصي | 2: الاسم الكامل
    3: الصف | 4: المعدل | 5: هاتف ولي الأمر | 6: هاتف الطالب
    */
    $studentNumber = trim((string)($row[0] ?? ''));
    $nationalId    = trim((string)($row[1] ?? ''));
    $fullName      = trim((string)($row[2] ?? ''));
    $className     = trim((string)($row[3] ?? ''));
    $gpa           = trim((string)($row[4] ?? ''));
    $guardianPhone = trim((string)($row[5] ?? ''));
    $phone         = trim((string)($row[6] ?? ''));

    /* حقول إلزامية */
    if ($studentNumber === '' || $fullName === '') {
        $skipped++;
        if (count($errors) < 20) {
            $errors[] = "سطر $rowNumber: الرقم الأكاديمي أو الاسم مفقود";
        }
        continue;
    }

    $email = $studentNumber . '@lms.edu';

    /* البريد مكرر؟ */
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        $skipped++;
        if (count($errors) < 20) {
            $errors[] = "سطر $rowNumber: الطالب ($studentNumber) مسجّل مسبقاً";
        }
        continue;
    }

    /* الرقم الأكاديمي مكرر؟ */
    $stmt = $db->prepare("SELECT id FROM students WHERE student_number = ?");
    $stmt->execute([$studentNumber]);

    if ($stmt->fetch()) {
        $skipped++;
        if (count($errors) < 20) {
            $errors[] = "سطر $rowNumber: الرقم الأكاديمي ($studentNumber) مستخدم";
        }
        continue;
    }

    /*
    الصف: يجب أن يكون موجوداً مسبقاً
    (النسخة السابقة كانت تنشئه تلقائياً — فخطأ إملائي = صف وهمي)
    */
    if ($className === '' || !isset($classMap[$className])) {
        $skipped++;
        if (count($errors) < 20) {
            $errors[] = "سطر $rowNumber: الصف ($className) غير موجود — أنشئه أولاً";
        }
        continue;
    }

    $classId = $classMap[$className];

    /*
    ================================================================
    (6) الإدراج داخل Transaction
    فشل جزئي كان يترك مستخدماً يتيماً بلا سجل طالب
    ================================================================
    */
    try {

        $db->beginTransaction();

        /* كلمة المرور الأولية = الرقم الأكاديمي */
        $password = password_hash($studentNumber, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO users (role_id, full_name, email, password, phone, status)
            VALUES (3, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$fullName, $email, $password, $phone]);

        $userId = (int)$db->lastInsertId();

        $stmt = $db->prepare("
            INSERT INTO students
                (user_id, department_id, class_id, student_number,
                 national_id, academic_level, gpa, guardian_phone_1)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $defaultDepartmentId,
            $classId,
            $studentNumber,
            $nationalId !== '' ? $nationalId : null,
            'General',
            $gpa !== '' ? $gpa : null,
            $guardianPhone !== '' ? $guardianPhone : null,
        ]);

        $db->commit();

        $imported++;

    } catch (Throwable $ex) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        $skipped++;

        if (count($errors) < 20) {
            $errors[] = "سطر $rowNumber: فشل الإدخال ($studentNumber)";
        }
    }
}

fclose($file);

/*
====================================================================
(7) تسجيل العملية في السجلات
عملية جماعية تُدخل حسابات مستخدمين — من أولى ما يستحق التوثيق
====================================================================
*/
Logger::log(
    'students',
    'import',
    "استيراد طلاب من ملف ($origName): "
    . "نجح $imported | تخطّى $skipped | إجمالي الصفوف " . max(0, $rowNumber - 1),
    null,
    null,
    'warning'
);

/*
====================================================================
(8) التقرير النهائي
====================================================================
*/
$_SESSION['success'] = "تم استيراد $imported طالباً بنجاح";

if ($skipped > 0) {

    $_SESSION['error'] = "تم تخطّي $skipped سطراً:<br>"
                       . implode('<br>', array_map('htmlspecialchars', $errors))