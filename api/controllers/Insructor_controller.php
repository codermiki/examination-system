<?php
require_once "services/Instructor_service.php";
require_once "helpers/Response_helper.php";
class Instructor_controller
{
    public static function assignInstructor($instructor, $course_id)
    {
        $result = Instructor_service::assignInstructor($instructor, $course_id);
        if (isset($result['error'])) {
            Response_helper::json($result, 400);
        } else {
            Response_helper::json($result, 200);
        }
    }

}