<?php
require_once "controllers/User_controller.php";
require_once "controllers/Course_controller.php";
require_once "helpers/Response_helper.php";

$method = $_SERVER['REQUEST_METHOD'];


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace("/softexam/api", "", $path);

switch ("$method $path") {
    case "GET /users":
        User_controller::getAllUsers();
        break;
        
    case "POST /addCourse":
        header("Content-Type: application/json");
        $data = json_decode(file_get_contents("php://input"), true);
        $year = $data['year'] ?? null;
        $semester = $data['semester'] ?? null;
        $course_ids = $data['course_ids'] ?? [];
        Course_controller::addCourse($year, $semester, $course_ids);
        break;
    default:
        Response_helper::json(['error' => 'Invalid Request'], 401);
        break;
}
