<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/BlockText.php';
require_once __DIR__ . '/BlockList.php';
require_once __DIR__ . '/BlockHtml.php';
require_once __DIR__ . '/BlockImage.php';
require_once __DIR__ . '/BlockTable.php';
require_once __DIR__ . '/ContentType.php';

final class ContentRenderer
{
    /**
     * Render Content
     */
    public static function render(
        Section $section,
        string $type,
        mixed $value
    ): void {

        switch ($type) {

            /*
            ---------------------------------------------------------
            Text
            ---------------------------------------------------------
            */

            case ContentType::TEXT:

                BlockText::render(

                    $section,

                    (string)$value

                );

                break;

            /*
            ---------------------------------------------------------
            Multi Line
            ---------------------------------------------------------
            */

            case ContentType::MULTILINE:

                BlockList::fromMultiline(

                    $section,

                    (string)$value

                );

                break;

            /*
            ---------------------------------------------------------
            List
            ---------------------------------------------------------
            */

            case ContentType::LIST:

                BlockList::render(

                    $section,

                    (array)$value

                );

                break;

            /*
            ---------------------------------------------------------
            HTML
            ---------------------------------------------------------
            */

            case ContentType::HTML:

                BlockHtml::render(

                    $section,

                    (string)$value

                );

                break;

            /*
            ---------------------------------------------------------
            Image
            ---------------------------------------------------------
            */

            case ContentType::IMAGE:

                BlockImage::render(

                    $section,

                    (string)$value

                );

                break;

            /*
            ---------------------------------------------------------
            Table
            ---------------------------------------------------------
            */

            case ContentType::TABLE:

                if (

                    is_array($value)

                    &&

                    isset($value['headers'])

                    &&

                    isset($value['rows'])

                ) {

                    BlockTable::render(

                        $section,

                        $value['headers'],

                        $value['rows']

                    );

                }

                break;

            /*
            ---------------------------------------------------------
            Unknown
            ---------------------------------------------------------
            */

            default:

                BlockText::render(

                    $section,

                    (string)$value

                );

        }

    }
}