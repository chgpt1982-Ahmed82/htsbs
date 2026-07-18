<?php

session_start();

require_once '../config/config.php';
require_once __DIR__ . '/Auth.php';      // ← كان مفقوداً (خطأ قاتل)
require_once __DIR__ . '/Logger.php';

// ✅ نسجّل الخروج والجلسة ما زالت حيّة (لنعرف من خرج)
Logger::logout();

// ثم نهدم الجلسة
$_SESSION = [];
session_destroy();

header("Location: " . BASE_URL . "/login.php");
exit;