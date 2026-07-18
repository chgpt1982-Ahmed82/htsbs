<?php

require_once __DIR__ . '/../../config/database.php';

class Calendar
{
    private $db;

    public function __construct()
    {
        $this->db =
        (new Database())->connect();
    }

    /*
    إنشاء حدث
    */

    public function create($data)
    {
        $stmt = $this->db->prepare(

        "INSERT INTO calendar_events
        (
            title,
            description,
            event_type,
            start_date,
            end_date,
            created_by
        )
        VALUES
        (
            ?,?,?,?,?,?
        )"

        );

        $stmt->execute([

            $data['title'],
            $data['description'],
            $data['event_type'],
            $data['start_date'],
            $data['end_date'],
            $data['created_by']

        ]);

        return $this->db->lastInsertId();
    }

    /*
    ربط الحدث بالصفوف
    */

    public function assignClasses(
        $eventId,
        $classIds
    )
    {
        foreach($classIds as $classId)
        {
            $stmt = $this->db->prepare(

            "INSERT INTO calendar_event_classes
            (
                event_id,
                class_id
            )
            VALUES
            (
                ?,?
            )"

            );

            $stmt->execute([

                $eventId,
                $classId

            ]);
        }
    }

    /*
    حدث واحد
    */

    public function find($id)
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM calendar_events

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
    جميع الأحداث
    */

    public function getAll()
    {
        $stmt = $this->db->query(

        "SELECT *

         FROM calendar_events

         ORDER BY start_date ASC"

        );

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    أحداث المعلم
    */

    public function getTeacherEvents(
        $teacherId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM calendar_events

         WHERE created_by=?

         ORDER BY start_date ASC"

        );

        $stmt->execute([
            $teacherId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    أحداث الطالب حسب الصف
    */

    public function getStudentEvents(
        $classId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            ce.*

         FROM calendar_events ce

         INNER JOIN calendar_event_classes cec
         ON ce.id=cec.event_id

         WHERE cec.class_id=?

         ORDER BY ce.start_date ASC"

        );

        $stmt->execute([
            $classId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    أحداث ولي الأمر
    */

    public function getParentEvents(
        $classId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT DISTINCT

            ce.*

         FROM calendar_events ce

         INNER JOIN calendar_event_classes cec
         ON ce.id=cec.event_id

         WHERE cec.class_id=?

         ORDER BY ce.start_date ASC"

        );

        $stmt->execute([
            $classId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    أحداث الشهر
    */

    public function getMonthEvents(
        $month,
        $year
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM calendar_events

         WHERE MONTH(start_date)=?
         AND YEAR(start_date)=?

         ORDER BY start_date ASC"

        );

        $stmt->execute([

            $month,
            $year

        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    الأحداث القادمة
    */

    public function getUpcomingEvents(
        $limit = 10
    )
    {
        $stmt = $this->db->prepare(

        "SELECT *

         FROM calendar_events

         WHERE start_date>=CURDATE()

         ORDER BY start_date ASC

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

    /*
    أحداث الصف
    */

    public function getEventClasses(
        $eventId
    )
    {
        $stmt = $this->db->prepare(

        "SELECT

            c.*

         FROM calendar_event_classes cec

         INNER JOIN classes c
         ON cec.class_id=c.id

         WHERE cec.event_id=?"

        );

        $stmt->execute([
            $eventId
        ]);

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }

    /*
    حذف حدث
    */

    public function delete(
        $id
    )
    {
        $stmt = $this->db->prepare(

        "DELETE FROM calendar_events

         WHERE id=?"

        );

        return $stmt->execute([
            $id
        ]);
    }

    /*
    عدد الأحداث
    */

    public function countEvents()
    {
        $stmt = $this->db->query(

        "SELECT COUNT(*) total

         FROM calendar_events"

        );

        return $stmt->fetchColumn();
    }
}
?>
