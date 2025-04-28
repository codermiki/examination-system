<?php
include_once 'config.php';

if (!isset($_SESSION["email"]) || !isset($_SESSION["role"])) {
    header('Location: login.php');
}

if ($_SESSION["role"] == 'student') {
    header('Location: ./student');
}

if ($_SESSION["role"] == 'instructor') {
    header('Location: ./instructor');
}

if ($_SESSION["role"] == 'admin') {
    header('Location: ./admin');
}
