<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

require_once __DIR__ . '/../WordConfig.php';

require_once __DIR__ . '/ContentType.php';
require_once __DIR__ . '/../Traits/DetectContentType.php';
require_once __DIR__ . '/../Traits/NormalizeContent.php';

require_once __DIR__ . '/BlockFactory.php';

require_once __DIR__ . '/CardText.php';
require_once __DIR__ . '/CardList.php';
require_once __DIR__ . '/CardHtml.php';
require_once __DIR__ . '/CardImage.php';
require_once __DIR__ . '/CardTable.php';

/*
|--------------------------------------------------------------------------
| SectionBlock — عنوان القسم الملوّن + محتواه
|
| التحسينات:
| - شريط عنوان بعرض الصفحة كاملاً + هامش داخلي مريح + محاذاة رأسية
| - شريط جانبي بلون القسم يفصل المحتوى بصرياً
| - تسميات الحقول بلون القسم + مسافات متوازنة (بدل الفراغات العشوائية)
|--------------------------------------------------------------------------
*/

class SectionBlock
{
    use DetectContentType;
    use NormalizeContent;

    public static function render(
        Section $section,
        string $title,
        array $rows,
        string $color = WordConfig::PRIMARY_COLOR
    ): void {

        if (empty($rows)) {
            return;
        }

        /*
        =====================================================
        شريط عنوان القسم (بعرض المحتوى كاملاً)
        =====================================================
        */

        $table = $section->addTable([
            'borderSize' => 0,
            'cellMargin' => WordConfig::CELL_MARGIN,
            'alignment'  => Jc::CENTER,
            'bidiVisual' => true,
        ]);

        $table->addRow();

        $table->addCell(
            WordConfig::CONTENT_WIDTH,
            [
                'bgColor' => $color,
                'valign'  => VerticalJc::CENTER,
            ]
        )->addText(
            $title,
            [
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::SUBTITLE_SIZE,
                'bold'  => true,
                'color' => 'FFFFFF',
                'rtl'   => true,
            ],
            [
                'alignment'   => Jc::RIGHT,
                'spaceBefore' => 40,
                'spaceAfter'  => 40,
                'bidi'        => true,
            ]
        );

        $section->addTextBreak(1);

        /*
        =====================================================
        محتوى القسم
        =====================================================
        */

        foreach ($rows as $label => $value) {

            $value = self::normalize($value);

            if ($value === null || $value === '') {
                continue;
            }

            // اسم الحقل (إن وُجد)
            if (trim((string)$label) !== '') {

                $section->addText(
                    (string)$label,
                    [
                        'name'  => WordConfig::FONT_NAME,
                        'size'  => 13,
                        'bold'  => true,
                        'color' => $color,
                        'rtl'   => true,
                    ],
                    [
                        'alignment'   => Jc::RIGHT,
                        'spaceBefore' => 80,
                        'spaceAfter'  => 40,
                        'bidi'        => true,
                    ]
                );
            }

            // المحتوى (نص / قائمة / جدول / HTML / صورة)
            BlockFactory::render($section, $value);

            $section->addTextBreak();
        }

        $section->addTextBreak();
    }
}
