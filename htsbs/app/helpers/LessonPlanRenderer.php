<?php

class LessonPlanRenderer
{
    /*
    ============================================================
    عرض التحضير كاملاً
    ============================================================
    */
public static function render(
    array $lesson,
    array $options = []
): string
{

    /*
    ==================================================
    Render Mode
    ==================================================
    */

    $mode = $options['mode'] ?? 'view';

    $isPrint = ($mode === 'print');

    $isPdf = ($mode === 'pdf');

    $isView = ($mode === 'view');

    /*
    ==================================================
    البيانات المرسلة هي JSON مباشرة
    ==================================================
    */

    $json = $lesson;

    if (empty($json)) {

        return '

        <div class="alert alert-warning">

            لا توجد بيانات للتحضير.

        </div>';

    }

    $html = '';

    $html .= self::lessonInfo($json);

    $html .= self::objectives($json);

    $html .= self::warmup($json);

    $html .= self::introduction($json);

    $html .= self::objectiveOne($json);

    $html .= self::objectiveTwo($json);

    $html .= self::conclusion($json);

    $html .= self::homework($json);

    $html .= self::resources($json);

    $html .= self::skills($json);

    $html .= self::values($json);

    $html .= self::differentiation($json);

    $html .= self::finalAssessment($json);

    return $html;

}

    /*
    ============================================================
    Bootstrap Card
    ============================================================
    */

    private static function card(
        string $title,
        string $icon,
        string $color,
        string $body
    ): string {

        return '

        <div class="card shadow border-0 mb-4">

            <div class="card-header bg-'
            .$color.
            ' text-white">

                <i class="bi '
                .$icon.
                '"></i>

                '.$title.'

            </div>

            <div class="card-body">

                '.$body.'

            </div>

        </div>

        ';

    }
    

    
    

    /*
    ============================================================
    معلومات الدرس
    ============================================================
    */

    private static function lessonInfo(array $json): string
    {
        $info = $json['lesson_info'] ?? [];

        $body = '

        <div class="row">

            <div class="col-md-6 mb-3">

                <strong>المادة</strong>

                <br>

                '.htmlspecialchars(
                    $info['subject'] ?? ''
                ).'

            </div>

            <div class="col-md-6 mb-3">

                <strong>الصف</strong>

                <br>

                '.htmlspecialchars(
                    $info['grade'] ?? ''
                ).'

            </div>

            <div class="col-md-6 mb-3">

                <strong>الوحدة</strong>

                <br>

                '.htmlspecialchars(
                    $info['unit'] ?? ''
                ).'

            </div>

            <div class="col-md-6 mb-3">

                <strong>عنوان الدرس</strong>

                <br>

                '.htmlspecialchars(
                    $info['lesson_title'] ?? ''
                ).'

            </div>

            <div class="col-md-12">

                <strong>مدة الحصة</strong>

                <br>

                '.htmlspecialchars(
                    $info['duration'] ?? ''
                ).'

            </div>

        </div>

        ';

        return self::card(

            'معلومات الدرس',

            'bi-book-fill',

            'primary',

            $body

        );

    }
    
    /*
    ============================================================
    أهداف التعلم
    ============================================================
    */

    private static function objectives(array $json): string
    {
        $items = $json['objectives'] ?? [];

        $body = '<ul class="list-group list-group-flush">';

        foreach ($items as $objective) {

            $body .= '

            <li class="list-group-item">

                <i class="bi bi-check-circle-fill text-success"></i>

                '

                . htmlspecialchars($objective) .

            '

            </li>

            ';

        }

        $body .= '</ul>';

        return self::card(

            'أهداف التعلم',

            'bi-bullseye',

            'success',

            $body

        );
    }

    /*
    ============================================================
    النشاط الاستهلالي
    ============================================================
    */

    private static function warmup(array $json): string
    {
        $warmup = $json['warmup'] ?? [];

        $body = '

        <div class="row">

            <div class="col-md-12 mb-3">

                <strong>النشاط</strong>

                <br>

                '

                . nl2br(htmlspecialchars($warmup['title'] ?? '')) .

            '

            </div>

            <div class="col-md-6 mb-3">

                <strong>دور المعلم</strong>

                <br>

                '

                . nl2br(htmlspecialchars($warmup['teacher_role'] ?? '')) .

            '

            </div>

            <div class="col-md-6 mb-3">

                <strong>دور الطلبة</strong>

                <br>

                '

                . nl2br(htmlspecialchars($warmup['student_role'] ?? '')) .

            '

            </div>

            <div class="col-md-6 mb-3">

                <strong>الوسائل</strong>

                <br>

                '

                . nl2br(htmlspecialchars($warmup['resources'] ?? '')) .

            '

            </div>

            <div class="col-md-6 mb-3">

                <strong>الزمن</strong>

                <br>

                '

                . htmlspecialchars($warmup['time'] ?? '') .

            '

            </div>

        </div>

        ';

        return self::card(

            'النشاط الاستهلالي',

            'bi-lightbulb-fill',

            'warning',

            $body

        );
    }

    /*
    ============================================================
    المقدمة
    ============================================================
    */

    private static function introduction(array $json): string
    {
        $intro = $json['introduction'] ?? [];

        $body = '

        <div class="p-2">

            '

            . nl2br(htmlspecialchars($intro['content'] ?? '')) .

        '

        </div>

        ';

        return self::card(

            'مقدمة الدرس',

            'bi-play-circle-fill',

            'info',

            $body

        );
    }
    
        /*
    ============================================================
    الهدف الأول
    ============================================================
    */

    private static function objectiveOne(array $json): string
    {
        $obj = $json['objective1'] ?? [];

        $body = '

        <div class="mb-3">

            <strong>الهدف</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['goal'] ?? '')).'

            </div>

        </div>

        <hr>

        <div class="mb-3">

            <strong>استراتيجية التدريس</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['strategy'] ?? '')).'

            </div>

        </div>

        <hr>

        <div class="mb-3">

            <strong>النشاط التكويني الأول</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['activity1'] ?? '')).'

            </div>

        </div>

        <hr>

        <div class="mb-3">

            <strong>النشاط التكويني الثاني</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['activity2'] ?? '')).'

            </div>

        </div>

        <hr>

        <div>

            <strong>التقويم</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['assessment'] ?? '')).'

            </div>

        </div>

        ';

        return self::card(

            'الهدف الأول',

            'bi-1-circle-fill',

            'primary',

            $body

        );
    }

    /*
    ============================================================
    الهدف الثاني
    ============================================================
    */

    private static function objectiveTwo(array $json): string
    {
        $obj = $json['objective2'] ?? [];

        $body = '

        <div class="mb-3">

            <strong>الهدف</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['goal'] ?? '')).'

            </div>

        </div>

        <hr>

        <div class="mb-3">

            <strong>استراتيجية التدريس</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['strategy'] ?? '')).'

            </div>

        </div>

        <hr>

        <div class="mb-3">

            <strong>النشاط التكويني الأول</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['activity1'] ?? '')).'

            </div>

        </div>

        <hr>

        <div class="mb-3">

            <strong>النشاط التكويني الثاني</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['activity2'] ?? '')).'

            </div>

        </div>

        <hr>

        <div>

            <strong>التقويم</strong>

            <div class="mt-2">

                '.nl2br(htmlspecialchars($obj['assessment'] ?? '')).'

            </div>

        </div>

        ';

        return self::card(

            'الهدف الثاني',

            'bi-2-circle-fill',

            'success',

            $body

        );
    }

    /*
    ============================================================
    الخاتمة
    ============================================================
    */

    private static function conclusion(array $json): string
    {
        $body = '

        <div class="p-2">

            '.nl2br(htmlspecialchars($json['conclusion'] ?? '')).'

        </div>

        ';

        return self::card(

            'الخاتمة',

            'bi-check2-square',

            'dark',

            $body

        );
    }

    /*
    ============================================================
    الواجب المنزلي
    ============================================================
    */

    private static function homework(array $json): string
    {
        $body = '

        <div class="p-2">

            '.nl2br(htmlspecialchars($json['homework'] ?? '')).'

        </div>

        ';

        return self::card(

            'الواجب المنزلي',

            'bi-house-check-fill',

            'secondary',

            $body

        );
    }
        /*
    ============================================================
    وسائل التعليم
    ============================================================
    */

    private static function resources(array $json): string
    {
        $resources = $json['resources'] ?? [];

        $body = '<ul class="list-group list-group-flush">';

        foreach ($resources as $item) {

            $body .= '

            <li class="list-group-item">

                <i class="bi bi-check-circle-fill text-success"></i>

                '.htmlspecialchars($item).'

            </li>

            ';

        }

        $body .= '</ul>';

        return self::card(

            'وسائل التعليم',

            'bi-easel2-fill',

            'dark',

            $body

        );
    }

    /*
    ============================================================
    مهارات القرن الحادي والعشرين
    ============================================================
    */

    private static function skills(array $json): string
    {
        $skills = $json['skills'] ?? [];

        $body = '<div class="d-flex flex-wrap gap-2">';

        foreach ($skills as $skill) {

            $body .= '

            <span class="badge bg-primary fs-6">

                '.htmlspecialchars($skill).'

            </span>

            ';

        }

        $body .= '</div>';

        return self::card(

            'مهارات القرن الحادي والعشرين',

            'bi-lightning-charge-fill',

            'primary',

            $body

        );
    }

    /*
    ============================================================
    القيم
    ============================================================
    */

    private static function values(array $json): string
    {
        $values = $json['values'] ?? [];

        $body = '<div class="d-flex flex-wrap gap-2">';

        foreach ($values as $value) {

            $body .= '

            <span class="badge bg-success fs-6">

                '.htmlspecialchars($value).'

            </span>

            ';

        }

        $body .= '</div>';

        return self::card(

            'القيم',

            'bi-heart-fill',

            'success',

            $body

        );
    }

    /*
    ============================================================
    التمايز
    ============================================================
    */

    private static function differentiation(array $json): string
    {
        $d = $json['differentiation'] ?? [];

        $body = '

        <div class="row">

            <div class="col-md-4">

                <div class="alert alert-success">

                    <h6>

                        الطلبة المتفوقون

                    </h6>

                    '.nl2br(htmlspecialchars($d['advanced'] ?? '')).'

                </div>

            </div>

            <div class="col-md-4">

                <div class="alert alert-primary">

                    <h6>

                        الطلبة المتوسطون

                    </h6>

                    '.nl2br(htmlspecialchars($d['average'] ?? '')).'

                </div>

            </div>

            <div class="col-md-4">

                <div class="alert alert-warning">

                    <h6>

                        الطلبة الذين يحتاجون دعماً

                    </h6>

                    '.nl2br(htmlspecialchars($d['support'] ?? '')).'

                </div>

            </div>

        </div>

        ';

        return self::card(

            'التمايز',

            'bi-people-fill',

            'warning',

            $body

        );
    }

    /*
    ============================================================
    التقويم الختامي
    ============================================================
    */

    private static function finalAssessment(array $json): string
    {
        $assessment = $json['final_assessment'] ?? [];

        $body = '';

        $body .= '<h5 class="text-primary">الأسئلة الشفهية</h5><ul>';

        foreach (($assessment['oral'] ?? []) as $item) {

            $body .= '<li>'.htmlspecialchars($item).'</li>';

        }

        $body .= '</ul>';

        $body .= '<hr>';

        $body .= '<h5 class="text-success">الأسئلة الكتابية</h5><ul>';

        foreach (($assessment['written'] ?? []) as $item) {

            $body .= '<li>'.htmlspecialchars($item).'</li>';

        }

        $body .= '</ul>';

        $body .= '<hr>';

        $body .= '

        <div class="alert alert-info">

            <strong>

                المهمة الأدائية

            </strong>

            <br><br>

            '.nl2br(htmlspecialchars($assessment['performance_task'] ?? '')).'

        </div>

        ';

        return self::card(

            'التقويم الختامي',

            'bi-clipboard-check-fill',

            'danger',

            $body

        );
    }

}
    
    
    