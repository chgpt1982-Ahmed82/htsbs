-- =====================================================================
-- LMS Module - جداول نظام التعلم التفاعلي الجديد
-- تُضاف على قاعدة البيانات u922823540_ictht دون أي تعديل على الجداول الحالية
-- جميع الجداول الجديدة تبدأ بـ lms_ لتجنب التعارض مع (certificates, settings)
-- التنفيذ: phpMyAdmin > Import أو SQL
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1) دروس نظام LMS (تعلم تسلسلي مرتبط بالمقررات الحالية)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_lessons` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) NOT NULL,
  `teacher_id` INT(11) NOT NULL,
  `lesson_order` INT(11) NOT NULL DEFAULT 1,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `objectives` TEXT DEFAULT NULL COMMENT 'أهداف التعلم',
  `outcomes` TEXT DEFAULT NULL COMMENT 'مخرجات التعلم',
  `image` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(500) DEFAULT NULL COMMENT 'رابط يوتيوب أو ملف مرفوع',
  `pdf_file` VARCHAR(255) DEFAULT NULL,
  `ppt_file` VARCHAR(255) DEFAULT NULL,
  `external_links` TEXT DEFAULT NULL COMMENT 'روابط خارجية (سطر لكل رابط)',
  `references_text` TEXT DEFAULT NULL COMMENT 'المراجع',
  `pass_grade` DECIMAL(5,2) NOT NULL DEFAULT 60.00 COMMENT 'درجة النجاح المطلوبة %',
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_lessons_course` (`course_id`,`lesson_order`),
  KEY `idx_lms_lessons_teacher` (`teacher_id`),
  CONSTRAINT `fk_lms_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_lessons_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2) ملفات إضافية للدرس
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_lesson_files` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` INT(11) NOT NULL,
  `file_title` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_lesson_files_lesson` (`lesson_id`),
  CONSTRAINT `fk_lms_lesson_files_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lms_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3) أنشطة الدرس (5 أنشطة متدرجة الصعوبة لكل درس)
-- activity_order: 1=اختيار من متعدد ⭐ .. 5=مشروع ⭐⭐⭐⭐⭐
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` INT(11) NOT NULL,
  `activity_order` TINYINT(1) NOT NULL COMMENT '1..5 مستوى الصعوبة',
  `activity_type` ENUM('mcq','true_false','ordering','matching','short_answer','project') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `max_grade` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `time_limit_minutes` INT(11) DEFAULT NULL COMMENT 'اختياري',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_activity_order` (`lesson_id`,`activity_order`),
  CONSTRAINT `fk_lms_activities_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lms_lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4) أسئلة الأنشطة
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_activity_questions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_id` INT(11) NOT NULL,
  `question_order` INT(11) NOT NULL DEFAULT 1,
  `question_text` TEXT NOT NULL,
  `points` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `explanation` TEXT DEFAULT NULL COMMENT 'شرح سبب صحة الإجابة',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_questions_activity` (`activity_id`,`question_order`),
  CONSTRAINT `fk_lms_questions_activity` FOREIGN KEY (`activity_id`) REFERENCES `lms_activities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5) إجابات الأسئلة
-- mcq/true_false : is_correct يحدد الإجابة الصحيحة
-- ordering       : correct_order يحدد الترتيب الصحيح
-- matching       : match_key يربط الطرف الأيمن بالأيسر (نفس المفتاح = زوج)
-- short_answer   : answer_text = الإجابة النموذجية (is_correct=1)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_activity_answers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question_id` INT(11) NOT NULL,
  `answer_text` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `correct_order` INT(11) DEFAULT NULL,
  `match_key` VARCHAR(50) DEFAULT NULL,
  `match_side` ENUM('left','right') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lms_answers_question` (`question_id`),
  CONSTRAINT `fk_lms_answers_question` FOREIGN KEY (`question_id`) REFERENCES `lms_activity_questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6) محاولات الطلاب على الأنشطة
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_student_activity_attempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `activity_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `attempt_no` INT(11) NOT NULL DEFAULT 1,
  `score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `is_passed` TINYINT(1) NOT NULL DEFAULT 0,
  `answers_json` LONGTEXT DEFAULT NULL COMMENT 'إجابات الطالب التفصيلية',
  `project_file` VARCHAR(255) DEFAULT NULL COMMENT 'ملف المشروع للنشاط الخامس',
  `teacher_feedback` TEXT DEFAULT NULL,
  `graded_by` INT(11) DEFAULT NULL COMMENT 'teacher_id عند التصحيح اليدوي',
  `started_at` DATETIME DEFAULT NULL,
  `finished_at` DATETIME DEFAULT NULL,
  `duration_seconds` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_attempts_activity_student` (`activity_id`,`student_id`),
  KEY `idx_lms_attempts_student` (`student_id`),
  CONSTRAINT `fk_lms_attempts_activity` FOREIGN KEY (`activity_id`) REFERENCES `lms_activities`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_attempts_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7) تقدم الطالب في الدروس (فتح تسلسلي + آخر موضع)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_lesson_progress` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `status` ENUM('locked','in_progress','completed') NOT NULL DEFAULT 'in_progress',
  `completed_activities` TINYINT(1) NOT NULL DEFAULT 0,
  `last_activity_id` INT(11) DEFAULT NULL,
  `last_question_index` INT(11) NOT NULL DEFAULT 0,
  `time_spent_seconds` INT(11) NOT NULL DEFAULT 0,
  `completed_at` DATETIME DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_lesson_progress` (`lesson_id`,`student_id`),
  KEY `idx_lms_lp_student` (`student_id`),
  CONSTRAINT `fk_lms_lp_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lms_lessons`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_lp_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8) التقدم الإجمالي لكل طالب في كل مقرر
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_student_progress` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `course_id` INT(11) NOT NULL,
  `completed_lessons` INT(11) NOT NULL DEFAULT 0,
  `progress_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `avg_grade` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_time_seconds` INT(11) NOT NULL DEFAULT 0,
  `last_lesson_id` INT(11) DEFAULT NULL,
  `last_activity_id` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_student_progress` (`student_id`,`course_id`),
  KEY `idx_lms_sp_course` (`course_id`),
  CONSTRAINT `fk_lms_sp_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_sp_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9) النجوم (نجمة ذهبية لكل درس مكتمل)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_stars` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `lesson_id` INT(11) NOT NULL,
  `course_id` INT(11) NOT NULL,
  `awarded_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_star` (`student_id`,`lesson_id`),
  KEY `idx_lms_stars_course` (`course_id`),
  CONSTRAINT `fk_lms_stars_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_stars_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lms_lessons`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_stars_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10) تعريف الشارات
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_badges` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `badge_key` VARCHAR(50) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(20) DEFAULT '🏅',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_badge_key` (`badge_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11) شارات الطلاب
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_student_badges` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `badge_id` INT(11) NOT NULL,
  `awarded_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_student_badge` (`student_id`,`badge_id`),
  CONSTRAINT `fk_lms_sb_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_sb_badge` FOREIGN KEY (`badge_id`) REFERENCES `lms_badges`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12) لوحة الصدارة (تُحدَّث تلقائياً بعد كل نشاط)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_leaderboard` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `total_stars` INT(11) NOT NULL DEFAULT 0,
  `total_badges` INT(11) NOT NULL DEFAULT 0,
  `completed_lessons` INT(11) NOT NULL DEFAULT 0,
  `avg_grade` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `progress_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_time_seconds` INT(11) NOT NULL DEFAULT 0,
  `rank_position` INT(11) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_leaderboard_student` (`student_id`),
  KEY `idx_lms_leaderboard_rank` (`rank_position`),
  CONSTRAINT `fk_lms_lb_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13) شهادات LMS (منفصلة عن جدول certificates الحالي)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_certificates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `course_id` INT(11) NOT NULL,
  `certificate_no` VARCHAR(100) NOT NULL,
  `stars` INT(11) NOT NULL DEFAULT 0,
  `badges` INT(11) NOT NULL DEFAULT 0,
  `progress_percent` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
  `final_grade` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `issue_date` DATE NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_certificate` (`student_id`,`course_id`),
  UNIQUE KEY `uq_lms_certificate_no` (`certificate_no`),
  CONSTRAINT `fk_lms_cert_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lms_cert_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 14) سجلات التدقيق (Audit Logs)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lms_logs_user` (`user_id`),
  KEY `idx_lms_logs_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 15) إعدادات وحدة LMS
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lms_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(150) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lms_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- البيانات الأولية
-- =====================================================================

INSERT IGNORE INTO `lms_settings` (`setting_key`,`setting_value`) VALUES
('default_pass_grade','60'),
('certificate_min_percent','100'),
('max_attempts','0'), -- 0 = غير محدود
('short_answer_similarity','80');

INSERT IGNORE INTO `lms_badges` (`badge_key`,`title`,`description`,`icon`) VALUES
('first_lesson','أول درس مكتمل','أكمل أول درس في المنصة','🥉'),
('five_lessons','خمسة دروس','أكمل خمسة دروس','🥈'),
('ten_lessons','عشرة دروس','أكمل عشرة دروس','🥇'),
('streak_5','خمسة أيام متتالية','تعلّم خمسة أيام متتالية دون انقطاع','🔥'),
('fastest','أسرع طالب','أسرع طالب في إنهاء درس','⚡'),
('perfect_score','درجة كاملة','حصل على الدرجة الكاملة في نشاط','🎯'),
('no_mistakes','بلا أخطاء','أنهى جميع أنشطة درس بدون أي خطأ','💯'),
('course_complete','مقرر كامل','أنهى مقرراً كاملاً بجميع دروسه وأنشطته','🏆');

SET FOREIGN_KEY_CHECKS = 1;
