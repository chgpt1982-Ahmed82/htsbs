<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class IntroductionSection
{
    /**
     * المقدمة
     */
    public static function build(
        Section $section,
        array $lesson
    ): void {

        if (empty($lesson['introduction'])) {
            return;
        }

        $introduction = $lesson['introduction'];

        /*
        ------------------------------------------------------------
        Array
        ------------------------------------------------------------
        */

        if (is_array($introduction)) {

            $items = [];

            foreach ($introduction as $item) {

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

            SectionBlock::render(

                $section,

                'المقدمة',

                [

                    '' => $items

                ],

                SectionTheme::PRIMARY

            );

            return;

        }

        /*
        ------------------------------------------------------------
        String
        ------------------------------------------------------------
        */

        $introduction = trim((string)$introduction);

        if ($introduction === '') {
            return;
        }

        SectionBlock::render(

            $section,

            'المقدمة',

            [

                '' => $introduction

            ],

            SectionTheme::PRIMARY

        );

    }
}