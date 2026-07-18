<?php

declare(strict_types=1);

require_once __DIR__ . '/DefaultTheme.php';

class ModernBlueTheme extends DefaultTheme
{
    /*
    |--------------------------------------------------------------------------
    | Theme Information
    |--------------------------------------------------------------------------
    */

    protected string $themeName = 'Modern Blue';

    protected string $version = '1.0';

    /*
    |--------------------------------------------------------------------------
    | Primary Colors
    |--------------------------------------------------------------------------
    */

    protected string $primary = '1565C0';

    protected string $secondary = '42A5F5';

    protected string $success = '2E7D32';

    protected string $warning = 'F9A825';

    protected string $danger = 'C62828';

    protected string $info = '039BE5';

    /*
    |--------------------------------------------------------------------------
    | Background
    |--------------------------------------------------------------------------
    */

    protected string $pageBackground = 'FFFFFF';

    protected string $cardBackground = 'FFFFFF';

    protected string $headerBackground = '1565C0';

    protected string $labelBackground = 'F5F9FF';

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    */

    protected string $titleColor = 'FFFFFF';

    protected string $textColor = '1F2937';

    protected string $mutedColor = '6B7280';

    /*
    |--------------------------------------------------------------------------
    | Borders
    |--------------------------------------------------------------------------
    */

    protected string $borderColor = 'D0E3FF';

    protected string $tableBorder = 'D0E3FF';

    /*
    |--------------------------------------------------------------------------
    | Logos
    |--------------------------------------------------------------------------
    */

    protected ?string $defaultSchoolLogo = null;

    protected ?string $defaultMinistryLogo = null;

    /*
    |--------------------------------------------------------------------------
    | Getters
    |--------------------------------------------------------------------------
    */

    public function themeName(): string
    {
        return $this->themeName;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function primaryColor(): string
    {
        return $this->primary;
    }

    public function secondaryColor(): string
    {
        return $this->secondary;
    }

    public function successColor(): string
    {
        return $this->success;
    }

    public function warningColor(): string
    {
        return $this->warning;
    }

    public function dangerColor(): string
    {
        return $this->danger;
    }

    public function infoColor(): string
    {
        return $this->info;
    }

    public function headerBackground(): string
    {
        return $this->headerBackground;
    }

    public function cardBackground(): string
    {
        return $this->cardBackground;
    }

    public function labelBackground(): string
    {
        return $this->labelBackground;
    }

    public function borderColor(): string
    {
        return $this->borderColor;
    }

    public function tableBorder(): string
    {
        return $this->tableBorder;
    }

    public function titleColor(): string
    {
        return $this->titleColor;
    }

    public function textColor(): string
    {
        return $this->textColor;
    }

    public function mutedColor(): string
    {
        return $this->mutedColor;
    }

    /*
    |--------------------------------------------------------------------------
    | Logos
    |--------------------------------------------------------------------------
    */

    public function ministryLogo(): ?string
    {
        return

            $this->config['ministry_logo']

            ??

            $this->defaultMinistryLogo;
    }

    public function schoolLogo(): ?string
    {
        return

            $this->config['school_logo']

            ??

            $this->defaultSchoolLogo;
    }

    /*
    |--------------------------------------------------------------------------
    | Header Style
    |--------------------------------------------------------------------------
    */

   public static function headerStyle(): 
    {
        return [

            'bold'  => true,

            'size'  => 22,

            'name'  => self::FONT_NAME,

            'color' => $this->titleColor

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Subtitle Style
    |--------------------------------------------------------------------------
    */

    public static function subtitleStyle(): 
    {
        return [

            'bold' => true,

            'size' => 15,

            'name' => self::FONT_NAME,

            'color' => $this->primary

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Card Style
    |--------------------------------------------------------------------------
    */

    public static function cardStyle(): 
    {
        return [

            'borderSize' => self::BORDER_SIZE,

            'borderColor' => $this->primary,

            'cellMargin' => self::CELL_MARGIN

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Table Style
    |--------------------------------------------------------------------------
    */

    public static function tableStyle(): 
    {
        return [

            'borderSize' => 6,

            'borderColor' => $this->tableBorder,

            'cellMargin' => 100

        ];
    }
}