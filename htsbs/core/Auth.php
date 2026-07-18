<?php

require_once __DIR__ . '/Session.php';

class Auth
{
    public static function login($user)
    {
        Session::start();

        Session::set('user_id',$user['id']);
        Session::set('role_id',$user['role_id']);
        Session::set('name',$user['full_name']);
    }

    public static function check()
    {
        Session::start();

        return isset($_SESSION['user_id']);
    }

    public static function logout()
    {
        Session::start();

        session_unset();

        session_destroy();
    }
}