<?php

class Database
{
    private $host = "localhost";
    private $dbname = "u922823540_htsbs";
    private $username = "u922823540_htsbs";
    private $password = "Hanan@7elwa";

    public function connect()
    {
        try {

            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password
            );

            $pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            /*
            ==========================
            توقيت البحرين
            ==========================
            */

            date_default_timezone_set('Asia/Bahrain');

            $pdo->exec("
                SET time_zone = '+03:00'
            ");

            return $pdo;

        } catch(PDOException $e) {

            die(
                "Database Connection Failed: "
                . $e->getMessage()
            );

        }
    }
}