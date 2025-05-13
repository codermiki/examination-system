<?php
require_once "controllers/User_controller.php";
require_once "controllers/Course_controller.php";
require_once "controllers/Student_controller.php";
require_once "controllers/Instructor_controller.php";
require_once "controllers/Exam_controller.php";
require_once "helpers/Response_helper.php";

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace("/softexam/api", "", $path);

switch ("$method $path") {
    case "GET /install":
        User_controller::install();
        break;

    // courses route handler

    case "POST /addCourse":
        $data = json_decode(file_get_contents("php://input"), true);
        $courses = $data['courses'] ?? [];
        Course_controller::addCourse($courses);
        break;

    case "POST /deleteCourse":
        $data = json_decode(file_get_contents("php://input"), true);
        $course_id = $data['course_id'];
        Course_controller::deleteCourse($course_id);
        break;

    case "POST /assignStudent":
        $data = json_decode(file_get_contents("php://input"), true);
        $course_id = $data['course_id'] ?? null;
        $student_ids = $data['student_ids'] ?? [];

        // Student route handler

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

    // Instructor route handler

    case "POST /assignInstructor":
        $data = json_decode(file_get_contents("php://input"), true);
        $instructor = $data['instructor'] ?? null;
        $course_id = $data['course_id'] ?? null;
        Instructor_controller::assignInstructor($instructor, $course_id);
        break;

    case "POST /updateAssignedInstructor":
        $data = json_decode(file_get_contents("php://input"), true);
        $course_id = $data['course_id'] ?? null;
        $instructor_id = $data['instructor_id'] ?? null;
        $status = $data['status'] ?? null;

        if (!$course_id || !$instructor_id || !$status) {
            Response_helper::json(['error' => 'All fields are required']);
            exit;
        }
        Instructor_controller::updateAssignedInstructor($course_id, $instructor_id, $status);
        break;

    case "POST /unassignInstructor":
        $data = json_decode(file_get_contents("php://input"), true);
        $instructor_id = $data['instructor_id'] ?? null;
        $course_id = $data['course_id'] ?? null;

        if (!$instructor_id || !$course_id) {
            Response_helper::json(['error' => 'Instructor id and Course id is required']);
            exit;
        }
        Instructor_controller::unassignInstructor($instructor_id, $course_id);
        break;

    // Exam schedule route handler

    case "POST /scheduleExam":
        $data = json_decode(file_get_contents("php://input"), true);
        $exam_id = $data['exam_id'] ?? null;
        $scheduled_date = $data['scheduled_date'] ?? null;

        if (!$exam_id || !$scheduled_date) {
            Response_helper::json(['error' => 'Exam id and Scheduled date is required']);
            exit;
        }
        Exam_controller::scheduleExam($exam_id, $scheduled_date);
        break;

    case "POST /updateExamSchedule":
        $data = json_decode(file_get_contents("php://input"), true);
        $exam_id = $data['exam_id'] ?? null;
        $scheduled_date = $data['scheduled_date'] ?? null;

        if (!$exam_id || !$scheduled_date) {
            Response_helper::json(['error' => 'Exam id and Scheduled date is required']);
            exit;
        }
        Exam_controller::updateExamSchedule($exam_id, $scheduled_date);
        break;

    case "POST /deleteSchedule":
        $data = json_decode(file_get_contents("php://input"), true);
        $exam_id = $data['exam_id'] ?? null;

        if (!$exam_id) {
            Response_helper::json(['error' => 'Exam id is required']);
            exit;
        }
        Exam_controller::deleteSchedule($exam_id);
        break;

    // Questions and Answer route handler

    case "POST /getQuestions":
        $data = json_decode(file_get_contents("php://input"), true);
        $exam_id = $data['exam_id'] ?? null;
        $student_id = $data['student_id'] ?? null;
        if (!$exam_id || !$student_id) {
            Response_helper::json(['error' => 'Exam id and Student id is required']);
            exit;
        }
        Exam_controller::getQuestions($exam_id, $student_id);
        break;

    case "POST /postAnswer":
        $data = json_decode(file_get_contents("php://input"), true);
        $student_id = $data['student_id'] ?? null;
        $exam_id = $data['exam_id'] ?? null;
        $question_id = $data['question_id'] ?? null;
        $answer_text = $data['answer_text'] ?? null;

        if (!$student_id || !$exam_id || !$question_id || !$answer_text) {
            Response_helper::json(['error' => 'Invalid request']);
            exit;
        }

        Exam_controller::postAnswer($student_id, $exam_id, $question_id, $answer_text);
        break;

    // Exam Submit route handler

    case "POST /submitExam":
        $data = json_decode(file_get_contents("php://input"), true);
        $student_id = $data['student_id'] ?? null;
        $exam_id = $data['exam_id'] ?? null;

        if (!$student_id || !$exam_id) {
            Response_helper::json(['error' => 'Invalid request']);
            exit;
        }

        Exam_controller::submitExam($student_id, $exam_id);
        break;

    // Invalid request route handler

    default:
        Response_helper::json(['error' => 'Invalid Request'], 401);
        break;
}
