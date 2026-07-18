<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Html Builder
|--------------------------------------------------------------------------
| AI Lesson Planner
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../helpers/LessonPlanRenderer.php';
require_once __DIR__ . '/../../helpers/LessonPlanTextBuilder.php';

class HtmlBuilder
{
    /*
    =====================================================
    Build Lesson
    =====================================================
    */

    public function build(array $lessonJson): array
    {

        if (empty($lessonJson)) {

            throw new Exception(

                'Lesson JSON is empty.'

            );

        }

        /*
        ==============================================
        Lesson Text
        ==============================================
        */

        $lessonPlan = LessonPlanTextBuilder::build(

            $lessonJson

        );

        if (trim($lessonPlan) === '') {

            throw new Exception(

                'فشل إنشاء النص.'

            );

        }

        /*
        ==============================================
        HTML
        ==============================================
        */

        $lessonPlanHtml = LessonPlanRenderer::render(

            $lessonJson,

            [

                'mode' => 'view'

            ]

        );

        if (trim($lessonPlanHtml) === '') {

            throw new Exception(

                'فشل إنشاء HTML.'

            );

        }

        /*
        ==============================================
        JSON
        ==============================================
        */

        $lessonPlanJson = json_encode(

            $lessonJson,

            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT

        );

        if ($lessonPlanJson === false) {

            throw new Exception(

                'فشل تحويل JSON.'

            );

        }

        /*
        ==============================================
        Return
        ==============================================
        */

        return [

            'lesson_plan' => $lessonPlan,

            'lesson_plan_html' => $lessonPlanHtml,

            'lesson_plan_json' => $lessonPlanJson

        ];

    }

}