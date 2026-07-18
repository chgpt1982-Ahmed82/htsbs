<?php

require_once dirname(__DIR__, 2) . '/config/database.php';

class Student
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getAll()
{
    $stmt = $this->db->prepare(
        "SELECT

            s.*,

            u.full_name,
            u.email,
            u.phone,

            d.department_name,

            c.class_name

         FROM students s

         INNER JOIN users u
            ON s.user_id = u.id

         LEFT JOIN departments d
            ON s.department_id = d.id

         LEFT JOIN classes c
            ON s.class_id = c.id

         ORDER BY u.full_name ASC"
    );

    $stmt->execute();

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
                    3,?,?,?,?, 'active'
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
                "INSERT INTO students
                (
                    user_id,
                    department_id,
                    class_id,
                    student_number,
                    national_id,
                    academic_level,
                    gpa,
                    guardian_phone_1,
                    guardian_phone_2
                )
                VALUES
                (
                    ?,?,?,?,?,?,?,?,?
                )"
            );
        
            $stmt->execute([
                $userId,
                $data['department_id'],
                $data['class_id'],
                $data['student_number'],
                $data['national_id'],
                $data['academic_level'],
                $data['gpa'],
                $data['guardian_phone_1'],
                $data['guardian_phone_2']
            ]);
        
            $this->db->commit();
        
            return true;
        
        } catch (Exception $e) {
        
            $this->db->rollBack();
        
            throw $e;
        }
        
        
        }

    
    public function getById($id)
        {
        $sql = "
        SELECT
        s.*,
        u.full_name,
        u.email,
        u.phone
        FROM students s
        INNER JOIN users u
        ON s.user_id = u.id
        WHERE s.id = ?
        ";
        
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
        
        }
        
        public function update($id, $data)
        {
        $student = $this->getById($id);
        
        
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
            $student['user_id']
        ]);
        
        $stmt = $this->db->prepare(
            "UPDATE students
             SET
             department_id=?,
             class_id=?,
             student_number=?,
             national_id=?,
             academic_level=?,
             gpa=?,
             guardian_phone_1=?,
             guardian_phone_2=?
             WHERE id=?"
        );
        
        return $stmt->execute([
            $data['department_id'],
            $data['class_id'],
            $data['student_number'],
            $data['national_id'],
            $data['academic_level'],
            $data['gpa'],
            $data['guardian_phone_1'],
            $data['guardian_phone_2'],
            $id
        ]);
        
        
        }

        
        public function delete($id)
        {
        $student = $this->getById($id);
        
        
        if (!$student) {
            return false;
        }
        
        $this->db->beginTransaction();
        
        try {
        
            /*
            Quiz Attempts
            */
            $stmt = $this->db->prepare(
                "DELETE FROM quiz_attempts
                 WHERE student_id=?"
            );
        
            $stmt->execute([$id]);
        
            /*
            Enrollments
            */
            try {
        
                $stmt = $this->db->prepare(
                    "DELETE FROM enrollments
                     WHERE student_id=?"
                );
        
                $stmt->execute([$id]);
        
            } catch(Exception $e) {}
        
            /*
            Attendance
            */
            try {
        
                $stmt = $this->db->prepare(
                    "DELETE FROM attendance
                     WHERE student_id=?"
                );
        
                $stmt->execute([$id]);
        
            } catch(Exception $e) {}
        
            /*
            Grades
            */
            try {
        
                $stmt = $this->db->prepare(
                    "DELETE FROM grades
                     WHERE student_id=?"
                );
        
                $stmt->execute([$id]);
        
            } catch(Exception $e) {}
        
            /*
            Student Courses
            */
            try {
        
                $stmt = $this->db->prepare(
                    "DELETE FROM student_courses
                     WHERE student_id=?"
                );
        
                $stmt->execute([$id]);
        
            } catch(Exception $e) {}
        
            /*
            Student Record
            */
            $stmt = $this->db->prepare(
                "DELETE FROM students
                 WHERE id=?"
            );
        
            $stmt->execute([$id]);
        
            /*
            User Record
            */
            $stmt = $this->db->prepare(
                "DELETE FROM users
                 WHERE id=?"
            );
        
            $stmt->execute([
                $student['user_id']
            ]);
        
            $this->db->commit();
        
            return true;
        
        } catch(Exception $e) {
        
            $this->db->rollBack();
        
            throw $e;
        }
        
        
        }

    public function getStudentId($userId)
        {
            $stmt = $this->db->prepare(
                "SELECT id
                 FROM students
                 WHERE user_id=?"
            );
        
            $stmt->execute([$userId]);
        
            return $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        }
        
        public function getStudentInfo($userId)
{
    $sql = "
    SELECT

        s.*,

        u.full_name,

        d.department_name,

        c.class_name

    FROM students s

    INNER JOIN users u
        ON s.user_id = u.id

    INNER JOIN departments d
        ON s.department_id = d.id

    LEFT JOIN classes c
        ON s.class_id = c.id

    WHERE s.user_id = ?
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        $userId
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}
            
       
        public function getAllByClass($classId = null)
            {
            $sql = "
            
            
            SELECT
            
                s.*,
            
                u.full_name,
                u.email,
                u.phone,
            
                d.department_name,
            
                c.class_name
            
            FROM students s
            
            INNER JOIN users u
                ON s.user_id = u.id
            
            LEFT JOIN departments d
                ON s.department_id = d.id
            
            LEFT JOIN classes c
                ON s.class_id = c.id
            
            ";
            
            if(!empty($classId))
            {
                $sql .= " WHERE s.class_id = ? ";
            }
            
            $sql .= " ORDER BY u.full_name ASC ";
            
            $stmt = $this->db->prepare($sql);
            
            if(!empty($classId))
            {
                $stmt->execute([$classId]);
            }
            else
            {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            }
        public function getClasses()
            {
            $stmt = $this->db->query(
            "SELECT *
            FROM classes
            ORDER BY class_name"
            );
            
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            }

    
}
?>
