<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';

/*
|--------------------------------------------------------------------------
| BlockText — النصوص داخل الأقسام
| كل خط عربي أُضيفت له 'rtl' => true وكل فقرة 'bidi' => true
| (بدون rtl على الخط، Word يعامل النص كـ LTR فتنقلب علامات الترقيم)
|--------------------------------------------------------------------------
*/

class BlockText
{
    /* ---------- أنماط موحّدة ---------- */

    private static function font(array $extra = []): array
    {
        return array_merge([
            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::FONT_SIZE,
            'color' => WordConfig::TEXT_COLOR,
            'rtl'   => true,
        ], $extra);
    }

    private static function para(array $extra = []): array
    {
        return array_merge([
            'alignment'  => Jc::RIGHT,
            'bidi'       => true,
            'spaceAfter' => 140,
        ], $extra);
    }

    /**
     * نص عادي
     */
    public static function render(Section $section, string $text): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $section->addText($text, self::font(), self::para());
    }

    /**
     * عنوان فرعي
     */
    public static function heading(Section $section, string $title): void
    {
        $title = trim($title);

        if ($title === '') {
            return;
        }

        $section->addText(
            $title,
            self::font([
                'size'  => WordConfig::SUBTITLE_SIZE,
                'bold'  => true,
                'color' => WordConfig::PRIMARY_COLOR,
            ]),
            self::para([
                'spaceBefore' => 120,
                'spaceAfter'  => 160,
            ])
        );
    }

    /**
     * نص عريض
     */
    public static function bold(Section $section, string $text): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $section->addText(
            $text,
            self::font(['bold' => true]),
            self::para(['spaceAfter' => 120])
        );
    }

    /**
     * عنوان : قيمة
     */
    public static function keyValue(Section $section, string $key, string $value): void
    {
        $run = $section->addTextRun(self::para(['spaceAfter' => 100]));

        $run->addText(
            $key . ' : ',
            self::font([
                'bold'  => true,
                'color' => WordConfig::PRIMARY_COLOR,
            ])
        );

        $run->addText(trim($value), self::font());
    }

    /**
     * ملاحظة
     */
    public static function note(Section $section, string $text): void
    {
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $section->addText(
            $text,
            self::font([
                'italic' => true,
                'size'   => WordConfig::FONT_SIZE - 1,
                'color'  => WordConfig::MUTED_COLOR,
            ]),
            self::para(['spaceAfter' => 120])
        );
    }

    /**
     * فقرة فارغة
     */
    public static function spacer(Section $section, int $lines = 1): void
    {
        $section->addTextBreak($lines);
    }
}
