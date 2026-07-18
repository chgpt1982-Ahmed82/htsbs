<?php

declare(strict_types=1);

require_once __DIR__ . '/../Components/ContentType.php';
require_once __DIR__ . '/../Components/CardImage.php';

trait DetectContentType
{
    /**
     * تحديد نوع المحتوى
     */
    private static function detectType(
        mixed $value
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Empty
        |--------------------------------------------------------------------------
        */

        if (

            $value === null ||

            $value === ''

        ) {

            return ContentType::EMPTY;

        }

        /*
        |--------------------------------------------------------------------------
        | Array
        |--------------------------------------------------------------------------
        */

        if (is_array($value)) {

            /*
            ------------------------------------------
            Table
            ------------------------------------------
            */

            if (

                isset($value[0])

                &&

                is_array($value[0])

            ) {

                return ContentType::TABLE;

            }

            return ContentType::LIST;

        }

        /*
        |--------------------------------------------------------------------------
        | Not String
        |--------------------------------------------------------------------------
        */

        if (!is_string($value)) {

            return ContentType::TEXT;

        }

        $value = trim($value);

        /*
        |--------------------------------------------------------------------------
        | Base64 Image
        |--------------------------------------------------------------------------
        */

        if (

            str_starts_with(

                $value,

                'data:image/'

            )

        ) {

            return ContentType::IMAGE;

        }

        /*
        |--------------------------------------------------------------------------
        | Local Image
        |--------------------------------------------------------------------------
        */

        if (

            CardImage::exists(

                $value

            )

        ) {

            return ContentType::IMAGE;

        }

        /*
        |--------------------------------------------------------------------------
        | HTML
        |--------------------------------------------------------------------------
        */

        if (

            preg_match(

                '/<[^>]+>/',

                $value

            )

        ) {

            return ContentType::HTML;

        }

        /*
        |--------------------------------------------------------------------------
        | JSON
        |--------------------------------------------------------------------------
        */

        json_decode(

            $value,

            true

        );

        if (

            json_last_error()

            ===

            JSON_ERROR_NONE

        ) {

            return ContentType::JSON;

        }

        /*
        |--------------------------------------------------------------------------
        | URL
        |--------------------------------------------------------------------------
        */

        if (

            filter_var(

                $value,

                FILTER_VALIDATE_URL

            )

        ) {

            return ContentType::URL;

        }

        /*
        |--------------------------------------------------------------------------
        | Multiline
        |--------------------------------------------------------------------------
        */

        if (

            str_contains(

                $value,

                "\n"

            )

        ) {

            return ContentType::MULTILINE;

        }

        /*
        |--------------------------------------------------------------------------
        | Default
        |--------------------------------------------------------------------------
        */

        return ContentType::TEXT;

    }

    /**
     * هل القيمة فارغة؟
     */
    private static function isEmpty(
        mixed $value
    ): bool {

        return self::detectType(

            $value

        ) === ContentType::EMPTY;

    }

    /**
     * هل القيمة صورة؟
     */
    private static function isImage(
        mixed $value
    ): bool {

        return self::detectType(

            $value

        ) === ContentType::IMAGE;

    }

    /**
     * هل القيمة HTML؟
     */
    private static function isHtml(
        mixed $value
    ): bool {

        return self::detectType(

            $value

        ) === ContentType::HTML;

    }

    /**
     * هل القيمة جدول؟
     */
    private static function isTable(
        mixed $value
    ): bool {

        return self::detectType(

            $value

        ) === ContentType::TABLE;

    }

    /**
     * هل القيمة قائمة؟
     */
    private static function isList(
        mixed $value
    ): bool {

        return self::detectType(

            $value

        ) === ContentType::LIST;

    }

    /**
     * هل القيمة متعددة الأسطر؟
     */
    private static function isMultiline(
        mixed $value
    ): bool {

        return self::detectType(

            $value

        ) === ContentType::MULTILINE;

    }

}