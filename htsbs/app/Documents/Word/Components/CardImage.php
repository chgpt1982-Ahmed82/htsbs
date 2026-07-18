<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\SimpleType\Jc;

class CardImage
{
    /**
     * إضافة صورة
     */
    public static function render(
        Cell $cell,
        string $image,
        int $width = 220,
        int $height = 160
    ): bool {

        $image = trim($image);

        if ($image === '') {
            return false;
        }

        if (!is_file($image)) {
            return false;
        }

        $cell->addImage(

            $image,

            [

                'width' => $width,

                'height' => $height,

                'alignment' => Jc::CENTER

            ]

        );

        return true;
    }

    /**
     * هل الصورة موجودة؟
     */
    public static function exists(
        string $image
    ): bool {

        return

            trim($image) !== ''

            &&

            is_file($image);

    }
}