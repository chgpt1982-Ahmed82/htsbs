<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';
require_once __DIR__ . '/CardText.php';
require_once __DIR__ . '/CardList.php';

class CardHtml
{
    /**
     * تحويل HTML إلى محتوى Word
     */
    public static function render(
        Cell $cell,
        string $html
    ): void {

        if (trim($html) === '') {
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
            '<meta charset="utf-8">'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        foreach ($dom->childNodes as $node) {

            self::parseNode(
                $cell,
                $node
            );

        }

    }

    /**
     * تحليل العناصر
     */
    private static function parseNode(
        Cell $cell,
        DOMNode $node
    ): void {

        switch ($node->nodeName) {

            case 'p':

                CardText::render(
                    $cell,
                    trim($node->textContent)
                );

                break;

            case 'strong':

            case 'b':

                CardText::bold(
                    $cell,
                    trim($node->textContent)
                );

                break;

            case 'em':

            case 'i':

                $cell->addText(

                    trim($node->textContent),

                    [

                        'italic' => true,

                        'name' => WordConfig::FONT_NAME,

                        'size' => WordConfig::FONT_SIZE

                    ],

                    [

                        'alignment' => Jc::RIGHT

                    ]

                );

                break;

            case 'br':

                $cell->addTextBreak();

                break;

            case 'ul':

                self::unorderedList(
                    $cell,
                    $node
                );

                break;

            case 'ol':

                self::orderedList(
                    $cell,
                    $node
                );

                break;

            case 'h1':

            case 'h2':

            case 'h3':

            case 'h4':

            case 'h5':

            case 'h6':

                CardText::heading(
                    $cell,
                    trim($node->textContent)
                );

                break;

            default:

                if ($node->hasChildNodes()) {

                    foreach ($node->childNodes as $child) {

                        self::parseNode(
                            $cell,
                            $child
                        );

                    }

                }

        }

    }

    /**
     * UL
     */
    private static function unorderedList(
        Cell $cell,
        DOMNode $node
    ): void {

        foreach ($node->childNodes as $li) {

            if ($li->nodeName !== 'li') {
                continue;
            }

            CardList::add(

                $cell,

                trim($li->textContent)

            );

        }

    }

    /**
     * OL
     */
    private static function orderedList(
        Cell $cell,
        DOMNode $node
    ): void {

        $items = [];

        foreach ($node->childNodes as $li) {

            if ($li->nodeName !== 'li') {
                continue;
            }

            $items[] = trim(
                $li->textContent
            );

        }

        CardList::numbered(

            $cell,

            $items

        );

    }

}