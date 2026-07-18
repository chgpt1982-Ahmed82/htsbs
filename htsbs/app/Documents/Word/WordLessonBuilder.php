<?php

declare(strict_types=1);


use PhpOffice\PhpWord\Element\Section;

class WordLessonBuilder
{
    /**
     * Build Lesson
     */
    public static function build(
        Section $section,
        array $lesson
    ): void {


InfoTable::render(

    $section,

    $lesson

);
        /*
        ==================================================
        الأهداف
        ==================================================
        */

        ObjectivesSection::build(

            $section,

            $lesson

        );

        /*
        ==================================================
        التمهيد
        ==================================================
        */

        WarmupSection::build(

            $section,

            $lesson

        );

        /*
        ==================================================
        المقدمة
        ==================================================
        */

        IntroductionSection::build(

            $section,

            $lesson

        );

        /*
        ==================================================
        الأنشطة
        ==================================================
        */

        ActivitiesSection::build(

            $section,

            $lesson

        );

        /*
        ==================================================
        الأقسام الاختيارية
        ==================================================
        */

        if (class_exists('AssessmentSection')) {

            AssessmentSection::build(

                $section,

                $lesson

            );

        }

        if (class_exists('ResourcesSection')) {

            ResourcesSection::build(

                $section,

                $lesson

            );

        }

        if (class_exists('SkillsSection')) {

            SkillsSection::build(

                $section,

                $lesson

            );

        }

        if (class_exists('ValuesSection')) {

            ValuesSection::build(

                $section,

                $lesson

            );

        }

        if (class_exists('HomeworkSection')) {

            HomeworkSection::build(

                $section,

                $lesson

            );

        }

        if (class_exists('ConclusionSection')) {

            ConclusionSection::build(

                $section,

                $lesson

            );

        }

        if (class_exists('FinalAssessmentSection')) {

            FinalAssessmentSection::build(

                $section,

                $lesson

            );

        }

    }
}