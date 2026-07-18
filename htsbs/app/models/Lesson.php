<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Lesson
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getTeacherCourses($userId)
    {
        $sql = "
        SELECT
            c.id,
            c.course_name
        FROM course_assignments ca

        INNER JOIN teachers t
            ON ca.teacher_id = t.id

        INNER JOIN courses c
            ON ca.course_id = c.id

        WHERE t.user_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
/*--------------------------------
    public function getTeacherId($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT id
             FROM teachers
             WHERE user_id=?"
        );

        $stmt->execute([$userId]);

        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        return $teacher['id'];
    }
--------------------------------------------*/


    public function getAllByTeacher($userId)
    {
        $sql = "
        SELECT
    
            l.*,
    
            c.course_name,
            c.course_code,
    
            COUNT(la.id) AS usage_count
    
        FROM lessons l
    
        INNER JOIN courses c
            ON l.course_id = c.id
    
        INNER JOIN teachers t
            ON l.teacher_id = t.id
    
        LEFT JOIN lesson_assignments la
            ON l.id = la.lesson_id
    
        WHERE t.user_id = ?
    
        GROUP BY l.id
    
        ORDER BY l.id DESC
        ";
    
        $stmt = $this->db->prepare($sql);
    
        $stmt->execute([$userId]);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO lessons
            (
                teacher_id,
                course_id,
                lesson_title,
                lesson_description,
                lesson_type,
                file_path,
                video_link
            )
            VALUES
            (
                ?,?,?,?,?,?,?
            )"
        );

        return $stmt->execute([
            $data['teacher_id'],
            $data['course_id'],
            $data['lesson_title'],
            $data['lesson_description'],
            $data['lesson_type'],
            $data['file_path'],
            $data['video_link']
        ]);
    }
    
        
       public function getById($id)
        {
        $stmt = $this->db->prepare(
        "SELECT * FROM lessons WHERE id=?"
        );
        
        
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        }
        
        public function update($id,$data)
        {
        $stmt = $this->db->prepare(
        "UPDATE lessons
        SET
        course_id=?,
        lesson_title=?,
        lesson_description=?,
        lesson_type=?,
        video_link=?
        WHERE id=?"
        );
        
        return $stmt->execute([
            $data['course_id'],
            $data['lesson_title'],
            $data['lesson_description'],
            $data['lesson_type'],
            $data['video_link'],
            $id
        ]);
        
        }
        
        public function delete($id)
        {
        $stmt = $this->db->prepare(
        "DELETE FROM lessons
        WHERE id=?"
        );
        
        
        return $stmt->execute([$id]);
        
        
        }
        
    /**
 * إحصائيات الدروس حسب الشهر
 */
 public function getTeacherId(
    int $userId
): ?int {

    $sql = "

        SELECT id

        FROM teachers

        WHERE user_id = ?

        LIMIT 1

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $userId

    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['id'] : null;

}
 
 
public function monthlyLessons( int $teacherId): array {

    $sql = "

        SELECT

            MONTH(created_at) AS month,

            COUNT(*) AS total

        FROM lessons

        WHERE teacher_id = ?

        AND YEAR(created_at)=YEAR(CURDATE())

        GROUP BY MONTH(created_at)

        ORDER BY MONTH(created_at)

    ";

    $stmt = $this->db->prepare($sql);

    $teacherId = $this->getTeacherId($teacherId);

    $stmt->execute([$teacherId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array_fill(1,12,0);

    foreach($rows as $row){

        $data[(int)$row['month']] = (int)$row['total'];

    }

    return $data;

}        
            
    
    
    
    
    
    
    
}
?>
