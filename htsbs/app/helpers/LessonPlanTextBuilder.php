<?php

class LessonPlanTextBuilder
{
    /*
    ==================================================
    تحويل JSON إلى نص
    ==================================================
    */

    public static function build(array $lesson): string
    {
        $text = '';

        $text .= self::lessonInfo($lesson);

        $text .= self::objectives($lesson);

        $text .= self::warmup($lesson);

        $text .= self::introduction($lesson);

        $text .= self::objectiveOne($lesson);

        $text .= self::objectiveTwo($lesson);

        $text .= self::conclusion($lesson);

        $text .= self::homework($lesson);

        $text .= self::resources($lesson);

        $text .= self::skills($lesson);

        $text .= self::values($lesson);

        $text .= self::differentiation($lesson);

        $text .= self::finalAssessment($lesson);

        return $text;
    }

    /*
    ==================================================
    معلومات الدرس
    ==================================================
    */

    private static function lessonInfo(array $lesson): string
    {
        $info = $lesson['lesson_info'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "معلومات الدرس\n";
        $text .= "==================================================\n\n";

        $text .= "المادة : "
            . ($info['subject'] ?? '') . "\n";

        $text .= "الصف : "
            . ($info['grade'] ?? '') . "\n";

        $text .= "الوحدة : "
            . ($info['unit'] ?? '') . "\n";

        $text .= "عنوان الدرس : "
            . ($info['lesson_title'] ?? '') . "\n";

        $text .= "مدة الحصة : "
            . ($info['duration'] ?? '') . "\n\n";

        return $text;
    }

    /*
    ==================================================
    أهداف التعلم
    ==================================================
    */

    private static function objectives(array $lesson): string
    {
        $objectives = $lesson['objectives'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "أهداف التعلم\n";
        $text .= "==================================================\n\n";

        foreach ($objectives as $index => $objective) {

            $text .= ($index + 1)

                . "- "

                . $objective

                . "\n";

        }

        $text .= "\n";

        return $text;
    }
    
      /*
    ==================================================
    النشاط الاستهلالي
    ==================================================
    */

    private static function warmup(array $lesson): string
    {
        $warmup = $lesson['warmup'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "النشاط الاستهلالي\n";
        $text .= "==================================================\n\n";

        $text .= "النشاط : "
            . ($warmup['title'] ?? '')
            . "\n\n";

        $text .= "دور المعلم :\n"
            . ($warmup['teacher_role'] ?? '')
            . "\n\n";

        $text .= "دور الطلبة :\n"
            . ($warmup['student_role'] ?? '')
            . "\n\n";

        $text .= "الوسائل : "
            . ($warmup['resources'] ?? '')
            . "\n";

        $text .= "الزمن : "
            . ($warmup['time'] ?? '')
            . "\n\n";

        return $text;
    }

    /*
    ==================================================
    مقدمة الدرس
    ==================================================
    */

    private static function introduction(array $lesson): string
    {
        $intro = $lesson['introduction'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "مقدمة الدرس\n";
        $text .= "==================================================\n\n";

        $text .= ($intro['content'] ?? '');

        $text .= "\n\n";

        return $text;
    }

    /*
    ==================================================
    الهدف الأول
    ==================================================
    */

    private static function objectiveOne(array $lesson): string
    {
        $obj = $lesson['objective1'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "الهدف الأول\n";
        $text .= "==================================================\n\n";

        $text .= "الهدف:\n";

        $text .= ($obj['goal'] ?? '') . "\n\n";

        $text .= "استراتيجية التدريس:\n";

        $text .= ($obj['strategy'] ?? '') . "\n\n";

        $text .= "النشاط الأول:\n";

        $text .= ($obj['activity1'] ?? '') . "\n\n";

        $text .= "النشاط الثاني:\n";

        $text .= ($obj['activity2'] ?? '') . "\n\n";

        $text .= "التقويم:\n";

        $text .= ($obj['assessment'] ?? '') . "\n\n";

        return $text;
    }

    /*
    ==================================================
    الهدف الثاني
    ==================================================
    */

    private static function objectiveTwo(array $lesson): string
    {
        $obj = $lesson['objective2'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "الهدف الثاني\n";
        $text .= "==================================================\n\n";

        $text .= "الهدف:\n";

        $text .= ($obj['goal'] ?? '') . "\n\n";

        $text .= "استراتيجية التدريس:\n";

        $text .= ($obj['strategy'] ?? '') . "\n\n";

        $text .= "النشاط الأول:\n";

        $text .= ($obj['activity1'] ?? '') . "\n\n";

        $text .= "النشاط الثاني:\n";

        $text .= ($obj['activity2'] ?? '') . "\n\n";

        $text .= "التقويم:\n";

        $text .= ($obj['assessment'] ?? '') . "\n\n";

        return $text;
    }
    
        /*
    ==================================================
    الخاتمة
    ==================================================
    */

    private static function conclusion(array $lesson): string
    {
        $text = '';

        $text .= "==================================================\n";
        $text .= "الخاتمة\n";
        $text .= "==================================================\n\n";

        $text .= ($lesson['conclusion'] ?? '');

        $text .= "\n\n";

        return $text;
    }

    /*
    ==================================================
    الواجب المنزلي
    ==================================================
    */

    private static function homework(array $lesson): string
    {
        $text = '';

        $text .= "==================================================\n";
        $text .= "الواجب المنزلي\n";
        $text .= "==================================================\n\n";

        $text .= ($lesson['homework'] ?? '');

        $text .= "\n\n";

        return $text;
    }

    /*
    ==================================================
    وسائل التعليم
    ==================================================
    */

    private static function resources(array $lesson): string
    {
        $resources = $lesson['resources'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "وسائل التعليم\n";
        $text .= "==================================================\n\n";

        foreach ($resources as $item) {

            $text .= "- " . $item . "\n";

        }

        $text .= "\n";

        return $text;
    }

    /*
    ==================================================
    مهارات القرن الحادي والعشرين
    ==================================================
    */

    private static function skills(array $lesson): string
    {
        $skills = $lesson['skills'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "مهارات القرن الحادي والعشرين\n";
        $text .= "==================================================\n\n";

        foreach ($skills as $item) {

            $text .= "- " . $item . "\n";

        }

        $text .= "\n";

        return $text;
    }

    /*
    ==================================================
    القيم
    ==================================================
    */

    private static function values(array $lesson): string
    {
        $values = $lesson['values'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "القيم\n";
        $text .= "==================================================\n\n";

        foreach ($values as $item) {

            $text .= "- " . $item . "\n";

        }

        $text .= "\n";

        return $text;
    }

    /*
    ==================================================
    التمايز
    ==================================================
    */

    private static function differentiation(array $lesson): string
    {
        $d = $lesson['differentiation'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "التمايز\n";
        $text .= "==================================================\n\n";

        $text .= "الطلبة المتفوقون:\n";
        $text .= ($d['advanced'] ?? '') . "\n\n";

        $text .= "الطلبة المتوسطون:\n";
        $text .= ($d['average'] ?? '') . "\n\n";

        $text .= "الطلبة الذين يحتاجون دعماً:\n";
        $text .= ($d['support'] ?? '') . "\n\n";

        return $text;
    }

    /*
    ==================================================
    التقويم الختامي
    ==================================================
    */

    private static function finalAssessment(array $lesson): string
    {
        $assessment = $lesson['final_assessment'] ?? [];

        $text = '';

        $text .= "==================================================\n";
        $text .= "التقويم الختامي\n";
        $text .= "==================================================\n\n";

        $text .= "الأسئلة الشفهية:\n";

        foreach (($assessment['oral'] ?? []) as $item) {

            $text .= "- " . $item . "\n";

        }

        $text .= "\n";

        $text .= "الأسئلة الكتابية:\n";

        foreach (($assessment['written'] ?? []) as $item) {

            $text .= "- " . $item . "\n";

        }

        $text .= "\n";

        $text .= "المهمة الأدائية:\n";

        $text .= ($assessment['performance_task'] ?? '');

        $text .= "\n\n";

        return $text;
    }

}
    
    