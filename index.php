<?php
include_once 'config.php';

if (!isset($_SESSION["email"]) || !isset($_SESSION["role"])) {
    header('Location: login.php');
}

if ($_SESSION["role"] == 'Student') {
    header('Location: ./student');
}

if ($_SESSION["role"] == 'Instructor') {
    header('Location: ./instructor');
}

if ($_SESSION["role"] == 'Admin') {
    header('Location: ./admin');
}
