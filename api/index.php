<?php
require_once "controllers/User_controller.php";
require_once "controllers/Course_controller.php";
require_once "controllers/Student_controller.php";
require_once "helpers/Response_helper.php";

$method = $_SERVER['REQUEST_METHOD'];


$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace("/softexam/api", "", $path);

switch ("$method $path") {
    case "GET /users":
        User_controller::getAllUsers();
        break;

    case "POST /addCourse":
        $data = json_decode(file_get_contents("php://input"), true);
        $year = $data['year'] ?? null;
        $semester = $data['semester'] ?? null;
        $course_ids = $data['course_ids'] ?? [];
        Course_controller::addCourse($year, $semester, $course_ids);
        break;

    case "POST /updateCourse":
        $data = json_decode(file_get_contents("php://input"), true);
        $year = $data['year'] ?? null;
        $semester = $data['semester'] ?? null;
        $course_id = $data['course_id'];
        Course_controller::updateCourse($year, $semester, $course_id);
        break;

    case "POST /assignStudent":
        $data = json_decode(file_get_contents("php://input"), true);
        $course_id = $data['course_id'] ?? null;
        $student_ids = $data['student_ids'] ?? [];

        if (!$course_id || empty($student_ids)) {
            Response_helper::json(['error' => 'Course ID and student list are required']);
            exit;
        }
        Student_controller::assignStudent($course_id, $student_ids);
        break;

    case "POST /updateAssignedStudent":
        $data = json_decode(file_get_contents("php://input"), true);
        $course_id = $data['course_id'] ?? null;
        $student_id = $data['student_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$course_id || !$student_id || !$status) {
            Response_helper::json(['error' => 'All fields are required']);
            exit;
        }
        Student_controller::updateAssignedStudent($course_id, $student_id, $status);
        break;

    case "POST /unassignStudent":
        $data = json_decode(file_get_contents("php://input"), true);
        $student_id = $data['student_id'] ?? null;
        $course_id = $data['course_id'] ?? null;

        if (!$student_id || !$course_id) {
            Response_helper::json(['error' => 'Student id and Course id is required']);
            exit;
        }
        Student_controller::unassignStudent($student_id, $course_id);
        break;

    case "POST /assignInstructor":
        $data = json_decode(file_get_contents("php://input"), true);
        $instructor = $data['instructor'] ?? null;
        $course_id = $data['course_id'] ?? null;
        Instructor_controller::assignInstructor($instructor, $course_id);
        break;

    default:
        Response_helper::json(['error' => 'Invalid Request'], 401);
        break;
}
