<?php
$host = "localhost";
$db_name = "exam_user";
$dsn = "mysql:host=$host;dbname=$db_name;";
$user = "root";
$pass = "";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    throw $error;
}
