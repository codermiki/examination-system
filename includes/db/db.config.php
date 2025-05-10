<?php
$host = "localhost";
$db_name = "online_exam_db";
$dsn = "mysql:host=$host;dbname=$db_name;";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    throw $error;
}
