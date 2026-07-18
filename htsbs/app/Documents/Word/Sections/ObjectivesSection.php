<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class ObjectivesSection
{
    /**
     * الأهداف التعليمية
     */
    public static function build(
        Section $section,
        array $lesson
    ): void {

        if (
            empty($lesson['objectives'])
        ) {
            return;
        }

        $objectives = $lesson['objectives'];

        /*
        ------------------------------------------------------------
        إذا كانت String نحولها إلى Array
        ------------------------------------------------------------
        */

        if (is_string($objectives)) {

            $objectives = preg_split(
                "/\r\n|\n|\r/",
                trim($objectives)
            );

        }

        /*
        ------------------------------------------------------------
        إذا لم تكن Array نتوقف
        ------------------------------------------------------------
        */

        if (!is_array($objectives)) {
            return;
        }

        /*
        ------------------------------------------------------------
        تنظيف البيانات
        ------------------------------------------------------------
        */

        $items = [];

        foreach ($objectives as $item) {

            if (is_array($item)) {

                $item = implode(' - ', $item);

            }

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

            'الأهداف التعليمية',

            [

                '' => $items

            ],

            SectionTheme::blockColor('objectives')

        );

    }
}