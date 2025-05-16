<?php
$host = "sql201.infinityfree.com";
$dbname = "if0_38979691_softexam_db";
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$user = "if0_38979691";
$pass = "ZnWDSmaNRFWjM";

// $host = "localhost";
// $dbname = "online_exam_db";
// $dsn = "mysql:host=$host;dbname=$dbname";
// $user = "root";
// $pass = "";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    throw $error;
}
