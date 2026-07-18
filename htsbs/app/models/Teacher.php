<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Teacher
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
            t.id,
            u.full_name,
            u.email,
            u.phone,
            d.department_name,
            t.specialization,
            t.qualification
        FROM teachers t
        INNER JOIN users u ON t.user_id = u.id
        INNER JOIN departments d ON t.department_id = d.id
        ORDER BY t.id DESC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments()
    {
        $stmt = $this->db->query(
            "SELECT * FROM departments ORDER BY department_name"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $this->db->beginTransaction();

        try {

            $password = password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            );

            $stmt = $this->db->prepare(
                "INSERT INTO users
                (
                    role_id,
                    full_name,
                    email,
                    password,
                    phone,
                    status
                )
                VALUES
                (
                    2,?,?,?,?, 'active'
                )"
            );

            $stmt->execute([
                $data['full_name'],
                $data['email'],
                $password,
                $data['phone']
            ]);

            $userId = $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO teachers
                (
                    user_id,
                    department_id,
                    specialization,
                    qualification
                )
                VALUES
                (
                    ?,?,?,?
                )"
            );

            $stmt->execute([
                $userId,
                $data['department_id'],
                $data['specialization'],
                $data['qualification']
            ]);

            $this->db->commit();

            return true;

        } catch(Exception $e) {

            $this->db->rollBack();

            throw $e;

        }
    }
    
    public function getById($id)
        {
        $sql = "
        SELECT
        t.*,
        u.full_name,
        u.email,
        u.phone
        FROM teachers t
        INNER JOIN users u
        ON t.user_id = u.id
        WHERE t.id = ?
        ";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
        }
        
        public function update($id, $data)
        {
        $teacher = $this->getById($id);
        
        
        $stmt = $this->db->prepare(
            "UPDATE users
             SET
             full_name=?,
             email=?,
             phone=?
             WHERE id=?"
        );
        
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone'],
            $teacher['user_id']
        ]);
        
        $stmt = $this->db->prepare(
            "UPDATE teachers
             SET
             department_id=?,
             specialization=?,
             qualification=?
             WHERE id=?"
        );
        
        return $stmt->execute([
            $data['department_id'],
            $data['specialization'],
            $data['qualification'],
            $id
        ]);
        
        
        }
        
        public function delete($id)
        {
        $teacher = $this->getById($id);
        
        
        $stmt = $this->db->prepare(
            "DELETE FROM teachers WHERE id=?"
        );
        
        $stmt->execute([$id]);
        
        $stmt = $this->db->prepare(
            "DELETE FROM users WHERE id=?"
        );
        
        return $stmt->execute([
            $teacher['user_id']
        ]);
        
        
        }
        
    /*
====================================================
جلب رقم المعلم من user_id
====================================================
*/

public function getTeacherIdByUser($userId)
{
    $stmt = $this->db->prepare("

        SELECT id

        FROM teachers

        WHERE user_id = ?

        LIMIT 1

    ");

    $stmt->execute([
        $userId
    ]);

    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {

        return null;

    }

    return (int)$teacher['id'];
}
        
        
}
?>
