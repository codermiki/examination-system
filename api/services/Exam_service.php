<?php
require_once "config/db.config.php";

class Exam_service
{
    public static function scheduleExam($exam_id, $scheduled_date)
    {
        global $conn;

        if (!$exam_id || !$scheduled_date) {
            return ['error' => 'Invalid input. Exam id and schedule date are required.'];
        }

        try {
            $stmt = $conn->prepare("INSERT INTO exam_schedules (exam_id, scheduled_date) VALUES (:exam_id, :scheduled_date)");

            // Check if already assigned (prevent duplicates)
            $checkStmt = $conn->prepare("SELECT * FROM exam_schedules WHERE exam_id = :exam_id");

            $checkStmt->execute([
                ':exam_id' => $exam_id
            ]);

            if ($checkStmt->rowCount() === 0) {
                $stmt->execute([
                    ':exam_id' => $exam_id,
                    ':scheduled_date' => $scheduled_date
                ]);
                return ['message' => 'Exam scheduled successfully.'];
            } else {
                return ['error' => 'Exam already scheduled.'];
            }
        } catch (PDOException $e) {
            return ['error' => "Failed to Schedule Exam"];
        }
    }

}
