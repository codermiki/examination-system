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

        $datetime = date(format: "Y-m-d H:i:s", timestamp: strtotime($scheduled_date));

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
                    ':scheduled_date' => $datetime
                ]);
                return ['message' => 'Exam scheduled successfully.'];
            } else {
                return ['error' => 'Exam already scheduled.'];
            }
        } catch (PDOException $e) {
            return ['error' => "Failed to Schedule Exam"];
        }
    }

    public static function updateExamSchedule($exam_id, $scheduled_date)
    {
        global $conn;

        if (!$exam_id || !$scheduled_date) {
            return ['error' => 'Invalid input. Exam id and schedule date are required.'];
        }

        try {
            // Check if the exam is already scheduled
            $checkStmt = $conn->prepare("SELECT * FROM exam_schedules WHERE exam_id = :exam_id");
            $checkStmt->execute([':exam_id' => $exam_id]);

            if ($checkStmt->rowCount() === 0) {
                return ['error' => 'Exam is not scheduled yet.'];
            }

            // Update the existing schedule
            $updateStmt = $conn->prepare("UPDATE exam_schedules SET scheduled_date = :scheduled_date WHERE exam_id = :exam_id");
            $updateStmt->execute([
                ':scheduled_date' => $scheduled_date,
                ':exam_id' => $exam_id
            ]);

            return ['message' => 'Exam schedule updated successfully.'];
        } catch (PDOException $e) {
            return ['error' => "Failed to update exam schedule."];
        }
    }

    public static function deleteSchedule($exam_id)
    {
        global $conn;

        if (!$exam_id) {
            return ['error' => 'Invalid input. Exam id is required.'];
        }

        try {
            // Check if the exam is scheduled
            $checkStmt = $conn->prepare("SELECT * FROM exam_schedules WHERE exam_id = :exam_id");

            $checkStmt->execute([':exam_id' => $exam_id]);

            if ($checkStmt->rowCount() === 0) {
                return ['error' => 'Exam is not scheduled yet.'];
            }

            // delete the existing schedule
            $deleteStmt = $conn->prepare("DELETE FROM exam_schedules WHERE exam_id = :exam_id");
            $deleteStmt->execute([
                ':exam_id' => $exam_id
            ]);

            return ['message' => 'Exam schedule deleted successfully.'];
        } catch (PDOException $e) {
            return ['error' => "Failed to delete exam schedule."];
        }
    }

    public static function getQuestions($exam_id, $student_id)
    {
        global $conn;

        // Step 1: Check exam status
        $checkStatus = $conn->prepare("SELECT has_taken FROM student_exam_status WHERE student_id = :student_id AND exam_id = :exam_id");
        $checkStatus->execute([
            ':student_id' => $student_id,
            ':exam_id' => $exam_id
        ]);
        $status = $checkStatus->fetchColumn();

        if ($status) {
            return ['error' => 'You have already completed this exam.'];
        }

        // Step 2: Fetch unanswered questions
        $sql = "SELECT 
                q.question_id,
                q.question_text,
                q.question_type,
                q.marks,
                qo.option_text,
                e.exam_id,
                e.exam_title
            FROM questions q
            JOIN exams e ON q.exam_id = e.exam_id
            LEFT JOIN question_options qo ON qo.question_id = q.question_id
            WHERE q.exam_id = :exam_id
              AND q.question_id NOT IN (
                  SELECT question_id FROM student_answers 
                  WHERE student_id = :student_id AND exam_id = :exam_id
              )
            ORDER BY RAND();";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':exam_id' => $exam_id,
            ':student_id' => $student_id
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return ['message' => 'You have answered all questions.'];
        }

        // Group by question_id and structure data
        $examData = [
            'exam_id' => $exam_id,
            'exam_title' => $rows[0]['exam_title'] ?? '',
            'questions' => []
        ];

        $questions = [];
        foreach ($rows as $row) {
            $qid = $row['question_id'];
            if (!isset($questions[$qid])) {
                $questions[$qid] = [
                    'question_id' => $qid,
                    'question_text' => $row['question_text'],
                    'question_type' => $row['question_type'],
                    'marks' => $row['marks'],
                    'options' => []
                ];
            }
            if (!empty($row['option_text'])) {
                $questions[$qid]['options'][] = $row['option_text'];
            }
        }

        foreach ($questions as &$q) {
            shuffle($q['options']);
        }

        $examData['questions'] = array_values($questions);

        return $examData;
    }

    public static function postAnswer($student_id, $exam_id, $question_id, $answer_text)
    {
        try {
            global $conn;

            // Query to get the correct answer
            $sql = "SELECT correct_answer FROM questions WHERE question_id = :question_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':question_id' => $question_id
            ]);
            $row = $stmt->fetch();

            if (!$row) {
                throw new Exception("No question found with the given question ID.");
            }

            // Step 2: Compare the answer
            $is_correct = ($answer_text === $row['correct_answer']) ? true : false;

            // Step 3: Insert the student's answer along with the result
            $query_insert = "INSERT INTO student_answers (student_id, exam_id, question_id, answer_text, is_correct)
                     VALUES (:student_id, :exam_id, :question_id, :answer_text, :is_correct)";
            $stmt_insert = $conn->prepare($query_insert);

            // Execute the insert query
            $stmt_insert->execute([
                ':student_id' => $student_id,
                ':exam_id' => $exam_id,
                ':question_id' => $question_id,
                ':answer_text' => $answer_text,
                ':is_correct' => $is_correct
            ]);

            // Step 4: Optionally, handle success
            return ['success' => 'Answer submitted successfully!'];
        } catch (PDOException $e) {
            // Handle PDO exceptions (database-related errors)
            return ['error' => 'Failed to submit Answer'];
        } catch (Exception $e) {
            // Handle other types of errors (like the no question found case)
            return ['error' => 'Failed to submit Answer'];

        }

    }

    public static function submitExam($student_id, $exam_id)
    {
        global $conn;
        try {
            $sql_check = "SELECT has_taken FROM student_exam_status WHERE student_id = :student_id AND exam_id = :exam_id";
            $stmt = $conn->prepare($sql_check);
            $stmt->execute([
                ':student_id' => $student_id,
                ':exam_id' => $exam_id
            ]);

            $result = $stmt->fetch();

            if (!$result) {
                $sql_score = "SELECT SUM(q.marks) AS total_score
                  FROM student_answers sa
                  JOIN questions q ON sa.question_id = q.question_id
                  WHERE sa.student_id = :student_id
                    AND sa.exam_id = :exam_id
                    AND sa.is_correct = TRUE";

                $stmt_score = $conn->prepare($sql_score);
                $stmt_score->execute([
                    ':student_id' => $student_id,
                    ':exam_id' => $exam_id
                ]);

                $result = $stmt_score->fetch();
                $score = $result['total_score'] ?? 0; // Default to 0 if null


                // Insert or update exam status with score
                $sql_insert = "INSERT INTO student_exam_status (student_id, exam_id, has_taken, taken_on, score)
                   VALUES (:student_id, :exam_id, TRUE, NOW(), :score);";

                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->execute([
                    ':student_id' => $student_id,
                    ':exam_id' => $exam_id,
                    ':score' => $score
                ]);

                return ['success' => 'Exam submitted successfully!'];
            } else {
                return ['error' => 'Exam already submitted'];
            }
        } catch (PDOException $e) {
            return ['error' => 'Failed to submit Exam'];
        }
    }
}
