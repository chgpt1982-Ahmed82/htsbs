<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';

class CardTable
{
    /**
     * رسم جدول
     *
     * أول صف يعتبر Header
     */
    public static function render(
        Cell $cell,
        array $rows
    ): void {

        if (empty($rows)) {
            return;
        }

        
        
        $table = $cell->addTable([

    'borderSize'  => 6,

    'borderColor' => 'D9D9D9',

    'cellMargin'  => 100

]);

        $header = true;

        foreach ($rows as $row) {

            if (!is_array($row)) {
                continue;
            }

            $table->addRow();

            foreach ($row as $column) {

                $style = [];

                if ($header) {

                    $style['bgColor'] =
                        WordConfig::PRIMARY_COLOR;

                }

                $tableCell = $table->addCell(
                    2200,
                    $style
                );

                $tableCell->addText(

                    (string)$column,

                    [

                        'bold'  => $header,

                        'name'  => WordConfig::FONT_NAME,

                        'size'  => WordConfig::FONT_SIZE,

                        'color' => $header
                            ? 'FFFFFF'
                            : '222222'

                    ],

                    [

                        'alignment' => Jc::CENTER

                    ]

                );

            }

            $header = false;

        }

    }

    /**
     * إنشاء جدول من Key => Value
     */
    public static function fromAssociative(
        Cell $cell,
        array $data
    ): void {

        $rows = [

            [

                'العنوان',

                'القيمة'

            ]

        ];

        foreach ($data as $key => $value) {

            if (is_array($value)) {

                $value = implode(
                    ', ',
                    $value
                );

            }

            $rows[] = [

                $key,

                (string)$value

            ];

        }

        self::render(

            $cell,

            $rows

        );

    }

    /**
     * إنشاء جدول تلقائياً
     */
    public static function auto(
        Cell $cell,
        mixed $value
    ): void {

        if (!is_array($value)) {

            return;

        }

        /*
        ------------------------------------------
        مصفوفة ثنائية
        ------------------------------------------
        */

        if (

            isset($value[0])

            &&

            is_array($value[0])

        ) {

            self::render(

                $cell,

                $value

            );

            return;

        }

        /*
        ------------------------------------------
        Key => Value
        ------------------------------------------
        */

        self::fromAssociative(

            $cell,

            $value

        );

    }

}