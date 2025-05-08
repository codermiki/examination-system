<?php
// includes/instructor/manage_questions.php

// This file handles the functionality for instructors to manage questions for a specific exam.

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
    echo '<p>Access denied. You must be a logged-in instructor to manage questions.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold exam details
$questions = []; // Array to hold questions

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

// --- Start: PHP Logic for Handling Question Actions (Delete) ---

// This is where you would handle actions like deleting a question
// based on GET or POST parameters.
// Example: If a delete link is clicked, the URL might be
// handle_action.php?action=instructor_manage_questions&exam_id=123&delete_question_id=456

$_GET['exam_id']=1; // Example exam_id for testing
$_GET['delete_question_id']=1; // Example question_id for testing

if (isset($_GET['delete_question_id']) && isset($_GET['exam_id'])) {
    $questionIdToDelete = filter_var($_GET['delete_question_id'], FILTER_VALIDATE_INT);
    $examId = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);

    if ($questionIdToDelete !== false && $questionIdToDelete > 0 && $examId !== false && $examId > 0) {
        try {
            // Start a transaction for deletion
            $pdo->beginTransaction();

            // IMPORTANT: Ensure the question belongs to an exam owned by the instructor
            // First, check if the exam belongs to the instructor
            $stmt = $pdo->prepare("SELECT exam_id FROM exams WHERE exam_id = :exam_id AND instructor_id = :instructor_id");
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
            $stmt->execute();
            $examCheck = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($examCheck) {
                // Exam belongs to the instructor, now check if the question belongs to this exam
                $stmt = $pdo->prepare("SELECT question_id FROM questions WHERE question_id = :question_id AND exam_id = :exam_id");
                $stmt->bindParam(':question_id', $questionIdToDelete, PDO::PARAM_INT);
                $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                $stmt->execute();
                $questionCheck = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($questionCheck) {
                    // Question belongs to this exam and the exam belongs to the instructor
                    // Proceed with deletion
                    // Delete related data first (student answers, choices)

                    // Delete student answers for this question
                    $stmt = $pdo->prepare("DELETE FROM student_answers WHERE question_id = :question_id");
                    $stmt->bindParam(':question_id', $questionIdToDelete, PDO::PARAM_INT);
                    $stmt->execute();

                    // Delete choices for this question (only applicable for multiple_choice)
                    $stmt = $pdo->prepare("DELETE FROM choices WHERE question_id = :question_id");
                    $stmt->bindParam(':question_id', $questionIdToDelete, PDO::PARAM_INT);
                    $stmt->execute();

                    // Finally, delete the question itself
                    $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id = :question_id");
                    $stmt->bindParam(':question_id', $questionIdToDelete, PDO::PARAM_INT);
                    $stmt->execute();

                    $pdo->commit(); // Commit the transaction
                    $message = '<p class="success">Question deleted successfully.</p>';

                } else {
                    $pdo->rollBack(); // Rollback if question doesn't belong to the exam
                    $message = '<p class="error">Question not found for this exam or you do not have permission to delete it.</p>';
                }
            } else {
                 $pdo->rollBack(); // Rollback if exam doesn't belong to the instructor
                 $message = '<p class="error">Exam not found or you do not have permission to manage its questions.</p>';
            }


        } catch (PDOException $e) {
            // Rollback the transaction if any error occurred
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error deleting question: " . $e->getMessage()); // Log the detailed error
            $message = '<p class="error">An error occurred while trying to delete the question. Please try again.</p>';
        }
    } else {
        $message = '<p class="error">Invalid question ID or exam ID for deletion.</p>';
    }

    // Optional: Redirect to the same page after deletion to prevent re-submission on refresh
    // This requires handling the AJAX response in script.js to then reload the manage_questions content
    // header('Location: handle_action.php?action=instructor_manage_questions&exam_id=' . $examId);
    // exit();
}

// --- End: PHP Logic for Handling Question Actions ---


// --- Start: PHP Logic for Fetching Exam Details and Questions ---

// Check if exam_id is provided in the GET request for displaying questions
$examId = null;
if (isset($_GET['exam_id']) && filter_var($_GET['exam_id'], FILTER_VALIDATE_INT)) {
    $examId = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);

    try {
        // Fetch exam details, ensuring it belongs to the instructor
        $sql = "SELECT e.exam_id, e.title, c.course_name
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
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question); // Break the reference with the last element
        } else {
            // Exam not found or doesn't belong to instructor - set an error message
            $message = '<p class="error">Exam not found or you do not have permission to manage its questions.</p>';
        }

    } catch (PDOException $e) {
        // Log error and display a user-friendly message
        error_log("Error fetching exam details and questions: " . $e->getMessage()); // Log the detailed error
        $message = '<p class="error">Error loading exam questions. Please try again later.</p>';
    }
} elseif (!isset($_GET['delete_question_id'])) {
     // Only show this error if not handling a delete request
    $message = '<p class="error">No exam ID provided to manage questions.</p>';
}

// --- End: PHP Logic for Fetching Exam Details and Questions ---
?>

<style>
    /* Basic styling for the manage questions page */
    .manage-questions-container { /* Changed class name for clarity */
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 900px;
        margin: 20px auto;
    }

    .manage-questions-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .question-list {
        margin-top: 20px;
    }

    .question-item {
        margin-bottom: 25px;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #fff; /* Changed background for list items */
    }

    .question-item h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #007bff;
        display: inline-block; /* Allow actions on the same line */
        margin-right: 15px;
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

    .question-actions {
        display: inline-block;
        float: right; /* Align actions to the right */
    }

    .question-actions a {
        margin-left: 10px;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 4px;
        display: inline-block;
    }

    .question-actions .edit-link {
        color: #fff;
        background-color: #ffc107;
    }

     .question-actions .edit-link:hover {
        background-color: #e0a800;
     }

    .question-actions .delete-link {
        color: #fff;
        background-color: #dc3545;
    }

    .question-actions .delete-link:hover {
        background-color: #c82333;
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

<div class="manage-questions-container">
    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <?php if ($exam): // Only display if exam data was fetched ?>
        <h2>Manage Questions for Exam: <?php echo htmlspecialchars($exam['title']); ?></h2>
        <p>Course: <?php echo htmlspecialchars($exam['course_name']); ?></p>

        <div class="question-list">
            <h3>Questions (<?php echo count($questions); ?>)</h3>
            <?php if (empty($questions)): ?>
                <p>No questions found for this exam.</p>
                 <p><a href="#" class="sidebar-link" data-content="instructor_create_question" data-exam-id="<?php echo $exam['exam_id']; ?>">Add New Question</a></p>
            <?php else: ?>
                 <p><a href="#" class="sidebar-link" data-content="instructor_create_question" data-exam-id="<?php echo $exam['exam_id']; ?>">Add New Question</a></p>
                <?php $questionNumber = 1; ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-item">
                        <h4>Question <?php echo $questionNumber++; ?></h4>
                        <div class="question-actions">
                            <a href="#" class="edit-link sidebar-link" data-content="instructor_edit_question" data-exam-id="<?php echo $exam['exam_id']; ?>" data-question-id="<?php echo $question['question_id']; ?>">Edit</a>
                            <a href="handle_action.php?action=instructor_manage_questions&exam_id=<?php echo $exam['exam_id']; ?>&delete_question_id=<?php echo $question['question_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this question? This action cannot be undone.');">Delete</a>
                        </div>
                        <div style="clear: both;"></div> <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
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

    <?php else: // Display message if exam data could not be fetched ?>
        <?php
        // Display feedback message if any (already set in the PHP logic)
        if (!empty($message)) {
            echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
        }
        ?>
    <?php endif; ?>

</div>

<?php
// No JavaScript needed for this basic view/manage page.
?>
