<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Word Core
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/WordConfig.php';
require_once __DIR__ . '/WordStyle.php';
require_once __DIR__ . '/WordHeader.php';
require_once __DIR__ . '/WordFooter.php';
require_once __DIR__ . '/WordLessonBuilder.php';
require_once __DIR__ . '/WordExporter.php';

/*
|--------------------------------------------------------------------------
| Components
|--------------------------------------------------------------------------
*/

foreach (glob(__DIR__ . '/Components/*.php') as $file) {
    require_once $file;
}

/*
|--------------------------------------------------------------------------
| Traits
|--------------------------------------------------------------------------
*/

foreach (glob(__DIR__ . '/Traits/*.php') as $file) {
    require_once $file;
}

/*
|--------------------------------------------------------------------------
| Sections
|--------------------------------------------------------------------------
*/

foreach (glob(__DIR__ . '/Sections/*.php') as $file) {
    require_once $file;
}

/*
|--------------------------------------------------------------------------
| Themes


foreach (glob(__DIR__ . '/../Themes/*.php') as $file) {
    require_once $file;
}


|--------------------------------------------------------------------------
*/