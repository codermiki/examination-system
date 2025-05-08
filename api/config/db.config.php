<?php
$host = "localhost";
$db_name = "softexam_db";
$dsn = "mysql:host=$host;dbname=$db_name;";
$user = "root";
$pass = "";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    die(json_encode(['error' => 'Database connection failed']));
}
