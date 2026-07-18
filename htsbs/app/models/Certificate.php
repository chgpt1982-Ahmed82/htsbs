<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Transcript.php';

class Certificate
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    إنشاء رقم شهادة
    */

    public function generateCertificateNumber()
    {
        $year =
        date('Y');

        $stmt = $this->db->query(

        "SELECT COUNT(*) total
         FROM certificates"

        );

        $count =
        $stmt->fetch(PDO::FETCH_ASSOC);

        $number =
        str_pad(

            $count['total'] + 1,

            4,

            '0',

            STR_PAD_LEFT

        );

        return

        'LMS-' .
        $year .
        '-' .
        $number;
    }

    /*
    إنشاء شهادة
    */

    public function createCertificate(
        $studentId
    )
    {
        /*
        التحقق من وجود شهادة
        */

        $check = $this->db->prepare(

        "SELECT id
         FROM certificates
         WHERE student_id=?"

        );

        $check->execute([
            $studentId
        ]);

        if(
        $check->fetch()
        )
        {
            return false;
        }

        $transcript =
        new Transcript();

        $finalGrade =
        $transcript->calculateFinalGrade(
            $studentId
        );

        $gpa =
        $transcript->calculateGPA(
            $studentId
        );

        $certificateNo =
        $this->generateCertificateNumber();

        $stmt = $this->db->prepare(

        "INSERT INTO certificates
        (
            student_id,
            certificate_no,
            final_grade,
            gpa,
            issue_date
        )
        VALUES
        (
            ?,?,?,?,?
        )"

        );

        return $stmt->execute([

            $studentId,

            $certificateNo,

            $finalGrade,

            $gpa,

            date('Y-m-d')

        ]);
    }

    /*
    جميع شهادات الطالب
    */

    public function getStudentCertificates(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM certificates

         WHERE student_id=?

         ORDER BY issue_date DESC"

        );

        $stmt->execute([
            $studentId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    شهادة واحدة
    */

    public function find(
        $id
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

        c.*,

        s.student_number,

        u.full_name

        FROM certificates c

        INNER JOIN students s
        ON c.student_id=s.id

        INNER JOIN users u
        ON s.user_id=u.id

        WHERE c.id=?"

        );

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }

    /*
    شهادة حسب الطالب
    */

    public function getByStudent(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM certificates

         WHERE student_id=?

         LIMIT 1"

        );

        $stmt->execute([
            $studentId
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }

    /*
    جميع الشهادات
    */

    public function getAll()
    {
        $stmt = $this->db->query(

        "SELECT

        c.*,

        u.full_name

        FROM certificates c

        INNER JOIN students s
        ON c.student_id=s.id

        INNER JOIN users u
        ON s.user_id=u.id

        ORDER BY c.issue_date DESC"

        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    حذف شهادة
    */

    public function delete(
        $id
    )
    {
        $stmt = $this->db->prepare(

        "DELETE FROM certificates
         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }

    /*
    التحقق من استحقاق الشهادة
    */

    public function isEligible(
        $studentId
    )
    {
        $transcript =
        new Transcript();

        $finalGrade =
        $transcript->calculateFinalGrade(
            $studentId
        );

        return
        $finalGrade >= 50;
    }
}
?>
