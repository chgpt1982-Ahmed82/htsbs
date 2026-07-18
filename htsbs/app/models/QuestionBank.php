<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class QuestionBank
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    /*
    الحصول على teacher_id
    */

    public function getTeacherId($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT id
             FROM teachers
             WHERE user_id=?"
        );

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }

    /*
    المقررات المسندة للمعلم
    */

    public function getTeacherCourses($userId)
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            c.id,
            c.course_name

         FROM course_assignments ca

         INNER JOIN teachers t
         ON ca.teacher_id=t.id

         INNER JOIN courses c
         ON ca.course_id=c.id

         WHERE t.user_id=?

         ORDER BY c.course_name"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    إضافة سؤال للبنك
    */

    public function create($data)
    {
        $stmt = $this->db->prepare(

        "INSERT INTO question_bank
        (
            teacher_id,
            course_id,
            category,
            question_text,
            option_a,
            option_b,
            option_c,
            option_d,
            correct_answer,
            marks
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,?
        )"

        );

        return $stmt->execute([

            $data['teacher_id'],
            $data['course_id'],
            $data['category'],
            $data['question_text'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['correct_answer'],
            $data['marks']

        ]);
    }

    /*
    جميع أسئلة المعلم
    */

    public function getAllByTeacher($userId)
    {
        $stmt = $this->db->prepare(

        "SELECT

            qb.*,
            c.course_name

         FROM question_bank qb

         INNER JOIN courses c
         ON qb.course_id=c.id

         INNER JOIN teachers t
         ON qb.teacher_id=t.id

         WHERE t.user_id=?

         ORDER BY qb.id DESC"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    سؤال واحد
    */

    public function getById($id)
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM question_bank

         WHERE id=?"

        );

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    تحديث سؤال
    */

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(

        "UPDATE question_bank

         SET

            course_id=?,
            category=?,
            question_text=?,
            option_a=?,
            option_b=?,
            option_c=?,
            option_d=?,
            correct_answer=?,
            marks=?

         WHERE id=?"

        );

        return $stmt->execute([

            $data['course_id'],
            $data['category'],
            $data['question_text'],
            $data['option_a'],
            $data['option_b'],
            $data['option_c'],
            $data['option_d'],
            $data['correct_answer'],
            $data['marks'],
            $id

        ]);
    }

    /*
    حذف سؤال
    */

    public function delete($id)
    {
        $stmt = $this->db->prepare(

        "DELETE FROM question_bank

         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }

    /*
    أسئلة مقرر محدد
    */

    public function getByCourse($courseId)
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM question_bank

         WHERE course_id=?

         ORDER BY id DESC"

        );

        $stmt->execute([
            $courseId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    أسئلة حسب التصنيف
    */

    public function getByCategory(
        $courseId,
        $category
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM question_bank

         WHERE course_id=?
         AND category=?

         ORDER BY id DESC"

        );

        $stmt->execute([

            $courseId,
            $category

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    سحب أسئلة عشوائية
    */

    public function getRandomQuestions(
        $courseId,
        $limit = 10
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM question_bank

         WHERE course_id=?

         ORDER BY RAND()

         LIMIT $limit"

        );

        $stmt->execute([
            $courseId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    عدد الأسئلة
    */

    public function countQuestions(
        $userId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT COUNT(*)

         FROM question_bank qb

         INNER JOIN teachers t
         ON qb.teacher_id=t.id

         WHERE t.user_id=?"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchColumn();
    }
    
    public function getTeacherClasses($userId, $courseId)
        {
        $stmt = $this->db->prepare(
        
        
        "SELECT DISTINCT
        
            c.id,
            c.class_name
        
         FROM course_assignments ca
        
         INNER JOIN teachers t
         ON ca.teacher_id=t.id
        
         INNER JOIN classes c
         ON ca.class_id=c.id
        
         WHERE t.user_id=?
         AND ca.course_id=?
        
         ORDER BY c.class_name"
        
        );
        
        $stmt->execute([
            $userId,
            $courseId
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        
        }

    
    
    
    
    
    
    
    
}
?>
