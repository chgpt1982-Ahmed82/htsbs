<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Activity
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }
    
/*-----------------------------------
    public function getTeacherId($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM teachers WHERE user_id=?"
        );

        $stmt->execute([$userId]);

        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        return $teacher['id'];
    }
    
    
-----------getTeacherId---------------*/

private function getTeacherId(int $userId): ?int {

    $sql = "

        SELECT id

        FROM teachers

        WHERE user_id = ?

        LIMIT 1

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([$userId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['id'] : null;

}





    public function getTeacherCourses($userId)
    {
        $sql = "
        SELECT DISTINCT
            c.id,
            c.course_name
        FROM course_assignments ca
        INNER JOIN teachers t
            ON ca.teacher_id=t.id
        INNER JOIN courses c
            ON ca.course_id=c.id
        WHERE t.user_id=?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

            public function getAllByTeacher($userId)
        {
            $sql = "
            SELECT
        
                a.*,
        
                c.course_name,
                c.course_code,
        
                COUNT(DISTINCT aa.id) AS usage_count,
        
                COUNT(DISTINCT s.id) AS submissions_count
        
            FROM activities a
        
            INNER JOIN courses c
                ON a.course_id = c.id
        
            INNER JOIN teachers t
                ON a.teacher_id = t.id
        
            LEFT JOIN activity_assignments aa
                ON a.id = aa.activity_id
        
            LEFT JOIN activity_submissions s
                ON a.id = s.activity_id
        
            WHERE t.user_id = ?
        
            GROUP BY a.id
        
            ORDER BY a.id DESC
            ";
        
            $stmt = $this->db->prepare($sql);
        
            $stmt->execute([$userId]);
        
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO activities
                (
                teacher_id,
                course_id,
                title,
                instructions,
                max_grade,
                due_date
                )
                VALUES
                (
                ?,?,?,?,?,?
                )"
                        );
    
        return $stmt->execute([
    $data['teacher_id'],
    $data['course_id'],
    $data['title'],
    $data['instructions'],
    $data['max_grade'],
    $data['due_date']
]);
    }
    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM activities WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


        public function update($id, $data)
        {
            $stmt = $this->db->prepare(
                "UPDATE activities
                 SET
                 course_id=?,
                 title=?,
                 instructions=?,
                 max_grade=?,
                 due_date=?
                 WHERE id=?"
            );
        
            return $stmt->execute([
                $data['course_id'],
                $data['title'],
                $data['instructions'],
                $data['max_grade'],
                $data['due_date'],
                $id
            ]);
        }


    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM activities
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
    
    public function getClasses()
    {
    return $this->db
        ->query("SELECT * FROM classes ORDER BY class_name")
        ->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
 * عدد الأنشطة الخاصة بالمعلم
 */
public function countTeacherActivities(int $userId): int {

    $teacherId = $this->getTeacherId($userId);

    if (!$teacherId) {
        return 0;
    }

    $sql = "

        SELECT COUNT(*) total

        FROM activities

        WHERE teacher_id = ?

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([$teacherId]);

    return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

}
    /**
 * آخر الأنشطة الخاصة بالمعلم
 */
public function latestActivities(
    int $teacherId,
    int $limit = 5
): array {

    $sql = "

        SELECT

            id,

            title,

            created_at

        FROM activities

        WHERE teacher_id = ?

        ORDER BY created_at DESC

        LIMIT ?

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->bindValue(
        1,
        $teacherId,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        2,
        $limit,
        PDO::PARAM_INT
    );

    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);

}
    
}
?>
