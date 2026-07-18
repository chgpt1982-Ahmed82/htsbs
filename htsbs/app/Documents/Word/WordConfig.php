<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| WordConfig — إعدادات تنسيق مستند التحضير الذكي
| (نفس أسماء الثوابت القديمة + ثوابت جديدة للتنسيق المحسّن)
|--------------------------------------------------------------------------
*/

class WordConfig
{
    /*
    |--------------------------------------------------------------------------
    | Fonts
    |--------------------------------------------------------------------------
    */

    // Cairo قد لا يكون مثبتاً على جهاز المستخدم، لذلك نستخدم خطاً عربياً
    // متوفراً افتراضياً في Windows/Office لضمان ظهور التنسيق لدى الجميع
    public const FONT_NAME = 'Sakkal Majalla';

    // خط بديل يُستخدم للأرقام والنصوص اللاتينية
    public const FONT_ALT = 'Segoe UI';

    public const FONT_SIZE     = 13;   // نص المتن
    public const TITLE_SIZE    = 20;   // عنوان المستند
    public const SUBTITLE_SIZE = 15;   // العناوين الفرعية
    public const SMALL_SIZE    = 10;   // التذييل والملاحظات

    /*
    |--------------------------------------------------------------------------
    | Colors (لوحة ألوان متناسقة)
    |--------------------------------------------------------------------------
    */

    public const PRIMARY_COLOR = '1F4E79';  // أزرق داكن أنيق
    public const SUCCESS_COLOR = '1E7B34';  // أخضر
    public const WARNING_COLOR = 'B85C00';  // برتقالي
    public const DANGER_COLOR  = 'B02418';  // أحمر
    public const PURPLE_COLOR  = '5B2D8E';  // بنفسجي

    public const ACCENT_COLOR  = 'C9A227';  // ذهبي (خط فاصل الترويسة)
    public const TEXT_COLOR    = '212529';  // لون النص الأساسي
    public const MUTED_COLOR   = '6C757D';  // نص ثانوي

    public const LABEL_BG      = 'DCE6F1';  // خلفية خلايا التسميات
    public const VALUE_BG      = 'FFFFFF';  // خلفية خلايا القيم
    public const ZEBRA_BG      = 'F4F7FB';  // تظليل الصفوف المتبادلة
    public const BORDER_COLOR  = 'B7C7DC';  // لون حدود الجداول

    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */

    public const PAGE_MARGIN = 900;
    public const CELL_MARGIN = 120;

    // العرض الكلي المتاح للجداول (twips) داخل هوامش A4
    public const CONTENT_WIDTH = 9400;
}
