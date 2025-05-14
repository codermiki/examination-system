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

    public static function updateExamSchedule($exam_id, $scheduled_date): void
    {
        $response = Exam_service::updateExamSchedule($exam_id, $scheduled_date);
        Response_helper::json($response);
    }

    public static function deleteSchedule($exam_id): void
    {
        $response = Exam_service::deleteSchedule($exam_id);
        Response_helper::json($response);
    }
    public static function getQuestions($exam_id, $student_id): void
    {
        $response = Exam_service::getQuestions($exam_id, $student_id);
        Response_helper::json($response);
    }

    public static function postAnswer($student_id, $exam_id, $question_id, $answer_text): void
    {
        $response = Exam_service::postAnswer($student_id, $exam_id, $question_id, $answer_text);
        Response_helper::json($response);
    }

    public static function submitExam($student_id, $exam_id): void
    {
        $response = Exam_service::submitExam($student_id, $exam_id);
        Response_helper::json($response);
    }

}
