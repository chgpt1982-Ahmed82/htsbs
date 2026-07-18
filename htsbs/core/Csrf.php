<?php
/*
=====================================================================
Csrf — الحماية من هجمات تزوير الطلبات عبر المواقع (CSRF)
=====================================================================
لماذا هذا ضروري؟
بدون رمز CSRF، يستطيع موقع خبيث خداع متصفح شخص مسجّل دخوله في نظامك
(مثلاً أدمن يتصفح موقعاً آخر في نفس المتصفح) ليرسل نموذجاً مخفياً
إلى مثل: /admin/students/delete.php?id=5 — والمتصفح يرفق كوكيز
الجلسة تلقائياً، فيُنفَّذ الحذف دون علم الضحية أو نيته.

الاستخدام:
------------------------------------------------------
1) في كل نموذج (form)، أضف مباشرة بعد <form ...>:

    <?php require_once __DIR__ . '/../../core/Csrf.php'; ?>
    <?= Csrf::field(); ?>

2) في أعلى كل ملف يعالج POST (store/update/delete/save_link...)،
   بعد Auth::check() مباشرة:

    require_once '../../core/Csrf.php';
    Csrf::verify();   // يوقف التنفيذ تلقائياً إن كان الرمز خاطئاً/مفقوداً

هذا كل ما يلزم — الصنف يتكفّل بالباقي (التوليد، والتخزين في الجلسة،
والمقارنة الآمنة ضد هجمات التوقيت Timing Attack عبر hash_equals).
=====================================================================
*/

class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /*
    ================================================
    توليد/استرجاع رمز الجلسة الحالي
    نفس الرمز يبقى صالحاً طوال الجلسة (لا يتغيّر مع كل صفحة)
    حتى تعمل عدة تبويبات مفتوحة في نفس الوقت بلا مشاكل
    ================================================
    */
    public static function token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /*
    ================================================
    حقل <input> جاهز للصق داخل أي <form>
    ================================================
    */
    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /*
    ================================================
    التحقق — يُستدعى في أول كل معالج POST
    يوقف التنفيذ فوراً (die) إن كان الرمز مفقوداً أو خاطئاً
    ================================================
    */
    public static function verify(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /* الطلبات غير POST لا تحتاج تحققاً (GET، مثل روابط الحذف بمعرّف) */
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $sent    = (string)($_POST['csrf_token'] ?? '');
        $stored  = (string)($_SESSION[self::SESSION_KEY] ?? '');

        $valid = $sent !== '' && $stored !== '' && hash_equals($stored, $sent);

        if (!$valid) {

            /* نسجّل المحاولة إن كان Logger متاحاً */
            if (class_exists('Logger')) {
                Logger::log(
                    'security',
                    'csrf_blocked',
                    'رفض طلب: رمز CSRF مفقود أو غير صالح — ' . ($_SERVER['REQUEST_URI'] ?? ''),
                    null,
                    null,
                    'danger'
                );
            }

            http_response_code(403);
            die('انتهت صلاحية الجلسة أو الطلب غير موثّق. يرجى إعادة تحميل الصفحة والمحاولة مجدداً.');
        }
    }

    /*
    ================================================
    التحقق من طلبات GET الحساسة (مثل روابط الحذف)
    اختياري — استخدمه فقط إن أردت حماية روابط GET أيضاً
    (يتطلب إضافة ?csrf_token=... في الرابط نفسه)
    ================================================
    */
    public static function verifyGet(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sent   = (string)($_GET['csrf_token'] ?? '');
        $stored = (string)($_SESSION[self::SESSION_KEY] ?? '');

        $valid = $sent !== '' && $stored !== '' && hash_equals($stored, $sent);

        if (!$valid) {

            if (class_exists('Logger')) {
                Logger::log(
                    'security',
                    'csrf_blocked_get',
                    'رفض طلب GET: رمز CSRF مفقود أو غير صالح — ' . ($_SERVER['REQUEST_URI'] ?? ''),
                    null,
                    null,
                    'danger'
                );
            }

            http_response_code(403);
            die('رابط غير موثّق أو منتهي الصلاحية.');
        }
    }

    /*
    ================================================
    إلحاق رمز CSRF بأي رابط GET حساس (مثل روابط الحذف)
    الاستخدام: <a href="<?= Csrf::url('delete.php?id=5') ?>">حذف</a>
    ================================================
    */
    public static function url(string $url): string
    {
        $sep = (strpos($url, '?') !== false) ? '&' : '?';
        return $url . $sep . 'csrf_token=' . urlencode(self::token());
    }
}
