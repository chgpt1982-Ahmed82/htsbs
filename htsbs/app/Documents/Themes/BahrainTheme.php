<?php

declare(strict_types=1);

require_once __DIR__ . '/DefaultTheme.php';

/**
 * Bahrain Ministry of Education Theme
 */
final class BahrainTheme extends DefaultTheme
{
    /*
    |--------------------------------------------------------------------------
    | Theme Info
    |--------------------------------------------------------------------------
    */

    public const NAME = 'Bahrain MOE';

    public const VERSION = '1.0';

    /*
    |--------------------------------------------------------------------------
    | Bahrain Colors
    |--------------------------------------------------------------------------
    */

    public const PRIMARY = '006C35';

    public const SUCCESS = '00843D';

    public const WARNING = 'C79A00';

    public const DANGER = 'B22222';

    public const PURPLE = '5E3A9E';

    public const INFO = '0077B6';

    /*
    |--------------------------------------------------------------------------
    | Background
    |--------------------------------------------------------------------------
    */

    public const PAGE_BACKGROUND = 'FFFFFF';

    public const CARD_BACKGROUND = 'FFFFFF';

    public const HEADER_BACKGROUND = self::PRIMARY;

    public const LABEL_BACKGROUND = 'F6F8F7';

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    */

    public const TITLE_COLOR = 'FFFFFF';

    public const TEXT_COLOR = '1F2937';

    public const MUTED_COLOR = '6B7280';

    /*
    |--------------------------------------------------------------------------
    | Borders
    |--------------------------------------------------------------------------
    */

    public const BORDER_COLOR = 'D9E4DD';

    public const TABLE_BORDER = 'D9E4DD';

    /*
    |--------------------------------------------------------------------------
    | Logos
    |--------------------------------------------------------------------------
    */

    public const MINISTRY_LOGO =
        __DIR__ .
        '/../../assets/images/moe_bahrain.png';

    public const SCHOOL_LOGO =
        __DIR__ .
        '/../../assets/images/school_logo.png';

    /*
    |--------------------------------------------------------------------------
    | Header
    |--------------------------------------------------------------------------
    */

    public static function headerStyle(): array
    {

        return [

            'bold' => true,

            'size' => 21,

            'name' => self::FONT_NAME,

            'color' => self::TITLE_COLOR

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Subtitle
    |--------------------------------------------------------------------------
    */

    public static function subtitleStyle(): array
    {

        return [

            'bold' => true,

            'size' => 15,

            'name' => self::FONT_NAME,

            'color' => self::PRIMARY

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Card
    |--------------------------------------------------------------------------
    */

    public static function cardStyle(
        string $color = self::PRIMARY
    ): array {

        return [

            'borderSize' => self::BORDER_SIZE,

            'borderColor' => $color,

            'cellMargin' => self::CELL_MARGIN,

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
    | Table
    |--------------------------------------------------------------------------
    */

    public static function tableStyle(): array
    {

        return [

            'borderSize' => 6,

            'borderColor' => self::TABLE_BORDER,

            'cellMargin' => 100,

            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER

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

            'italic' => true,

            'color' => self::MUTED_COLOR

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | معلومات الترويسة
    |--------------------------------------------------------------------------
    */

    public static function ministryName(): string
    {
        return 'وزارة التربية والتعليم';
    }

    public static function countryName(): string
    {
        return 'مملكة البحرين';
    }

    public static function systemName(): string
    {
        return 'Learning Management System (LMS)';
    }

    public static function documentTitle(): string
    {
        return 'تحضير درس';
    }

    /*
    |--------------------------------------------------------------------------
    | شعار الوزارة
    |--------------------------------------------------------------------------
    */

    public static function ministryLogo(): ?string
    {

        return file_exists(self::MINISTRY_LOGO)

            ? self::MINISTRY_LOGO

            : null;

    }

    /*
    |--------------------------------------------------------------------------
    | شعار المدرسة
    |--------------------------------------------------------------------------
    */

    public static function schoolLogo(): ?string
    {

        return file_exists(self::SCHOOL_LOGO)

            ? self::SCHOOL_LOGO

            : null;

    }

}