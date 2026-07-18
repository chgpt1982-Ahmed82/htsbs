<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Course
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
            "SELECT * FROM courses ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "
        INSERT INTO courses
        (
            course_name,
            course_code,
            credit_hours,
            description,
            department_id
        )
        VALUES
        (
            ?,?,?,?,?
        )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['course_name'],
            $data['course_code'],
            $data['credit_hours'],
            $data['description'],
            $data['department_id']
        ]);
    }
    
    public function getById($id)
    {
    $stmt = $this->db->prepare(
        "SELECT * FROM courses WHERE id = ?"
    );

    $stmt->execute([$id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update($id,$data)
    {
    $stmt = $this->db->prepare(
        "UPDATE courses
         SET
         course_name=?,
         course_code=?,
         credit_hours=?,
         description=?,
         department_id=?
         WHERE id=?"
    );

    return $stmt->execute([
        $data['course_name'],
        $data['course_code'],
        $data['credit_hours'],
        $data['description'],
        $data['department_id'],
        $id
    ]);
    }
    
    public function delete($id)
    {
    $stmt = $this->db->prepare(
        "DELETE FROM courses WHERE id=?"
    );

    return $stmt->execute([$id]);
    }
}