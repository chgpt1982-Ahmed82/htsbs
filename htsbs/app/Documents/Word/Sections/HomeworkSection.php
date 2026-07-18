<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class HomeworkSection
{
    /**
     * الواجب المنزلي
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

        $homework =

            $lesson['homework']

            ??

            $lesson['assignment']

            ??

            $lesson['assignments']

            ??

            $lesson['home_assignment']

            ??

            $lesson['student_homework']

            ??

            null;

        if (empty($homework)) {
            return;
        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        if (is_string($homework)) {

            $homework = trim($homework);

            if ($homework === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'الواجب المنزلي',

                [

                    '' => $homework

                ],

                SectionTheme::blockColor('homework')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليس Array
        ------------------------------------------------------------
        */

        if (!is_array($homework)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($homework as $task) {

            /*
            --------------------------------------------------------
            Array
            --------------------------------------------------------
            */

            if (is_array($task)) {

                /*
                جدول
                */

                if (

                    isset($task['headers'])

                    &&

                    isset($task['rows'])

                ) {

                    $items[] = [

                        'headers' =>

                            $task['headers'],

                        'rows' =>

                            $task['rows']

                    ];

                    continue;

                }

                /*
                صورة
                */

                if (

                    isset($task['image'])

                ) {

                    $items[] = [

                        'image' =>

                            $task['image'],

                        'caption' =>

                            $task['caption']

                            ??

                            ''

                    ];

                    continue;

                }

                /*
                عنوان + وصف
                */

                if (

                    isset($task['title'])

                ) {

                    $text =

                        $task['title'];

                    if (

                        !empty(

                            $task['description']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            $task['description'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                السؤال + الدرجة
                */

                if (

                    isset($task['question'])

                ) {

                    $text =

                        'السؤال: '

                        .

                        $task['question'];

                    if (

                        !empty(

                            $task['marks']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            'الدرجة: '

                            .

                            $task['marks'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                Array عادي
                */

                $items[] = implode(

                    ' - ',

                    $task

                );

                continue;

            }

            /*
            --------------------------------------------------------
            نص
            --------------------------------------------------------
            */

            $task = trim((string)$task);

            if ($task === '') {
                continue;
            }

            $items[] = $task;

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

            'الواجب المنزلي',

            [

                '' => $items

            ],

            SectionTheme::blockColor('homework')

        );

    }

}