<?php

require_once __DIR__ . '/../../config/database.php';

class Assignment
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    إنشاء واجب
    */

    public function create($data)
    {
        $stmt = $this->db->prepare(

        "INSERT INTO assignments
            (
            teacher_id,
            course_id,
            class_id,
            title,
            description,
            due_date,
            file_path
            )
            VALUES
            (
            ?,?,?,?,?,?,?
            )
            "

        );

        return $stmt->execute([

            $data['teacher_id'],
            $data['course_id'],
            $data['class_id'],
            $data['title'],
            $data['description'],
            $data['due_date'],
            $data['file_path']
            
            ]);
    }

    /*
    جميع الواجبات
    */

    public function getAll()
    {
        $stmt = $this->db->query(

        "SELECT

            a.*,
            c.course_name

         FROM assignments a

         LEFT JOIN courses c
         ON a.course_id=c.id

         ORDER BY a.created_at DESC"

        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    واجبات مادة معينة
    */

    public function getByCourse($courseId)
    {
        $stmt = $this->db->prepare(

        "SELECT *
         FROM assignments
         WHERE course_id=?
         ORDER BY created_at DESC"

        );

        $stmt->execute([
            $courseId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    واجب واحد
    */

    public function find($id)
    {
        $stmt = $this->db->prepare(

        "SELECT *
         FROM assignments
         WHERE id=?"

        );

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    تسليم واجب
    */

    public function submit($data)
    {
        $stmt = $this->db->prepare(

        "INSERT INTO assignment_submissions
        (
            assignment_id,
            student_id,
            submission_text,
            file_path
        )
        VALUES
        (
            ?,?,?,?
        )"

        );

        return $stmt->execute([

            $data['assignment_id'],
            $data['student_id'],
            $data['submission_text'],
            $data['file_path']

        ]);
    }

    /*
    جميع التسليمات
    */

    public function getSubmissions($assignmentId)
    {
        $stmt = $this->db->prepare(

        "SELECT

            s.*,
            u.full_name

         FROM assignment_submissions s

         INNER JOIN students st
         ON s.student_id=st.id

         INNER JOIN users u
         ON st.user_id=u.id

         WHERE s.assignment_id=?

         ORDER BY s.submitted_at DESC"

        );

        $stmt->execute([
            $assignmentId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    تصحيح الواجب
    */

    public function grade(
        $submissionId,
        $score,
        $feedback
    )
    {
        $stmt = $this->db->prepare(

        "UPDATE assignment_submissions

         SET

         score=?,
         feedback=?

         WHERE id=?"

        );

        return $stmt->execute([

            $score,
            $feedback,
            $submissionId

        ]);
    }

    /*
    واجبات الطالب
    */

    public function getStudentSubmissions(
        $studentId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            s.*,
            a.title

         FROM assignment_submissions s

         INNER JOIN assignments a
         ON s.assignment_id=a.id

         WHERE s.student_id=?

         ORDER BY s.submitted_at DESC"

        );

        $stmt->execute([
            $studentId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStudentAssignments($classId)
    {
        $stmt = $this->db->prepare(
    
        "SELECT
    
        a.*,
        c.course_name
    
        FROM assignments a
    
        LEFT JOIN courses c
        ON a.course_id=c.id
    
        WHERE a.class_id=?
    
        ORDER BY a.created_at DESC"
    
        );
    
        $stmt->execute([
            $classId
        ]);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        
        
        
    
    
    
}
?>
