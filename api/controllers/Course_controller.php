<?php
require_once "services/Course_service.php";
require_once "helpers/Response_helper.php";
class Course_controller
{
    public static function addCourse($courses): void
    {
        $response = Course_service::addCourse($courses);
        Response_helper::json($response);
    }
    public static function deleteCourse($course_id): void
    {
        $response = Course_service::deleteCourse($course_id);
        Response_helper::json($response);
    }
}
