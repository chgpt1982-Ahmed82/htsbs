<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

/*
|--------------------------------------------------------------------------
| WordHeader — ترويسة المستند (تظهر في أعلى كل صفحة)
|
| التغييرات عن النسخة السابقة:
| 1. أُزيل جدول معلومات الدرس من هنا (كان يتكرر مرتين لأن
|    WordLessonBuilder يستدعي InfoTable::render أيضاً)
|    → صار جدول المعلومات في InfoTable فقط، مصدر واحد.
| 2. الشعارات تُحلّ تلقائياً من عدة مسارات محتملة
|    (كانت لا تظهر أبداً بسبب ضبطها إلى سلسلة فارغة في export_word.php)
| 3. خط ذهبي فاصل + محاذاة RTL صحيحة + عنوان بانر للمستند
|--------------------------------------------------------------------------
*/

class WordHeader
{
    public static function build(Section $section, array $lesson): void
    {
        $header = $section->addHeader();

        /*
        ==================================================
        جدول الترويسة: [شعار] [نصوص المؤسسة] [شعار]
        بلا حدود — مجرد شبكة لضبط المحاذاة
        ==================================================
        */

        $table = $header->addTable([
            'width'      => 100 * 50,
            'unit'       => 'pct',
            'borderSize' => 0,
            'cellMargin' => 40,
            'alignment'  => Jc::CENTER,
        ]);

        $table->addRow();

        /* ---------- شعار الوزارة (يمين في RTL) ---------- */
        $cell = $table->addCell(1600, ['valign' => VerticalJc::CENTER]);
        $ministryLogo = self::resolveLogo($lesson['ministry_logo'] ?? '', 'moe.png');

        if ($ministryLogo !== '') {
            $cell->addImage($ministryLogo, [
                'width'     => 58,
                'height'    => 58,
                'alignment' => Jc::CENTER,
            ]);
        }

        /* ---------- نصوص المؤسسة (الوسط) ---------- */
        $cell = $table->addCell(6200, ['valign' => VerticalJc::CENTER]);

        $orgLines = [
            'مملكة البحرين',
            'وزارة التربية والتعليم',
            (string)($lesson['school_name'] ?? ''),
        ];

        foreach ($orgLines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }

            $cell->addText(
                $line,
                [
                    'name'  => WordConfig::FONT_NAME,
                    'size'  => $i === 2 ? 12 : 13,
                    'bold'  => $i < 2,
                    'color' => $i === 2 ? WordConfig::MUTED_COLOR : WordConfig::PRIMARY_COLOR,
                    'rtl'   => true,
                ],
                [
                    'alignment'  => Jc::CENTER,
                    'spaceAfter' => 0,
                    'bidi'       => true,
                ]
            );
        }

        /* ---------- شعار المدرسة (يسار في RTL) ---------- */
        $cell = $table->addCell(1600, ['valign' => VerticalJc::CENTER]);
        $schoolLogo = self::resolveLogo($lesson['school_logo'] ?? '', 'school.png');

        if ($schoolLogo !== '') {
            $cell->addImage($schoolLogo, [
                'width'     => 58,
                'height'    => 58,
                'alignment' => Jc::CENTER,
            ]);
        }

        /*
        ==================================================
        خط ذهبي فاصل أسفل الترويسة
        ==================================================
        */

        $header->addLine([
            'weight' => 2,
            'width'  => 470,
            'height' => 0,
            'color'  => WordConfig::ACCENT_COLOR,
        ]);

        /*
        ==================================================
        بانر عنوان المستند (في متن الصفحة الأولى)
        ==================================================
        */

        $banner = $section->addTable([
            'borderSize'  => 0,
            'cellMargin'  => WordConfig::CELL_MARGIN,
            'alignment'   => Jc::CENTER,
            'bidiVisual'  => true,
        ]);

        $banner->addRow();

        $banner->addCell(
            WordConfig::CONTENT_WIDTH,
            [
                'bgColor' => WordConfig::PRIMARY_COLOR,
                'valign'  => VerticalJc::CENTER,
            ]
        )->addText(
            (string)($lesson['document_title'] ?? 'تحضير درس'),
            [
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::TITLE_SIZE,
                'bold'  => true,
                'color' => 'FFFFFF',
                'rtl'   => true,
            ],
            [
                'alignment'   => Jc::CENTER,
                'spaceBefore' => 60,
                'spaceAfter'  => 60,
                'bidi'        => true,
            ]
        );

        $section->addTextBreak(1);
    }

    /*
    ==================================================
    تحديد مسار الشعار: يجرب المسار المُمرَّر أولاً،
    ثم مجلد assets/images في جذر المشروع
    ==================================================
    */
    private static function resolveLogo(string $given, string $defaultFile): string
    {
        // 1) المسار المُمرَّر (إن كان موجوداً فعلاً على القرص)
        if ($given !== '' && is_file($given)) {
            return $given;
        }

        // 2) مسارات احتياطية داخل المشروع
        $root = dirname(__DIR__, 3); // .../public_html

        $candidates = [
            $root . '/assets/images/' . $defaultFile,
            $root . '/assets/images/moe.png',
            $root . '/public/assets/images/' . $defaultFile,
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return ''; // لا شعار — تُترك الخلية فارغة بلا خطأ
    }
}
