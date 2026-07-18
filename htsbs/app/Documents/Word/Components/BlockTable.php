<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

require_once __DIR__ . '/../WordConfig.php';

/*
|--------------------------------------------------------------------------
| BlockTable — جداول المحتوى داخل الأقسام
|
| التصحيح الجوهري:
| النسخة السابقة كانت تستخدم array_reverse() لعكس الأعمدة يدوياً،
| وهذه حيلة خاطئة تُفسد التطابق بين رؤوس الأعمدة وبياناتها،
| كما أن الجدول يبقى LTR في Word (المؤشر ينتقل يساراً).
|
| الصحيح: 'bidiVisual' => true على الجدول نفسه
| فيقلب Word ترتيب الأعمدة بصرياً ويجعل اتجاه الجدول RTL،
| مع الإبقاء على ترتيب البيانات كما هي في المصفوفة.
|--------------------------------------------------------------------------
*/

class BlockTable
{
    /* ---------- أنماط موحّدة ---------- */

    private static function tableStyle(string $borderColor): array
    {
        return [
            'borderSize'  => 6,
            'borderColor' => $borderColor,
            'cellMargin'  => WordConfig::CELL_MARGIN,
            'alignment'   => Jc::CENTER,
            'bidiVisual'  => true,   // ← اتجاه الجدول من اليمين لليسار
        ];
    }

    private static function cellPara(string $align = 'right'): array
    {
        return [
            'alignment'  => $align === 'center' ? Jc::CENTER : Jc::RIGHT,
            'bidi'       => true,
            'spaceAfter' => 0,
        ];
    }

    private static function font(array $extra = []): array
    {
        return array_merge([
            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::FONT_SIZE,
            'color' => WordConfig::TEXT_COLOR,
            'rtl'   => true,
        ], $extra);
    }

    /**
     * جدول برؤوس أعمدة
     *
     * $headers = ['المهارة','الوصف','التقييم']
     * $rows    = [['التفكير','تحليل البيانات','ممتاز'], ...]
     */
    public static function render(
        Section $section,
        array $headers,
        array $rows,
        string $headerColor = WordConfig::PRIMARY_COLOR
    ): void {

        if (empty($headers)) {
            return;
        }

        $cols  = max(count($headers), 1);
        $width = (int)(WordConfig::CONTENT_WIDTH / $cols);

        $table = $section->addTable(self::tableStyle($headerColor));

        /*
        |----------------------------------------------------------
        | رؤوس الأعمدة (بدون عكس — bidiVisual يتكفّل بالاتجاه)
        |----------------------------------------------------------
        */

        $table->addRow();

        foreach ($headers as $header) {

            $table->addCell(
                $width,
                [
                    'bgColor' => $headerColor,
                    'valign'  => VerticalJc::CENTER,
                ]
            )->addText(
                trim((string)$header),
                self::font([
                    'bold'  => true,
                    'color' => 'FFFFFF',
                ]),
                self::cellPara('center')
            );
        }

        /*
        |----------------------------------------------------------
        | صفوف البيانات (مع تظليل متبادل)
        |----------------------------------------------------------
        */

        $i = 0;

        foreach ($rows as $row) {

            if (!is_array($row)) {
                continue;
            }

            $table->addRow();

            $bg = ($i % 2 === 0)
                ? WordConfig::VALUE_BG
                : WordConfig::ZEBRA_BG;

            foreach ($row as $cellValue) {

                if (is_array($cellValue)) {
                    $cellValue = implode('، ', $cellValue);
                }

                $table->addCell(
                    $width,
                    [
                        'valign'  => VerticalJc::CENTER,
                        'bgColor' => $bg,
                    ]
                )->addText(
                    trim((string)$cellValue),
                    self::font(),
                    self::cellPara()
                );
            }

            $i++;
        }

        $section->addTextBreak();
    }

    /**
     * جدول بسيط من مصفوفة ثنائية (بلا رؤوس أعمدة)
     * العمود الأول يُعامل كتسمية
     */
    public static function simple(Section $section, array $rows): void
    {
        $table = $section->addTable(
            self::tableStyle(WordConfig::BORDER_COLOR)
        );

        foreach ($rows as $row) {

            if (!is_array($row)) {
                continue;
            }

            $table->addRow();

            $first = true;

            foreach ($row as $cellValue) {

                if (is_array($cellValue)) {
                    $cellValue = implode('، ', $cellValue);
                }

                $table->addCell(
                    4500,
                    [
                        'valign'  => VerticalJc::CENTER,
                        'bgColor' => $first
                            ? WordConfig::LABEL_BG
                            : WordConfig::VALUE_BG,
                    ]
                )->addText(
                    trim((string)$cellValue),
                    self::font(
                        $first
                            ? ['bold' => true, 'color' => WordConfig::PRIMARY_COLOR]
                            : []
                    ),
                    self::cellPara()
                );

                $first = false;
            }
        }

        $section->addTextBreak();
    }
}
