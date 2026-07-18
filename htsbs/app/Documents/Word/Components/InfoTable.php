<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

require_once __DIR__ . '/../WordConfig.php';

/*
|--------------------------------------------------------------------------
| InfoTable — جدول بيانات الدرس (المصدر الوحيد لهذه البيانات في المستند)
|
| التصحيح الجوهري:
| النسخة السابقة كانت تقرأ مفاتيح غير موجودة في جدول lesson_plans
| (chapter_name, lesson_name, page_no, duration) فتخرج الخلايا فارغة.
| الحقول الفعلية هي:
|   subject_name | class_name | unit_name | lesson_title
|   lesson_duration | student_level | teacher_name | export_date
|--------------------------------------------------------------------------
*/

class InfoTable
{
    public static function render(Section $section, array $lesson): void
    {
        $table = $section->addTable([
            'borderSize'  => 6,
            'borderColor' => WordConfig::BORDER_COLOR,
            'cellMargin'  => WordConfig::CELL_MARGIN,
            'alignment'   => Jc::CENTER,
            'bidiVisual'  => true,   // ترتيب الأعمدة من اليمين لليسار
        ]);

        /*
        ==================================================
        صف عنوان الدرس (بعرض كامل + خلفية مميزة)
        ==================================================
        */

        $table->addRow();

        self::cell($table, 'عنوان الدرس', true, 2300);

        self::cell(
            $table,
            self::val($lesson, 'lesson_title'),
            false,
            7100,
            ['gridSpan' => 3],
            true // إبراز عنوان الدرس
        );

        /*
        ==================================================
        المادة | الصف
        ==================================================
        */

        $table->addRow();
        self::cell($table, 'المادة', true, 2300);
        self::cell($table, self::val($lesson, 'subject_name'), false, 2400);
        self::cell($table, 'الصف', true, 2300);
        self::cell($table, self::val($lesson, 'class_name'), false, 2400);

        /*
        ==================================================
        الوحدة | مدة الحصة
        ==================================================
        */

        $duration = trim((string)($lesson['lesson_duration'] ?? ''));
        $duration = $duration !== '' ? $duration . ' دقيقة' : '—';

        $table->addRow();
        self::cell($table, 'الوحدة', true, 2300, [], false, true);
        self::cell($table, self::val($lesson, 'unit_name'), false, 2400, [], false, true);
        self::cell($table, 'مدة الحصة', true, 2300, [], false, true);
        self::cell($table, $duration, false, 2400, [], false, true);

        /*
        ==================================================
        مستوى الطلبة | المعلم
        ==================================================
        */

        $table->addRow();
        self::cell($table, 'مستوى الطلبة', true, 2300);
        self::cell($table, self::val($lesson, 'student_level'), false, 2400);
        self::cell($table, 'المعلم', true, 2300);
        self::cell($table, self::val($lesson, 'teacher_name'), false, 2400);

        /*
        ==================================================
        التاريخ | الكلمات المفتاحية
        ==================================================
        */

        $date = trim((string)($lesson['export_date'] ?? date('Y-m-d')));

        $table->addRow();
        self::cell($table, 'التاريخ', true, 2300, [], false, true);
        self::cell($table, $date, false, 2400, [], false, true);
        self::cell($table, 'الكلمات المفتاحية', true, 2300, [], false, true);
        self::cell($table, self::val($lesson, 'keywords'), false, 2400, [], false, true);

        $section->addTextBreak(1);
    }

    /*
    ==================================================
    استخراج قيمة نصية آمنة (تدعم المصفوفات القادمة من JSON)
    ==================================================
    */
    private static function val(array $lesson, string $key): string
    {
        $value = $lesson[$key] ?? '';

        if (is_array($value)) {
            $value = implode('، ', array_filter(array_map('strval', $value)));
        }

        $value = trim((string)$value);

        return $value !== '' ? $value : '—';
    }

    /*
    ==================================================
    إنشاء خلية منسّقة
    $isLabel  : خلية تسمية (خلفية زرقاء فاتحة + خط عريض)
    $highlight: إبراز (عنوان الدرس)
    $zebra    : تظليل خفيف للصفوف المتبادلة
    ==================================================
    */
    private static function cell(
        $table,
        string $text,
        bool $isLabel,
        int $width,
        array $extraStyle = [],
        bool $highlight = false,
        bool $zebra = false
    ): void {

        $bg = WordConfig::VALUE_BG;

        if ($isLabel) {
            $bg = WordConfig::LABEL_BG;
        } elseif ($zebra) {
            $bg = WordConfig::ZEBRA_BG;
        }

        $cellStyle = array_merge(
            [
                'valign'  => VerticalJc::CENTER,
                'bgColor' => $bg,
            ],
            $extraStyle
        );

        $cell = $table->addCell($width, $cellStyle);

        $run = $cell->addTextRun([
            'alignment'  => $isLabel ? Jc::CENTER : Jc::RIGHT,
            'spaceAfter' => 0,
            'bidi'       => true,
        ]);

        $run->addText(
            $text,
            [
                'name'  => WordConfig::FONT_NAME,
                'size'  => $highlight ? 15 : WordConfig::FONT_SIZE,
                'bold'  => $isLabel || $highlight,
                'color' => $isLabel
                    ? WordConfig::PRIMARY_COLOR
                    : WordConfig::TEXT_COLOR,
                'rtl'   => true,
            ]
        );
    }
}
