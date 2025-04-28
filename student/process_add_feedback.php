<?php
// includes/student/process_add_feedback.php

// This script handles the POST request from the Add Feedback form,
// validates the feedback data, and saves it to the database.
include_once '../config.php';
include_once '../includes/db/db.config.php';
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
$courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
$examId = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT); // Exam ID is optional
$messageText = trim($_POST['feedback_message'] ?? '');
$rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);

// Basic validation
if ($courseId === false || $courseId <= 0 || empty($messageText) || $rating === false || $rating < 1 || $rating > 5) {
    echo '<p class="error">Error: Please select a course, provide a message, and a rating between 1 and 5.</p>';
    exit(); // Stop processing if basic validation fails
}

try {
    // Insert into feedbacks table
    $sql = "INSERT INTO feedbacks (student_id, course_id, exam_id, message, rating) VALUES (:student_id, :course_id, :exam_id, :message, :rating)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
    // Bind exam_id, handle null if not provided (0 or null from filter_var)
    if ($examId === null || $examId <= 0) {
         $stmt->bindValue(':exam_id', null, PDO::PARAM_NULL);
    } else {
         $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
    }
    $stmt->bindParam(':message', $messageText, PDO::PARAM_STR);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo '<p class="success">Feedback submitted successfully. Thank you!</p>';
    } else {
         throw new Exception("Error inserting feedback data: " . implode(" ", $stmt->errorInfo()));
    }

} catch (PDOException $e) {
    error_log("Error submitting feedback: " . $e->getMessage());
    echo '<p class="error">An error occurred while submitting feedback. Please try again.</p>';
} catch (Exception $e) {
     error_log("Feedback submission error: " . $e->getMessage());
     echo '<p class="error">An error occurred while submitting feedback. Please try again.</p>';
}
?>
