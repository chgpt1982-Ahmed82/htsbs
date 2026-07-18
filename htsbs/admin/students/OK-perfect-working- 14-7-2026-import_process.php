<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

session_start();

require_once '../../config/database.php';

$db = (new Database())->connect();

if(
    !isset($_FILES['excel_file']) ||
    $_FILES['excel_file']['error'] != 0
){
    die('File upload failed');
}

$file = fopen(
    $_FILES['excel_file']['tmp_name'],
    'r'
);

$rowNumber = 0;
$imported = 0;

while(
    ($row = fgetcsv($file,10000,","))
    !== false
)
{
    $rowNumber++;

    if($rowNumber == 1)
    {
        continue;
    }

    $studentNumber = trim($row[0] ?? '');
    $nationalId    = trim($row[1] ?? '');
    $fullName      = trim($row[2] ?? '');
    $className     = trim($row[3] ?? '');
    $gpa           = trim($row[4] ?? '');
    $guardianPhone = trim($row[5] ?? '');
    $phone         = trim($row[6] ?? '');

    if(
        empty($studentNumber) ||
        empty($fullName)
    ){
        continue;
    }

    $email =
        $studentNumber .
        '@lms.edu';

    $academicLevel = 'General';

    $checkUser = $db->prepare(
        "SELECT id
         FROM users
         WHERE email=?"
    );

    $checkUser->execute([
        $email
    ]);

    if($checkUser->fetch())
    {
        continue;
    }

    $checkStudent = $db->prepare(
        "SELECT id
         FROM students
         WHERE student_number=?"
    );

    $checkStudent->execute([
        $studentNumber
    ]);

    if($checkStudent->fetch())
    {
        continue;
    }

    $classStmt = $db->prepare(
        "SELECT id
         FROM classes
         WHERE class_name=?"
    );

    $classStmt->execute([
        $className
    ]);

    $class = $classStmt->fetch(PDO::FETCH_ASSOC);

    if(!$class)
    {
        $stmt = $db->prepare(
            "INSERT INTO classes
            (
                class_name
            )
            VALUES
            (
                ?
            )"
        );

        $stmt->execute([
            $className
        ]);

        $classId =
        $db->lastInsertId();
    }
    else
    {
        $classId =
        $class['id'];
    }

    $password = password_hash(
        $studentNumber,
        PASSWORD_DEFAULT
    );

    $stmt = $db->prepare(
        "INSERT INTO users
        (
            role_id,
            full_name,
            email,
            password,
            phone,
            status
        )
        VALUES
        (
            ?,?,?,?,?,?
        )"
    );

    $stmt->execute([
        3,
        $fullName,
        $email,
        $password,
        $phone,
        'active'
    ]);

    $userId =
    $db->lastInsertId();

    $stmt = $db->prepare(
        "INSERT INTO students
        (
            user_id,
            department_id,
            class_id,
            student_number,
            national_id,
            academic_level,
            gpa,
            guardian_phone_1
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?
        )"
    );

    $stmt->execute([
        $userId,
        1,
        $classId,
        $studentNumber,
        $nationalId,
        $academicLevel,
        $gpa,
        $guardianPhone
    ]);

    $imported++;
}

fclose($file);

$_SESSION['success'] =
$imported .
' students imported successfully';

header(
    'Location: index.php'
);

exit;
?>
