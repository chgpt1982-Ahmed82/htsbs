<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class QuizAssignment
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /*
    =================================
    Assign Quiz To Class
    =================================
    */

    public function assignQuiz(
        $quizId,
        $classId,
        $teacherId,
        $academicYear = null,
        $semester = null
    )
    {
        $stmt = $this->db->prepare("
        INSERT INTO quiz_assignments
        (
            quiz_id,
            class_id,
            teacher_id,
            academic_year,
            semester
        )
        VALUES
        (
            ?,?,?,?,?
        )
        ");

        return $stmt->execute([

            $quizId,
            $classId,
            $teacherId,
            $academicYear,
            $semester

        ]);
    }

    /*
    =================================
    Remove Assignment
    =================================
    */

    public function removeAssignment($id)
    {
        $stmt = $this->db->prepare("
        DELETE FROM quiz_assignments
        WHERE id = ?
        ");

        return $stmt->execute([
            $id
        ]);
    }

    /*
    =================================
    Get Teacher Assignments
    =================================
    */

    public function getAssignments($teacherId)
    {
        $stmt = $this->db->prepare("

        SELECT

            qa.id,

            qa.academic_year,

            qa.semester,

            qa.assigned_at,

            q.id AS quiz_id,

            q.title,

            q.duration_minutes,

            q.total_marks,

            c.course_name,

            c.course_code,

            cl.class_name

        FROM quiz_assignments qa

        INNER JOIN quizzes q
            ON qa.quiz_id = q.id

        INNER JOIN courses c
            ON q.course_id = c.id

        INNER JOIN classes cl
            ON qa.class_id = cl.id

        WHERE qa.teacher_id = ?

        ORDER BY qa.id DESC

        ");

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    =================================
    Get Teacher Quizzes
    =================================
    */

    public function getTeacherQuizzes($teacherId)
    {
        $stmt = $this->db->prepare("

        SELECT

            q.id,

            q.title,

            q.duration_minutes,

            q.total_marks,

            c.course_name,

            c.course_code

        FROM quizzes q

        INNER JOIN courses c
            ON q.course_id = c.id

        WHERE q.teacher_id = ?

        ORDER BY q.id DESC

        ");

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    =================================
    Get Teacher Classes
    =================================
    */

    public function getTeacherClasses($teacherId)
    {
        $stmt = $this->db->prepare("

        SELECT DISTINCT

            cl.id,

            cl.class_name

        FROM course_assignments ca

        INNER JOIN classes cl
            ON ca.class_id = cl.id

        WHERE ca.teacher_id = ?

        ORDER BY cl.class_name

        ");

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    =================================
    Check Existing Assignment
    =================================
    */

    public function assignmentExists(
        $quizId,
        $classId
    )
    {
        $stmt = $this->db->prepare("

        SELECT COUNT(*)

        FROM quiz_assignments

        WHERE quiz_id = ?
        AND class_id = ?

        ");

        $stmt->execute([
            $quizId,
            $classId
        ]);

        return $stmt->fetchColumn() > 0;
    }

    /*
    =================================
    Count Quiz Usage
    =================================
    */

    public function countQuizUsage($quizId)
    {
        $stmt = $this->db->prepare("

        SELECT COUNT(*)

        FROM quiz_assignments

        WHERE quiz_id = ?

        ");

        $stmt->execute([
            $quizId
        ]);

        return $stmt->fetchColumn();
    }
}
?>
