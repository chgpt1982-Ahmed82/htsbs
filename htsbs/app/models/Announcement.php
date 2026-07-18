<?php

require_once __DIR__ . '/../../config/database.php';

class Announcement
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    إنشاء إعلان
    */

    public function create($data)
    {
        $stmt = $this->db->prepare(

        "INSERT INTO announcements
        (
            title,
            message,
            role,
            created_by
        )
        VALUES
        (
            ?,?,?,?
        )"

        );

        $stmt->execute([

            $data['title'],
            $data['message'],
            $data['role'],
            $data['created_by']

        ]);

        return $this->db->lastInsertId();
    }

    /*
    ربط الإعلان بالصفوف
    */

    public function assignClasses(
        $announcementId,
        $classIds
    )
    {
        foreach($classIds as $classId)
        {
            $stmt = $this->db->prepare(

            "INSERT INTO announcement_classes
            (
                announcement_id,
                class_id
            )
            VALUES
            (
                ?,?
            )"

            );

            $stmt->execute([
                $announcementId,
                $classId
            ]);
        }
    }

    /*
    جميع الإعلانات
    */

    public function getAll()
    {
        $stmt = $this->db->query(

        "SELECT *

         FROM announcements

         ORDER BY created_at DESC"

        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    إعلان واحد
    */

    public function find($id)
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM announcements

         WHERE id=?"

        );

        $stmt->execute([
            $id
        ]);

        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }

    /*
    إعلانات حسب الدور
    */

    public function getByRole(
        $role
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM announcements

         WHERE role='all'
         OR role=?

         ORDER BY created_at DESC"

        );

        $stmt->execute([
            $role
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    إعلانات الطالب حسب الصف
    */

    public function getStudentAnnouncements(
        $classId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            a.*

         FROM announcements a

         INNER JOIN announcement_classes ac
         ON a.id=ac.announcement_id

         WHERE ac.class_id=?

         ORDER BY a.created_at DESC"

        );

        $stmt->execute([
            $classId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    إعلانات ولي الأمر
    */

    public function getParentAnnouncements(
        $classId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            a.*

         FROM announcements a

         INNER JOIN announcement_classes ac
         ON a.id=ac.announcement_id

         WHERE ac.class_id=?

         ORDER BY a.created_at DESC"

        );

        $stmt->execute([
            $classId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    حذف إعلان
    */

    public function delete($id)
    {
        $stmt = $this->db->prepare(

        "DELETE FROM announcements

         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }

    /*
    إعلانات المعلم
    */

    public function getTeacherAnnouncements(
        $teacherId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM announcements

         WHERE created_by=?

         ORDER BY created_at DESC"

        );

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    الصفوف المرتبطة بالإعلان
    */

    public function getAnnouncementClasses(
        $announcementId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            c.*

         FROM announcement_classes ac

         INNER JOIN classes c
         ON ac.class_id=c.id

         WHERE ac.announcement_id=?"

        );

        $stmt->execute([
            $announcementId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    آخر الإعلانات
    */

    public function getLatest(
        $limit = 5
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM announcements

         ORDER BY created_at DESC

         LIMIT ?"

        );

        $stmt->bindValue(
            1,
            (int)$limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}
?>
