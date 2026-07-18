<?php

declare(strict_types=1);

require_once __DIR__ . '/WordConfig.php';

final class SectionTheme
{
    /*
    |--------------------------------------------------------------------------
    | Colors
    |--------------------------------------------------------------------------
    */

    public const PRIMARY = WordConfig::PRIMARY_COLOR;
    public const SUCCESS = WordConfig::SUCCESS_COLOR;
    public const WARNING = WordConfig::WARNING_COLOR;
    public const DANGER  = WordConfig::DANGER_COLOR;
    public const PURPLE  = WordConfig::PURPLE_COLOR;

    public const TEXT = '222222';

    public const LIGHT = 'F8F9FA';

    public const BORDER = 'D9E2F3';

    /*
    |--------------------------------------------------------------------------
    | Font Styles
    |--------------------------------------------------------------------------
    */

    public static function titleFont(): array
    {
        return [

            'name'  => WordConfig::FONT_NAME,
            'size'  => 18,
            'bold'  => true,
            'color' => 'FFFFFF'

        ];
    }

    public static function headingFont(): array
    {
        return [

            'name'  => WordConfig::FONT_NAME,
            'size'  => 15,
            'bold'  => true,
            'color' => self::PRIMARY

        ];
    }

    public static function labelFont(): array
    {
        return [

            'name'  => WordConfig::FONT_NAME,
            'size'  => 12,
            'bold'  => true,
            'color' => self::PRIMARY

        ];
    }

    public static function textFont(): array
    {
        return [

            'name'  => WordConfig::FONT_NAME,
            'size'  => WordConfig::FONT_SIZE,
            'color' => self::TEXT

        ];
    }

    public static function noteFont(): array
    {
        return [

            'name'   => WordConfig::FONT_NAME,
            'size'   => WordConfig::FONT_SIZE - 1,
            'italic' => true,
            'color'  => '777777'

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Paragraph Styles
    |--------------------------------------------------------------------------
    */

    public static function rtlParagraph(): array
    {
        return [

            'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,

            'bidi'       => true,

            'spaceAfter' => 120

        ];
    }

    public static function centerParagraph(): array
    {
        return [

            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,

            'spaceAfter'=>120

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Table Styles
    |--------------------------------------------------------------------------
    */

    public static function table(
        string $color = self::PRIMARY
    ): array {

        return [

            'borderSize'  => 8,

            'borderColor' => $color,

            'cellMargin'  => 120,

            'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::CENTER

        ];

    }

    public static function headerCell(
        string $color = self::PRIMARY
    ): array {

        return [

            'bgColor' => $color,

            'valign'  => 'center'

        ];

    }

    public static function labelCell(): array
    {
        return [

            'bgColor' => self::LIGHT,

            'valign'  => 'center'

        ];
    }

    public static function valueCell(): array
    {
        return [

            'valign' => 'center'

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Block Colors
    |--------------------------------------------------------------------------
    */

    public static function blockColor(
        string $name
    ): string {

        return match (strtolower($name)) {

            'objectives' => self::SUCCESS,

            'warmup' => self::WARNING,

            'activities' => self::PRIMARY,

            'assessment' => self::DANGER,

            'resources' => self::PURPLE,

            'homework' => self::PRIMARY,

            default => self::PRIMARY

        };

    }
}