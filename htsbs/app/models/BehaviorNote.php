<?php

class BehaviorNote
{
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/../../config/database.php';

        $database = new Database();

        $this->db = $database->connect();
    }

    /*
    ==============================
    إضافة ملاحظة
    ==============================
    */

    public function addNote(
        $studentId,
        $teacherId,
        $type,
        $title,
        $details,
        $noteDate
    )
    {
        $sql = "
        INSERT INTO behavior_notes
        (
            student_id,
            teacher_id,
            note_type,
            title,
            details,
            note_date
        )
        VALUES
        (
            ?,?,?,?,?,?
        )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $studentId,
            $teacherId,
            $type,
            $title,
            $details,
            $noteDate
        ]);
    }

    /*
    ==============================
    ملاحظات طالب
    ==============================
    */

    public function getStudentNotes($studentId)
    {
        $sql = "
SELECT

    b.*,
    s.student_number,
    u.full_name,
    c.class_name

FROM behavior_notes b

INNER JOIN students s
    ON b.student_id = s.id

INNER JOIN users u
    ON s.user_id = u.id

LEFT JOIN classes c
    ON s.class_id = c.id

WHERE b.teacher_id = ?

ORDER BY b.id DESC";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$studentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ==============================
    ملاحظات المعلم
    ==============================
    */

   public function getTeacherNotes($teacherId)
{
    $sql = "
    SELECT

        b.*,

        s.student_number,

        u.full_name,

        c.class_name

    FROM behavior_notes b

    INNER JOIN students s
        ON b.student_id = s.id

    INNER JOIN users u
        ON s.user_id = u.id

    LEFT JOIN classes c
        ON s.class_id = c.id

    WHERE b.teacher_id = ?

    ORDER BY b.id DESC
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([$teacherId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /*
    ==============================
    جلب ملاحظة واحدة
    ==============================
    */

    public function getById($id)
    {
        $sql = "
        SELECT *
        FROM behavior_notes
        WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    ==============================
    تعديل ملاحظة
    ==============================
    */

    public function updateNote(
        $id,
        $type,
        $title,
        $details,
        $noteDate
    )
    {
        $sql = "
        UPDATE behavior_notes
        SET

            note_type = ?,
            title = ?,
            details = ?,
            note_date = ?

        WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $type,
            $title,
            $details,
            $noteDate,
            $id
        ]);
    }

    /*
    ==============================
    حذف ملاحظة
    ==============================
    */

    public function deleteNote($id)
    {
        $sql = "
        DELETE FROM behavior_notes
        WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$id]);
    }

    /*
    ==============================
    عدد الإيجابية
    ==============================
    */

    public function countPositive($studentId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM behavior_notes
        WHERE student_id=?
        AND note_type='positive'
        ");

        $stmt->execute([$studentId]);

        return $stmt->fetchColumn();
    }

    /*
    ==============================
    عدد السلبية
    ==============================
    */

    public function countNegative($studentId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM behavior_notes
        WHERE student_id=?
        AND note_type='negative'
        ");

        $stmt->execute([$studentId]);

        return $stmt->fetchColumn();
    }

    /*
    ==============================
    عدد التنبيهات
    ==============================
    */

    public function countWarnings($studentId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM behavior_notes
        WHERE student_id=?
        AND note_type='warning'
        ");

        $stmt->execute([$studentId]);

        return $stmt->fetchColumn();
    }

    /*
    ==============================
    إجمالي الملاحظات
    ==============================
    */

    public function countTotal($studentId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM behavior_notes
        WHERE student_id=?
        ");

        $stmt->execute([$studentId]);

        return $stmt->fetchColumn();
    }

    /*
    ==============================
    تعليم كمقروءة
    ==============================
    */

    public function markAsRead($id)
    {
        $stmt = $this->db->prepare("
        UPDATE behavior_notes
        SET is_read=1
        WHERE id=?
        ");

        return $stmt->execute([$id]);
    }
    

/**
 * الحصول على Teacher ID من User ID
 */
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


  /**
 * عدد الملاحظات السلوكية للمعلم
 */
public function countTeacherBehaviorNotes(
    int $teacherId
): int {

    $sql = "

        SELECT COUNT(*) AS total

        FROM behavior_notes

        WHERE teacher_id = ?

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $teacherId

    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int)($row['total'] ?? 0);

}

/**
 * عدد المخالفات السلبية
 */
public function countNegativeBehaviorNotes(
    int $teacherId
): int {

    $sql = "

        SELECT COUNT(*) AS total

        FROM behavior_notes

        WHERE teacher_id = ?

        AND note_type = 'negative'

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([

        $teacherId

    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int)($row['total'] ?? 0);

}

/**
 * إحصائيات الملاحظات السلوكية
 */
public function behaviorStatistics(
    int $userId
): array {

    $teacherId = $this->getTeacherId($userId);

    if (!$teacherId) {

        return [

            'positive' => 0,
            'warning'  => 0,
            'negative' => 0,
            'total'    => 0

        ];

    }

    $sql = "

        SELECT

            note_type,

            COUNT(*) AS total

        FROM behavior_notes

        WHERE teacher_id = ?

        GROUP BY note_type

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([$teacherId]);

    $stats = [

        'positive' => 0,
        'warning'  => 0,
        'negative' => 0,
        'total'    => 0

    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        $stats[$row['note_type']] = (int)$row['total'];

        $stats['total'] += (int)$row['total'];

    }

    return $stats;

}
    
}