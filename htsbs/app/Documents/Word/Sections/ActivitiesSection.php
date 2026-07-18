<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class ActivitiesSection
{
    /**
     * الأنشطة التعليمية
     */
    public static function build(
        Section $section,
        array $lesson
    ): void {

        /*
        ------------------------------------------------------------
        البحث عن الأنشطة
        ------------------------------------------------------------
        */

        $activities =

            $lesson['activities']

            ??

            $lesson['lesson_activities']

            ??

            null;

        if (empty($activities)) {
            return;
        }

        /*
        ------------------------------------------------------------
        إذا كانت String
        ------------------------------------------------------------
        */

        if (is_string($activities)) {

            $activities = trim($activities);

            if ($activities === '') {
                return;
            }

            SectionBlock::render(

                $section,

                'الأنشطة التعليمية',

                [

                    '' => $activities

                ],

                SectionTheme::blockColor('activities')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        إذا لم تكن Array
        ------------------------------------------------------------
        */

        if (!is_array($activities)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تجهيز البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($activities as $activity) {

            /*
            ----------------------------------------
            نشاط عبارة عن مصفوفة
            ----------------------------------------
            */

            if (is_array($activity)) {

                /*
                جدول
                */

                if (

                    isset($activity['headers'])

                    &&

                    isset($activity['rows'])

                ) {

                    $items[] = [

                        'headers' =>

                            $activity['headers'],

                        'rows' =>

                            $activity['rows']

                    ];

                    continue;

                }

                /*
                صورة
                */

                if (

                    isset($activity['image'])

                ) {

                    $items[] = [

                        'image' =>

                            $activity['image'],

                        'caption' =>

                            $activity['caption']

                            ??

                            ''

                    ];

                    continue;

                }

                /*
                عنوان + وصف
                */

                if (

                    isset($activity['title'])

                ) {

                    $text =

                        $activity['title'];

                    if (

                        !empty(

                            $activity['description']

                        )

                    ) {

                        $text .=

                            "\n"

                            .

                            $activity['description'];

                    }

                    $items[] = $text;

                    continue;

                }

                /*
                مصفوفة عادية
                */

                $items[] = implode(

                    ' - ',

                    $activity

                );

                continue;

            }

            /*
            ----------------------------------------
            نص عادي
            ----------------------------------------
            */

            $activity = trim((string)$activity);

            if ($activity === '') {
                continue;
            }

            $items[] = $activity;

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

            'الأنشطة التعليمية',

            [

                '' => $items

            ],

            SectionTheme::blockColor('activities')

        );

    }
}