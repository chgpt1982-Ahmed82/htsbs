<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Attendance
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE attendance
             SET
             status=?,
             notes=?
             WHERE id=?"
        );

        return $stmt->execute([
            $data['status'],
            $data['notes'],
            $id
        ]);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM attendance
             WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM attendance
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
}
?>
