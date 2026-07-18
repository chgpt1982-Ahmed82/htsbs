<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| JsonValidator
|--------------------------------------------------------------------------
| AI Lesson Planner
|--------------------------------------------------------------------------
*/

class JsonValidator
{
    /**
     * الأقسام المطلوبة
     */
    private array $requiredSections = [

        'lesson_info',

        'objectives',

        'warmup',

        'introduction',

        'objective1',

        'objective2',

        'conclusion',

        'homework',

        'resources',

        'skills',

        'values',

        'differentiation',

        'final_assessment'

    ];

    /*
    =====================================================
    Validate AI JSON
    =====================================================
    */

    public function validate(string $jsonText): array
    {

        $jsonText = $this->cleanMarkdown($jsonText);

        $lesson = json_decode(

            $jsonText,

            true

        );

        if (

            json_last_error() !== JSON_ERROR_NONE

        ) {

            throw new Exception(

                'JSON Error : '

                .

                json_last_error_msg()

            );

        }

        if (

            !is_array($lesson)

        ) {

            throw new Exception(

                'الاستجابة ليست JSON صالح.'

            );

        }

        $this->checkSections(

            $lesson

        );

        $this->normalize(

            $lesson

        );

        return $lesson;

    }

    /*
    =====================================================
    Remove Markdown
    =====================================================
    */

    private function cleanMarkdown(
        string $text
    ): string
    {

        $text = trim($text);

        $text = preg_replace(

            '/^```json/i',

            '',

            $text

        );

        $text = preg_replace(

            '/^```/',

            '',

            $text

        );

        $text = preg_replace(

            '/```$/',

            '',

            $text

        );

        return trim($text);

    }

    /*
    =====================================================
    Check Required Sections
    =====================================================
    */

    private function checkSections(
        array $lesson
    ): void
    {

        foreach (

            $this->requiredSections

            as $section

        ) {

            if (

                !array_key_exists(

                    $section,

                    $lesson

                )

            ) {

                throw new Exception(

                    "القسم {$section} غير موجود."

                );

            }

        }

    }

    /*
    =====================================================
    Normalize JSON
    =====================================================
    */

    private function normalize(
        array &$lesson
    ): void
    {

        /*
        lesson_info
        */

        $lesson['lesson_info'] ??= [];

        $lesson['lesson_info']['subject'] ??= '';

        $lesson['lesson_info']['grade'] ??= '';

        $lesson['lesson_info']['unit'] ??= '';

        $lesson['lesson_info']['lesson_title'] ??= '';

        $lesson['lesson_info']['duration'] ??= '';

        /*
        Arrays
        */

        $this->ensureArray(

            $lesson,

            'objectives'

        );

        $this->ensureArray(

            $lesson,

            'resources'

        );

        $this->ensureArray(

            $lesson,

            'skills'

        );

        $this->ensureArray(

            $lesson,

            'values'

        );

        /*
        Warmup
        */

        $lesson['warmup'] ??= [];

        $lesson['warmup']['title'] ??= '';

        $lesson['warmup']['teacher_role'] ??= '';

        $lesson['warmup']['student_role'] ??= '';

        $lesson['warmup']['resources'] ??= '';

        $lesson['warmup']['time'] ??= '';

        /*
        Introduction
        */

        $lesson['introduction'] ??= [];

        $lesson['introduction']['content'] ??= '';

        /*
        Objective1
        */

        $lesson['objective1'] ??= [];

        $this->normalizeObjective(

            $lesson['objective1']

        );

        /*
        Objective2
        */

        $lesson['objective2'] ??= [];

        $this->normalizeObjective(

            $lesson['objective2']

        );

        /*
        Differentiation
        */

        $lesson['differentiation'] ??= [];

        $lesson['differentiation']['advanced'] ??= '';

        $lesson['differentiation']['average'] ??= '';

        $lesson['differentiation']['support'] ??= '';

        /*
        Final Assessment
        */

        $lesson['final_assessment'] ??= [];

        $lesson['final_assessment']['oral'] ??= [];

        $lesson['final_assessment']['written'] ??= [];

        $lesson['final_assessment']['performance_task'] ??= '';

        /*
        Strings
        */

        $lesson['conclusion'] ??= '';

        $lesson['homework'] ??= '';

    }

    /*
    =====================================================
    Normalize Objective
    =====================================================
    */

    private function normalizeObjective(
        array &$objective
    ): void
    {

        $objective['goal'] ??= '';

        $objective['strategy'] ??= '';

        $objective['activity1'] ??= '';

        $objective['activity2'] ??= '';

        $objective['assessment'] ??= '';

    }

    /*
    =====================================================
    Ensure Array
    =====================================================
    */

    private function ensureArray(
        array &$lesson,
        string $key
    ): void
    {

        if (

            !isset($lesson[$key])

        ) {

            $lesson[$key] = [];

            return;

        }

        if (

            !is_array(

                $lesson[$key]

            )

        ) {

            $lesson[$key] = [

                $lesson[$key]

            ];

        }

    }

}