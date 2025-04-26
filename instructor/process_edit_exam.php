<?php
// includes/instructor/process_edit_exam.php

// This script handles the POST request from the Edit Exam form
// and updates the exam data in the database.

include_once '../includes/db/db.config.php'; // Include database connection
include_once '../config.php'; // Include configuration file

// Ensure this script is only accessed via a POST request from handle_action.php
// Basic check, handle_action.php should already include necessary security checks.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Optionally redirect or show an error if accessed directly
    // header('Location: ../../index.php');
    // exit();
    echo '<p class="error">Invalid request method.</p>';
    exit();
}

// Assume $pdo and $instructorId are available from the including script (handle_action.php)
// If not, you would need to include config.php and get the instructor ID here.
// include_once '../../config.php';
// if (session_status() == PHP_SESSION_NONE) { session_start(); }
// if (!isset($_SESSION['user_id'])) { echo '<p>Access denied.</p>'; exit(); }
// $instructorId = $_SESSION['user_id'];


// Get and validate exam details from POST
$examIdToUpdate = filter_var($_POST['exam_id'] ?? 0, FILTER_VALIDATE_INT);
$examTitle = trim($_POST['examTitle'] ?? '');
$examDescription = trim($_POST['examDescription'] ?? '');
$examDuration = filter_var($_POST['examDuration'] ?? 0, FILTER_VALIDATE_INT);
$courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
$totalMarks = filter_var($_POST['total_marks'] ?? 0, FILTER_VALIDATE_INT);
$questionsData = $_POST['questions'] ?? []; // Array of questions from the form

// Basic validation
if ($examIdToUpdate === false || $examIdToUpdate <= 0 || empty($examTitle) || $examDuration === false || $examDuration <= 0 || $courseId === false || $courseId <= 0 || $totalMarks === false || $totalMarks < 0) {
    echo '<p class="error">Error: Invalid exam ID or missing/incorrect exam details.</p>';
    exit(); // Stop processing if basic validation fails
} elseif (!is_array($questionsData) || count($questionsData) === 0) {
     echo '<p class="error">Error: Please add at least one question.</p>';
     exit(); // Stop processing if no questions are added
} else {
    // Data seems valid, proceed with database update
    try {
        // Start a database transaction
        $pdo->beginTransaction();

        // 1. Update exams table
        // Ensure the exam belongs to the instructor
        $stmt = $pdo->prepare("UPDATE exams SET course_id = :course_id, title = :title, description = :description, time_limit = :time_limit, total_marks = :total_marks WHERE exam_id = :exam_id AND instructor_id = :instructor_id");
        $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $examTitle, PDO::PARAM_STR);
        $stmt->bindParam(':description', $examDescription, PDO::PARAM_STR);
        $stmt->bindParam(':time_limit', $examDuration, PDO::PARAM_INT);
        $stmt->bindParam(':total_marks', $totalMarks, PDO::PARAM_INT);
        $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
             throw new Exception("Error updating exam details: " . implode(" ", $stmt->errorInfo()));
        }

        // 2. Manage Questions and Choices
        // Delete existing questions/choices for this exam and re-insert the ones submitted in the form.
        // IMPORTANT: Consider if there are already student answers for this exam.
        // Deleting questions will affect student results. A more complex system
        // might archive old questions or prevent editing after students have taken the exam.
        // For this example, we'll proceed with deletion and re-insertion.

        // Delete existing student answers related to this exam's questions
        $stmt = $pdo->prepare("DELETE sa FROM student_answers sa JOIN questions q ON sa.question_id = q.question_id WHERE q.exam_id = :exam_id");
        $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
        $stmt->execute();

        // Delete existing choices for this exam's questions
        $stmt = $pdo->prepare("DELETE c FROM choices c JOIN questions q ON c.question_id = q.question_id WHERE q.exam_id = :exam_id");
        $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
        $stmt->execute();

        // Delete existing questions for this exam
        $stmt = $pdo->prepare("DELETE FROM questions WHERE exam_id = :exam_id");
        $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
        $stmt->execute();


        // Now, insert the questions and choices from the submitted form data
        $allowedQuestionTypes = ['true_false', 'multiple_choice', 'blank_space'];
        foreach ($questionsData as $question) {
            $questionText = trim($question['text'] ?? '');
            $questionType = $question['type'] ?? '';
            $correctAnswer = null; // Will store correct answer for applicable types

            // Validate question data
            if (empty($questionText) || empty($questionType) || !in_array($questionType, $allowedQuestionTypes)) {
                // Log or handle invalid question data - might skip or throw error
                 error_log("Invalid question data during update for exam ID " . $examIdToUpdate . ": " . json_encode($question));
                continue; // Skip this invalid question
            }

            // Determine correct answer based on type
            if ($questionType === 'true_false') {
                $correctAnswer = $question['correct_answer'] ?? null;
                if (!in_array($correctAnswer, ['true', 'false'])) {
                     error_log("Invalid correct answer for True/False question during update for exam ID " . $examIdToUpdate);
                     continue; // Skip this invalid question
                }
            } elseif ($questionType === 'blank_space') {
                // For blank space, collect all submitted answers and store them as a delimited string
                if (isset($question['answers']) && is_array($question['answers']) && count($question['answers']) > 0) {
                     $validAnswers = array_filter($question['answers'], 'trim');
                     if (count($validAnswers) > 0) {
                         $correctAnswer = implode('|', $validAnswers); // Store as pipe-separated string
                     } else {
                          // Depending on your design, you might require at least one answer for blanks
                          // error_log("Blank space question missing answers during update for exam ID " . $examIdToUpdate);
                          // continue; // Skip if answers are required
                     }
                } else {
                     // Depending on your design, you might require answers for blanks
                     // error_log("Blank space question missing answers during update for exam ID " . $examIdToUpdate);
                     // continue; // Skip if answers are required
                }
            }

            // Insert into questions table
            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
            $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
            $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
            $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting question during update: " . implode(" ", $stmt->errorInfo()));
            }
            $questionId = $pdo->lastInsertId(); // Get the ID of the newly inserted question

            // Handle choices for multiple choice questions
            if ($questionType === 'multiple_choice') {
                if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                     error_log("Multiple choice question missing options during update for exam ID " . $examIdToUpdate);
                     continue; // Skip this question if options are missing/insufficient
                }
                 if (!isset($question['correct_answer'])) {
                     error_log("Multiple choice question missing correct_answer selection during update for exam ID " . $examIdToUpdate);
                     continue; // Skip this question if correct answer is not selected
                }
                $correctOptionValue = $question['correct_answer']; // The value that indicates the correct option

                foreach ($question['options'] as $optionValue => $optionText) {
                    $optionText = trim($optionText);
                    if (empty($optionText)) {
                         continue; // Skip empty options
                    }

                    // Determine if this choice is the correct one
                    $isCorrect = ($optionValue === $correctOptionValue);

                    $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (:question_id, :choice_text, :is_correct)");
                    $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                    $stmt->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                    $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);

                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting choice during update: " . implode(" ", $stmt->errorInfo()));
                    }
                }
            }
        }

        $pdo->commit(); // Commit the transaction if all updates/insertions were successful
        echo '<p class="success">Exam "' . htmlspecialchars($examTitle) . '" updated successfully.</p>';

    } catch (Exception $e) {
        // Rollback the transaction if any error occurred
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Exam update error: " . $e->getMessage()); // Log the detailed error
        echo '<p class="error">Error updating exam: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
?>
