<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\SimpleType\Jc;

require_once __DIR__ . '/../WordConfig.php';

require_once __DIR__ . '/ContentType.php';
require_once __DIR__ . '/../Traits/DetectContentType.php';
require_once __DIR__ . '/../Traits/NormalizeContent.php';
require_once __DIR__ . '/CardText.php';
require_once __DIR__ . '/CardList.php';
require_once __DIR__ . '/CardHtml.php';
require_once __DIR__ . '/CardImage.php';
require_once __DIR__ . '/CardTable.php';

class SectionCard
{
    
    use DetectContentType;
    use NormalizeContent;
    /**
     * إنشاء بطاقة احترافية
     */
    public static function render(
        Section $section,
        string $title,
        array $rows = [],
        string $color = WordConfig::PRIMARY_COLOR
    ): void {

        if (empty($rows)) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Card Table
        |--------------------------------------------------------------------------
        */

$table = $section->addTable([

    'borderSize'  => 8,

    'borderColor' => $color,

    'cellMargin'  => 150,

    'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,

    'rtl'         => true,

    'bidi'        => true

]);
        
       

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $table->addRow();

       $table->addCell(

    9000,

    [

        'gridSpan' => 2,

        'bgColor'  => $color,
        'valign' => 'center'

    ]

)->addText(

            $title,

            [

                'bold'  => true,

                'size'  => WordConfig::TITLE_SIZE,

                'name'  => WordConfig::FONT_NAME,

                'color' => 'FFFFFF'

            ],

            [

                'alignment' => Jc::CENTER

            ]

        );

        /*
        |--------------------------------------------------------------------------
        | Rows
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $label => $value) {
            
            $value = self::normalize($value);
        
            
            if (

                $value === null ||

                $value === ''

            ) {

                continue;

            }

           $table->addRow();

/*
|--------------------------------------------------------------------------
| القيمة أولاً
|--------------------------------------------------------------------------
*/

$cell = $table->addCell(

    6500,

    [

        'valign' => 'center'

    ]

);

/*
|--------------------------------------------------------------------------
| العنوان ثانياً
|--------------------------------------------------------------------------
*/

$table->addCell(

    2500,

    [

        'bgColor' => 'F5F5F5',

        'valign'  => 'center'

    ]

)->addText(

    (string)$label,

    [

        'bold' => true,

        'name' => WordConfig::FONT_NAME,

        'size' => WordConfig::FONT_SIZE

    ],

    [

        'alignment' => Jc::CENTER,

        'bidi' => true

    ]

);




/*
|--------------------------------------------------------------------------
| Detect Type
|--------------------------------------------------------------------------
*/

$type = self::detectType(

    $value

);

switch ($type) {

    /*
    ------------------------------------------
    Image
    ------------------------------------------
    */

    case ContentType::IMAGE:

        CardImage::render(

            $cell,

            (string)$value

        );

        break;

    /*
    ------------------------------------------
    HTML
    ------------------------------------------
    */

    case ContentType::HTML:

        CardHtml::render(

            $cell,

            (string)$value

        );

        break;

    /*
    ------------------------------------------
    Table
    ------------------------------------------
    */

    case ContentType::TABLE:

        CardTable::render(

            $cell,

            $value

        );

        break;

    /*
    ------------------------------------------
    List
    ------------------------------------------
    */

    case ContentType::LIST:

        CardList::fromArray(

            $cell,

            $value

        );

        break;

    /*
    ------------------------------------------
    Multiline
    ------------------------------------------
    */

    case ContentType::MULTILINE:

        CardList::fromMultiline(

            $cell,

            (string)$value

        );

        break;

    /*
    ------------------------------------------
    Text
    ------------------------------------------
    */

    case ContentType::TEXT:

    default:

        CardText::render(

            $cell,

            (string)$value

        );

}


        }

        $section->addTextBreak();

    }

    /**
     * بطاقة نصية
     */
    public static function paragraph(
        Section $section,
        string $title,
        string $text,
        string $color = WordConfig::PRIMARY_COLOR
    ): void {

        self::render(

            $section,

            $title,

            [

                '' => $text

            ],

            $color

        );

    }

    /**
     * بطاقة قائمة
     */
    public static function bulletList(
        Section $section,
        string $title,
        array $items,
        string $color = WordConfig::PRIMARY_COLOR
    ): void {

        self::render(

            $section,

            $title,

            [

                '' => $items

            ],

            $color

        );

    }











    /**
     * ألوان رأس البطاقة
     */
    private static function headerColor(
        string $color
    ): string {

        return match (strtoupper($color)) {

            WordConfig::SUCCESS_COLOR => WordConfig::SUCCESS_COLOR,

            WordConfig::WARNING_COLOR => WordConfig::WARNING_COLOR,

            WordConfig::DANGER_COLOR => WordConfig::DANGER_COLOR,

            WordConfig::PURPLE_COLOR => WordConfig::PURPLE_COLOR,

            default => WordConfig::PRIMARY_COLOR

        };

    }

}