<?php
// includes/student/student_view_result.php

// This file displays the result of a specific exam taken by the student.

// Include necessary configuration or database files
include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is a student
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in student to view results.</p>';
    exit();
}

$studentId = $_SESSION['user_id']; // Get the logged-in student's user_id
$studentExam = null; // Variable to hold student exam record
$exam = null; // Variable to hold exam details
$questions = []; // Array to hold questions with student answers
$message = ''; // Variable for messages

// --- Start: PHP Logic for Fetching Result Details ---

// Check if student_exam_id is provided in the GET request
if (isset($_GET['student_exam_id']) && filter_var($_GET['student_exam_id'], FILTER_VALIDATE_INT)) {
    $studentExamId = filter_var($_GET['student_exam_id'], FILTER_VALIDATE_INT);

    try {
        // Fetch the student_exams record, ensuring it belongs to the student and is submitted
        $sql = "SELECT se.*, e.exam_id, e.title, e.description, e.time_limit, e.total_marks, c.course_name
                FROM student_exams se
                JOIN exams e ON se.exam_id = e.exam_id
                JOIN courses c ON e.course_id = c.course_id
                WHERE se.id = :student_exam_id AND se.student_id = :student_id AND se.submitted_at IS NOT NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);
        $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        $studentExam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($studentExam) {
            $exam = $studentExam; // Use the fetched data for exam details

            // Fetch questions for this exam and the student's answers
            $sql = "SELECT q.*, sa.answer_text, sa.is_correct as student_answer_is_correct
                    FROM questions q
                    LEFT JOIN student_answers sa ON q.question_id = sa.question_id AND sa.student_exam_id = :student_exam_id
                    WHERE q.exam_id = :exam_id
                    ORDER BY q.question_id ASC"; // Order might be different

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);
            $stmt->bindParam(':exam_id', $exam['exam_id'], PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For multiple-choice questions, fetch their choices
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'multiple_choice') {
                    $sql = "SELECT choice_id, choice_text, is_correct FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC"; // Order might be different
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question);

        } else {
            $message = '<p class="error">Exam result not found or you do not have permission to view it.</p>';
        }

    } catch (PDOException $e) {
        error_log("Error fetching exam result: " . $e->getMessage());
        $message = '<p class="error">Error loading exam result. Please try again later.</p>';
    }
} else {
    $message = '<p class="error">No exam result ID provided.</p>';
}

// --- End: PHP Logic for Fetching Result Details ---

?>

<style>
    /* Basic styling for the view result page */
    .view-result-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 900px;
        margin: 20px auto;
    }

    .view-result-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .result-summary {
        margin-bottom: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #fff;
        text-align: center;
    }

    .result-summary p {
        margin-bottom: 8px;
    }

    .result-summary strong {
        color: #555;
    }

    .score {
        font-size: 1.5em;
        font-weight: bold;
        color: #28a745; /* Green for score */
    }

    .question-list {
        margin-top: 20px;
    }

    .question-item {
        margin-bottom: 25px;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #fff;
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

    .options-list, .answer-details {
        list-style: none;
        padding: 0;
        margin-top: 10px;
    }

    .options-list li, .answer-details p {
        margin-bottom: 5px;
        padding: 5px;
        border-bottom: 1px solid #eee;
    }

    .options-list li strong, .answer-details strong {
        color: #333;
    }

    .correct-answer {
        color: #28a745; /* Green */
        font-weight: bold;
    }

    .student-answer {
        color: #007bff; /* Blue */
        font-weight: bold;
    }

    .incorrect-answer {
        color: #dc3545; /* Red */
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

<div class="view-result-container">
    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <?php if ($studentExam): ?>
        <h2>Exam Result: <?php echo htmlspecialchars($exam['title']); ?></h2>

        <div class="result-summary">
            <p>Course: <?php echo htmlspecialchars($exam['course_name']); ?></p>
            <p>Submitted On: <?php echo htmlspecialchars($studentExam['submitted_at']); ?></p>
            <p>Your Score: <span class="score"><?php echo htmlspecialchars($studentExam['score']); ?> / <?php echo htmlspecialchars($exam['total_marks']); ?></span></p>
        </div>

        <div class="question-list">
            <h3>Your Answers</h3>
            <?php if (empty($questions)): ?>
                <p>No questions found for this exam result.</p>
            <?php else: ?>
                <?php $questionNumber = 1; ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-item">
                        <h4>Question <?php echo $questionNumber++; ?></h4>
                        <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                        <p class="question-type">Type: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $question['question_type']))); ?></p>

                        <div class="answer-details">
                            <?php if ($question['question_type'] === 'multiple_choice' && isset($question['choices'])): ?>
                                <p>Your Answer:
                                    <span class="<?php echo $question['student_answer_is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                        <?php echo htmlspecialchars($question['answer_text'] ?? 'No Answer'); ?>
                                    </span>
                                </p>
                                <p>Correct Option:</p>
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
                                <p>Your Answer:
                                    <span class="<?php echo $question['student_answer_is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                         <?php echo htmlspecialchars(ucfirst($question['answer_text'] ?? 'No Answer')); ?>
                                    </span>
                                </p>
                                <p>Correct Answer: <span class="correct-answer"><?php echo htmlspecialchars(ucfirst($question['correct_answer'])); ?></span></p>
                            <?php elseif ($question['question_type'] === 'blank_space'): ?>
                                <p>Your Answer:
                                     <span class="<?php echo $question['student_answer_is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                        <?php echo nl2br(htmlspecialchars($question['answer_text'] ?? 'No Answer')); ?>
                                     </span>
                                </p>
                                <p>Correct Answer(s): <span class="correct-answer"><?php echo nl2br(htmlspecialchars(str_replace('|', ', ', $question['correct_answer']))); ?></span></p>
                            <?php endif; ?>
                             </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    <?php else: // Display message if result data could not be fetched ?>
        <?php
        // Display error message (already set in the PHP logic)
        if (!empty($message)) {
            echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
        }
        ?>
    <?php endif; ?>

</div>

<?php
// No JavaScript needed for this page.
?>
