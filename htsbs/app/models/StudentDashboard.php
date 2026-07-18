<?php

require_once dirname(__DIR__,2).'/config/database.php';

class StudentDashboard
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    public function getStudentInfo($userId)
    {
        $stmt = $this->db->prepare("
        SELECT
            u.full_name,
            u.profile_image,
            s.student_number,
            c.class_name
        FROM students s
        INNER JOIN users u
            ON s.user_id=u.id
        LEFT JOIN classes c
            ON s.class_id=c.id
        WHERE s.user_id=?
        ");

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function countLessons($userId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)

        FROM lesson_assignments la

        INNER JOIN students s
            ON la.class_id=s.class_id

        WHERE s.user_id=?
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchColumn();
    }

    public function countActivities($studentUserId)
        {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
        
                FROM activity_assignments aa
        
                INNER JOIN students s
                    ON aa.class_id = s.class_id
        
                WHERE s.user_id = ?
            ");
        
            $stmt->execute([
                $studentUserId
            ]);
        
            return $stmt->fetchColumn();
        }

    public function countQuizzes($userId)
{
    $stmt = $this->db->prepare("

    SELECT COUNT(*)

    FROM quiz_assignments qa

    INNER JOIN students s
        ON qa.class_id = s.class_id

    INNER JOIN quizzes q
        ON qa.quiz_id = q.id

    WHERE s.user_id = ?

    AND q.is_published = 1

    ");

    $stmt->execute([
        $userId
    ]);

    return $stmt->fetchColumn();
}

    public function countNotifications($userId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM notifications
        WHERE user_id=?
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchColumn();
    }

    public function recentLessons($userId)
    {
        $stmt = $this->db->prepare("
        SELECT
            l.lesson_title,
            l.created_at

        FROM lessons l

        INNER JOIN lesson_assignments la
            ON l.id=la.lesson_id

        INNER JOIN students s
            ON la.class_id=s.class_id

        WHERE s.user_id=?

        ORDER BY l.id DESC

        LIMIT 5
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recentNotifications($userId)
    {
        $stmt = $this->db->prepare("
        SELECT *
        FROM notifications
        WHERE user_id=?
        ORDER BY id DESC
        LIMIT 5
        ");

        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}