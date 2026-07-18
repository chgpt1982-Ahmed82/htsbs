<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

/*
|--------------------------------------------------------------------------
| WordExporter — إنشاء وتنزيل مستند Word
|
| ضبط الاتجاه RTL على المستويات الأربعة:
|   1. المستند : لغة عربية + نمط فقرة افتراضي RTL
|   2. المقطع  : bidi = true  (اتجاه الصفحة من اليمين لليسار)
|   3. الفقرة  : alignment = RIGHT + bidi = true
|   4. الخط    : rtl = true
|--------------------------------------------------------------------------
*/

class WordExporter
{
    private PhpWord $phpWord;

    public function __construct()
    {
        $this->phpWord = new PhpWord();

        /*
        ==========================================
        الخط الافتراضي
        ==========================================
        */

        $this->phpWord->setDefaultFontName(WordConfig::FONT_NAME);
        $this->phpWord->setDefaultFontSize(WordConfig::FONT_SIZE);

        /*
        ==========================================
        (1) لغة المستند: عربية
        ==========================================
        */

        $this->phpWord->getSettings()->setThemeFontLang(
            new \PhpOffice\PhpWord\Style\Language('ar-SA')
        );

        /*
        ==========================================
        (2) نمط الفقرة الافتراضي لكل المستند: RTL
        هذا هو المفتاح لجعل أي نص لم يُحدَّد له نمط
        يظهر تلقائياً بمحاذاة اليمين واتجاه عربي
        ==========================================
        */

        $this->phpWord->setDefaultParagraphStyle([
            'alignment'  => Jc::RIGHT,
            'bidi'       => true,
            'spaceAfter' => 120,
        ]);

        /*
        ==========================================
        تسجيل الأنماط
        ==========================================
        */

        WordStyle::register($this->phpWord);
    }

    /*
    ==========================================
    تصدير التحضير
    ==========================================
    */

    public function download(array $lesson, string $fileName): void
    {
        /*
        ==========================================
        (3) المقطع: صفحة RTL كاملة
        ==========================================
        */

        $section = $this->phpWord->addSection([
            'orientation'  => 'portrait',
            'paperSize'    => 'A4',
            'marginTop'    => WordConfig::PAGE_MARGIN,
            'marginBottom' => WordConfig::PAGE_MARGIN,
            'marginLeft'   => WordConfig::PAGE_MARGIN,
            'marginRight'  => WordConfig::PAGE_MARGIN,
            'bidi'         => true,   // اتجاه المقطع من اليمين لليسار
            'rtl'          => true,
        ]);

        // الترويسة
        WordHeader::build($section, $lesson);

        // المتن
        WordLessonBuilder::build($section, $lesson);

        // التذييل
        WordFooter::build($section);

        /*
        ==========================================
        التنزيل
        ==========================================
        */

        if (headers_sent()) {
            throw new RuntimeException('Headers already sent.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '.docx"');
        header('Cache-Control: max-age=0');

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $writer = IOFactory::createWriter($this->phpWord, 'Word2007');

        try {
            $writer->save('php://output');
        } finally {
            exit;
        }
    }
}
