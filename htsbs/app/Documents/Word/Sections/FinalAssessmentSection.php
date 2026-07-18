<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class FinalAssessmentSection
{
    /**
     * التقويم الختامي
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

            $lesson['final_assessment']

            ??

            $lesson['summative_assessment']

            ??

            $lesson['exit_ticket']

            ??

            $lesson['closing_assessment']

            ??

            $lesson['final_questions']

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

                'التقويم الختامي',

                [

                    '' => $assessment

                ],

                SectionTheme::blockColor('finalassessment')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليست Array
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

            if (is_array($item)) {

                /*
                ----------------------------------------------------
                جدول
                ----------------------------------------------------
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
                ----------------------------------------------------
                صورة
                ----------------------------------------------------
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
                ----------------------------------------------------
                سؤال + إجابة
                ----------------------------------------------------
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

                            "\n"

                            .

                            "الإجابة المتوقعة: "

                            .

                            $item['answer'];

                    }

                    if (

                        !empty(

                            $item['marks']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            "الدرجة: "

                            .

                            $item['marks'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                ----------------------------------------------------
                عنوان + وصف
                ----------------------------------------------------
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
                ----------------------------------------------------
                Array عادي
                ----------------------------------------------------
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

            'التقويم الختامي',

            [

                '' => $items

            ],

            SectionTheme::blockColor('finalassessment')

        );

    }

}