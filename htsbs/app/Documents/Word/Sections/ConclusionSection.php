<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class ConclusionSection
{
    /**
     * الخاتمة وغلق الدرس
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

        $conclusion =

            $lesson['conclusion']

            ??

            $lesson['lesson_closure']

            ??

            $lesson['closure']

            ??

            $lesson['closing']

            ??

            $lesson['lesson_summary']

            ??

            null;

        if (empty($conclusion)) {
            return;
        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        if (is_string($conclusion)) {

            $conclusion = trim($conclusion);

            if ($conclusion === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'الخاتمة وغلق الدرس',

                [

                    '' => $conclusion

                ],

                SectionTheme::blockColor('conclusion')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليست Array
        ------------------------------------------------------------
        */

        if (!is_array($conclusion)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($conclusion as $item) {

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
                ملخص + ملاحظة
                ----------------------------------------------------
                */

                if (

                    isset($item['summary'])

                ) {

                    $text =

                        "الملخص: "

                        .

                        $item['summary'];

                    if (

                        !empty(

                            $item['note']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            "ملاحظة: "

                            .

                            $item['note'];

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
            String
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

            'الخاتمة وغلق الدرس',

            [

                '' => $items

            ],

            SectionTheme::blockColor('conclusion')

        );

    }

}