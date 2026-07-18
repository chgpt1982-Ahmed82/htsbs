<?php

declare(strict_types=1);

echo file_exists('../../app/Services/AI/PromptBuilder.php')
    ? 'YES'
    : 'NO';

/*
|--------------------------------------------------------------------------
| Prompt Builder
|--------------------------------------------------------------------------
| AI Lesson Planner
| Bahrain Ministry of Education
|--------------------------------------------------------------------------
*/

class PromptBuilder
{
    /**
     * إنشاء جميع الـ Prompts
     */
    public function build(array $data): array
    {
        return [

            'system' => $this->buildSystemPrompt(),

            'user'   => $this->buildUserPrompt($data)

        ];
    }

    /**
     * System Prompt
     */
    private function buildSystemPrompt(): string
    {
        return <<<PROMPT

أنت خبير تربوي متخصص في إعداد تحاضير الدروس وفق منهج وزارة التربية والتعليم بمملكة البحرين.

يجب الالتزام بالتعليمات التالية بدقة:

1- أرجع JSON صالح فقط.
2- لا تستخدم Markdown.
3- لا تكتب ```json.
4- لا تكتب أي شرح قبل أو بعد JSON.
5- لا تضف أي نص خارج JSON.
6- استخدم اللغة العربية الفصحى.
7- اجعل الأنشطة تفاعلية.
8- استخدم التعلم النشط.
9- اربط التقويم بالأهداف.
10- راع الفروق الفردية.
11- اجعل المحتوى مناسباً للمرحلة الدراسية.
12- لا تترك أي عنصر فارغ.
13- استخدم استراتيجيات تدريس حديثة.
14- اجعل الأنشطة قابلة للتطبيق داخل الصف.
15- اجعل التقويم متنوعاً.
16- استخدم مهارات القرن الحادي والعشرين.
17- أضف القيم الوطنية والأخلاقية المناسبة.
18- اجعل جميع المخرجات باللغة العربية.

أرجع الهيكل التالي فقط:

{

"lesson_info":{

"subject":"",
"grade":"",
"unit":"",
"lesson_title":"",
"duration":""

},

"objectives":[

"",
"",
""

],

"warmup":{

"title":"",
"teacher_role":"",
"student_role":"",
"resources":"",
"time":""

},

"introduction":{

"content":""

},

"objective1":{

"goal":"",
"strategy":"",
"activity1":"",
"activity2":"",
"assessment":""

},

"objective2":{

"goal":"",
"strategy":"",
"activity1":"",
"activity2":"",
"assessment":""

},

"conclusion":"",

"homework":"",

"resources":[

"",
""

],

"skills":[

"",
""

],

"values":[

"",
""

],

"differentiation":{

"advanced":"",
"average":"",
"support":""

},

"final_assessment":{

"oral":[

"",
""

],

"written":[

"",
""

],

"performance_task":""

}

}

لا تضف أي حقول إضافية.

أرجع JSON فقط.

PROMPT;
    }
     /**
     * إنشاء User Prompt
     */
    private function buildUserPrompt(array $data): string
    {
        $subject = $data['subject_name']
            ?? $data['subject']
            ?? $data['subject_id']
            ?? '';

        $grade = $data['class_name']
            ?? $data['grade']
            ?? $data['class_id']
            ?? '';

        $unit = $data['unit_name'] ?? '';

        $lessonTitle = $data['lesson_title'] ?? '';

        $lessonDescription = $data['lesson_description'] ?? '';

        $learningOutcomes = $data['learning_outcomes'] ?? '';

        $keywords = $data['keywords'] ?? '';

        $lessonDuration = $data['lesson_duration'] ?? '45';

        $studentLevel = $data['student_level'] ?? 'متوسط';

        $resources = $data['resources'] ?? [];

        if (is_array($resources)) {

            $resources = implode('، ', $resources);

        }

        $additionalInstructions =
            $data['additional_instructions']
            ?? $data['ai_prompt']
            ?? '';

        return <<<PROMPT

أنشئ تحضير درس احترافي وفق البيانات التالية:

============================

المادة:
{$subject}

الصف:
{$grade}

الوحدة:
{$unit}

عنوان الدرس:
{$lessonTitle}

============================

وصف الدرس:

{$lessonDescription}

============================

نواتج التعلم:

{$learningOutcomes}

============================

الكلمات المفتاحية:

{$keywords}

============================

مدة الحصة:

{$lessonDuration} دقيقة

============================

مستوى الطلبة:

{$studentLevel}

============================

الوسائل التعليمية:

{$resources}

============================

تعليمات إضافية:

{$additionalInstructions}

============================

المطلوب:

- أنشئ تحضيراً احترافياً.
- اجعل الأهداف بصيغة SMART.
- استخدم التعلم النشط.
- استخدم استراتيجيات حديثة.
- اجعل الأنشطة متنوعة.
- اجعل التقويم مرتبطاً بالأهداف.
- أضف التقويم البنائي والختامي.
- أضف الواجب المنزلي.
- أضف مهارات القرن الحادي والعشرين.
- أضف القيم المناسبة.
- راع الفروق الفردية.
- أرجع JSON فقط.

PROMPT;
    }

}   
    