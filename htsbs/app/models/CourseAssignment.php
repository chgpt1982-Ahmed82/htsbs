<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class CourseAssignment
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getAll()
    {
        $sql = "
        SELECT
            ca.*,
            u.full_name,
            c.course_name,
            cl.class_name
        FROM course_assignments ca
        INNER JOIN teachers t
            ON ca.teacher_id = t.id
        INNER JOIN users u
            ON t.user_id = u.id
        INNER JOIN courses c
            ON ca.course_id = c.id
        INNER JOIN classes cl
            ON ca.class_id = cl.id
        ORDER BY ca.id DESC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

 public function latestActivities($userId)
{
    $sql = "
    SELECT
        a.title,
        a.created_at
    FROM activities a
    INNER JOIN teachers t
        ON a.teacher_id = t.id
    WHERE t.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([$userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}       

    
    
    public function getTeachers()
    {
        $sql = "
        SELECT
            t.id,
            u.full_name
        FROM teachers t
        INNER JOIN users u
            ON t.user_id = u.id
        ORDER BY u.full_name
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCourses()
    {
        return $this->db
            ->query("SELECT * FROM courses ORDER BY course_name")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClasses()
    {
        return $this->db
            ->query("SELECT * FROM classes ORDER BY class_name")
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO course_assignments
            (
                teacher_id,
                course_id,
                class_id,
                semester,
                academic_year
            )
            VALUES
            (
                ?,?,?,?,?
            )"
        );

        return $stmt->execute([
            $data['teacher_id'],
            $data['course_id'],
            $data['class_id'],
            $data['semester'],
            $data['academic_year']
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM course_assignments
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
    
    
    public function getById($id)
        {
        $stmt = $this->db->prepare(
        "SELECT * FROM course_assignments
        WHERE id=?"
        );
        
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        }
        
        public function update($id, $data)
        {
        $stmt = $this->db->prepare(
        "UPDATE course_assignments
        SET
        teacher_id=?,
        course_id=?,
        class_id=?,
        semester=?,
        academic_year=?
        WHERE id=?"
        );
        
        return $stmt->execute([
            $data['teacher_id'],
            $data['course_id'],
            $data['class_id'],
            $data['semester'],
            $data['academic_year'],
            $id
        ]);
        
        
        }
        
        public function getTeacherCourses($userId)
            {
            $sql = "
            SELECT
            c.course_name,
            c.course_code,
            cl.class_name,
            ca.semester,
            ca.academic_year
            FROM course_assignments ca
            
     
            INNER JOIN teachers t
                ON ca.teacher_id = t.id
            
            INNER JOIN courses c
                ON ca.course_id = c.id
            
            INNER JOIN classes cl
                ON ca.class_id = cl.id
            
            WHERE t.user_id = ?
            
            ORDER BY c.course_name
            ";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
         
            
            }


            
           
          public function countTeacherClasses($userId)
                {
                $stmt = $this->db->prepare(
                "SELECT COUNT(*)
                FROM course_assignments ca
                INNER JOIN teachers t
                ON ca.teacher_id = t.id
                WHERE t.user_id = ?"
                );
                
                
                $stmt->execute([$userId]);
                
                return $stmt->fetchColumn();
                
                
                }

            
            
            public function countTeacherQuizzes($userId)
            {
            $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM quizzes q
            INNER JOIN teachers t
            ON q.teacher_id = t.id
            WHERE t.user_id = ?
            ");
            
            
            $stmt->execute([$userId]);
            
            return $stmt->fetchColumn();
            
            
            }

        public function countTeacherCourses($userId)
            {
            $stmt = $this->db->prepare(
            "SELECT COUNT(*)
            FROM course_assignments ca
            INNER JOIN teachers t
            ON ca.teacher_id = t.id
            WHERE t.user_id = ?"
            );
            
            $stmt->execute([$userId]);
            
            return $stmt->fetchColumn();
            
            
            }
                
        public function countTeacherStudents($userId)
            {
                $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT s.id)
            
                FROM students s
            
                INNER JOIN course_assignments ca
                    ON s.class_id = ca.class_id
            
                INNER JOIN teachers t
                    ON ca.teacher_id = t.id
            
                WHERE t.user_id = ?
                ");
            
                $stmt->execute([$userId]);
            
                return $stmt->fetchColumn();
            }    
            
           public function countTeacherActivities($userId)
            {
                $stmt = $this->db->prepare("
                SELECT id
                FROM teachers
                WHERE user_id=?
                ");
            
                $stmt->execute([$userId]);
            
                $teacherId = $stmt->fetchColumn();
            
                $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM activities
                WHERE teacher_id=?
                ");
            
                $stmt->execute([$teacherId]);
            
                return $stmt->fetchColumn();
            }
                    
        
            private function getTeacherId($userId)
            {
                $stmt = $this->db->prepare("
                SELECT id
                FROM teachers
                WHERE user_id=?
                ");
            
                $stmt->execute([$userId]);
            
                return $stmt->fetchColumn();
            }
              
            public function latestLessons($userId)
            {
                $teacherId = $this->getTeacherId($userId);
            
                $stmt = $this->db->prepare("
                SELECT lesson_title, created_at
                FROM lessons
                WHERE teacher_id=?
                ORDER BY id DESC
                LIMIT 5
                ");
            
                $stmt->execute([$teacherId]);
            
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
         public function countTeacherLessons($userId)
            {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*)
            
                    FROM lessons l
            
                    INNER JOIN teachers t
                        ON l.teacher_id = t.id
            
                    WHERE t.user_id = ?
                ");
            
                $stmt->execute([$userId]);
            
                return $stmt->fetchColumn();
            }      
            
            

/**
 * عدد الواجبات الخاصة بالمعلم
 */
public function countTeacherAssignments($userId)
            {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*)
            
                    FROM assignments l
            
                    INNER JOIN teachers t
                        ON l.teacher_id = t.id
            
                    WHERE t.user_id = ?
                ");
            
                $stmt->execute([$userId]);
            
                return $stmt->fetchColumn();
            }      
            


    /**
 * آخر الواجبات الخاصة بالمعلم
 */


 public function latestAssignments($userId)
            {
                $teacherId = $this->getTeacherId($userId);
            
                $stmt = $this->db->prepare("
                SELECT title, created_at
                FROM assignments
                WHERE teacher_id=?
                ORDER BY id DESC
                LIMIT 5
                ");
            
                $stmt->execute([$teacherId]);
            
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

                        
}
?>
