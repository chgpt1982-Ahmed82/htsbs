<?php

require_once __DIR__ . '/../../config/database.php';

class Notification
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    public function create(
        $userId,
        $title,
        $message,
        $type
    )
    {
        $stmt = $this->db->prepare(

        "INSERT INTO notifications
        (
            user_id,
            title,
            message,
            type
        )
        VALUES
        (
            ?,?,?,?
        )"

        );

        return $stmt->execute([

            $userId,
            $title,
            $message,
            $type

        ]);
    }

    public function getUserNotifications($userId)
    {
        $stmt = $this->db->prepare(

        "SELECT *
         FROM notifications
         WHERE user_id=?
         ORDER BY created_at DESC"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unreadCount($userId)
    {
        $stmt = $this->db->prepare(

        "SELECT COUNT(*) total
         FROM notifications
         WHERE user_id=?
         AND is_read=0"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetch()['total'];
    }

    public function markRead($id)
    {
        $stmt = $this->db->prepare(

        "UPDATE notifications
         SET is_read=1
         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }
}