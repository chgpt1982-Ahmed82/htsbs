<?php

declare(strict_types=1);

use PhpOffice\PhpWord\SimpleType\Jc;

abstract class DefaultTheme
{
    /*
    |--------------------------------------------------------------------------
    | Theme Information
    |--------------------------------------------------------------------------
    */

    public const NAME = 'Default';

    public const VERSION = '1.0';

    /*
    |--------------------------------------------------------------------------
    | Fonts
    |--------------------------------------------------------------------------
    */

    public const FONT_NAME = 'Cairo';

    public const FONT_SIZE = 12;

    public const TITLE_SIZE = 20;

    public const SUBTITLE_SIZE = 15;

    public const SMALL_SIZE = 10;

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

    public const INFO = '0099CC';

    /*
    |--------------------------------------------------------------------------
    | Background
    |--------------------------------------------------------------------------
    */

    public const PAGE_BACKGROUND = 'FFFFFF';

    public const CARD_BACKGROUND = 'FFFFFF';

    public const HEADER_BACKGROUND = self::PRIMARY;

    public const LABEL_BACKGROUND = 'F5F5F5';

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    */

    public const TITLE_COLOR = 'FFFFFF';

    public const TEXT_COLOR = '222222';

    public const MUTED_COLOR = '777777';

    /*
    |--------------------------------------------------------------------------
    | Borders
    |--------------------------------------------------------------------------
    */

    public const BORDER_COLOR = 'CCCCCC';

    public const BORDER_SIZE = 8;

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public const TABLE_BORDER = 'D9D9D9';

    public const TABLE_HEADER = self::PRIMARY;

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    public const LOGO_WIDTH = 70;

    public const LOGO_HEIGHT = 70;

    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */

    public const PAGE_MARGIN = 900;

    public const CELL_MARGIN = 150;

    /*
    |--------------------------------------------------------------------------
    | Header Style
    |--------------------------------------------------------------------------
    */

    public static function headerStyle(): array
    {

        return [

            'bold' => true,

            'size' => self::TITLE_SIZE,

            'color' => self::TITLE_COLOR,

            'name' => self::FONT_NAME

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Subtitle Style
    |--------------------------------------------------------------------------
    */

    public static function subtitleStyle(): array
    {

        return [

            'bold' => true,

            'size' => self::SUBTITLE_SIZE,

            'color' => self::PRIMARY,

            'name' => self::FONT_NAME

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Normal Text
    |--------------------------------------------------------------------------
    */

    public static function textStyle(): array
    {

        return [

            'name' => self::FONT_NAME,

            'size' => self::FONT_SIZE,

            'color' => self::TEXT_COLOR

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Small Text
    |--------------------------------------------------------------------------
    */

    public static function smallStyle(): array
    {

        return [

            'name' => self::FONT_NAME,

            'size' => self::SMALL_SIZE,

            'color' => self::MUTED_COLOR

        ];

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

            'cellMargin' => self::CELL_MARGIN,

            'alignment' => Jc::CENTER

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

            'bgColor' => $color

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

            'bgColor' => self::LABEL_BACKGROUND

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

            'borderSize' => 6,

            'borderColor' => self::TABLE_BORDER,

            'cellMargin' => 100,

            'alignment' => Jc::CENTER

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Footer
    |--------------------------------------------------------------------------
    */

    public static function footerStyle(): array
    {

        return [

            'name' => self::FONT_NAME,

            'size' => 10,

            'color' => self::MUTED_COLOR

        ];

    }

}