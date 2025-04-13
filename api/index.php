<?php
require_once "controllers/User_controller.php";
require_once "helpers/Response_helper.php";

$method = $_SERVER['REQUEST_METHOD'];


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace("/softexam/api", "", $path);

switch ("$method $path") {
    case "GET /users":
        User_controller::getAllUsers();
        break;
    default:
        Response_helper::json(['error' => 'Invalid Request'], 401);
        break;
}
