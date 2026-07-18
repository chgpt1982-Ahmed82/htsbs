<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class ClassModel
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
            "SELECT * FROM classes ORDER BY id DESC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM classes WHERE id=?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO classes
            (
                class_name,
                academic_year,
                semester
            )
            VALUES
            (
                ?,?,?
            )"
        );

        return $stmt->execute([
            $data['class_name'],
            $data['academic_year'],
            $data['semester']
        ]);
    }

    public function update($id,$data)
    {
        $stmt = $this->db->prepare(
            "UPDATE classes
             SET
             class_name=?,
             academic_year=?,
             semester=?
             WHERE id=?"
        );

        return $stmt->execute([
            $data['class_name'],
            $data['academic_year'],
            $data['semester'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM classes
             WHERE id=?"
        );

        return $stmt->execute([$id]);
    }
}
?>
