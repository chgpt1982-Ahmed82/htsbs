<?php

require_once __DIR__ . '/../../config/database.php';

class Message
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    إرسال رسالة
    */
    public function send(
        $senderId,
        $receiverId,
        $subject,
        $message
    )
    {
        $stmt = $this->db->prepare(

        "INSERT INTO messages
        (
            sender_id,
            receiver_id,
            subject,
            message
        )
        VALUES
        (
            ?,?,?,?
        )"

        );

        return $stmt->execute([

            $senderId,
            $receiverId,
            $subject,
            $message

        ]);
    }

    /*
    صندوق الوارد
    ⚠️ أُضيف شرط receiver_deleted = 0 — لا تُعرض الرسائل التي حذفها المستلم
    */
    public function inbox($userId)
    {
        $stmt = $this->db->prepare(


        "SELECT

            m.*,

            u.full_name AS sender_name,
            u.role_id,

            d.department_name,
            c.class_name

        FROM messages m

        INNER JOIN users u
            ON m.sender_id = u.id

        LEFT JOIN teachers t
            ON u.id = t.user_id

        LEFT JOIN departments d
            ON t.department_id = d.id

        LEFT JOIN students s
            ON u.id = s.user_id

        LEFT JOIN classes c
            ON s.class_id = c.id

        WHERE m.receiver_id = ?
          AND m.receiver_deleted = 0

        ORDER BY m.created_at DESC"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);


    }


    /*
    الرسائل المرسلة
    ⚠️ أُضيف شرط sender_deleted = 0 — لا تُعرض الرسائل التي حذفها المرسل
    */
    public function sent($userId)
    {
        $stmt = $this->db->prepare(

        "SELECT

            m.*,
            u.full_name AS receiver_name

        FROM messages m

        INNER JOIN users u
        ON m.receiver_id = u.id

        WHERE m.sender_id = ?
          AND m.sender_deleted = 0

        ORDER BY m.created_at DESC"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    عرض رسالة
    (بلا تغيير — find() تُستخدم من الطرفين، والصفحة المستدعية
     تتحقق من الملكية بنفسها كما هو مطبَّق في view.php)
    */
    public function find($id)
    {
        $stmt = $this->db->prepare(


        "SELECT

            m.*,

            s.full_name AS sender_name,
            s.role_id AS sender_role,

            r.full_name AS receiver_name,

            d.department_name,
            c.class_name

        FROM messages m

        INNER JOIN users s
            ON m.sender_id = s.id

        INNER JOIN users r
            ON m.receiver_id = r.id

        LEFT JOIN teachers t
            ON s.id = t.user_id

        LEFT JOIN departments d
            ON t.department_id = d.id

        LEFT JOIN students st
            ON s.id = st.user_id

        LEFT JOIN classes c
            ON st.class_id = c.id

        WHERE m.id = ?"

        );

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);


    }


    /*
    تعليم كمقروءة
    */
    public function markRead($id)
    {
        $stmt = $this->db->prepare(

        "UPDATE messages
         SET is_read=1
         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }

    /*
    ====================================================================
    حذف من صندوق الوارد (المستلم)
    حذف منطقي: نضع receiver_deleted = 1 بدل حذف السطر فعلياً
    إن كان الطرف الآخر (المرسل) قد حذفها هو أيضاً، نحذف السطر نهائياً
    (لا فائدة من الاحتفاظ بسطر حذفه الطرفان معاً)
    ====================================================================
    */
    public function deleteForReceiver($id)
    {
        $stmt = $this->db->prepare("
            UPDATE messages SET receiver_deleted = 1 WHERE id = ?
        ");
        $result = $stmt->execute([$id]);

        $this->purgeIfBothDeleted($id);

        return $result;
    }

    /*
    حذف من صندوق الصادر (المرسل)
    نفس منطق الحذف المنطقي أعلاه، على الطرف المقابل
    */
    public function deleteForSender($id)
    {
        $stmt = $this->db->prepare("
            UPDATE messages SET sender_deleted = 1 WHERE id = ?
        ");
        $result = $stmt->execute([$id]);

        $this->purgeIfBothDeleted($id);

        return $result;
    }

    /*
    حذف نهائي فقط إذا حذفها الطرفان معاً
    (تنظيف تلقائي — لا تتراكم رسائل ميتة في الجدول للأبد)
    */
    private function purgeIfBothDeleted($id): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM messages
            WHERE id = ? AND sender_deleted = 1 AND receiver_deleted = 1
        ");
        $stmt->execute([$id]);
    }

    /*
    حذف فعلي مباشر (للاستخدام الإداري فقط إن احتجته لاحقاً)
    ⚠️ لم يعد يُستخدم من delete.php / delete_sent.php
    */
    public function delete($id)
    {
        $stmt = $this->db->prepare(

        "DELETE FROM messages
         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }

    /*
    عدد الرسائل غير المقروءة
    ⚠️ أُضيف شرط receiver_deleted = 0 — رسالة محذوفة من الوارد
    لا يجب أن تُحتسب ضمن العداد غير المقروء
    */
    public function unreadCount($userId)
    {
        $stmt = $this->db->prepare(

        "SELECT COUNT(*) total

         FROM messages

         WHERE receiver_id=?
         AND is_read=0
         AND receiver_deleted=0"

        );

        $stmt->execute([
            $userId
        ]);

        return $stmt->fetch()['total'];
    }
}
?>