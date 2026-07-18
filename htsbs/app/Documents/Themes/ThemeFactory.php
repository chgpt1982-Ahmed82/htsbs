<?php

declare(strict_types=1);

require_once __DIR__ . '/ThemeManager.php';

final class ThemeFactory
{
    /**
     * إنشاء Theme
     */
    public static function create(
        ?string $theme = null,
        array $config = []
    ): object {

        $class = ThemeManager::make($theme);

        return new $class($config);

    }

    /**
     * Theme الافتراضي
     */
    public static function current(
        array $config = []
    ): object {

        return self::create(

            null,

            $config

        );

    }

}