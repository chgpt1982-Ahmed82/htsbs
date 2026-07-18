<?php
/*
=====================================================================
PdfImporter — استيراد ملزمة PDF وتحويلها إلى دروس مقترحة
=====================================================================
يعتمد على مكتبة smalot/pdfparser (PHP خالص، لا تحتاج SSH ولا exec()):
    composer require smalot/pdfparser

الفكرة:
  1. extractPages()   → نص كل صفحة من الـPDF (مصفوفة)
  2. suggestLessons()  → تقطيع تلقائي بناءً على أنماط عناوين متكررة
     مثل: "الدرس :1 الخوارزميات" و "اسئلةالدرس: ..."
     (هذا النمط مأخوذ من هيكلة ملزمة تقن 107 الفعلية، لكنه عام بما يكفي
      ليعمل مع أي ملزمة تتّبع نمط "الدرس N: العنوان" في العناوين)
  3. النتيجة تُعرض للمعلم في شاشة مراجعة (review.php) ليؤكدها أو يعدّلها
     — لا إنشاء تلقائي نهائي بلا موافقة بشرية، لتفادي أخطاء التقطيع.
=====================================================================
*/

require_once __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;

class PdfImporter
{
    /**
     * استخراج نص كل صفحة من ملف PDF
     * @return array<int, string>  مفهرسة من 1
     * @throws Exception عند فشل القراءة (ملف تالف/محمي بكلمة مرور)
     */
    public static function extractPages(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new Exception('الملف غير موجود');
        }

        try {

            $parser   = new Parser();
            $document = $parser->parseFile($filePath);
            $pages    = $document->getPages();

        } catch (Throwable $ex) {
            throw new Exception('تعذّرت قراءة ملف PDF — تأكد أنه غير محمي بكلمة مرور: ' . $ex->getMessage());
        }

        $result = [];

        foreach ($pages as $index => $page) {

            $text = $page->getText();

            /* تنظيف بسيط: أسطر فارغة متكررة */
            $text = preg_replace("/\n{3,}/", "\n\n", $text);
            $text = trim((string)$text);

            $result[$index + 1] = $text;
        }

        if (!$result) {
            throw new Exception('لم يُستخرج أي نص — قد يكون الملف صوراً ممسوحة ضوئياً بلا طبقة نص');
        }

        return $result;
    }

    /**
     * اقتراح تقطيع الصفحات إلى دروس بناءً على أنماط العناوين
     *
     * @param array<int,string> $pages نتيجة extractPages()
     * @return array<int, array{
     *     title: string,
     *     start_page: int,
     *     end_page: int,
     *     content: string,
     *     exercises_hint: string|null
     * }>
     */
    public static function suggestLessons(array $pages): array
    {
        /*
        ====================================================================
        أنماط العناوين المدعومة (Regex مرن يتحمّل اختلاف المسافات
        الناتج عن استخراج PDF، وهي مشكلة شائعة جداً مع النصوص العربية):

        1) "الدرس :1 الخوارزميات"  أو  "الدرس:1 الخوارزميات"
        2) "الدرس 1: الخوارزميات"
        3) "اسئلة الدرس: الخوارزميات"  (فاصل نهاية الدرس / بداية التمارين)
        ====================================================================
        */
        $lessonPattern = '/الدرس\s*[:\s]\s*(\d+)\s*[:\s]?\s*([^\r\n]{2,80})/u';
        $examplePattern = '/اسئلة\s*الدرس\s*[:\s]/u';

        $markers = [];   // كل عثور على بداية درس: [page, lesson_no, title]

        foreach ($pages as $pageNum => $text) {

            if (preg_match($lessonPattern, $text, $m)) {

                $lessonNo = (int)$m[1];
                $title    = trim($m[2]);

                /* تجاهل تكرار كاذب لنفس رقم الدرس في نفس الصفحة (فهرس مثلاً) */
                $alreadyFound = array_filter(
                    $markers,
                    fn($mk) => $mk['lesson_no'] === $lessonNo
                );

                if ($alreadyFound) {
                    continue;
                }

                $markers[] = [
                    'page'      => $pageNum,
                    'lesson_no' => $lessonNo,
                    'title'     => $title,
                ];
            }
        }

        /* لا عناوين مكتشفة؟ نُرجع الملف كاملاً كدرس واحد يدوي التعديل */
        if (!$markers) {
            return [[
                'title'          => 'درس مستورد (يحتاج مراجعة يدوية للعنوان)',
                'start_page'     => 1,
                'end_page'       => count($pages),
                'content'        => implode("\n\n", $pages),
                'exercises_hint' => null,
            ]];
        }

        /* ترتيب حسب رقم الصفحة تحسباً لملف غير مرتّب */
        usort($markers, fn($a, $b) => $a['page'] <=> $b['page']);

        $lessons    = [];
        $totalPages = count($pages);

        foreach ($markers as $i => $marker) {

            $startPage = $marker['page'];
            $endPage   = isset($markers[$i + 1])
                ? $markers[$i + 1]['page'] - 1
                : $totalPages;

            if ($endPage < $startPage) {
                $endPage = $startPage;
            }

            /* تجميع نص الدرس من صفحاته */
            $lessonText = '';
            $exerciseText = null;
            $inExercises = false;

            for ($p = $startPage; $p <= $endPage; $p++) {

                $pageText = $pages[$p] ?? '';

                /* فصل محتوى الشرح عن قسم "أسئلة الدرس" داخل نفس النطاق */
                if (!$inExercises && preg_match($examplePattern, $pageText)) {
                    $parts        = preg_split($examplePattern, $pageText, 2);
                    $lessonText  .= "\n\n" . ($parts[0] ?? '');
                    $exerciseText = ($exerciseText ?? '') . "\n\n" . ($parts[1] ?? '');
                    $inExercises  = true;
                    continue;
                }

                if ($inExercises) {
                    $exerciseText = ($exerciseText ?? '') . "\n\n" . $pageText;
                } else {
                    $lessonText .= "\n\n" . $pageText;
                }
            }

            $lessons[] = [
                'title'          => $marker['title'],
                'start_page'     => $startPage,
                'end_page'       => $endPage,
                'content'        => trim($lessonText),
                'exercises_hint' => $exerciseText !== null ? trim($exerciseText) : null,
            ];
        }

        return $lessons;
    }

    /**
     * حفظ نتيجة الاستخراج كملف JSON على القرص
     * (تفادياً لتخزين نصوص ضخمة في أعمدة قاعدة البيانات)
     */
    public static function saveJson(string $dir, string $name, array $data): string
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = rtrim($dir, '/') . '/' . $name . '.json';

        file_put_contents(
            $path,
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        return $path;
    }

    public static function loadJson(string $path): array
    {
        if (!is_file($path)) {
            throw new Exception('ملف البيانات المستخرجة غير موجود');
        }

        $data = json_decode((string)file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }
}
