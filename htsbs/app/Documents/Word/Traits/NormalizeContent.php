<?php

declare(strict_types=1);

trait NormalizeContent
{
    /**
     * تنظيف القيمة
     */
    private static function normalize(
        mixed $value
    ): mixed {

        if ($value === null) {
            return '';
        }

        /*
        |--------------------------------------------------------------------------
        | String
        |--------------------------------------------------------------------------
        */

        if (is_string($value)) {

            $value = html_entity_decode(
                $value,
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );

            $value = trim($value);

            /*
            ------------------------------------------
            إزالة الفراغات الزائدة
            ------------------------------------------
            */

            $value = preg_replace(
                "/[ \t]+/",
                " ",
                $value
            );

            /*
            ------------------------------------------
            توحيد أسطر النهاية
            ------------------------------------------
            */

            $value = str_replace(
                ["\r\n", "\r"],
                "\n",
                $value
            );

            return $value;
        }

        /*
        |--------------------------------------------------------------------------
        | Array
        |--------------------------------------------------------------------------
        */

        if (is_array($value)) {

            foreach ($value as $key => $item) {

                $value[$key] = self::normalize(
                    $item
                );

            }

            return $value;

        }

        return $value;

    }

    /**
     * تنظيف جميع بيانات الدرس
     */
    private static function normalizeLesson(
        array $lesson
    ): array {

        foreach ($lesson as $key => $value) {

            $lesson[$key] = self::normalize(
                $value
            );

        }

        return $lesson;

    }

    /**
     * تحويل JSON إلى Array
     */
    private static function decodeJson(
        string $json
    ): array {

        $data = json_decode(
            $json,
            true
        );

        if (

            json_last_error()

            !==

            JSON_ERROR_NONE

        ) {

            return [];

        }

        return self::normalize(
            $data
        );

    }

    /**
     * إزالة وسوم HTML
     */
    private static function plainText(
        string $html
    ): string {

        return trim(

            strip_tags(

                html_entity_decode(
                    $html,
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )

            )

        );

    }

    /**
     * تحويل النص متعدد الأسطر إلى Array
     */
    private static function multilineToArray(
        string $text
    ): array {

        $text = self::normalize(
            $text
        );

        return array_values(

            array_filter(

                preg_split(
                    "/\n/",
                    $text
                )

            )

        );

    }

    /**
     * تحويل Array إلى Multiline
     */
    private static function arrayToMultiline(
        array $items
    ): string {

        return implode(
            PHP_EOL,
            array_filter($items)
        );

    }

    /**
     * إزالة القيم الفارغة
     */
    private static function removeEmpty(
        array $items
    ): array {

        return array_filter(

            $items,

            function ($value) {

                return !(
                    $value === null ||
                    $value === '' ||
                    $value === []
                );

            }

        );

    }

}