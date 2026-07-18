<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class ValuesSection
{
    /**
     * القيم والاتجاهات
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

        $values =

            $lesson['values']

            ??

            $lesson['educational_values']

            ??

            $lesson['moral_values']

            ??

            $lesson['citizenship_values']

            ??

            $lesson['behavior_values']

            ??

            null;

        if (empty($values)) {
            return;
        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        if (is_string($values)) {

            $values = trim($values);

            if ($values === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'القيم والاتجاهات',

                [

                    '' => $values

                ],

                SectionTheme::blockColor('values')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليست Array
        ------------------------------------------------------------
        */

        if (!is_array($values)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($values as $value) {

            if (is_array($value)) {

                /*
                ----------------------------------------------------
                جدول
                ----------------------------------------------------
                */

                if (

                    isset($value['headers'])

                    &&

                    isset($value['rows'])

                ) {

                    $items[] = [

                        'headers' =>

                            $value['headers'],

                        'rows' =>

                            $value['rows']

                    ];

                    continue;

                }

                /*
                ----------------------------------------------------
                صورة
                ----------------------------------------------------
                */

                if (

                    isset($value['image'])

                ) {

                    $items[] = [

                        'image' =>

                            $value['image'],

                        'caption' =>

                            $value['caption']

                            ??

                            ''

                    ];

                    continue;

                }

                /*
                ----------------------------------------------------
                قيمة + شرح
                ----------------------------------------------------
                */

                if (

                    isset($value['title'])

                ) {

                    $text =

                        $value['title'];

                    if (

                        !empty(

                            $value['description']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            $value['description'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                ----------------------------------------------------
                اسم + تطبيق
                ----------------------------------------------------
                */

                if (

                    isset($value['name'])

                ) {

                    $text =

                        $value['name'];

                    if (

                        !empty(

                            $value['application']

                        )

                    ) {

                        $text .=

                            "\nالتطبيق: "

                            .

                            $value['application'];

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

                    $value

                );

                continue;

            }

            /*
            --------------------------------------------------------
            String
            --------------------------------------------------------
            */

            $value = trim((string)$value);

            if ($value === '') {
                continue;
            }

            $items[] = $value;

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

            'القيم والاتجاهات',

            [

                '' => $items

            ],

            SectionTheme::blockColor('values')

        );

    }

}