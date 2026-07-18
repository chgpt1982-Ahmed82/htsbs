<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Department
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getAll()
    {
        $stmt = $this->db->query(
            "SELECT * FROM departments ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM departments WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO departments
            (department_name, department_code)
            VALUES (?,?)"
        );

        return $stmt->execute([
            $data['department_name'],
            $data['department_code']
        ]);
    }

    public function update($id,$data)
    {
        $stmt = $this->db->prepare(
            "UPDATE departments
             SET
             department_name=?,
             department_code=?
             WHERE id=?"
        );

        return $stmt->execute([
            $data['department_name'],
            $data['department_code'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM departments
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
}