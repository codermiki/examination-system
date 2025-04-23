<?php
// includes/instructor/process_create_exam.php

include_once '../includes/db/db.config.php'; // Include database connection
include_once '../config.php'; // Include configuration file

// This script handles the POST request from the Create Exam form
// and saves the exam data to the database.

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


// Get and validate exam details
$examTitle = trim($_POST['examTitle'] ?? '');
$examDescription = trim($_POST['examDescription'] ?? '');
$examDuration = filter_var($_POST['examDuration'] ?? 0, FILTER_VALIDATE_INT);
$courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
$totalMarks = filter_var($_POST['total_marks'] ?? 0, FILTER_VALIDATE_INT);
$questionsData = $_POST['questions'] ?? []; // Array of questions from the form

// Basic validation
if (empty($examTitle) || $examDuration === false || $examDuration <= 0 || $courseId === false || $courseId <= 0 || $totalMarks === false || $totalMarks < 0) {
    echo '<p class="error">Error: Please fill in all required exam details correctly.</p>';
    exit(); // Stop processing if basic validation fails
} elseif (!is_array($questionsData) || count($questionsData) === 0) {
     echo '<p class="error">Error: Please add at least one question.</p>';
     exit(); // Stop processing if no questions are added
} else {
    // Data seems valid, proceed with database insertion
    try {
        // Start a database transaction
        $pdo->beginTransaction();

        // 1. Insert into exams table
        $stmt = $pdo->prepare("INSERT INTO exams (course_id, instructor_id, title, description, time_limit, total_marks, status) VALUES (:course_id, :instructor_id, :title, :description, :time_limit, :total_marks, 'inactive')");
        $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $examTitle, PDO::PARAM_STR);
        $stmt->bindParam(':description', $examDescription, PDO::PARAM_STR);
        $stmt->bindParam(':time_limit', $examDuration, PDO::PARAM_INT);
        $stmt->bindParam(':total_marks', $totalMarks, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting exam data: " . implode(" ", $stmt->errorInfo()));
        }
        $examId = $pdo->lastInsertId(); // Get the ID of the newly inserted exam

        // 2. Insert questions and choices
        $allowedQuestionTypes = ['true_false', 'multiple_choice', 'blank_space'];
        foreach ($questionsData as $question) {
            $questionText = trim($question['text'] ?? '');
            $questionType = $question['type'] ?? '';
            $correctAnswer = null; // Will store correct answer for applicable types

            // Validate question data
            if (empty($questionText) || empty($questionType) || !in_array($questionType, $allowedQuestionTypes)) {
                // Log or handle invalid question data - might skip or throw error
                 error_log("Invalid question data during create exam: " . json_encode($question));
                continue; // Skip this invalid question
            }

            // Determine correct answer based on type
            if ($questionType === 'true_false') {
                $correctAnswer = $question['correct_answer'] ?? null;
                if (!in_array($correctAnswer, ['true', 'false'])) {
                     error_log("Invalid correct answer for True/False question during create exam.");
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
                          // error_log("Blank space question missing answers during create exam.");
                          // continue; // Skip if answers are required
                     }
                } else {
                     // Depending on your design, you might require answers for blanks
                     // error_log("Blank space question missing answers during create exam.");
                     // continue; // Skip if answers are required
                }
            }

            // Insert into questions table
            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
            $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
            $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting question during create exam: " . implode(" ", $stmt->errorInfo()));
            }
            $questionId = $pdo->lastInsertId(); // Get the ID of the newly inserted question

            // Handle choices for multiple choice questions
            if ($questionType === 'multiple_choice') {
                if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                     error_log("Multiple choice question missing options during create exam.");
                     continue; // Skip this question if options are missing/insufficient
                }
                 if (!isset($question['correct_answer'])) {
                     error_log("Multiple choice question missing correct_answer selection during create exam.");
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
                        throw new Exception("Error inserting choice during create exam: " . implode(" ", $stmt->errorInfo()));
                    }
                }
            }
        }

        $pdo->commit(); // Commit the transaction if all insertions were successful
        echo '<p class="success">Exam "' . htmlspecialchars($examTitle) . '" created successfully.</p>';

    } catch (Exception $e) {
        // Rollback the transaction if any error occurred
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Exam creation error: " . $e->getMessage()); // Log the detailed error
        echo '<p class="error">Error creating exam: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
?>
