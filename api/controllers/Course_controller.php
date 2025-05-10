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
    public static function updateCourse($year, $semester, $course_id): void
    {
        $response = Course_service::updateCourse($year, $semester, $course_id);
        Response_helper::json($response);
    }
}
