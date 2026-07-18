<?php

declare(strict_types=1);

/**
 * أنواع المحتوى المدعومة داخل SectionCard
 */
final class ContentType
{
    /*
    |--------------------------------------------------------------------------
    | Basic Types
    |--------------------------------------------------------------------------
    */

    public const EMPTY = 'empty';

    public const TEXT = 'text';

    public const HTML = 'html';

    public const IMAGE = 'image';

    public const TABLE = 'table';

    public const LIST = 'list';

    public const MULTILINE = 'multiline';

    public const JSON = 'json';

    public const URL = 'url';

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public static function all(): array
    {
        return [

            self::EMPTY,

            self::TEXT,

            self::HTML,

            self::IMAGE,

            self::TABLE,

            self::LIST,

            self::MULTILINE,

            self::JSON,

            self::URL

        ];
    }

    /**
     * التحقق من النوع
     */
    public static function isValid(
        string $type
    ): bool {

        return in_array(

            $type,

            self::all(),

            true

        );

    }
}