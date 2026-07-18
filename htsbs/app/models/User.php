<?php

require_once __DIR__ . '/../../config/database.php';

class User
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function findByEmail($email)
    {
        $sql = "
        SELECT *
        FROM users
        WHERE email = ?
        LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}