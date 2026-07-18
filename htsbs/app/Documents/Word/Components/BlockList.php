<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';
require_once __DIR__ . '/BlockText.php';

/*
|--------------------------------------------------------------------------
| BlockList — القوائم النقطية والمرقمة
| النقطة (•) تظهر على يمين النص:
|   - الفقرة: alignment = RIGHT + bidi = true
|   - المسافة البادئة: 'indent' من اليمين عبر bidi
|   - الخط: rtl = true
|--------------------------------------------------------------------------
*/

class BlockList
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
            'bidi'       => true,   // النقطة تنتقل لليمين
            'spaceAfter' => 80,
        ], $extra);
    }

    /**
     * قائمة نقطية
     */
    public static function render(Section $section, array $items): void
    {
        foreach ($items as $item) {

            if (is_array($item)) {
                $item = implode(' - ', $item);
            }

            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            /*
            ملاحظة: نترك نمط القائمة افتراضياً (null) ونعتمد على
            bidi في الفقرة لنقل النقطة إلى اليمين — تمرير اسم نمط
            ترقيم هنا قد يسبب استثناء في بعض إصدارات PhpWord.
            */
            $section->addListItem(
                $item,
                0,
                self::font(),
                null,
                self::para()
            );
        }
    }

    /**
     * قائمة من نص متعدد الأسطر
     */
    public static function fromMultiline(Section $section, string $text): void
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($text));

        if (!$lines) {
            return;
        }

        self::render($section, $lines);
    }

    /**
     * قائمة مرقمة (الرقم على اليمين)
     */
    public static function numbered(Section $section, array $items): void
    {
        $i = 1;

        foreach ($items as $item) {

            if (is_array($item)) {
                $item = implode(' - ', $item);
            }

            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            $run = $section->addTextRun(self::para(['spaceAfter' => 100]));

            // الرقم أولاً (سيظهر على اليمين بفضل bidi)
            $run->addText(
                $i . '. ',
                self::font([
                    'bold'  => true,
                    'color' => WordConfig::PRIMARY_COLOR,
                ])
            );

            $run->addText($item, self::font());

            $i++;
        }
    }

    /**
     * Array أو نص متعدد الأسطر
     */
    public static function auto(Section $section, mixed $value): void
    {
        if (is_array($value)) {
            self::render($section, $value);
            return;
        }

        if (!is_string($value)) {
            return;
        }

        if (preg_match("/\r\n|\n|\r/", $value)) {
            self::fromMultiline($section, $value);
            return;
        }

        self::render($section, [$value]);
    }

    /**
     * عنصر واحد
     */
    public static function add(Section $section, string $item): void
    {
        $item = trim($item);

        if ($item === '') {
            return;
        }

        self::render($section, [$item]);
    }

    /**
     * عنوان ثم قائمة
     */
    public static function titled(Section $section, string $title, array $items): void
    {
        BlockText::heading($section, $title);
        self::render($section, $items);
    }

    /**
     * خط فاصل بسيط
     */
    public static function divider(Section $section): void
    {
        $section->addTextBreak();
    }
}
