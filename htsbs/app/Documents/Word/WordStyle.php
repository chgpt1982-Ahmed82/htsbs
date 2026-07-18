<?php

declare(strict_types=1);

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/WordConfig.php';

/*
|--------------------------------------------------------------------------
| WordStyle — تسجيل الأنماط المسماة
| جميع الأنماط الآن RTL: alignment=RIGHT + bidi=true + rtl=true
| والجداول bidiVisual=true (ترتيب الأعمدة من اليمين لليسار)
|--------------------------------------------------------------------------
*/

class WordStyle
{
    public static function register(PhpWord $phpWord): void
    {
        /*
        ==================================================
        فقرة RTL أساسية
        ==================================================
        */

        $phpWord->addParagraphStyle('RTL', [
            'alignment'  => Jc::RIGHT,
            'bidi'       => true,
            'spaceAfter' => 120,
        ]);

        $phpWord->addParagraphStyle('Paragraph', [
            'alignment'  => Jc::RIGHT,
            'bidi'       => true,
            'spaceAfter' => 140,
        ]);

        $phpWord->addParagraphStyle('CenterRTL', [
            'alignment'  => Jc::CENTER,
            'bidi'       => true,
            'spaceAfter' => 100,
        ]);

        /*
        ==================================================
        خطوط عربية (rtl = true ضرورية لكل خط عربي)
        ==================================================
        */

        $arabic = [
            'name' => WordConfig::FONT_NAME,
            'size' => WordConfig::FONT_SIZE,
            'rtl'  => true,
        ];

        $phpWord->addFontStyle('Arabic', $arabic);
        $phpWord->addFontStyle('Normal', $arabic + ['color' => WordConfig::TEXT_COLOR]);

        $phpWord->addFontStyle('Bold', [
            'name' => WordConfig::FONT_NAME,
            'size' => WordConfig::FONT_SIZE,
            'bold' => true,
            'rtl'  => true,
        ]);

        $phpWord->addFontStyle('Small', [
            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::SMALL_SIZE,
            'color' => WordConfig::MUTED_COLOR,
            'rtl'   => true,
        ]);

        /*
        ==================================================
        العناوين
        ==================================================
        */

        $phpWord->addTitleStyle(
            1,
            [
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::TITLE_SIZE,
                'bold'  => true,
                'color' => WordConfig::PRIMARY_COLOR,
                'rtl'   => true,
            ],
            [
                'alignment'  => Jc::CENTER,
                'bidi'       => true,
                'spaceAfter' => 300,
            ]
        );

        $phpWord->addTitleStyle(
            2,
            [
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::SUBTITLE_SIZE,
                'bold'  => true,
                'color' => WordConfig::PRIMARY_COLOR,
                'rtl'   => true,
            ],
            [
                'alignment'   => Jc::RIGHT,
                'bidi'        => true,
                'spaceBefore' => 240,
                'spaceAfter'  => 140,
            ]
        );

        /*
        ==================================================
        الجداول — bidiVisual لعكس ترتيب الأعمدة
        ==================================================
        */

        $phpWord->addTableStyle(
            'LessonTable',
            [
                'borderSize'  => 6,
                'borderColor' => WordConfig::BORDER_COLOR,
                'cellMargin'  => WordConfig::CELL_MARGIN,
                'alignment'   => Jc::CENTER,
                'bidiVisual'  => true,
            ],
            [
                'bgColor' => WordConfig::LABEL_BG,
            ]
        );

        // نص رأس الجدول: حجم معقول (كان 18 = ضخم جداً)
        $phpWord->addFontStyle('TableHeader', [
            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::FONT_SIZE,
            'bold'  => true,
            'color' => WordConfig::PRIMARY_COLOR,
            'rtl'   => true,
        ]);

        $phpWord->addFontStyle('TableText', [
            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::FONT_SIZE,
            'color' => WordConfig::TEXT_COLOR,
            'rtl'   => true,
        ]);

        /*
        ==================================================
        القوائم النقطية — النقطة على اليمين
        ==================================================
        */

        $phpWord->addNumberingStyle('LessonList', [
            'type'   => 'multilevel',
            'levels' => [
                [
                    'format'  => 'bullet',
                    'text'    => '•',
                    'left'    => 360,
                    'hanging' => 180,
                    'alignment' => Jc::RIGHT,
                ],
            ],
        ]);
    }
}
