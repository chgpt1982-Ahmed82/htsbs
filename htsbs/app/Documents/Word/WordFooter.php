<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/WordConfig.php';

/*
|--------------------------------------------------------------------------
| WordFooter — تذييل المستند
|
| التحسينات:
| - خط ذهبي رفيع بدل الرمادي الباهت
| - سطر واحد مرتب: اسم النظام | رقم الصفحة | التاريخ (بدل ثلاثة أسطر مكدسة)
| - محاذاة RTL صحيحة وأحجام متناسقة
|--------------------------------------------------------------------------
*/

class WordFooter
{
    public static function build(Section $section): void
    {
        $footer = $section->addFooter();

        /*
        ==================================================
        خط فاصل
        ==================================================
        */

        $footer->addLine([
            'weight' => 1,
            'width'  => 470,
            'height' => 0,
            'color'  => WordConfig::ACCENT_COLOR,
        ]);

        /*
        ==================================================
        جدول التذييل: [التاريخ] [رقم الصفحة] [اسم النظام]
        بلا حدود — لضبط المحاذاة بدقة بدل التبويبات
        ==================================================
        */

        $table = $footer->addTable([
            'width'      => 100 * 50,
            'unit'       => 'pct',
            'borderSize' => 0,
            'cellMargin' => 0,
            'bidiVisual' => true,
        ]);

        $table->addRow();

        $small = [
            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::SMALL_SIZE,
            'color' => WordConfig::MUTED_COLOR,
            'rtl'   => true,
        ];

        /* اليمين: نظام إدارة التعلم */
        $table->addCell(3200)->addText(
            'نظام إدارة التعلم — التحضير الذكي',
            $small,
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 0, 'bidi' => true]
        );

        /* الوسط: رقم الصفحة */
        $table->addCell(3000)->addPreserveText(
            'صفحة {PAGE} من {NUMPAGES}',
            [
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::SMALL_SIZE,
                'bold'  => true,
                'color' => WordConfig::PRIMARY_COLOR,
            ],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );

        /* اليسار: تاريخ الإصدار */
        $table->addCell(3200)->addText(
            date('Y-m-d'),
            $small,
            ['alignment' => Jc::LEFT, 'spaceAfter' => 0]
        );
    }
}
