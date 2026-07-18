<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class ResourcesSection
{
    /**
     * الوسائل والموارد التعليمية
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

        $resources =

            $lesson['resources']

            ??

            $lesson['teaching_resources']

            ??

            $lesson['learning_resources']

            ??

            $lesson['instructional_materials']

            ??

            $lesson['materials']

            ??

            null;

        if (empty($resources)) {
            return;
        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        if (is_string($resources)) {

            $resources = trim($resources);

            if ($resources === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'الموارد والوسائل التعليمية',

                [

                    '' => $resources

                ],

                SectionTheme::blockColor('resources')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليس Array
        ------------------------------------------------------------
        */

        if (!is_array($resources)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($resources as $resource) {

            /*
            --------------------------------------------------------
            Array
            --------------------------------------------------------
            */

            if (is_array($resource)) {

                /*
                جدول
                */

                if (

                    isset($resource['headers'])

                    &&

                    isset($resource['rows'])

                ) {

                    $items[] = [

                        'headers' =>

                            $resource['headers'],

                        'rows' =>

                            $resource['rows']

                    ];

                    continue;

                }

                /*
                صورة
                */

                if (

                    isset($resource['image'])

                ) {

                    $items[] = [

                        'image' =>

                            $resource['image'],

                        'caption' =>

                            $resource['caption']

                            ??

                            ''

                    ];

                    continue;

                }

                /*
                اسم + وصف
                */

                if (

                    isset($resource['title'])

                ) {

                    $text =

                        $resource['title'];

                    if (

                        !empty(

                            $resource['description']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            $resource['description'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                نوع + اسم
                */

                if (

                    isset($resource['type'])

                ) {

                    $text =

                        $resource['type'];

                    if (

                        !empty(

                            $resource['name']

                        )

                    ) {

                        $text .=

                            ' : '

                            .

                            $resource['name'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                Array عادي
                */

                $items[] = implode(

                    ' - ',

                    $resource

                );

                continue;

            }

            /*
            --------------------------------------------------------
            String
            --------------------------------------------------------
            */

            $resource = trim((string)$resource);

            if ($resource === '') {
                continue;
            }

            $items[] = $resource;

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

            'الموارد والوسائل التعليمية',

            [

                '' => $items

            ],

            SectionTheme::blockColor('resources')

        );

    }

}