<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class SkillsSection
{
    /**
     * المهارات المستهدفة
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

        $skills =

            $lesson['skills']

            ??

            $lesson['target_skills']

            ??

            $lesson['learning_skills']

            ??

            $lesson['life_skills']

            ??

            $lesson['core_skills']

            ??

            null;

        if (empty($skills)) {
            return;
        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        if (is_string($skills)) {

            $skills = trim($skills);

            if ($skills === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'المهارات المستهدفة',

                [

                    '' => $skills

                ],

                SectionTheme::blockColor('skills')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        ليست Array
        ------------------------------------------------------------
        */

        if (!is_array($skills)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($skills as $skill) {

            if (is_array($skill)) {

                /*
                جدول
                */

                if (

                    isset($skill['headers'])

                    &&

                    isset($skill['rows'])

                ) {

                    $items[] = [

                        'headers' => $skill['headers'],

                        'rows'    => $skill['rows']

                    ];

                    continue;

                }

                /*
                صورة
                */

                if (

                    isset($skill['image'])

                ) {

                    $items[] = [

                        'image'   => $skill['image'],

                        'caption' =>

                            $skill['caption']

                            ??

                            ''

                    ];

                    continue;

                }

                /*
                مهارة + وصف
                */

                if (

                    isset($skill['title'])

                ) {

                    $text = $skill['title'];

                    if (

                        !empty(

                            $skill['description']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            $skill['description'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                اسم + مستوى
                */

                if (

                    isset($skill['name'])

                ) {

                    $text =

                        $skill['name'];

                    if (

                        !empty(

                            $skill['level']

                        )

                    ) {

                        $text .=

                            ' ('

                            .

                            $skill['level']

                            .

                            ')';

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                Array عادي
                */

                $items[] = implode(

                    ' - ',

                    $skill

                );

                continue;

            }

            /*
            --------------------------------------------------------
            نص
            --------------------------------------------------------
            */

            $skill = trim((string)$skill);

            if ($skill === '') {
                continue;
            }

            $items[] = $skill;

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

            'المهارات المستهدفة',

            [

                '' => $items

            ],

            SectionTheme::blockColor('skills')

        );

    }

}