<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/../WordConfig.php';

class CardList
{
    /**
     * قائمة نقطية من Array
     */
    public static function render(
        Section $section,
        array $items
    ): void {

        foreach ($items as $item) {

            if (is_array($item)) {
                $item = implode(' - ', $item);
            }

            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            $section->addListItem(

                $item,

                0,

                [

                    'name' => WordConfig::FONT_NAME,

                    'size' => WordConfig::FONT_SIZE

                ],

                [

                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,

                    'bidi' => true

                ]

            );

        }

    }

    /**
     * قائمة من نص متعدد الأسطر
     */
    public static function fromMultiline(
        Section $section,
        string $text
    ): void {

        $lines = preg_split(

            "/\r\n|\n|\r/",

            trim($text)

        );

        if (!$lines) {
            return;
        }

        self::render(

            $section,

            $lines

        );

    }

    /**
     * قائمة مرقمة
     */
    public static function numbered(
        Section $section,
        array $items
    ): void {

        $index = 1;

        foreach ($items as $item) {

            $item = trim((string)$item);

            if ($item === '') {
                continue;
            }

            $section->addText(

                $index . '. ' . $item,

                [

                    'name' => WordConfig::FONT_NAME,

                    'size' => WordConfig::FONT_SIZE

                ],

                [

                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,

                    'bidi' => true

                ]

            );

            $index++;

        }

    }

    /**
     * اكتشاف النوع تلقائياً
     */
    public static function auto(
        Section $section,
        mixed $value
    ): void {

        if (is_array($value)) {

            self::render(

                $section,

                $value

            );

            return;

        }

        if (!is_string($value)) {
            return;
        }

        if (str_contains($value, "\n")) {

            self::fromMultiline(

                $section,

                $value

            );

            return;

        }

        self::render(

            $section,

            [

                $value

            ]

        );

    }

    /**
     * عنصر واحد
     */
    public static function add(
        Section $section,
        string $item
    ): void {

        self::render(

            $section,

            [

                $item

            ]

        );

    }
}