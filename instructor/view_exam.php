<?php
// includes/instructor/view_exam.php

// This file handles the functionality for instructors to view details of a specific exam.

// Include necessary configuration or database files
// Assuming config.php establishes a $pdo database connection
include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
// Also check if user_id is set in the session
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in instructor to view exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold exam details
$questions = []; // Array to hold questions

// --- Start: PHP Logic for Fetching Exam Details ---

// Check if exam_id is provided in the GET request
if (isset($_GET['exam_id']) && filter_var($_GET['exam_id'], FILTER_VALIDATE_INT)) {
    $examId = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);
    $examId=1;
    $instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

    try {
        // Fetch exam details, ensuring it belongs to the instructor
        $sql = "SELECT e.*, c.course_name
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.exam_id = :exam_id AND e.instructor_id = :instructor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        // If exam is found, fetch its questions
        if ($exam) {
            $sql = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC"; // Order might be different in practice
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For each multiple-choice question, fetch its choices
            foreach ($questions as &$question) { // Use & to modify the original array elements
                if ($question['question_type'] === 'multiple_choice') {
                    $sql = "SELECT * FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC"; // Order might be different
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question); // Break the reference with the last element
        } else {
            $message = '<p class="error">Exam not found or you do not have permission to view it.</p>';
        }

    } catch (PDOException $e) {
        // Log error and display a user-friendly message
        error_log("Error fetching exam details: " . $e->getMessage()); // Log the detailed error
        $message = '<p class="error">Error loading exam details. Please try again later.</p>';
    }
} else {
    $message = '<p class="error">No exam ID provided.</p>';
}

// --- End: PHP Logic for Fetching Exam Details ---
?>

<style>
    /* Basic styling for the view exam page */
    .view-exam-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 900px;
        margin: 20px auto;
    }

    .view-exam-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .exam-details, .question-details {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fff;
    }

    .exam-details p, .question-details p {
        margin-bottom: 8px;
    }

    .exam-details strong, .question-details strong {
        color: #555;
    }

    .question-list {
        margin-top: 20px;
    }

    .question-item {
        margin-bottom: 25px;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #f2f2f2;
    }

    .question-item h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #007bff;
    }

    .question-item .question-text {
        font-weight: bold;
        margin-bottom: 10px;
    }

    .question-item .question-type {
        font-style: italic;
        color: #666;
        margin-bottom: 10px;
        font-size: 0.9em;
    }

    .options-list {
        list-style: none;
        padding: 0;
        margin-top: 10px;
    }

    .options-list li {
        margin-bottom: 5px;
        padding: 5px;
        border-bottom: 1px solid #eee;
    }

    .options-list li strong {
        color: #333;
    }

    .correct-answer {
        color: #28a745;
        font-weight: bold;
    }

    .message {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="view-exam-container">
    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <?php if ($exam): ?>
        <h2>Exam Details: <?php echo htmlspecialchars($exam['title']); ?></h2>

        <div class="exam-details">
            <p><strong>Course:</strong> <?php echo htmlspecialchars($exam['course_name']); ?></p>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
            <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam['time_limit']); ?> minutes</p>
            <p><strong>Total Marks:</strong> <?php echo htmlspecialchars($exam['total_marks']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($exam['status'])); ?></p>
            <p><strong>Created At:</strong> <?php echo htmlspecialchars($exam['created_at']); ?></p>
        </div>

        <div class="question-list">
            <h3>Questions (<?php echo count($questions); ?>)</h3>
            <?php if (empty($questions)): ?>
                <p>No questions found for this exam.</p>
            <?php else: ?>
                <?php $questionNumber = 1; ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-item">
                        <h4>Question <?php echo $questionNumber++; ?></h4>
                        <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                        <p class="question-type">Type: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $question['question_type']))); ?></p>

                        <?php if ($question['question_type'] === 'multiple_choice' && isset($question['choices'])): ?>
                            <p>Options:</p>
                            <ul class="options-list">
                                <?php foreach ($question['choices'] as $choice): ?>
                                    <li>
                                        <?php if ($choice['is_correct']): ?>
                                            <span class="correct-answer">Correct:</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($choice['choice_text']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php elseif ($question['question_type'] === 'true_false'): ?>
                            <p><strong>Correct Answer:</strong> <span class="correct-answer"><?php echo htmlspecialchars(ucfirst($question['correct_answer'])); ?></span></p>
                        <?php elseif ($question['question_type'] === 'blank_space'): ?>
                             <p><strong>Correct Answer(s):</strong> <span class="correct-answer"><?php echo nl2br(htmlspecialchars(str_replace('|', ', ', $question['correct_answer']))); ?></span></p>
                             <p><small>Blank answers are separated by commas. In the question text, <code>[BLANK]</code> indicates a blank space.</small></p>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>

<?php
// No JavaScript needed for this basic view page.
?>
