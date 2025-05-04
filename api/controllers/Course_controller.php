<?php
require_once "services/Course_service.php";
require_once "helpers/Response_helper.php";
class Course_controller
{
    public static function addCourse($year, $semester, $course_ids): void
    {
        $response = Course_service::addCourse($year, $semester, $course_ids);
        Response_helper::json($response);
    }
    public static function assignInstructor($instructor_id, $course_id)
    {
        $result = Course_service::assignInstructor($instructor_id, $course_id);
        if (isset($result['error'])) {
            Response_helper::json($result, 400);
        } else {
            Response_helper::json($result, 200);
        }
    }
}
