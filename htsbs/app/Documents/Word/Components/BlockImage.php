<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';

class BlockImage
{
    /**
     * عرض صورة
     */
    public static function render(
        Section $section,
        string $image,
        int $width = 350,
        int $height = 220,
        string $caption = ''
    ): void {

        $image = trim($image);

        if ($image === '') {
            return;
        }

        if (!file_exists($image)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | الصورة
        |--------------------------------------------------------------------------
        */

        $section->addImage(

            $image,

            [

                'width'     => $width,

                'height'    => $height,

                'alignment' => Jc::CENTER

            ]

        );

        /*
        |--------------------------------------------------------------------------
        | وصف الصورة
        |--------------------------------------------------------------------------
        */

        if ($caption !== '') {

            $section->addText(

                trim($caption),

                [

                    'name'  => WordConfig::FONT_NAME,

                    'size'  => WordConfig::FONT_SIZE - 1,

                    'italic'=> true,

                    'color' => '666666'

                ],

                [

                    'alignment' => Jc::CENTER,

                    'bidi'      => true,

                    'spaceAfter'=> 180

                ]

            );

        }

    }

    /**
     * عرض عدة صور
     */
    public static function gallery(
        Section $section,
        array $images,
        int $width = 220,
        int $height = 150
    ): void {

        foreach ($images as $image) {

            if (!is_string($image)) {
                continue;
            }

            self::render(

                $section,

                $image,

                $width,

                $height

            );

        }

    }

    /**
     * التحقق من وجود الصورة
     */
    public static function exists(
        string $image
    ): bool {

        return

            trim($image) !== ''

            &&

            file_exists($image);

    }

    /**
     * شعار المدرسة
     */
    public static function schoolLogo(
        Section $section,
        string $image
    ): void {

        self::render(

            $section,

            $image,

            90,

            90

        );

    }

    /**
     * شعار الوزارة
     */
    public static function ministryLogo(
        Section $section,
        string $image
    ): void {

        self::render(

            $section,

            $image,

            90,

            90

        );

    }

}