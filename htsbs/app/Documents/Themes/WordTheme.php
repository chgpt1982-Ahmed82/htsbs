<?php

declare(strict_types=1);

/**
 * Word Theme
 *
 * مسؤول عن هوية مستندات Word
 */
final class WordTheme
{
    /*
    |--------------------------------------------------------------------------
    | Main Colors
    |--------------------------------------------------------------------------
    */

    public const PRIMARY = '2F5597';

    public const SUCCESS = '008000';

    public const WARNING = 'C55A11';

    public const DANGER = 'C00000';

    public const PURPLE = '7030A0';

    public const INFO = '00A6D6';

    /*
    |--------------------------------------------------------------------------
    | Background
    |--------------------------------------------------------------------------
    */

    public const HEADER_BG = self::PRIMARY;

    public const CARD_BG = 'FFFFFF';

    public const LABEL_BG = 'F5F5F5';

    public const TABLE_HEADER_BG = self::PRIMARY;

    public const TABLE_ROW_BG = 'FFFFFF';

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    */

    public const TITLE_COLOR = 'FFFFFF';

    public const TEXT_COLOR = '222222';

    public const MUTED_COLOR = '666666';

    /*
    |--------------------------------------------------------------------------
    | Borders
    |--------------------------------------------------------------------------
    */

    public const BORDER = 'BFBFBF';

    public const BORDER_SIZE = 8;

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    public const TABLE_BORDER = 'D9D9D9';

    public const TABLE_BORDER_SIZE = 6;

    /*
    |--------------------------------------------------------------------------
    | Header Colors
    |--------------------------------------------------------------------------
    */

    public static function headerColor(
        string $color
    ): string {

        return match (strtoupper($color)) {

            self::SUCCESS => self::SUCCESS,

            self::WARNING => self::WARNING,

            self::DANGER => self::DANGER,

            self::PURPLE => self::PURPLE,

            self::INFO => self::INFO,

            default => self::PRIMARY

        };

    }

    /*
    |--------------------------------------------------------------------------
    | Card Style
    |--------------------------------------------------------------------------
    */

    public static function cardStyle(
        string $color = self::PRIMARY
    ): array {

        return [

            'borderSize' => self::BORDER_SIZE,

            'borderColor' => $color,

            'cellMargin' => 150,

            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Header Cell
    |--------------------------------------------------------------------------
    */

    public static function headerCell(
        string $color = self::PRIMARY
    ): array {

        return [

            'gridSpan' => 2,

            'bgColor' => self::headerColor($color)

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Label Cell
    |--------------------------------------------------------------------------
    */

    public static function labelCell(): array
    {

        return [

            'bgColor' => self::LABEL_BG

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Table Style
    |--------------------------------------------------------------------------
    */

    public static function tableStyle(): array
    {

        return [

            'borderSize' => self::TABLE_BORDER_SIZE,

            'borderColor' => self::TABLE_BORDER,

            'cellMargin' => 100

        ];

    }

}