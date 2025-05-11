<?php
require_once "services/Exam_service.php";
require_once "helpers/Response_helper.php";
class Exam_controller
{
    public static function scheduleExam($exam_id, $scheduled_date): void
    {
        $response = Exam_service::scheduleExam($exam_id, $scheduled_date);
        Response_helper::json($response);
    }
    // public static function deleteCourse($course_id): void
    // {
    //     $response = Course_service::deleteCourse($course_id);
    //     Response_helper::json($response);
    // }
}
