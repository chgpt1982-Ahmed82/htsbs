<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';

class CardText
{
    /**
     * نص عادي
     */
    public static function render(
        Section $section,
        string $text
    ): void {

        $text = trim($text);

        if ($text === '') {
            return;
        }

        $section->addText(

            $text,

            [

                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::FONT_SIZE,
                'color' => '222222'

            ],

            [

                'alignment' => Jc::RIGHT,
                'bidi'      => true,
                'spaceAfter'=> 120

            ]

        );

    }

    /**
     * عنوان فرعي
     */
    public static function heading(
        Section $section,
        string $title
    ): void {

        $title = trim($title);

        if ($title === '') {
            return;
        }

        $section->addText(

            $title,

            [

                'bold'  => true,
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::SUBTITLE_SIZE,
                'color' => WordConfig::PRIMARY_COLOR

            ],

            [

                'alignment' => Jc::RIGHT,
                'bidi'      => true,
                'spaceAfter'=> 180

            ]

        );

    }

    /**
     * نص عريض
     */
    public static function bold(
        Section $section,
        string $text
    ): void {

        $text = trim($text);

        if ($text === '') {
            return;
        }

        $section->addText(

            $text,

            [

                'bold' => true,
                'name' => WordConfig::FONT_NAME,
                'size' => WordConfig::FONT_SIZE

            ],

            [

                'alignment' => Jc::RIGHT,
                'bidi'      => true

            ]

        );

    }

    /**
     * عنوان : قيمة
     */
    public static function keyValue(
        Section $section,
        string $key,
        string $value
    ): void {

        $run = $section->addTextRun([

            'alignment' => Jc::RIGHT,
            'bidi'      => true

        ]);

        $run->addText(

            $key . ' : ',

            [

                'bold'  => true,
                'name'  => WordConfig::FONT_NAME,
                'size'  => WordConfig::FONT_SIZE,
                'color' => WordConfig::PRIMARY_COLOR

            ]

        );

        $run->addText(

            trim($value),

            [

                'name' => WordConfig::FONT_NAME,
                'size' => WordConfig::FONT_SIZE

            ]

        );

    }

    /**
     * ملاحظة
     */
    public static function note(
        Section $section,
        string $text
    ): void {

        $text = trim($text);

        if ($text === '') {
            return;
        }

        $section->addText(

            $text,

            [

                'italic' => true,
                'name'   => WordConfig::FONT_NAME,
                'size'   => WordConfig::FONT_SIZE - 1,
                'color'  => '666666'

            ],

            [

                'alignment' => Jc::RIGHT,
                'bidi'      => true

            ]

        );

    }
}