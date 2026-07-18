<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class AssessmentSection
{
    /**
     * التقويم
     */
    public static function build(
        Section $section,
        array $lesson
    ): void {

        /*
        ------------------------------------------------------------
        أسماء الحقول المحتملة
        ------------------------------------------------------------
        */

        $assessment =

            $lesson['assessment']

            ??

            $lesson['assessment_questions']

            ??

            $lesson['evaluation']

            ??

            $lesson['evaluation_questions']

            ??

            $lesson['formative_assessment']

            ??

            null;

        if (empty($assessment)) {
            return;
        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        if (is_string($assessment)) {

            $assessment = trim($assessment);

            if ($assessment === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'التقويم',

                [

                    '' => $assessment

                ],

                SectionTheme::blockColor('assessment')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليس Array
        ------------------------------------------------------------
        */

        if (!is_array($assessment)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($assessment as $item) {

            /*
            --------------------------------------------------------
            إذا كان العنصر Array
            --------------------------------------------------------
            */

            if (is_array($item)) {

                /*
                جدول
                */

                if (

                    isset($item['headers'])

                    &&

                    isset($item['rows'])

                ) {

                    $items[] = [

                        'headers' =>

                            $item['headers'],

                        'rows' =>

                            $item['rows']

                    ];

                    continue;

                }

                /*
                صورة
                */

                if (

                    isset($item['image'])

                ) {

                    $items[] = [

                        'image' =>

                            $item['image'],

                        'caption' =>

                            $item['caption']

                            ??

                            ''

                    ];

                    continue;

                }

                /*
                سؤال + إجابة
                */

                if (

                    isset($item['question'])

                ) {

                    $text =

                        'السؤال: '

                        .

                        $item['question'];

                    if (

                        !empty(

                            $item['answer']

                        )

                    ) {

                        $text .=

                            "\n\nالإجابة المتوقعة: "

                            .

                            $item['answer'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                عنوان + وصف
                */

                if (

                    isset($item['title'])

                ) {

                    $text =

                        $item['title'];

                    if (

                        !empty(

                            $item['description']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            $item['description'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                Array عادي
                */

                $items[] = implode(

                    ' - ',

                    $item

                );

                continue;

            }

            /*
            --------------------------------------------------------
            نص
            --------------------------------------------------------
            */

            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            $items[] = $item;

        }

        if (empty($items)) {
            return;
        }

        /*
        ------------------------------------------------------------
        عرض القسم
        ------------------------------------------------------------
        */

        SectionBlock::render(

            $section,

            'التقويم',

            [

                '' => $items

            ],

            SectionTheme::blockColor('assessment')

        );

    }
}