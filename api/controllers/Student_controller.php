<?php
require_once "services/Student_service.php";
require_once "helpers/Response_helper.php";
class Student_controller
{
    public static function assignStudent($course_id, $student_ids): void
    {
        $response = Student_service::assignStudent($course_id, $student_ids);
        Response_helper::json($response);
    }
}
