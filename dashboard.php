<?php

require_once 'core/Auth.php';

if(!Auth::check())
{
    header("Location: login.php");
    exit;
}

echo "<h1>Welcome</h1>";

echo $_SESSION['name'];