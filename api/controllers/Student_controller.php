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
    public static function updateAssignedStudent($course_id, $student_id, $status): void
    {
        $response = Student_service::updateAssignedStudent($course_id, $student_id, $status);
        Response_helper::json($response);
    }
    public static function unassignStudent( $student_id,$course_id): void
    {
        $response = Student_service::unassignStudent($student_id,$course_id);
        Response_helper::json($response);
    }
}
