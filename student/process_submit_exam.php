<?php
// includes/student/process_submit_exam.php

// This script handles the POST request from the Take Exam form,
// processes student answers, grades the exam, and saves the result to the database.
include_once '../config.php';
include_once '../includes/db/db.config.php';
// Ensure this script is only accessed via a POST request from handle_action.php
// Basic check, handle_action.php should already include necessary security checks.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Optionally redirect or show an error if accessed directly
    // header('Location: ../../index.php');
    // exit();
    echo '<p class="error">Invalid request method.</p>';
    exit();
}

// Assume $pdo and $studentId are available from the including script (handle_action.php)
// If not, you would need to include config.php and get the student ID here.
// include_once '../../config.php';
// if (session_status() == PHP_SESSION_NONE) { session_start(); }
// if (!isset($_SESSION['user_id'])) { echo '<p>Access denied.</p>'; exit(); }
// $studentId = $_SESSION['user_id'];


// Get submitted data
$examId = filter_var($_POST['exam_id'] ?? 0, FILTER_VALIDATE_INT);
$studentExamId = filter_var($_POST['student_exam_id'] ?? 0, FILTER_VALIDATE_INT);
$submittedAnswers = $_POST['answers'] ?? []; // Array of student answers

// Basic validation
if ($examId === false || $examId <= 0 || $studentExamId === false || $studentExamId <= 0 || !is_array($submittedAnswers)) {
    echo '<p class="error">Error: Invalid exam or submission data.</p>';
    exit();
}

try {
    // Start a database transaction
    $pdo->beginTransaction();

    // Verify the student_exam record belongs to the logged-in student and the correct exam, and is not already submitted
    $stmt = $pdo->prepare("SELECT id, submitted_at FROM student_exams WHERE id = :student_exam_id AND student_id = :student_id AND exam_id = :exam_id");
    $stmt->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
    $stmt->execute();
    $studentExamRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$studentExamRecord || $studentExamRecord['submitted_at'] !== null) {
        // Either the record doesn't exist for this student/exam, or it's already submitted
        echo '<p class="error">Error: Invalid or already submitted exam session.</p>';
        $pdo->rollBack(); // Rollback any potential partial operations (though none should have happened yet)
        exit();
    }

    // Fetch the exam details to get total marks (if needed for scoring scaling)
    $stmt = $pdo->prepare("SELECT total_marks FROM exams WHERE exam_id = :exam_id");
    $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
    $stmt->execute();
    $examDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalExamMarks = $examDetails['total_marks'] ?? 0;


    // Fetch correct answers and question types for all questions in this exam
    $correctAnswers = [];
    $stmt = $pdo->prepare("SELECT question_id, question_type, correct_answer FROM questions WHERE exam_id = :exam_id");
    $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
    $stmt->execute();
    $examQuestions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($examQuestions as $q) {
        $correctAnswers[$q['question_id']] = $q;
        // For multiple choice, also fetch choices to find the correct one
        if ($q['question_type'] === 'multiple_choice') {
            $stmt = $pdo->prepare("SELECT choice_text FROM choices WHERE question_id = :question_id AND is_correct = TRUE");
            $stmt->bindParam(':question_id', $q['question_id'], PDO::PARAM_INT);
            $stmt->execute();
            $correctChoice = $stmt->fetch(PDO::FETCH_ASSOC);
            // Store the correct choice text
            $correctAnswers[$q['question_id']]['correct_choice_text'] = $correctChoice['choice_text'] ?? null;
        }
    }

    $score = 0;
    $questionsGradedCount = 0; // Count questions we could grade

    // Delete any previous student answers for this student_exam session (in case of resume/resubmit logic)
    // In a standard submit-once system, this might not be necessary.
    $stmt = $pdo->prepare("DELETE FROM student_answers WHERE student_exam_id = :student_exam_id");
    $stmt->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);
    $stmt->execute();


    // Process and grade each submitted answer
    $stmtInsertAnswer = $pdo->prepare("INSERT INTO student_answers (student_exam_id, question_id, answer_text, is_correct) VALUES (:student_exam_id, :question_id, :answer_text, :is_correct)");

    foreach ($submittedAnswers as $questionId => $answerData) {
        // Ensure the submitted question ID is valid for this exam
        if (!isset($correctAnswers[$questionId])) {
            error_log("Submitted answer for invalid question ID: " . $questionId . " for student_exam_id: " . $studentExamId);
            continue; // Skip invalid questions
        }

        $question = $correctAnswers[$questionId];
        $studentAnswerText = trim($answerData['answer'] ?? '');
        $isCorrect = false; // Assume incorrect until proven otherwise

        // Grade based on question type
        switch ($question['question_type']) {
            case 'true_false':
                // Case-insensitive comparison for true/false
                if (strtolower($studentAnswerText) === strtolower($question['correct_answer'])) {
                    $isCorrect = true;
                }
                $questionsGradedCount++;
                break;

            case 'multiple_choice':
                // Compare student's selected option text with the correct choice text
                if ($studentAnswerText === $question['correct_choice_text']) {
                    $isCorrect = true;
                }
                 $questionsGradedCount++;
                break;

            case 'blank_space':
                // Grade blank space. Assumes correct_answer is pipe-separated.
                // Simple grading: student answer must match one of the correct answers exactly (case-insensitive, trimmed)
                // For multiple blanks, this simple logic assumes the student provides answers in the same order,
                // separated by pipes, or matches one of the full correct answer strings.
                // A more robust system would parse [BLANK] and match individual answers.
                $correctBlankAnswers = array_map('strtolower', array_map('trim', explode('|', $question['correct_answer'])));
                $studentBlankAnswerTrimmedLower = strtolower($studentAnswerText);

                if (in_array($studentBlankAnswerTrimmedLower, $correctBlankAnswers)) {
                    $isCorrect = true;
                }
                 $questionsGradedCount++;
                break;

            // Math Equation and Coding would require complex evaluation logic here
            // For now, they are not in the DB schema, so we ignore them if submitted.
            default:
                 // Log unknown question types if encountered
                 error_log("Unknown question type encountered during grading: " . $question['question_type'] . " for question ID: " . $questionId);
                 continue; // Skip grading for unknown types
        }

        // If the answer is correct, add to the score (assuming 1 mark per correct question)
        if ($isCorrect) {
            $score++;
        }

        // Insert the student's answer into the student_answers table
        $stmtInsertAnswer->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);
        $stmtInsertAnswer->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $stmtInsertAnswer->bindParam(':answer_text', $studentAnswerText, PDO::PARAM_STR);
        $stmtInsertAnswer->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);

        if (!$stmtInsertAnswer->execute()) {
            throw new Exception("Error inserting student answer for question ID " . $questionId . ": " . implode(" ", $stmtInsertAnswer->errorInfo()));
        }
    }

    // Scale the score if total_marks is different from the number of graded questions
    // This is a very basic scaling. A real system might assign marks per question.
    if ($questionsGradedCount > 0 && $totalExamMarks > 0 && $questionsGradedCount !== $totalExamMarks) {
         $scaledScore = ($score / $questionsGradedCount) * $totalExamMarks;
         $score = round($scaledScore, 2); // Round to 2 decimal places
    }


    // Update the student_exams table with submission time and score
    $stmt = $pdo->prepare("UPDATE student_exams SET submitted_at = NOW(), score = :score WHERE id = :student_exam_id");
    $stmt->bindParam(':score', $score, PDO::PARAM_STR); // Use STR for DECIMAL type
    $stmt->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);

    if (!$stmt->execute()) {
         throw new Exception("Error updating student_exams record: " . implode(" ", $stmt->errorInfo()));
    }


    $pdo->commit(); // Commit the transaction if all operations were successful

    // Provide feedback to the user
    echo '<p class="success">Exam submitted successfully! Your score is ' . htmlspecialchars($score) . ' out of ' . htmlspecialchars($totalExamMarks) . '.</p>';

} catch (Exception $e) {
    // Rollback the transaction if any error occurred
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Exam submission error for student_exam_id " . $studentExamId . ": " . $e->getMessage()); // Log the detailed error
    echo '<p class="error">An error occurred during exam submission: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
