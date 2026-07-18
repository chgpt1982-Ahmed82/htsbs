-- ============================================================
-- جدول التخطيطات العميقة للدروس الفائقة
-- deep_lesson_plans
-- ============================================================

CREATE TABLE IF NOT EXISTS `deep_lesson_plans` (

    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- المعلم والمادة والصف
    `teacher_id`          INT UNSIGNED NOT NULL,
    `subject_id`          INT UNSIGNED NOT NULL,
    `class_id`            INT UNSIGNED NOT NULL,

    -- معلومات الدرس الأساسية
    `unit_name`           VARCHAR(255) NOT NULL,
    `lesson_title`        VARCHAR(255) NOT NULL,
    `lesson_date`         DATE,
    `lesson_duration`     TINYINT UNSIGNED DEFAULT 45,
    `student_level`       VARCHAR(50) DEFAULT 'متوسط',

    -- الأهداف السلوكية
    `objective_1`         TEXT,
    `objective_2`         TEXT,

    -- المهارات الأساسية
    `skill_1`             TEXT,
    `skill_2`             TEXT,

    -- طريقة التدريس والوسائل
    `teaching_method`     VARCHAR(100),
    `reinforcement`       VARCHAR(255),
    `technology`          VARCHAR(255),
    `resources`           JSON COMMENT 'قائمة الوسائل التعليمية',
    `facilities`          JSON COMMENT 'المرافق المدرسية المستخدمة',

    -- محتوى الدرس
    `lesson_description`  TEXT,
    `learning_outcomes`   TEXT,
    `keywords`            VARCHAR(500),

    -- التمايز 6G6Y
    `challenge_card`      TEXT  COMMENT 'بطاقة التحدي الخضراء',
    `support_card`        TEXT  COMMENT 'بطاقة المساعدة الصفراء',

    -- الربط والإثراء
    `national_exams_link` TEXT,
    `homework`            TEXT,
    `bahrain_link`        TEXT,

    -- محتوى التخطيط (مولّد بالذكاء الاصطناعي)
    `lesson_plan`         LONGTEXT COMMENT 'نص التخطيط',
    `lesson_plan_json`    LONGTEXT COMMENT 'JSON التخطيط الكامل',
    `lesson_plan_html`    LONGTEXT COMMENT 'HTML التخطيط للعرض',

    -- إعدادات الذكاء الاصطناعي
    `ai_prompt`           LONGTEXT,
    `ai_model`            VARCHAR(50),
    `generation_time`     FLOAT DEFAULT 0,
    `tokens_used`         INT UNSIGNED DEFAULT 0,

    -- الحالة والإصدار
    `status`              ENUM('draft','published','archived') DEFAULT 'draft',
    `is_favorite`         TINYINT(1) DEFAULT 0,
    `version_no`          SMALLINT UNSIGNED DEFAULT 1,
    `notes`               TEXT,

    -- إحصائيات التصدير
    `exported_pdf`        INT UNSIGNED DEFAULT 0,
    `exported_word`       INT UNSIGNED DEFAULT 0,
    `printed_count`       INT UNSIGNED DEFAULT 0,

    -- التوقيتات
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_teacher` (`teacher_id`),
    INDEX `idx_subject` (`subject_id`),
    INDEX `idx_class`   (`class_id`),
    INDEX `idx_status`  (`status`),
    INDEX `idx_favorite`(`is_favorite`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
