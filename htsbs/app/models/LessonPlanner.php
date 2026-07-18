<?php

require_once __DIR__ . '/../../config/database.php';

class LessonPlanner
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /*
    ============================================================
    إنشاء تحضير جديد
    ============================================================
    */

    public function create($data)
    {
        $sql = "

        INSERT INTO lesson_plans
        (
            teacher_id,
            subject_id,
            class_id,
            unit_name,
            lesson_title,
            lesson_description,
            learning_outcomes,
            keywords,
            lesson_duration,
            resources,
            student_level,
            ai_prompt,
            lesson_plan,
            lesson_plan_json,
            lesson_plan_html,
            notes,
            version_no,
            status,
            is_favorite,
            ai_model,
            generation_time,
            tokens_used,
            exported_pdf,
            exported_word,
            printed_count
        )

        VALUES
        (
            :teacher_id,
            :subject_id,
            :class_id,
            :unit_name,
            :lesson_title,
            :lesson_description,
            :learning_outcomes,
            :keywords,
            :lesson_duration,
            :resources,
            :student_level,
            :ai_prompt,
            :lesson_plan,
            :lesson_plan_json,
            :lesson_plan_html,
            :notes,
            :version_no,
            :status,
            :is_favorite,
            :ai_model,
            :generation_time,
            :tokens_used,
            :exported_pdf,
            :exported_word,
            :printed_count
        )

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([

            ':teacher_id'         => $data['teacher_id'],
            ':subject_id'         => $data['subject_id'],
            ':class_id'           => $data['class_id'],
            ':unit_name'          => $data['unit_name'],
            ':lesson_title'       => $data['lesson_title'],
            ':lesson_description' => $data['lesson_description'],
            ':learning_outcomes'  => $data['learning_outcomes'],
            ':keywords'           => $data['keywords'],
            ':lesson_duration'    => $data['lesson_duration'],
            ':resources'          => $data['resources'],
            ':student_level'      => $data['student_level'],
            ':ai_prompt'          => $data['ai_prompt'],
            ':lesson_plan'        => $data['lesson_plan'],
            ':lesson_plan_json' => $data['lesson_plan_json'],
            ':lesson_plan_html' => $data['lesson_plan_html'],
            ':notes'              => $data['notes'] ?? '',
            ':version_no'         => $data['version_no'] ?? 1,
            ':status'             => $data['status'] ?? 'draft',
            ':is_favorite'        => $data['is_favorite'] ?? 0,
            ':ai_model'           => $data['ai_model'] ?? '',
            ':generation_time'    => $data['generation_time'] ?? 0,
            ':tokens_used'        => $data['tokens_used'] ?? 0,
            ':exported_pdf'       => $data['exported_pdf'] ?? 0,
            ':exported_word'      => $data['exported_word'] ?? 0,
            ':printed_count'      => $data['printed_count'] ?? 0

        ]);

        return $this->db->lastInsertId();
    }

    /*
    ============================================================
    جلب تحضير بواسطة ID
    ============================================================
    */

    public function find($id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM lesson_plans
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    جميع تحاضير المعلم
    ============================================================
    */

    public function getTeacherPlans($teacherId)
    {
        $sql = "

        SELECT

            lp.*,
            c.course_name,
            cl.class_name

        FROM lesson_plans lp

        LEFT JOIN courses c
            ON c.id = lp.subject_id

        LEFT JOIN classes cl
            ON cl.id = lp.class_id

        WHERE lp.teacher_id = ?

        ORDER BY lp.created_at DESC

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /*
    ============================================================
    تعديل التحضير
    ============================================================
    */

    public function update($id, $data)
    {
        $sql = "

        UPDATE lesson_plans

        SET

            subject_id          = :subject_id,
            class_id            = :class_id,
            unit_name           = :unit_name,
            lesson_title        = :lesson_title,
            lesson_description  = :lesson_description,
            learning_outcomes   = :learning_outcomes,
            keywords            = :keywords,
            lesson_duration     = :lesson_duration,
            resources           = :resources,
            student_level       = :student_level,
            ai_prompt           = :ai_prompt,
            lesson_plan         = :lesson_plan,
            lesson_plan_json = :lesson_plan_json,
            lesson_plan_html = :lesson_plan_html,
            notes               = :notes,
            version_no          = :version_no,
            status              = :status,
            updated_at          = NOW()

        WHERE id = :id

        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([

            ':subject_id'         => $data['subject_id'],
            ':class_id'           => $data['class_id'],
            ':unit_name'          => $data['unit_name'],
            ':lesson_title'       => $data['lesson_title'],
            ':lesson_description' => $data['lesson_description'],
            ':learning_outcomes'  => $data['learning_outcomes'],
            ':keywords'           => $data['keywords'],
            ':lesson_duration'    => $data['lesson_duration'],
            ':resources'          => $data['resources'],
            ':student_level'      => $data['student_level'],
            ':ai_prompt'          => $data['ai_prompt'],
            ':lesson_plan'        => $data['lesson_plan'],
            ':lesson_plan_json' => $data['lesson_plan_json'],
            ':lesson_plan_html' => $data['lesson_plan_html'],
            ':notes'              => $data['notes'],
            ':version_no'         => $data['version_no'],
            ':status'             => $data['status'],
            ':id'                 => $id

        ]);
    }

    /*
    ============================================================
    حذف التحضير
    ============================================================
    */

    public function delete($id)
    {
        $stmt = $this->db->prepare(

            "DELETE FROM lesson_plans WHERE id = ?"

        );

        return $stmt->execute([$id]);
    }

    /*
    ============================================================
    عدد تحاضير المعلم
    ============================================================
    */

    public function countTeacherPlans($teacherId)
    {
        $stmt = $this->db->prepare(

            "SELECT COUNT(*)

             FROM lesson_plans

             WHERE teacher_id = ?"

        );

        $stmt->execute([$teacherId]);

        return (int)$stmt->fetchColumn();
    }

    /*
    ============================================================
    عدد التحاضير حسب الحالة
    ============================================================
    */

    public function countByStatus($teacherId, $status)
    {
        $stmt = $this->db->prepare(

            "SELECT COUNT(*)

             FROM lesson_plans

             WHERE

                teacher_id = ?

             AND

                status = ?"

        );

        $stmt->execute([

            $teacherId,

            $status

        ]);

        return (int)$stmt->fetchColumn();
    }

    /*
    ============================================================
    جميع التحاضير المفضلة
    ============================================================
    */

    public function favorites($teacherId)
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM lesson_plans

             WHERE

                teacher_id = ?

             AND

                is_favorite = 1

             ORDER BY created_at DESC"

        );

        $stmt->execute([

            $teacherId

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    تغيير حالة المفضلة
    ============================================================
    */

    public function toggleFavorite($id)
    {
        $stmt = $this->db->prepare(

            "UPDATE lesson_plans

             SET

                is_favorite =
                IF(is_favorite = 1, 0, 1)

             WHERE id = ?"

        );

        return $stmt->execute([$id]);
    }
    
       /*
    ============================================================
    البحث في التحاضير
    ============================================================
    */

    public function search($teacherId, $keyword)
    {
        $keyword = "%{$keyword}%";

        $sql = "

        SELECT

            lp.*,
            c.course_name,
            cl.class_name

        FROM lesson_plans lp

        LEFT JOIN courses c
            ON c.id = lp.subject_id

        LEFT JOIN classes cl
            ON cl.id = lp.class_id

        WHERE

            lp.teacher_id = ?

        AND
        (
            lp.lesson_title LIKE ?
            OR lp.unit_name LIKE ?
            OR lp.keywords LIKE ?
        )

        ORDER BY lp.created_at DESC

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([

            $teacherId,
            $keyword,
            $keyword,
            $keyword

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    أحدث التحاضير
    ============================================================
    */

    public function latestPlans($teacherId, $limit = 5)
    {
        $stmt = $this->db->prepare(

            "SELECT

                id,
                lesson_title,
                unit_name,
                status,
                created_at

             FROM lesson_plans

             WHERE teacher_id = ?

             ORDER BY created_at DESC

             LIMIT {$limit}"

        );

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    تحاضير اليوم
    ============================================================
    */

    public function plansToday($teacherId)
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM lesson_plans

             WHERE

                teacher_id = ?

             AND

                DATE(created_at) = CURDATE()

             ORDER BY created_at DESC"

        );

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    تحاضير هذا الأسبوع
    ============================================================
    */

    public function plansThisWeek($teacherId)
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM lesson_plans

             WHERE

                teacher_id = ?

             AND

                YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)

             ORDER BY created_at DESC"

        );

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    تحاضير هذا الشهر
    ============================================================
    */

    public function plansThisMonth($teacherId)
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM lesson_plans

             WHERE

                teacher_id = ?

             AND

                MONTH(created_at)=MONTH(CURDATE())

             AND

                YEAR(created_at)=YEAR(CURDATE())

             ORDER BY created_at DESC"

        );

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    أحدث التحاضير للوحة التحكم
    ============================================================
    */

    public function dashboardLatest($teacherId, $limit = 10)
    {
        $stmt = $this->db->prepare(

            "SELECT

                lesson_title,
                unit_name,
                status,
                created_at

             FROM lesson_plans

             WHERE teacher_id = ?

             ORDER BY created_at DESC

             LIMIT {$limit}"

        );

        $stmt->execute([$teacherId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    إحصائيات لوحة التحكم
    ============================================================
    */

    public function dashboardStatistics($teacherId)
    {
        return [

            'total' => $this->countTeacherPlans($teacherId),

            'drafts' => $this->countByStatus(
                $teacherId,
                'draft'
            ),

            'published' => $this->countByStatus(
                $teacherId,
                'published'
            ),

            'favorites' => count(
                $this->favorites($teacherId)
            ),

            'today' => count(
                $this->plansToday($teacherId)
            ),

            'week' => count(
                $this->plansThisWeek($teacherId)
            ),

            'month' => count(
                $this->plansThisMonth($teacherId)
            )

        ];
    }
    
    
     /*
    ============================================================
    تغيير حالة التحضير
    ============================================================
    */

    public function changeStatus($id, $status)
    {
        $stmt = $this->db->prepare(

            "UPDATE lesson_plans

             SET

                status = ?,
                updated_at = NOW()

             WHERE id = ?"

        );

        return $stmt->execute([

            $status,

            $id

        ]);
    }

    /*
    ============================================================
    حفظ استجابة الذكاء الاصطناعي
    ============================================================
    */

    public function saveAIResponse($id,$prompt,$lessonPlan,$lessonJson,$lessonHtml
)
    {
        $stmt = $this->db->prepare(

            "UPDATE lesson_plans

SET

ai_prompt=?,

lesson_plan=?,

lesson_plan_json=?,

lesson_plan_html=?,

updated_at=NOW()

WHERE id=?"

        );

        return $stmt->execute([

$prompt,

$lessonPlan,

$lessonJson,

$lessonHtml,

$id

]);
    }

    /*
    ============================================================
    نسخ التحضير
    ============================================================
    */

    public function duplicatePlan($id)
    {
        $plan = $this->find($id);

        if (!$plan) {

            return false;

        }

        unset($plan['id']);

        unset($plan['created_at']);

        unset($plan['updated_at']);

        $plan['lesson_title'] .= ' (نسخة)';

        return $this->create($plan);
    }

    /*
    ============================================================
    بيانات التصدير
    ============================================================
    */

    public function exportData($lessonPlanId)
    {
        $sql = "

        SELECT

            lp.*,

            u.full_name,

            c.course_name,

            cl.class_name

        FROM lesson_plans lp

        LEFT JOIN users u
            ON u.id = lp.teacher_id

        LEFT JOIN courses c
            ON c.id = lp.subject_id

        LEFT JOIN classes cl
            ON cl.id = lp.class_id

        WHERE lp.id = ?

        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([

            $lessonPlanId

        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    إضافة إصدار
    ============================================================
    */

    public function addVersion($lessonPlanId, $lessonPlan)
    {
        $stmt = $this->db->prepare(

            "INSERT INTO lesson_plan_versions
            (
                lesson_plan_id,
                lesson_plan
            )
            VALUES
            (
                ?,
                ?
            )"

        );

        return $stmt->execute([

            $lessonPlanId,

            $lessonPlan

        ]);
    }

    /*
    ============================================================
    جميع الإصدارات
    ============================================================
    */

    public function getVersions($lessonPlanId)
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM lesson_plan_versions

             WHERE lesson_plan_id = ?

             ORDER BY created_at DESC"

        );

        $stmt->execute([

            $lessonPlanId

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ============================================================
    رفع مرفق
    ============================================================
    */

    public function uploadAttachment($lessonPlanId, $fileName, $filePath)
    {
        $stmt = $this->db->prepare(

            "INSERT INTO lesson_plan_files
            (
                lesson_plan_id,
                file_name,
                file_path
            )
            VALUES
            (
                ?,
                ?,
                ?
            )"

        );

        return $stmt->execute([

            $lessonPlanId,

            $fileName,

            $filePath

        ]);
    }

    /*
    ============================================================
    جميع المرفقات
    ============================================================
    */

    public function getAttachments($lessonPlanId)
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM lesson_plan_files

             WHERE lesson_plan_id = ?

             ORDER BY uploaded_at DESC"

        );

        $stmt->execute([

            $lessonPlanId

        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    
    
    
    /*
    ============================================================
    عدد مرات تصدير ملف word
  ============================================================
    */
    
  
    
    public function increaseWordExport(int $id): bool
{
    $stmt = $this->db->prepare(
        "UPDATE lesson_plans
         SET exported_word = exported_word + 1
         WHERE id = ?"
    );

    return $stmt->execute([$id]);
}

    /*
    ============================================================
    حذف التحضير كاملاً
    ============================================================
    */

    public function deleteComplete($lessonPlanId)
    {
        $this->db->prepare(

            "DELETE FROM lesson_plan_files
             WHERE lesson_plan_id=?"

        )->execute([

            $lessonPlanId

        ]);

        $this->db->prepare(

            "DELETE FROM lesson_plan_versions
             WHERE lesson_plan_id=?"

        )->execute([

            $lessonPlanId

        ]);

        return $this->delete($lessonPlanId);
    }

/**
 * جلب التحضير مع أسماء المادة والصف للتصدير
 */
public function getLessonForExport(int $lessonId): array
{
    $sql = "
        SELECT

            lp.*,

            c.course_name AS subject_name,

            cl.class_name

        FROM lesson_plans lp

        LEFT JOIN courses c
            ON c.id = lp.subject_id

        LEFT JOIN classes cl
            ON cl.id = lp.class_id

        WHERE lp.id = ?

        LIMIT 1
    ";

    $stmt = $this->db->prepare($sql);

    $stmt->execute([
        $lessonId
    ]);

    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | دمج بيانات JSON
    |--------------------------------------------------------------------------
    */

    if (!empty($lesson['lesson_plan_json'])) {

        $json = json_decode(
            $lesson['lesson_plan_json'],
            true
        );

        if (
            json_last_error() === JSON_ERROR_NONE &&
            is_array($json)
        ) {

            $lesson = array_merge(
    $lesson,
    $json
);

$lesson = $this->normalizeLessonData(
    $lesson
);

        }

    }

    return $lesson;
}


/**
 * توحيد بيانات التحضير القادمة من JSON
 */
private function normalizeLessonData(array $lesson): array
{
    /*
    |--------------------------------------------------------------------------
    | Objectives
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['objectives'])) {

        if (isset($lesson['learning_objectives'])) {

            $lesson['objectives'] =
                $lesson['learning_objectives'];

        } elseif (isset($lesson['learningOutcomes'])) {

            $lesson['objectives'] =
                $lesson['learningOutcomes'];

        } else {

            $lesson['objectives'] = [];

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Warmup
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['warmup'])) {

        if (isset($lesson['warm_up'])) {

            $lesson['warmup'] =
                $lesson['warm_up'];

        } elseif (isset($lesson['starter'])) {

            $lesson['warmup'] =
                $lesson['starter'];

        } else {

            $lesson['warmup'] = [];

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Introduction
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['introduction'])) {

        if (isset($lesson['intro'])) {

            $lesson['introduction'] =
                $lesson['intro'];

        } else {

            $lesson['introduction'] = [];

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Activities
    |--------------------------------------------------------------------------
    */

    if (isset($lesson['activities'])
        && is_array($lesson['activities'])) {

        $counter = 1;

        foreach ($lesson['activities'] as $activity) {

            $lesson['objective' . $counter] = [

                'goal' =>

                    $activity['goal']
                    ??

                    $activity['objective']
                    ??

                    '',

                'strategy' =>

                    $activity['strategy']
                    ??

                    '',

                'activity1' =>

                    $activity['activity1']
                    ??

                    $activity['teacher_activity']
                    ??

                    '',

                'activity2' =>

                    $activity['activity2']
                    ??

                    $activity['student_activity']
                    ??

                    '',

                'assessment' =>

                    $activity['assessment']
                    ??

                    ''

            ];

            $counter++;

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['resources'])) {

        $lesson['resources'] = [];

    }

    /*
    |--------------------------------------------------------------------------
    | Skills
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['skills'])) {

        if (isset($lesson['twenty_first_skills'])) {

            $lesson['skills'] =
                $lesson['twenty_first_skills'];

        } else {

            $lesson['skills'] = [];

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Values
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['values'])) {

        $lesson['values'] = [];

    }

    /*
    |--------------------------------------------------------------------------
    | Homework
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['homework'])) {

        if (isset($lesson['assignment'])) {

            $lesson['homework'] =
                $lesson['assignment'];

        } else {

            $lesson['homework'] = '';

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Conclusion
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['conclusion'])) {

        $lesson['conclusion'] = '';

    }

    /*
    |--------------------------------------------------------------------------
    | Final Assessment
    |--------------------------------------------------------------------------
    */

    if (!isset($lesson['final_assessment'])) {

        $lesson['final_assessment'] = '';

    }

    return $lesson;
}










}



