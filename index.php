<?php
include_once 'config.php';

if (!isset($_SESSION["username"]) || !isset($_SESSION["role"])) {
    header('Location: login.php');
}

if ($_SESSION["role"] == 1) {
    header('Location: ./student');
}

if ($_SESSION["role"] == 2) {
    header('Location: ./instructor');
}

if ($_SESSION["role"] == 3) {
    header('Location: ./admin');
}
