<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../Components/SectionBlock.php';
require_once __DIR__ . '/../SectionTheme.php';

class WarmupSection
{
    /**
     * التمهيد
     */
    public static function build(
        Section $section,
        array $lesson
    ): void {

        if (empty($lesson['warmup'])) {
            return;
        }

        $warmup = $lesson['warmup'];

        /*
        ------------------------------------------------------------
        إذا كانت البيانات Array
        ------------------------------------------------------------
        */

        if (is_array($warmup)) {

            $items = [];

            foreach ($warmup as $item) {

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

                'التمهيد',

                [

                    '' => $items

                ],

                SectionTheme::blockColor('warmup')

            );

            return;

        }

        /*
        ------------------------------------------------------------
        إذا كانت String
        ------------------------------------------------------------
        */

        $warmup = trim((string)$warmup);

        if ($warmup === '') {
            return;
        }

        SectionBlock::render(

            $section,

            'التمهيد',

            [

                '' => $warmup

            ],

            SectionTheme::blockColor('warmup')

        );

    }
}