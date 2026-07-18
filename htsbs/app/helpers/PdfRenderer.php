<?php

require_once __DIR__ . '/LessonPlanRenderer.php';

class PdfRenderer
{
    /**
     * إنشاء HTML مخصص لـ mPDF
     */
    public static function render(
        array $lesson,
        array $lessonJson,
        array $school = []
    ): string {

        /*
        ==================================================
        School Information
        ==================================================
        */

        $schoolName   = $school['school_name']   ?? '';
        $ministryName = $school['ministry_name'] ?? '';
        $schoolLogo   = $school['school_logo']   ?? '';
        $moeLogo      = $school['moe_logo']      ?? '';
        $academicYear = $school['academic_year'] ?? '';

        $printDate = date('Y-m-d');
        $printTime = date('H:i');

        ob_start();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<style>

body{

    direction:rtl;

    font-family:cairo;

    font-size:13px;

    color:#222;

    line-height:1.9;

}

table{

    width:100%;

    border-collapse:collapse;

}

.header{

    border-bottom:2px solid #1565C0;

    padding-bottom:10px;

    margin-bottom:20px;

}

.logo{

    width:70px;

    height:70px;

}

.school-name{

    text-align:center;

    font-size:22px;

    font-weight:bold;

    color:#1565C0;

}

.ministry{

    text-align:center;

    font-size:13px;

    color:#666;

    margin-top:4px;

}

.title{

    text-align:center;

    font-size:18px;

    font-weight:bold;

    margin-top:12px;

    margin-bottom:15px;

}

.info{

    margin-top:15px;

    margin-bottom:20px;

}

.info td{

    border:1px solid #DDD;

    padding:8px;

}

.label{

    background:#F5F5F5;

    font-weight:bold;

    width:22%;

}

.section{

    border:1px solid #DDD;

    margin-bottom:18px;

}

.section-title{

    background:#1565C0;

    color:#FFF;

    font-weight:bold;

    padding:8px;

    font-size:14px;

}

.section-body{

    padding:12px;

}

ul{

    margin:0;

    padding-right:20px;

}

li{

    margin-bottom:6px;

}

.footer{

    margin-top:30px;

    border-top:1px solid #CCC;

    padding-top:8px;

    font-size:10px;

    color:#666;

    text-align:center;

}

.signature{

    margin-top:40px;

}

.signature td{

    text-align:center;

    padding-top:40px;

}

.page-break{

    page-break-after:always;

}

</style>

</head>

<body>

<div class="header">

<table>

<tr>

<td width="15%" align="center">

<?php if($moeLogo && file_exists($moeLogo)): ?>

<img src="<?= $moeLogo ?>" class="logo">

<?php endif; ?>

</td>

<td width="70%">

<div class="school-name">

<?= htmlspecialchars($schoolName) ?>

</div>

<div class="ministry">

<?= htmlspecialchars($ministryName) ?>

</div>

<div class="title">

تحضير درس باستخدام الذكاء الاصطناعي

</div>

</td>

<td width="15%" align="center">

<?php if($schoolLogo && file_exists($schoolLogo)): ?>

<img src="<?= $schoolLogo ?>" class="logo">

<?php endif; ?>

</td>

</tr>

</table>

<table class="info">

<tr>

<td class="label">المعلم</td>

<td><?= htmlspecialchars($lesson['full_name']) ?></td>

<td class="label">المادة</td>

<td><?= htmlspecialchars($lesson['course_name']) ?></td>

</tr>

<tr>

<td class="label">الصف</td>

<td><?= htmlspecialchars($lesson['class_name']) ?></td>

<td class="label">الوحدة</td>

<td><?= htmlspecialchars($lesson['unit_name']) ?></td>

</tr>

<tr>

<td class="label">عنوان الدرس</td>

<td><?= htmlspecialchars($lesson['lesson_title']) ?></td>

<td class="label">السنة الدراسية</td>

<td><?= htmlspecialchars($academicYear) ?></td>

</tr>

</table>

<?php

/*
==================================================
معلومات الدرس من JSON
==================================================
*/

$info = $lessonJson['lesson_info'] ?? [];

$objectives = $lessonJson['objectives'] ?? [];

$warmup = $lessonJson['warmup'] ?? [];

$introduction = $lessonJson['introduction'] ?? [];

?>

<!-- ==================================================
معلومات الدرس
================================================== -->

<div class="section">

<div class="section-title">

معلومات الدرس

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label">

المادة

</td>

<td>

<?= htmlspecialchars($info['subject'] ?? '') ?>

</td>

<td class="label">

الصف

</td>

<td>

<?= htmlspecialchars($info['grade'] ?? '') ?>

</td>

</tr>

<tr>

<td class="label">

الوحدة

</td>

<td>

<?= htmlspecialchars($info['unit'] ?? '') ?>

</td>

<td class="label">

مدة الحصة

</td>

<td>

<?= htmlspecialchars($info['duration'] ?? '') ?>

</td>

</tr>

</table>

</div>

</div>

<!-- ==================================================
أهداف التعلم
================================================== -->

<div class="section">

<div class="section-title">

أهداف التعلم

</div>

<div class="section-body">

<ul>

<?php foreach($objectives as $objective): ?>

<li>

<?= nl2br(htmlspecialchars($objective)) ?>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>

<!-- ==================================================
النشاط الاستهلالي
================================================== -->

<div class="section">

<div class="section-title">

النشاط الاستهلالي

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label">

عنوان النشاط

</td>

<td colspan="3">

<?= nl2br(htmlspecialchars($warmup['title'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

دور المعلم

</td>

<td>

<?= nl2br(htmlspecialchars($warmup['teacher_role'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

دور الطلبة

</td>

<td>

<?= nl2br(htmlspecialchars($warmup['student_role'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

الوسائل

</td>

<td>

<?= nl2br(htmlspecialchars($warmup['resources'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

الزمن

</td>

<td>

<?= htmlspecialchars($warmup['time'] ?? '') ?>

</td>

</tr>

</table>

</div>

</div>

<!-- ==================================================
مقدمة الدرس
================================================== -->

<div class="section">

<div class="section-title">

مقدمة الدرس

</div>

<div class="section-body">

<?= nl2br(htmlspecialchars($introduction['content'] ?? '')) ?>

</div>

</div>

<?php

/*
==================================================
الهدف الأول
==================================================
*/

$objective1 = $lessonJson['objective1'] ?? [];

/*
==================================================
الهدف الثاني
==================================================
*/

$objective2 = $lessonJson['objective2'] ?? [];

?>

<!-- ============================================= -->
<!-- الهدف الأول                                   -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

الهدف الأول

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label" width="20%">

الهدف

</td>

<td>

<?= nl2br(htmlspecialchars($objective1['goal'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

استراتيجية التدريس

</td>

<td>

<?= nl2br(htmlspecialchars($objective1['strategy'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

النشاط الفردي

</td>

<td>

<?= nl2br(htmlspecialchars($objective1['activity1'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

النشاط الجماعي

</td>

<td>

<?= nl2br(htmlspecialchars($objective1['activity2'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

التقويم

</td>

<td>

<?= nl2br(htmlspecialchars($objective1['assessment'] ?? '')) ?>

</td>

</tr>

</table>

</div>

</div>

<!-- ============================================= -->
<!-- الهدف الثاني                                  -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

الهدف الثاني

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label" width="20%">

الهدف

</td>

<td>

<?= nl2br(htmlspecialchars($objective2['goal'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

استراتيجية التدريس

</td>

<td>

<?= nl2br(htmlspecialchars($objective2['strategy'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

النشاط الفردي

</td>

<td>

<?= nl2br(htmlspecialchars($objective2['activity1'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

النشاط الجماعي

</td>

<td>

<?= nl2br(htmlspecialchars($objective2['activity2'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

التقويم

</td>

<td>

<?= nl2br(htmlspecialchars($objective2['assessment'] ?? '')) ?>

</td>

</tr>

</table>

</div>

</div>

<?php

/*
==================================================
الخاتمة
==================================================
*/

$conclusion = $lessonJson['conclusion'] ?? '';

/*
==================================================
الواجب
==================================================
*/

$homework = $lessonJson['homework'] ?? '';

/*
==================================================
الوسائل
==================================================
*/

$resources = $lessonJson['resources'] ?? [];

/*
==================================================
المهارات
==================================================
*/

$skills = $lessonJson['skills'] ?? [];

/*
==================================================
القيم
==================================================
*/

$values = $lessonJson['values'] ?? [];

?>

<!-- ============================================= -->
<!-- الخاتمة                                       -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

الخاتمة والتقويم الختامي

</div>

<div class="section-body">

<?= nl2br(htmlspecialchars($conclusion)) ?>

</div>

</div>

<!-- ============================================= -->
<!-- الواجب المنزلي                                -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

الواجب المنزلي

</div>

<div class="section-body">

<?= nl2br(htmlspecialchars($homework)) ?>

</div>

</div>

<!-- ============================================= -->
<!-- الوسائل التعليمية                              -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

الوسائل التعليمية

</div>

<div class="section-body">

<ul>

<?php foreach($resources as $item): ?>

<li>

<?= htmlspecialchars($item) ?>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>

<!-- ============================================= -->
<!-- المهارات                                      -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

المهارات المستهدفة

</div>

<div class="section-body">

<ul>

<?php foreach($skills as $item): ?>

<li>

<?= htmlspecialchars($item) ?>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>

<!-- ============================================= -->
<!-- القيم                                         -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

القيم والاتجاهات

</div>

<div class="section-body">

<ul>

<?php foreach($values as $item): ?>

<li>

<?= htmlspecialchars($item) ?>

</li>

<?php endforeach; ?>

</ul>

</div>

</div>

<?php

/*
==================================================
التمايز
==================================================
*/

$differentiation = $lessonJson['differentiation'] ?? [];

/*
==================================================
التقويم النهائي
==================================================
*/

$finalAssessment = $lessonJson['final_assessment'] ?? [];

/*
==================================================
ملاحظات المعلم
==================================================
*/

$teacherNotes = $lesson['notes'] ?? '';

?>

<!-- ============================================= -->
<!-- التمايز                                      -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

مراعاة الفروق الفردية (Differentiation)

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label" width="22%">

الطلبة المتقدمون

</td>

<td>

<?= nl2br(htmlspecialchars($differentiation['advanced'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

الطلبة متوسطو المستوى

</td>

<td>

<?= nl2br(htmlspecialchars($differentiation['average'] ?? '')) ?>

</td>

</tr>

<tr>

<td class="label">

الطلبة الذين يحتاجون دعماً

</td>

<td>

<?= nl2br(htmlspecialchars($differentiation['support'] ?? '')) ?>

</td>

</tr>

</table>

</div>

</div>

<!-- ============================================= -->
<!-- التقويم النهائي                              -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

التقويم النهائي

</div>

<div class="section-body">

<?php if(!empty($finalAssessment['oral'])): ?>

<b>أولاً: التقويم الشفهي</b>

<ul>

<?php foreach($finalAssessment['oral'] as $item): ?>

<li><?= htmlspecialchars($item) ?></li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

<?php if(!empty($finalAssessment['written'])): ?>

<b>ثانياً: التقويم التحريري</b>

<ul>

<?php foreach($finalAssessment['written'] as $item): ?>

<li><?= htmlspecialchars($item) ?></li>

<?php endforeach; ?>

</ul>

<?php endif; ?>

<?php if(!empty($finalAssessment['performance_task'])): ?>

<b>ثالثاً: المهمة الأدائية</b>

<div style="margin-top:8px;">

<?= nl2br(htmlspecialchars($finalAssessment['performance_task'])) ?>

</div>

<?php endif; ?>

</div>

</div>

<!-- ============================================= -->
<!-- ملاحظات المعلم                               -->
<!-- ============================================= -->

<?php if(!empty($teacherNotes)): ?>

<div class="section">

<div class="section-title">

ملاحظات المعلم

</div>

<div class="section-body">

<?= nl2br(htmlspecialchars($teacherNotes)) ?>

</div>

</div>

<?php endif; ?>

<?php

/*
==================================================
بيانات الذكاء الاصطناعي
==================================================
*/

$aiModel = $lesson['ai_model'] ?? '-';

$version = $lesson['version_no'] ?? 1;

$tokens = $lesson['tokens_used'] ?? 0;

$generationTime = $lesson['generation_time'] ?? 0;

$status = $lesson['status'] ?? 'Draft';

$printedCount = $lesson['printed_count'] ?? 0;

$pdfCount = $lesson['exported_pdf'] ?? 0;

$wordCount = $lesson['exported_word'] ?? 0;

$createdAt = $lesson['created_at'] ?? '';

$updatedAt = $lesson['updated_at'] ?? '';

?>

<!-- ============================================= -->
<!-- معلومات إنشاء التحضير                         -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

معلومات إنشاء التحضير

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label">

نموذج الذكاء الاصطناعي

</td>

<td>

<?= htmlspecialchars($aiModel) ?>

</td>

<td class="label">

الإصدار

</td>

<td>

<?= htmlspecialchars($version) ?>

</td>

</tr>

<tr>

<td class="label">

عدد Tokens

</td>

<td>

<?= number_format((int)$tokens) ?>

</td>

<td class="label">

مدة الإنشاء

</td>

<td>

<?= htmlspecialchars($generationTime) ?> ثانية

</td>

</tr>

<tr>

<td class="label">

الحالة

</td>

<td>

<?= htmlspecialchars($status) ?>

</td>

<td class="label">

عدد مرات الطباعة

</td>

<td>

<?= (int)$printedCount ?>

</td>

</tr>

<tr>

<td class="label">

عدد ملفات PDF

</td>

<td>

<?= (int)$pdfCount ?>

</td>

<td class="label">

عدد ملفات Word

</td>

<td>

<?= (int)$wordCount ?>

</td>

</tr>

<tr>

<td class="label">

تاريخ الإنشاء

</td>

<td>

<?= htmlspecialchars($createdAt) ?>

</td>

<td class="label">

آخر تعديل

</td>

<td>

<?= htmlspecialchars($updatedAt) ?>

</td>

</tr>

</table>

</div>

</div>

<!-- ============================================= -->
<!-- بيانات الطباعة                               -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

بيانات الطباعة

</div>

<div class="section-body">

<table class="info">

<tr>

<td class="label">

تاريخ الطباعة

</td>

<td>

<?= htmlspecialchars($printDate) ?>

</td>

<td class="label">

وقت الطباعة

</td>

<td>

<?= htmlspecialchars($printTime) ?>

</td>

</tr>

<tr>

<td class="label">

المدرسة

</td>

<td>

<?= htmlspecialchars($schoolName) ?>

</td>

<td class="label">

السنة الدراسية

</td>

<td>

<?= htmlspecialchars($academicYear) ?>

</td>

</tr>

<tr>

<td class="label">

اسم المعلم

</td>

<td>

<?= htmlspecialchars($lesson['full_name'] ?? '') ?>

</td>

<td class="label">

المادة

</td>

<td>

<?= htmlspecialchars($lesson['course_name'] ?? '') ?>

</td>

</tr>

</table>

</div>

</div>

<!-- ============================================= -->
<!-- ملاحظة نظام                                   -->
<!-- ============================================= -->

<div class="section">

<div class="section-title">

ملاحظة

</div>

<div class="section-body">

تم إنشاء هذا التحضير بواسطة نظام

<strong>AI Lesson Planner</strong>

ويعتمد على نموذج الذكاء الاصطناعي مع إمكانية قيام المعلم بتعديله قبل الاستخدام داخل الصف.

</div>

</div>

<?php

/*
==================================================
التوقيعات
==================================================
*/

?>

<!-- ============================================= -->
<!-- التوقيعات                                    -->
<!-- ============================================= -->

<div class="signature">

<table>

<tr>

<td width="33%" align="center">

<div style="margin-top:55px;">

....................................

</div>

<div style="margin-top:8px;font-weight:bold;">

المعلم

</div>

</td>

<td width="34%" align="center">

<div style="margin-top:55px;">

....................................

</div>

<div style="margin-top:8px;font-weight:bold;">

رئيس القسم

</div>

</td>

<td width="33%" align="center">

<div style="margin-top:55px;">

....................................

</div>

<div style="margin-top:8px;font-weight:bold;">

مدير المدرسة

</div>

</td>

</tr>

</table>

</div>

<!-- ============================================= -->
<!-- فاصل                                          -->
<!-- ============================================= -->

<div style="margin-top:35px;"></div>

<hr>

<!-- ============================================= -->
<!-- Footer                                        -->
<!-- ============================================= -->

<table width="100%">

<tr>

<td width="35%" align="right">

<strong>

<?= htmlspecialchars($schoolName) ?>

</strong>

<br>

<?= htmlspecialchars($ministryName) ?>

</td>

<td width="30%" align="center">

AI Lesson Planner

<br>

Academic Year

<?= htmlspecialchars($academicYear) ?>

</td>

<td width="35%" align="left">

Printed

<?= htmlspecialchars($printDate) ?>

<br>

<?= htmlspecialchars($printTime) ?>

</td>

</tr>

</table>

<!-- ============================================= -->
<!-- معلومات إضافية                               -->
<!-- ============================================= -->

<div class="footer">

تم إنشاء هذا الملف بواسطة نظام

<strong>AI Lesson Planner</strong>

<br>

جميع البيانات محفوظة داخل قاعدة البيانات ويمكن إعادة إنشاء ملف PDF أو Word في أي وقت.

<br><br>

Version :

<strong>

<?= htmlspecialchars($version) ?>

</strong>

&nbsp;&nbsp;|&nbsp;&nbsp;

Status :

<strong>

<?= htmlspecialchars($status) ?>

</strong>

&nbsp;&nbsp;|&nbsp;&nbsp;

Printed :

<strong>

<?= (int)$printedCount ?>

</strong>

&nbsp;&nbsp;|&nbsp;&nbsp;

PDF :

<strong>

<?= (int)$pdfCount ?>

</strong>

&nbsp;&nbsp;|&nbsp;&nbsp;

Word :

<strong>

<?= (int)$wordCount ?>

</strong>

</div>

<!-- ============================================= -->
<!-- نهاية الصفحة                                 -->
<!-- ============================================= -->

<div style="page-break-after:avoid;"></div>
</body>

</html>

<?php

        /*
        ==================================================
        Return HTML
        ==================================================
        */

        return ob_get_clean();

    }

}




