<?php

declare(strict_types=1);

require_once __DIR__ . '/DefaultTheme.php';
require_once __DIR__ . '/BahrainTheme.php';
require_once __DIR__ . '/ModernBlueTheme.php';
require_once __DIR__ . '/GreenEducationTheme.php';

final class ThemeManager
{
    /**
     * القالب الافتراضي
     */
    private static string $defaultTheme = 'bahrain';

    /**
     * جميع القوالب
     */
    private static array $themes = [

        'default' => DefaultTheme::class,

        'bahrain' => BahrainTheme::class,

        'modern' => ModernBlueTheme::class,

        'green' => GreenEducationTheme::class

    ];

    /**
     * تغيير القالب الافتراضي
     */
    public static function setDefault(
        string $theme
    ): void {

        if (

            isset(self::$themes[$theme])

        ) {

            self::$defaultTheme = $theme;

        }

    }

    /**
     * الحصول على القالب الحالي
     */
    public static function current(): string
    {

        return self::$themes[

            self::$defaultTheme

        ];

    }

    /**
     * اختيار قالب
     */
    public static function make(
        ?string $theme
    ): string {

        if (

            empty($theme)

        ) {

            return self::current();

        }

        $theme = strtolower(

            trim($theme)

        );

        return self::$themes[$theme]

            ??

            self::current();

    }

    /**
     * جميع القوالب
     */
    public static function all(): array
    {

        return self::$themes;

    }

    /**
     * أسماء القوالب
     */
    public static function names(): array
    {

        return [

            'default' => 'Default',

            'bahrain' => 'Bahrain',

            'modern' => 'Modern Blue',

            'green' => 'Green Education'

        ];

    }

    /**
     * هل القالب موجود؟
     */
    public static function exists(
        string $theme
    ): bool {

        return isset(

            self::$themes[

                strtolower($theme)

            ]

        );

    }

    /**
     * حذف جميع المسافات
     */
    public static function normalize(
        string $theme
    ): string {

        return strtolower(

            trim($theme)

        );

    }

}