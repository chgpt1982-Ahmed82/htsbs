<?php

require_once __DIR__ . '/../../config/database.php';

class Exam
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    إنشاء امتحان
    */

    public function create($data)
    {
        $stmt = $this->db->prepare(

        "INSERT INTO exams
        (
            teacher_id,
            course_id,
            exam_name,
            exam_type,
            exam_date,
            max_marks
        )
        VALUES
        (
            ?,?,?,?,?,?
        )"

        );

        $stmt->execute([

            $data['teacher_id'],
            $data['course_id'],
            $data['exam_name'],
            $data['exam_type'],
            $data['exam_date'],
            $data['max_marks']

        ]);

        return $this->db->lastInsertId();
    }

    /*
    ربط الامتحان بالصفوف
    */

    public function assignClasses(
        $examId,
        $classIds
    )
    {
        foreach($classIds as $classId)
        {
            $stmt = $this->db->prepare(

            "INSERT INTO exam_classes
            (
                exam_id,
                class_id
            )
            VALUES
            (
                ?,?
            )"

            );

            $stmt->execute([
                $examId,
                $classId
            ]);
        }
    }

    /*
    مقررات المعلم
    */

    public function getTeacherCourses(
        $teacherId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            c.id,
            c.course_name

         FROM course_assignments ca

         INNER JOIN courses c
         ON ca.course_id=c.id

         WHERE ca.teacher_id=?

         ORDER BY c.course_name"

        );

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    الصفوف المسندة للمعلم حسب المقرر
    */

    public function getCourseClasses(
        $teacherId,
        $courseId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            cl.id,
            cl.class_name

         FROM course_assignments ca

         INNER JOIN classes cl
         ON ca.class_id=cl.id

         WHERE ca.teacher_id=?
         AND ca.course_id=?

         ORDER BY cl.class_name"

        );

        $stmt->execute([

            $teacherId,
            $courseId

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    جميع امتحانات المعلم
    */

    public function getTeacherExams(
        $teacherId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            e.*,
            c.course_name

         FROM exams e

         LEFT JOIN courses c
         ON e.course_id=c.id

         WHERE e.teacher_id=?

         ORDER BY e.exam_date DESC"

        );

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    بيانات امتحان واحد
    */

    public function find($id)
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM exams

         WHERE id=?"

        );

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    امتحانات الطالب حسب صفه
    */

    public function getStudentExams(
        $classId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            e.*,
            c.course_name

         FROM exams e

         INNER JOIN exam_classes ec
         ON e.id=ec.exam_id

         LEFT JOIN courses c
         ON e.course_id=c.id

         WHERE ec.class_id=?

         ORDER BY e.exam_date DESC"

        );

        $stmt->execute([
            $classId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    حفظ درجة طالب
    */

    public function saveResult(
        $examId,
        $studentId,
        $marks,
        $remarks
    )
    {
        $check = $this->db->prepare(

        "SELECT id

         FROM exam_results

         WHERE exam_id=?
         AND student_id=?"

        );

        $check->execute([

            $examId,
            $studentId

        ]);

        $existing =
        $check->fetch(PDO::FETCH_ASSOC);

        if($existing)
        {
            $stmt = $this->db->prepare(

            "UPDATE exam_results

             SET

             marks=?,
             remarks=?

             WHERE id=?"

            );

            return $stmt->execute([

                $marks,
                $remarks,
                $existing['id']

            ]);
        }

        $stmt = $this->db->prepare(

        "INSERT INTO exam_results
        (
            exam_id,
            student_id,
            marks,
            remarks
        )
        VALUES
        (
            ?,?,?,?
        )"

        );

        return $stmt->execute([

            $examId,
            $studentId,
            $marks,
            $remarks

        ]);
    }

    /*
    نتائج امتحان
    */

    public function getResults(
        $examId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            r.*,
            u.full_name

         FROM exam_results r

         INNER JOIN students s
         ON r.student_id=s.id

         INNER JOIN users u
         ON s.user_id=u.id

         WHERE r.exam_id=?

         ORDER BY u.full_name"

        );

        $stmt->execute([
            $examId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    نتائج طالب
    */

    public function getStudentResults(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            r.*,
            e.exam_name,
            e.exam_type,
            e.max_marks,
            e.exam_date

         FROM exam_results r

         INNER JOIN exams e
         ON r.exam_id=e.id

         WHERE r.student_id=?

         ORDER BY e.exam_date DESC"

        );

        $stmt->execute([
            $studentId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    الصفوف المرتبطة بالامتحان
    */

    public function getExamClasses(
        $examId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            c.*

         FROM exam_classes ec

         INNER JOIN classes c
         ON ec.class_id=c.id

         WHERE ec.exam_id=?"

        );

        $stmt->execute([
            $examId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
