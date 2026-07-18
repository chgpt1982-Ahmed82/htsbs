<?php

/**
 * DeepLessonPlanner Model
 *
 * نموذج قاعدة البيانات للتخطيطات العميقة
 * المسار: app/models/DeepLessonPlanner.php
 */

class DeepLessonPlanner
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->connect();
    }

    /*
    =========================================
    إنشاء تخطيط جديد
    =========================================
    */

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
        INSERT INTO deep_lesson_plans
        (
            teacher_id, subject_id, class_id,
            unit_name, lesson_title, lesson_date,
            lesson_duration, student_level,
            objective_1, objective_2,
            skill_1, skill_2,
            teaching_method, reinforcement, technology,
            resources, facilities,
            lesson_description, learning_outcomes, keywords,
            challenge_card, support_card,
            national_exams_link, homework, bahrain_link,
            lesson_plan, lesson_plan_json, lesson_plan_html,
            ai_prompt, ai_model,
            generation_time, tokens_used,
            status, is_favorite, version_no,
            exported_pdf, exported_word, printed_count
        )
        VALUES
        (
            :teacher_id, :subject_id, :class_id,
            :unit_name, :lesson_title, :lesson_date,
            :lesson_duration, :student_level,
            :objective_1, :objective_2,
            :skill_1, :skill_2,
            :teaching_method, :reinforcement, :technology,
            :resources, :facilities,
            :lesson_description, :learning_outcomes, :keywords,
            :challenge_card, :support_card,
            :national_exams_link, :homework, :bahrain_link,
            :lesson_plan, :lesson_plan_json, :lesson_plan_html,
            :ai_prompt, :ai_model,
            :generation_time, :tokens_used,
            :status, :is_favorite, :version_no,
            :exported_pdf, :exported_word, :printed_count
        )
        ");

        $stmt->execute([
            ':teacher_id'          => $data['teacher_id'],
            ':subject_id'          => $data['subject_id'],
            ':class_id'            => $data['class_id'],
            ':unit_name'           => $data['unit_name'] ?? '',
            ':lesson_title'        => $data['lesson_title'] ?? '',
            ':lesson_date'         => $data['lesson_date'] ?? date('Y-m-d'),
            ':lesson_duration'     => $data['lesson_duration'] ?? 45,
            ':student_level'       => $data['student_level'] ?? 'متوسط',
            ':objective_1'         => $data['objective_1'] ?? '',
            ':objective_2'         => $data['objective_2'] ?? '',
            ':skill_1'             => $data['skill_1'] ?? '',
            ':skill_2'             => $data['skill_2'] ?? '',
            ':teaching_method'     => $data['teaching_method'] ?? '',
            ':reinforcement'       => $data['reinforcement'] ?? '',
            ':technology'          => $data['technology'] ?? '',
            ':resources'           => is_array($data['resources']) ? json_encode($data['resources'], JSON_UNESCAPED_UNICODE) : ($data['resources'] ?? '[]'),
            ':facilities'          => is_array($data['facilities']) ? json_encode($data['facilities'], JSON_UNESCAPED_UNICODE) : ($data['facilities'] ?? '[]'),
            ':lesson_description'  => $data['lesson_description'] ?? '',
            ':learning_outcomes'   => $data['learning_outcomes'] ?? '',
            ':keywords'            => $data['keywords'] ?? '',
            ':challenge_card'      => $data['challenge_card'] ?? '',
            ':support_card'        => $data['support_card'] ?? '',
            ':national_exams_link' => $data['national_exams_link'] ?? '',
            ':homework'            => $data['homework'] ?? '',
            ':bahrain_link'        => $data['bahrain_link'] ?? '',
            ':lesson_plan'         => $data['lesson_plan'] ?? '',
            ':lesson_plan_json'    => $data['lesson_plan_json'] ?? '',
            ':lesson_plan_html'    => $data['lesson_plan_html'] ?? '',
            ':ai_prompt'           => $data['ai_prompt'] ?? '',
            ':ai_model'            => $data['ai_model'] ?? '',
            ':generation_time'     => $data['generation_time'] ?? 0,
            ':tokens_used'         => $data['tokens_used'] ?? 0,
            ':status'              => $data['status'] ?? 'draft',
            ':is_favorite'         => $data['is_favorite'] ?? 0,
            ':version_no'          => $data['version_no'] ?? 1,
            ':exported_pdf'        => $data['exported_pdf'] ?? 0,
            ':exported_word'       => $data['exported_word'] ?? 0,
            ':printed_count'       => $data['printed_count'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /*
    =========================================
    جلب تخطيط بالمعرف
    =========================================
    */

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM deep_lesson_plans WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /*
    =========================================
    جلب تخطيطات المعلم
    =========================================
    */

    public function getTeacherPlans(int $userId): array
    {
        $stmt = $this->db->prepare("
        SELECT
            dlp.*,
            c.course_name,
            cl.class_name
        FROM deep_lesson_plans dlp
        LEFT JOIN courses c  ON c.id  = dlp.subject_id
        LEFT JOIN classes cl ON cl.id = dlp.class_id
        WHERE dlp.teacher_id = ?
        ORDER BY dlp.created_at DESC
        ");

        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    =========================================
    إحصائيات
    =========================================
    */

    public function countTeacherPlans(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deep_lesson_plans WHERE teacher_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function countByStatus(int $userId, string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM deep_lesson_plans WHERE teacher_id = ? AND status = ?");
        $stmt->execute([$userId, $status]);
        return (int)$stmt->fetchColumn();
    }

    /*
    =========================================
    المفضلة
    =========================================
    */

    public function favorites(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM deep_lesson_plans WHERE teacher_id = ? AND is_favorite = 1");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    =========================================
    تحديث
    =========================================
    */

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
        UPDATE deep_lesson_plans SET
            lesson_plan_json = :json,
            lesson_plan_html = :html,
            lesson_plan      = :text,
            version_no       = version_no + 1,
            updated_at       = NOW()
        WHERE id = ?
        ");

        return $stmt->execute([
            ':json' => $data['lesson_plan_json'] ?? '',
            ':html' => $data['lesson_plan_html'] ?? '',
            ':text' => $data['lesson_plan'] ?? '',
            $id,
        ]);
    }

    /*
    =========================================
    حذف
    =========================================
    */

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM deep_lesson_plans WHERE id = ? AND teacher_id = ?");
        return $stmt->execute([$id, $userId]);
    }
}
