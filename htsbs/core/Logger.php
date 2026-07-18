<?php
/*
=====================================================================
Logger — تسجيل نشاط النظام (Audit Log)
=====================================================================
الاستخدام (سطر واحد من أي مكان في المشروع):

    require_once __DIR__ . '/../core/Logger.php';

    Logger::log('students', 'create', 'إضافة طالب: أحمد علي', 'student', 15);

أو الاختصارات الجاهزة:

    Logger::login($user);                  // تسجيل دخول ناجح
    Logger::loginFailed($email);           // محاولة دخول فاشلة (danger)
    Logger::logout();
    Logger::created('students', 'أحمد علي', 15);
    Logger::updated('courses', 'الرياضيات', 3);
    Logger::deleted('students', 'أحمد علي', 15);   // danger تلقائياً

ملاحظات تصميمية:
- لا يرمي استثناءات أبداً: إذا فشل التسجيل لأي سبب، يُتجاهل بصمت
  حتى لا يُعطّل السجلُّ العملياتِ الأساسية للنظام.
- يلتقط user_id و role_id و الاسم من الـ Session تلقائياً.
- يخزن نسخة من اسم المستخدم وقت الحدث، فيبقى السجل مفهوماً
  حتى لو حُذف المستخدم لاحقاً.
=====================================================================
*/

require_once __DIR__ . '/../config/database.php';

class Logger
{
    /* اتصال مُعاد استخدامه لتفادي فتح اتصال جديد لكل سطر */
    private static ?PDO $db = null;

    private static function db(): ?PDO
    {
        if (self::$db instanceof PDO) {
            return self::$db;
        }

        try {
            $database = new Database();
            self::$db = $database->connect();
            return self::$db;
        } catch (Throwable $e) {
            return null; // فشل الاتصال — لا نُعطّل النظام
        }
    }

    /*
    ====================================
    الدالة الأساسية
    ====================================
    */
    public static function log(
        string $module,
        string $action,
        string $details = '',
        ?string $targetType = null,
        ?int $targetId = null,
        string $severity = 'info'
    ): void {

        try {
            $db = self::db();
            if (!$db) return;

            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }

            $userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $roleId   = isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : null;
            $userName = $_SESSION['name'] ?? null;

            if (!in_array($severity, ['info', 'warning', 'danger'], true)) {
                $severity = 'info';
            }

            $stmt = $db->prepare("
                INSERT INTO system_logs
                    (user_id, user_name, role_id, module, action, details,
                     severity, target_type, target_id, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId,
                $userName ? mb_substr((string)$userName, 0, 150) : null,
                $roleId,
                mb_substr($module, 0, 50),
                mb_substr($action, 0, 100),
                $details !== '' ? $details : null,
                $severity,
                $targetType ? mb_substr($targetType, 0, 50) : null,
                $targetId,
                self::ip(),
                mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);

        } catch (Throwable $e) {
            // صامت عمداً — السجل لا يجب أن يُعطّل النظام
        }
    }

    /*
    ====================================
    اختصارات جاهزة
    ====================================
    */

    /** تسجيل دخول ناجح */
    public static function login(array $user): void
    {
        // نضمن وجود بيانات الجلسة حتى لو استُدعيت قبل Auth::login
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION['user_id'] = $_SESSION['user_id'] ?? ($user['id'] ?? null);
        $_SESSION['role_id'] = $_SESSION['role_id'] ?? ($user['role_id'] ?? null);
        $_SESSION['name']    = $_SESSION['name']    ?? ($user['full_name'] ?? null);

        self::log('auth', 'login', 'تسجيل دخول ناجح', 'user', (int)($user['id'] ?? 0));
    }

    /** محاولة دخول فاشلة */
    public static function loginFailed(string $identifier): void
    {
        self::log(
            'auth',
            'login_failed',
            'محاولة دخول فاشلة: ' . $identifier,
            null,
            null,
            'danger'
        );
    }

    /** تسجيل خروج */
    public static function logout(): void
    {
        self::log('auth', 'logout', 'تسجيل خروج');
    }

    /** إضافة سجل */
    public static function created(string $module, string $name, ?int $id = null): void
    {
        self::log($module, 'create', 'إضافة: ' . $name, rtrim($module, 's'), $id);
    }

    /** تعديل سجل */
    public static function updated(string $module, string $name, ?int $id = null): void
    {
        self::log($module, 'update', 'تعديل: ' . $name, rtrim($module, 's'), $id, 'warning');
    }

    /** حذف سجل (خطير دائماً) */
    public static function deleted(string $module, string $name, ?int $id = null): void
    {
        self::log($module, 'delete', 'حذف: ' . $name, rtrim($module, 's'), $id, 'danger');
    }

    /*
    ====================================
    عنوان IP الحقيقي (خلف بروكسي Hostinger)
    ====================================
    */
    private static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {

            if (empty($_SERVER[$key])) {
                continue;
            }

            // X-Forwarded-For قد يحتوي عدة عناوين مفصولة بفاصلة
            $ip = trim(explode(',', (string)$_SERVER[$key])[0]);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
