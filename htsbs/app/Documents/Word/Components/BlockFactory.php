<?php

declare(strict_types=1);

use PhpOffice\PhpWord\Element\Section;

require_once __DIR__ . '/ContentType.php';

require_once __DIR__ . '/../Traits/DetectContentType.php';
require_once __DIR__ . '/../Traits/NormalizeContent.php';

require_once __DIR__ . '/ContentRenderer.php';

require_once __DIR__ . '/BlockText.php';
require_once __DIR__ . '/BlockList.php';
require_once __DIR__ . '/BlockHtml.php';
require_once __DIR__ . '/BlockImage.php';
require_once __DIR__ . '/BlockTable.php';

final class BlockFactory
{
    use DetectContentType;
    use NormalizeContent;

    /**
     * اختيار الـ Block المناسب
     */
    public static function render(
        Section $section,
        mixed $value
    ): void {

        $value = self::normalize($value);

        if ($value === null || $value === '') {
            return;
        }

        $type = self::detectType($value);

       ContentRenderer::render(   $section,    $type,    $value);

    }
}