<?php
/*
=====================================================================
LmsHelper - المحرك الأساسي لوحدة LMS
التقدم | الفتح التسلسلي | النجوم | الشارات | لوحة الصدارة | الشهادات
=====================================================================
*/

class LmsHelper
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /*
    ====================================
    جلب سجل الطالب من user_id
    ====================================
    */
    public function getStudentByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, u.full_name, u.profile_image, u.email,
                   c.class_name, d.department_name
            FROM students s
            INNER JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN departments d ON s.department_id = d.id
            WHERE s.user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /*
    ====================================
    جلب سجل المعلم من user_id
    ====================================
    */
    public function getTeacherByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*, u.full_name
            FROM teachers t
            INNER JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ?
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /*
    ====================================
    مقررات الطالب (عبر صفه من course_assignments الموجود)
    ====================================
    */
    public function getStudentCourses(int $classId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT c.id, c.course_name, c.course_code, c.description,
                   u.full_name AS teacher_name, t.id AS teacher_id,
                   (SELECT COUNT(*) FROM lms_lessons l
                     WHERE l.course_id = c.id AND l.is_published = 1) AS lessons_count,
                   (SELECT COUNT(*) FROM lms_activities a
                     INNER JOIN lms_lessons l2 ON a.lesson_id = l2.id
                     WHERE l2.course_id = c.id AND l2.is_published = 1) AS activities_count,
                   (SELECT COUNT(DISTINCT s2.id) FROM students s2
                     INNER JOIN course_assignments ca2 ON ca2.class_id = s2.class_id
                     WHERE ca2.course_id = c.id) AS students_count
            FROM course_assignments ca
            INNER JOIN courses c ON ca.course_id = c.id
            INNER JOIN teachers t ON ca.teacher_id = t.id
            INNER JOIN users u ON t.user_id = u.id
            WHERE ca.class_id = ?
            ORDER BY c.course_name
        ");
        $stmt->execute([$classId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    ====================================
    دروس مقرر مع حالة القفل التسلسلي للطالب
    الدرس مفتوح إذا: هو الأول، أو الدرس السابق مكتمل
    ====================================
    */
    public function getCourseLessonsWithStatus(int $courseId, int $studentId): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*,
                   lp.status AS progress_status,
                   lp.completed_activities,
                   lp.time_spent_seconds,
                   (SELECT COUNT(*) FROM lms_activities a WHERE a.lesson_id = l.id) AS total_activities,
                   (SELECT COUNT(*) FROM lms_stars st
                     WHERE st.lesson_id = l.id AND st.student_id = ?) AS has_star
            FROM lms_lessons l
            LEFT JOIN lms_lesson_progress lp
                   ON lp.lesson_id = l.id AND lp.student_id = ?
            WHERE l.course_id = ? AND l.is_published = 1
            ORDER BY l.lesson_order ASC, l.id ASC
        ");
        $stmt->execute([$studentId, $studentId, $courseId]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $previousCompleted = true; // الدرس الأول مفتوح دائماً

        foreach ($lessons as $i => &$lesson) {
            $completed = ($lesson['progress_status'] === 'completed');

            if ($previousCompleted) {
                $lesson['is_locked'] = false;
                $lesson['display_status'] = $completed ? 'completed' : 'open';
            } else {
                $lesson['is_locked'] = true;
                $lesson['display_status'] = 'locked';
            }

            $previousCompleted = $completed;
        }

        return $lessons;
    }

    /*
    ====================================
    هل الدرس مفتوح لهذا الطالب؟ (حماية من الوصول المباشر بالرابط)
    ====================================
    */
    public function isLessonUnlocked(int $lessonId, int $studentId): bool
    {
        $stmt = $this->db->prepare("SELECT course_id, lesson_order FROM lms_lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lesson) return false;

        // عدد الدروس السابقة غير المكتملة
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM lms_lessons l
            WHERE l.course_id = ? AND l.is_published = 1
              AND (l.lesson_order < ? OR (l.lesson_order = ? AND l.id < ?))
              AND NOT EXISTS (
                  SELECT 1 FROM lms_lesson_progress lp
                  WHERE lp.lesson_id = l.id
                    AND lp.student_id = ?
                    AND lp.status = 'completed'
              )
        ");
        $stmt->execute([
            $lesson['course_id'],
            $lesson['lesson_order'],
            $lesson['lesson_order'],
            $lessonId,
            $studentId
        ]);

        return (int)$stmt->fetchColumn() === 0;
    }

    /*
    ====================================
    فتح/تحديث سجل تقدم درس + حفظ آخر موضع
    ====================================
    */
    public function touchLessonProgress(int $lessonId, int $studentId, ?int $activityId = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO lms_lesson_progress (lesson_id, student_id, status, last_activity_id)
            VALUES (?, ?, 'in_progress', ?)
            ON DUPLICATE KEY UPDATE
                last_activity_id = COALESCE(VALUES(last_activity_id), last_activity_id)
        ");
        $stmt->execute([$lessonId, $studentId, $activityId]);

        // حفظ آخر درس/نشاط في التقدم الإجمالي
        $stmt = $this->db->prepare("SELECT course_id FROM lms_lessons WHERE id = ?");
        $stmt->execute([$lessonId]);
        $courseId = (int)$stmt->fetchColumn();

        if ($courseId) {
            $stmt = $this->db->prepare("
                INSERT INTO lms_student_progress (student_id, course_id, last_lesson_id, last_activity_id)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    last_lesson_id   = VALUES(last_lesson_id),
                    last_activity_id = COALESCE(VALUES(last_activity_id), last_activity_id)
            ");
            $stmt->execute([$studentId, $courseId, $lessonId, $activityId]);
        }
    }

    /*
    ====================================
    إضافة وقت التعلم (يُستدعى عبر AJAX دورياً)
    ====================================
    */
    public function addTimeSpent(int $lessonId, int $studentId, int $seconds): void
    {
        $seconds = max(0, min($seconds, 300)); // حماية من قيم غير منطقية

        $stmt = $this->db->prepare("
            UPDATE lms_lesson_progress
            SET time_spent_seconds = time_spent_seconds + ?
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->execute([$seconds, $lessonId, $studentId]);

        $stmt = $this->db->prepare("
            UPDATE lms_student_progress sp
            INNER JOIN lms_lessons l ON l.course_id = sp.course_id
            SET sp.total_time_seconds = sp.total_time_seconds + ?
            WHERE l.id = ? AND sp.student_id = ?
        ");
        $stmt->execute([$seconds, $lessonId, $studentId]);
    }

    /*
    ====================================
    إعادة احتساب تقدم الطالب بعد اجتياز نشاط
    - إذا اكتملت الأنشطة الخمسة بنجاح: الدرس مكتمل + نجمة + فحص الشارات
    ====================================
    */
    public function recalculateAfterActivity(int $activityId, int $studentId): array
    {
        $result = [
            'lesson_completed' => false,
            'star_awarded'     => false,
            'new_badges'       => [],
            'certificate'      => null
        ];

        // بيانات النشاط والدرس
        $stmt = $this->db->prepare("
            SELECT a.lesson_id, l.course_id, l.pass_grade,
                   (SELECT COUNT(*) FROM lms_activities WHERE lesson_id = a.lesson_id) AS total_activities
            FROM lms_activities a
            INNER JOIN lms_lessons l ON a.lesson_id = l.id
            WHERE a.id = ?
        ");
        $stmt->execute([$activityId]);
        $ctx = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ctx) return $result;

        $lessonId = (int)$ctx['lesson_id'];
        $courseId = (int)$ctx['course_id'];

        // عدد الأنشطة المجتازة في هذا الدرس
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT a.id)
            FROM lms_activities a
            WHERE a.lesson_id = ?
              AND EXISTS (
                  SELECT 1 FROM lms_student_activity_attempts att
                  WHERE att.activity_id = a.id
                    AND att.student_id = ?
                    AND att.is_passed = 1
              )
        ");
        $stmt->execute([$lessonId, $studentId]);
        $passedCount = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            UPDATE lms_lesson_progress
            SET completed_activities = ?
            WHERE lesson_id = ? AND student_id = ?
        ");
        $stmt->execute([$passedCount, $lessonId, $studentId]);

        // اكتمال الدرس؟
        if ($passedCount >= (int)$ctx['total_activities'] && (int)$ctx['total_activities'] > 0) {

            $stmt = $this->db->prepare("
                UPDATE lms_lesson_progress
                SET status = 'completed',
                    completed_at = COALESCE(completed_at, NOW())
                WHERE lesson_id = ? AND student_id = ? AND status != 'completed'
            ");
            $stmt->execute([$lessonId, $studentId]);
            $justCompleted = $stmt->rowCount() > 0;

            $result['lesson_completed'] = true;

            if ($justCompleted) {
                // ⭐ نجمة ذهبية
                $stmt = $this->db->prepare("
                    INSERT IGNORE INTO lms_stars (student_id, lesson_id, course_id)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$studentId, $lessonId, $courseId]);
                $result['star_awarded'] = $stmt->rowCount() > 0;

                $this->notifyStudent($studentId, 'إنجاز جديد ⭐',
                    'أحسنت! أكملت درساً وحصلت على نجمة ذهبية');
            }
        }

        // تحديث التقدم الإجمالي للمقرر
        $this->refreshCourseProgress($studentId, $courseId);

        // فحص الشارات
        $result['new_badges'] = $this->checkAndAwardBadges($studentId, $activityId, $lessonId, $courseId);

        // فحص استحقاق الشهادة
        $result['certificate'] = $this->checkCertificate($studentId, $courseId);

        // تحديث لوحة الصدارة
        $this->refreshLeaderboard($studentId);

        return $result;
    }

    /*
    ====================================
    تحديث نسبة الإنجاز ومتوسط الدرجات لمقرر
    ====================================
    */
    public function refreshCourseProgress(int $studentId, int $courseId): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM lms_lessons
            WHERE course_id = ? AND is_published = 1
        ");
        $stmt->execute([$courseId]);
        $totalLessons = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM lms_lesson_progress lp
            INNER JOIN lms_lessons l ON lp.lesson_id = l.id
            WHERE lp.student_id = ? AND l.course_id = ? AND lp.status = 'completed'
        ");
        $stmt->execute([$studentId, $courseId]);
        $completed = (int)$stmt->fetchColumn();

        // متوسط أفضل النتائج في المقرر
        $stmt = $this->db->prepare("
            SELECT AVG(best_score) FROM (
                SELECT MAX(att.score) AS best_score
                FROM lms_student_activity_attempts att
                INNER JOIN lms_activities a ON att.activity_id = a.id
                INNER JOIN lms_lessons l ON a.lesson_id = l.id
                WHERE att.student_id = ? AND l.course_id = ?
                GROUP BY a.id
            ) x
        ");
        $stmt->execute([$studentId, $courseId]);
        $avg = round((float)$stmt->fetchColumn(), 2);

        $percent = $totalLessons > 0 ? round(($completed / $totalLessons) * 100, 2) : 0;

        $stmt = $this->db->prepare("
            INSERT INTO lms_student_progress
                (student_id, course_id, completed_lessons, progress_percent, avg_grade)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                completed_lessons = VALUES(completed_lessons),
                progress_percent  = VALUES(progress_percent),
                avg_grade         = VALUES(avg_grade)
        ");
        $stmt->execute([$studentId, $courseId, $completed, $percent, $avg]);
    }

    /*
    ====================================
    محرك الشارات (Badges Engine)
    ====================================
    */
    public function checkAndAwardBadges(int $studentId, int $activityId, int $lessonId, int $courseId): array
    {
        $awarded = [];

        // إجمالي الدروس المكتملة
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM lms_lesson_progress
            WHERE student_id = ? AND status = 'completed'
        ");
        $stmt->execute([$studentId]);
        $completedLessons = (int)$stmt->fetchColumn();

        if ($completedLessons >= 1)  $awarded[] = $this->awardBadge($studentId, 'first_lesson');
        if ($completedLessons >= 5)  $awarded[] = $this->awardBadge($studentId, 'five_lessons');
        if ($completedLessons >= 10) $awarded[] = $this->awardBadge($studentId, 'ten_lessons');

        // 🎯 درجة كاملة في آخر محاولة
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM lms_student_activity_attempts
            WHERE student_id = ? AND activity_id = ? AND score >= 100
        ");
        $stmt->execute([$studentId, $activityId]);
        if ((int)$stmt->fetchColumn() > 0) {
            $awarded[] = $this->awardBadge($studentId, 'perfect_score');
        }

        // 💯 درس كامل بدون أخطاء (أفضل نتيجة 100 في كل أنشطته)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM lms_activities a
            WHERE a.lesson_id = ?
              AND (SELECT COALESCE(MAX(score),0)
                     FROM lms_student_activity_attempts att
                    WHERE att.activity_id = a.id AND att.student_id = ?) < 100
        ");
        $stmt->execute([$lessonId, $studentId]);
        $imperfect = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT status FROM lms_lesson_progress WHERE lesson_id=? AND student_id=?");
        $stmt->execute([$lessonId, $studentId]);
        if ($imperfect === 0 && $stmt->fetchColumn() === 'completed') {
            $awarded[] = $this->awardBadge($studentId, 'no_mistakes');
        }

        // 🔥 خمسة أيام تعلم متتالية
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT DATE(created_at))
            FROM lms_student_activity_attempts
            WHERE student_id = ?
              AND created_at >= DATE_SUB(CURDATE(), INTERVAL 4 DAY)
        ");
        $stmt->execute([$studentId]);
        if ((int)$stmt->fetchColumn() >= 5) {
            $awarded[] = $this->awardBadge($studentId, 'streak_5');
        }

        // ⚡ أسرع طالب أنهى هذا الدرس (أقل وقت بين المكملين وعددهم > 1)
        $stmt = $this->db->prepare("
            SELECT student_id FROM lms_lesson_progress
            WHERE lesson_id = ? AND status = 'completed' AND time_spent_seconds > 0
            ORDER BY time_spent_seconds ASC
            LIMIT 1
        ");
        $stmt->execute([$lessonId]);
        if ((int)$stmt->fetchColumn() === $studentId) {
            $awarded[] = $this->awardBadge($studentId, 'fastest');
        }

        // 🏆 مقرر كامل
        $stmt = $this->db->prepare("
            SELECT progress_percent FROM lms_student_progress
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$studentId, $courseId]);
        if ((float)$stmt->fetchColumn() >= 100) {
            $awarded[] = $this->awardBadge($studentId, 'course_complete');
        }

        // إزالة القيم الفارغة (الشارات الممنوحة سابقاً)
        return array_values(array_filter($awarded));
    }

    /*
    ====================================
    منح شارة (تُمنح مرة واحدة فقط)
    ====================================
    */
    private function awardBadge(int $studentId, string $badgeKey): ?array
    {
        $stmt = $this->db->prepare("SELECT id, title, icon FROM lms_badges WHERE badge_key = ?");
        $stmt->execute([$badgeKey]);
        $badge = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$badge) return null;

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO lms_student_badges (student_id, badge_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$studentId, $badge['id']]);

        if ($stmt->rowCount() > 0) {
            $this->notifyStudent($studentId, 'شارة جديدة ' . $badge['icon'],
                'حصلت على شارة: ' . $badge['title']);
            return $badge;
        }
        return null; // ممنوحة مسبقاً
    }

    /*
    ====================================
    فحص استحقاق الشهادة وإصدارها تلقائياً
    الشروط: كل الدروس + كل الأنشطة + نسبة الإنجاز المحددة
    ====================================
    */
    public function checkCertificate(int $studentId, int $courseId): ?array
    {
        $minPercent = (float)$this->getSetting('certificate_min_percent', 100);

        $stmt = $this->db->prepare("
            SELECT progress_percent, avg_grade FROM lms_student_progress
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$studentId, $courseId]);
        $prog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prog || (float)$prog['progress_percent'] < $minPercent) {
            return null;
        }

        // موجودة مسبقاً؟
        $stmt = $this->db->prepare("
            SELECT * FROM lms_certificates WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$studentId, $courseId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) return $existing;

        // إحصائيات للشهادة
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM lms_stars WHERE student_id=? AND course_id=?");
        $stmt->execute([$studentId, $courseId]);
        $stars = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM lms_student_badges WHERE student_id=?");
        $stmt->execute([$studentId]);
        $badges = (int)$stmt->fetchColumn();

        // رقم شهادة فريد
        $certNo = 'LMS-' . date('Y') . '-' . str_pad($courseId, 3, '0', STR_PAD_LEFT)
                . '-' . str_pad($studentId, 4, '0', STR_PAD_LEFT)
                . '-' . strtoupper(bin2hex(random_bytes(3)));

        $stmt = $this->db->prepare("
            INSERT INTO lms_certificates
                (student_id, course_id, certificate_no, stars, badges,
                 progress_percent, final_grade, issue_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())
        ");
        $stmt->execute([
            $studentId, $courseId, $certNo, $stars, $badges,
            $prog['progress_percent'], $prog['avg_grade']
        ]);

        $this->notifyStudent($studentId, 'مبروك! شهادة جديدة 🎓',
            'استحققت شهادة إتمام المقرر - رقم الشهادة: ' . $certNo);

        $stmt = $this->db->prepare("SELECT * FROM lms_certificates WHERE certificate_no = ?");
        $stmt->execute([$certNo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /*
    ====================================
    تحديث لوحة الصدارة تلقائياً
    ====================================
    */
    public function refreshLeaderboard(int $studentId): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO lms_leaderboard
                (student_id, total_stars, total_badges, completed_lessons,
                 avg_grade, progress_percent, total_time_seconds)
            SELECT
                s.id,
                (SELECT COUNT(*) FROM lms_stars WHERE student_id = s.id),
                (SELECT COUNT(*) FROM lms_student_badges WHERE student_id = s.id),
                (SELECT COUNT(*) FROM lms_lesson_progress
                  WHERE student_id = s.id AND status = 'completed'),
                (SELECT COALESCE(AVG(avg_grade),0) FROM lms_student_progress
                  WHERE student_id = s.id),
                (SELECT COALESCE(AVG(progress_percent),0) FROM lms_student_progress
                  WHERE student_id = s.id),
                (SELECT COALESCE(SUM(total_time_seconds),0) FROM lms_student_progress
                  WHERE student_id = s.id)
            FROM students s WHERE s.id = ?
            ON DUPLICATE KEY UPDATE
                total_stars        = VALUES(total_stars),
                total_badges       = VALUES(total_badges),
                completed_lessons  = VALUES(completed_lessons),
                avg_grade          = VALUES(avg_grade),
                progress_percent   = VALUES(progress_percent),
                total_time_seconds = VALUES(total_time_seconds)
        ");
        $stmt->execute([$studentId]);

        // إعادة الترتيب: نجوم ثم شارات ثم إنجاز ثم متوسط ثم سرعة
        $this->db->exec("SET @r := 0");
        $this->db->exec("
            UPDATE lms_leaderboard lb
            INNER JOIN (
                SELECT id, (@r := @r + 1) AS new_rank
                FROM lms_leaderboard
                ORDER BY total_stars DESC, total_badges DESC,
                         progress_percent DESC, avg_grade DESC,
                         total_time_seconds ASC
            ) ranked ON lb.id = ranked.id
            SET lb.rank_position = ranked.new_rank
        ");
    }

    /*
    ====================================
    ترتيب الطالب
    ====================================
    */
    public function getStudentRank(int $studentId): int
    {
        $stmt = $this->db->prepare("SELECT rank_position FROM lms_leaderboard WHERE student_id = ?");
        $stmt->execute([$studentId]);
        return (int)$stmt->fetchColumn();
    }

    /*
    ====================================
    إشعار الطالب (يستخدم جدول notifications الحالي)
    ====================================
    */
    public function notifyStudent(int $studentId, string $title, string $message): void
    {
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $userId = (int)$stmt->fetchColumn();
        if (!$userId) return;

        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, 'lesson')
        ");
        $stmt->execute([$userId, $title, $message]);
    }

    /*
    ====================================
    سجل التدقيق (Audit Log)
    ====================================
    */
    public function log(?int $userId, string $action, string $details = ''): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO lms_logs (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $action, $details,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /*
    ====================================
    قراءة إعداد
    ====================================
    */
    public function getSetting(string $key, $default = null)
    {
        $stmt = $this->db->prepare("SELECT setting_value FROM lms_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }
}
