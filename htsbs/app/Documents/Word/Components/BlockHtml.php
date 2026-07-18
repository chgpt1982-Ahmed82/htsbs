<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/BlockText.php';
require_once __DIR__ . '/BlockList.php';
require_once __DIR__ . '/BlockTable.php';
require_once __DIR__ . '/BlockImage.php';

class BlockHtml
{
    /**
     * Render HTML
     */
    public static function render(
        Section $section,
        string $html
    ): void {

        $html = trim($html);

        if ($html === '') {
            return;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $html = mb_convert_encoding(
            $html,
            'HTML-ENTITIES',
            'UTF-8'
        );

        $dom->loadHTML(
            '<meta charset="utf-8">' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        foreach ($dom->childNodes as $node) {

            self::parseNode(
                $section,
                $node
            );

        }

    }

    /**
     * Parse Node
     */
    private static function parseNode(
        Section $section,
        DOMNode $node
    ): void {

        switch (strtolower($node->nodeName)) {

            /*
            ---------------------------------------------------------
            Paragraph
            ---------------------------------------------------------
            */

            case 'p':

                BlockText::render(

                    $section,

                    trim($node->textContent)

                );

                break;

            /*
            ---------------------------------------------------------
            Headings
            ---------------------------------------------------------
            */

            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':

                BlockText::heading(

                    $section,

                    trim($node->textContent)

                );

                break;

            /*
            ---------------------------------------------------------
            Bold
            ---------------------------------------------------------
            */

            case 'strong':
            case 'b':

                BlockText::bold(

                    $section,

                    trim($node->textContent)

                );

                break;

            /*
            ---------------------------------------------------------
            UL
            ---------------------------------------------------------
            */

            case 'ul':

                $items = [];

                foreach ($node->childNodes as $li) {

                    if (

                        strtolower($li->nodeName)

                        ===

                        'li'

                    ) {

                        $items[] = trim(

                            $li->textContent

                        );

                    }

                }

                BlockList::render(

                    $section,

                    $items

                );

                break;

            /*
            ---------------------------------------------------------
            OL
            ---------------------------------------------------------
            */

            case 'ol':

                $items = [];

                foreach ($node->childNodes as $li) {

                    if (

                        strtolower($li->nodeName)

                        ===

                        'li'

                    ) {

                        $items[] = trim(

                            $li->textContent

                        );

                    }

                }

                BlockList::numbered(

                    $section,

                    $items

                );

                break;

            /*
            ---------------------------------------------------------
            IMG
            ---------------------------------------------------------
            */

            case 'img':

                $src = '';

                if (

                    $node->attributes

                    &&

                    $node->attributes->getNamedItem('src')

                ) {

                    $src =

                        $node

                        ->attributes

                        ->getNamedItem('src')

                        ->nodeValue;

                }

                if (

                    $src !== ''

                ) {

                    BlockImage::render(

                        $section,

                        $src

                    );

                }

                break;

            /*
            ---------------------------------------------------------
            BR
            ---------------------------------------------------------
            */

            case 'br':

                BlockText::spacer(

                    $section

                );

                break;

            /*
            ---------------------------------------------------------
            Table
            ---------------------------------------------------------
            */

            case 'table':

                self::parseTable(

                    $section,

                    $node

                );

                break;

            /*
            ---------------------------------------------------------
            Default
            ---------------------------------------------------------
            */

            default:

                if (

                    $node->hasChildNodes()

                ) {

                    foreach (

                        $node->childNodes

                        as

                        $child

                    ) {

                        self::parseNode(

                            $section,

                            $child

                        );

                    }

                }

        }

    }

    /**
     * Parse HTML Table
     */
    private static function parseTable(
        Section $section,
        DOMNode $table
    ): void {

        $headers = [];

        $rows = [];

        foreach (

            $table->childNodes

            as

            $tr

        ) {

            if (

                strtolower($tr->nodeName)

                !==

                'tr'

            ) {

                continue;

            }

            $row = [];

            $hasHeader = false;

            foreach (

                $tr->childNodes

                as

                $cell

            ) {

                $name = strtolower(

                    $cell->nodeName

                );

                if (

                    $name !== 'th'

                    &&

                    $name !== 'td'

                ) {

                    continue;

                }

                if (

                    $name === 'th'

                ) {

                    $hasHeader = true;

                }

                $row[] = trim(

                    $cell->textContent

                );

            }

            if (

                $hasHeader

            ) {

                $headers = $row;

            } else {

                $rows[] = $row;

            }

        }

        if (

            empty($headers)

        ) {

            if (

                !empty($rows)

            ) {

                $headers = array_shift(

                    $rows

                );

            }

        }

        if (

            empty($headers)

        ) {

            return;

        }

        BlockTable::render(

            $section,

            $headers,

            $rows

        );

    }

}