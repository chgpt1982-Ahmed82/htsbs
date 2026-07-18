<?php

require 'vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'xbriyaz'
]);

$html = '

<html dir="rtl">

<head>

<meta charset="UTF-8">

<style>

body{
    direction:rtl;
    text-align:right;
    font-family:xbriyaz;
}

h1{
    text-align:center;
}

</style>

</head>

<body>

<h1>
تقرير السلوك الطلابي
</h1>

<p>
مرحباً بكم في نظام إدارة التعلم
</p>

</body>

</html>

';

$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

$mpdf->WriteHTML($html);

$mpdf->Output();