<?php

require_once __DIR__ . '/../../config/database.php';

class Transcript
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    متوسط الحضور
    */

    public function getStudentAttendanceAverage(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

        COUNT(*) AS total,

        SUM(
            CASE
            WHEN status='Present'
            THEN 1
            ELSE 0
            END
        ) AS present_count

        FROM attendance

        WHERE student_id=?"

        );

        $stmt->execute([
            $studentId
        ]);

        $row =
        $stmt->fetch(PDO::FETCH_ASSOC);

        if(
        !$row
        ||
        $row['total'] == 0
        )
        {
            return 0;
        }

        return round(

            (
                $row['present_count']
                /
                $row['total']
            ) * 100,

            2

        );
    }

    /*
    متوسط الواجبات
    */

    public function getStudentAssignmentAverage(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

        AVG(score) AS average_score

        FROM assignment_submissions

        WHERE student_id=?
        AND score IS NOT NULL"

        );

        $stmt->execute([
            $studentId
        ]);

        $row =
        $stmt->fetch(PDO::FETCH_ASSOC);

        return round(
            $row['average_score']
            ?? 0,
            2
        );
    }

    /*
    متوسط الامتحانات
    */

    public function getStudentExamAverage(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

        AVG(marks) AS average_marks

        FROM exam_results

        WHERE student_id=?
        AND marks IS NOT NULL"

        );

        $stmt->execute([
            $studentId
        ]);

        $row =
        $stmt->fetch(PDO::FETCH_ASSOC);

        return round(
            $row['average_marks']
            ?? 0,
            2
        );
    }

    /*
    إعدادات الأوزان
    */

    public function getSettings()
    {
        $stmt = $this->db->query(

        "SELECT *

         FROM transcript_settings

         LIMIT 1"

        );

        $settings =
        $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$settings)
        {
            return [

                'attendance_weight' => 10,
                'assignment_weight' => 20,
                'exam_weight' => 70

            ];
        }

        return $settings;
    }

    /*
    الدرجة النهائية
    */

    public function calculateFinalGrade(
        $studentId
    )
    {
        $attendance =
        $this->getStudentAttendanceAverage(
            $studentId
        );

        $assignments =
        $this->getStudentAssignmentAverage(
            $studentId
        );

        $exams =
        $this->getStudentExamAverage(
            $studentId
        );

        $settings =
        $this->getSettings();

        $finalGrade =

        (
            $attendance *
            (
                $settings['attendance_weight']
                / 100
            )
        )

        +

        (
            $assignments *
            (
                $settings['assignment_weight']
                / 100
            )
        )

        +

        (
            $exams *
            (
                $settings['exam_weight']
                / 100
            )
        );

        return round(
            $finalGrade,
            2
        );
    }

    /*
    GPA
    */

    public function calculateGPA(
        $studentId
    )
    {
        $grade =
        $this->calculateFinalGrade(
            $studentId
        );

        if($grade >= 90)
        {
            return 4.0;
        }

        if($grade >= 80)
        {
            return 3.0;
        }

        if($grade >= 70)
        {
            return 2.0;
        }

        if($grade >= 60)
        {
            return 1.0;
        }

        return 0.0;
    }

    /*
    التقرير الكامل
    */

    public function getTranscript(
        $studentId
    )
    {
        return [

            'attendance' =>

            $this->getStudentAttendanceAverage(
                $studentId
            ),

            'assignments' =>

            $this->getStudentAssignmentAverage(
                $studentId
            ),

            'exams' =>

            $this->getStudentExamAverage(
                $studentId
            ),

            'final_grade' =>

            $this->calculateFinalGrade(
                $studentId
            ),

            'gpa' =>

            $this->calculateGPA(
                $studentId
            )

        ];
    }
}
?>
