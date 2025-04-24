<?php
// includes/student/student_take_exam.php

// This file is a placeholder for the exam taking interface.
// Implementing the full exam taking logic is complex and requires:
// - Fetching exam details and questions
// - Displaying questions based on type (MC, TF, Blank)
// - Handling student input
// - Implementing a timer
// - Saving student answers periodically or on submission
// - Preventing cheating (optional but recommended)

// Include necessary configuration or database files
include_once '../config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is a student
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in student to take exams.</p>';
    exit();
}

$studentId = $_SESSION['user_id']; // Get the logged-in student's user_id
$examId = null; // Variable to hold the exam ID from the request
$exam = null; // Variable to hold exam details
$questions = []; // Array to hold questions

// --- Start: PHP Logic to Fetch Exam and Questions ---

// Check if exam_id is provided in the GET request
if (isset($_GET['exam_id']) && filter_var($_GET['exam_id'], FILTER_VALIDATE_INT)) {
    $examId = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);

    try {
        // Fetch exam details
        $sql = "SELECT e.*, c.course_name
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.exam_id = :exam_id AND e.status = 'active'"; // Check if exam is active
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        // If exam is found and active, fetch its questions
        if ($exam) {
            // Check if the student has already started this exam
            $stmt = $pdo->prepare("SELECT id FROM student_exams WHERE student_id = :student_id AND exam_id = :exam_id AND submitted_at IS NULL");
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();
            $studentExam = $stmt->fetch(PDO::FETCH_ASSOC);

            $studentExamId = null;

            if ($studentExam) {
                // Student has already started this exam, resume it
                $studentExamId = $studentExam['id'];
                // You would also fetch their existing answers here
                // $stmt = $pdo->prepare("SELECT * FROM student_answers WHERE student_exam_id = :student_exam_id");
                // $stmt->bindParam(':student_exam_id', $studentExamId, PDO::PARAM_INT);
                // $stmt->execute();
                // $studentAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p class='success'>Resuming exam: " . htmlspecialchars($exam['title']) . "</p>";

            } else {
                // Student is starting the exam for the first time
                // Check exam schedule to see if it's within the allowed time frame
                // This requires more complex logic involving exam_schedule and current time.
                // For simplicity, this placeholder assumes it's allowed if the exam is active.

                // Insert a new record into student_exams
                $stmt = $pdo->prepare("INSERT INTO student_exams (student_id, exam_id, started_at) VALUES (:student_id, :exam_id, NOW())");
                $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
                $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                 if ($stmt->execute()) {
                     $studentExamId = $pdo->lastInsertId();
                     echo "<p class='success'>Starting exam: " . htmlspecialchars($exam['title']) . "</p>";
                 } else {
                     throw new Exception("Error starting exam.");
                 }
            }

            // Fetch questions for the exam
            $sql = "SELECT q.* FROM questions q WHERE q.exam_id = :exam_id ORDER BY q.question_id ASC"; // Order might be randomized in a real system
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For multiple-choice questions, fetch their choices
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'multiple_choice') {
                    $sql = "SELECT choice_id, choice_text FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC"; // Order might be randomized
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question);

        } else {
            echo '<p class="error">Exam not found, not active, or you do not have permission to take it.</p>';
        }

    } catch (PDOException $e) {
        error_log("Error fetching exam for taking: " . $e->getMessage());
        echo '<p class="error">Error loading exam. Please try again later.</p>';
    } catch (Exception $e) {
         error_log("Error starting/resuming exam: " . $e->getMessage());
         echo '<p class="error">Error starting or resuming exam. Please try again later.</p>';
    }

} else {
    echo '<p class="error">No exam ID provided to take.</p>';
}

// --- End: PHP Logic to Fetch Exam and Questions ---

?>

<style>
    /* Basic styling for the take exam page */
    .take-exam-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        margin: 20px auto;
    }

    .take-exam-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .exam-info {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .question-section {
        margin-top: 20px;
    }

    .question-item {
        background-color: #fff;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
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

    .question-options-student { /* Different class name for student view */
        margin-top: 10px;
        padding-left: 20px;
    }

    .question-options-student .option-group {
        margin-bottom: 10px;
    }

    .question-options-student input[type="radio"],
    .question-options-student input[type="checkbox"] { /* If you add multi-select */
        margin-right: 5px;
    }

    .question-options-student label {
        font-weight: normal;
    }

    .blank-input {
        width: 200px; /* Adjust as needed */
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-left: 5px;
        margin-right: 5px;
    }

     .coding-area textarea {
         width: 100%;
         padding: 10px;
         border: 1px solid #ccc;
         border-radius: 4px;
         font-family: monospace;
         min-height: 200px;
         resize: vertical;
     }


    .submit-exam-button {
        display: block;
        width: 100%;
        background-color: #28a745;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1.1em;
        margin-top: 20px;
    }

    .submit-exam-button:hover {
        background-color: #218838;
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

<div class="take-exam-container">
    <?php if ($exam): ?>
        <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
        <div class="exam-info">
            <p>Course: <?php echo htmlspecialchars($exam['course_name']); ?></p>
            <p>Time Limit: <?php echo htmlspecialchars($exam['time_limit']); ?> minutes</p>
            <div id="examTimer">Time Left: --:--</div>
        </div>

        <form id="takeExamForm" method="POST" action="handle_action.php?action=student_submit_exam">
             <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['exam_id']); ?>">
            <input type="hidden" name="student_exam_id" value="<?php echo htmlspecialchars($studentExamId); ?>">


            <div class="question-section">
                <h3>Questions</h3>
                <?php if (empty($questions)): ?>
                    <p>No questions found for this exam.</p>
                <?php else: ?>
                    <?php $questionNumber = 1; ?>
                    <?php foreach ($questions as $question): ?>
                        <div class="question-item">
                            <h4>Question <?php echo $questionNumber++; ?></h4>
                             <input type="hidden" name="answers[<?php echo $question['question_id']; ?>][question_id]" value="<?php echo $question['question_id']; ?>">

                            <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>

                            <div class="question-options-student">
                                <?php if ($question['question_type'] === 'multiple_choice' && isset($question['choices'])): ?>
                                    <?php foreach ($question['choices'] as $choice): ?>
                                        <div class="option-group">
                                            <input type="radio"
                                                   name="answers[<?php echo $question['question_id']; ?>][answer]"
                                                   id="choice_<?php echo $choice['choice_id']; ?>"
                                                   value="<?php echo htmlspecialchars($choice['choice_text']); ?>"
                                                   required> <label for="choice_<?php echo $choice['choice_id']; ?>">
                                                <?php echo htmlspecialchars($choice['choice_text']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                     <div class="option-group">
                                        <input type="radio" name="answers[<?php echo $question['question_id']; ?>][answer]" id="tf_<?php echo $question['question_id']; ?>_true" value="true" required>
                                        <label for="tf_<?php echo $question['question_id']; ?>_true">True</label>
                                    </div>
                                     <div class="option-group">
                                        <input type="radio" name="answers[<?php echo $question['question_id']; ?>][answer]" id="tf_<?php echo $question['question_id']; ?>_false" value="false">
                                        <label for="tf_${question['question_id']}_false">False</label>
                                    </div>
                                <?php elseif ($question['question_type'] === 'blank_space'): ?>
                                     <p>Enter your answer(s):</p>
                                     <textarea name="answers[<?php echo $question['question_id']; ?>][answer]" rows="2" placeholder="Enter your answer(s) here"></textarea>
                                     <?php endif; ?>
                            </div>
                             </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="submit" class="submit-exam-button">Submit Exam</button>
        </form>

    <?php else: // Display message if exam data could not be fetched ?>
        <?php
        // Display error message (already set in the PHP logic)
        // if (!empty($message)) { echo $message; }
        ?>
    <?php endif; ?>

</div>

<script>
    // --- Start: JavaScript for Timer (Basic Placeholder) ---
    // This is a very basic timer and needs significant improvement for a real exam system.
    // A real system would handle timing on the server-side to prevent client-side manipulation.

    document.addEventListener('DOMContentLoaded', () => {
        const timerElement = document.getElementById('examTimer');
        const timeLimitMinutes = <?php echo $exam ? (int)$exam['time_limit'] : 0; ?>;
        let timeLeftSeconds = timeLimitMinutes * 60;

        if (timerElement && timeLimitMinutes > 0) {
            // In a real system, you would calculate remaining time based on started_at timestamp
            // and potentially retrieve it from the server if resuming an exam.

            const timerInterval = setInterval(() => {
                const minutes = Math.floor(timeLeftSeconds / 60);
                const seconds = timeLeftSeconds % 60;

                timerElement.textContent = `Time Left: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                if (timeLeftSeconds <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = 'Time is up!';
                    // Automatically submit the form when time is up
                    document.getElementById('takeExamForm').submit();
                    alert('Time is up! Your exam has been automatically submitted.'); // Basic alert
                }

                timeLeftSeconds--;
            }, 1000);

            // Basic warning before closing/leaving the page (can be annoying)
            // A real system would handle this server-side or with more sophisticated JS
            window.addEventListener('beforeunload', (event) => {
                 // Cancel the event as stated by the standard.
                 event.preventDefault();
                 // Chrome requires returnValue to be set.
                 event.returnValue = '';
                 // Display a message to the user (most browsers ignore custom messages)
                 return 'Your exam is in progress. Are you sure you want to leave?';
            });
        }
    });
    // --- End: JavaScript for Timer ---

    // Optional: Handle form submission with AJAX to prevent full page reload
    // and potentially save answers periodically.
    // This is highly recommended for a real exam system.
    // document.getElementById('takeExamForm').addEventListener('submit', function(e) {
    //     e.preventDefault(); // Prevent default form submission

    //     const formData = new FormData(this); // Get form data

    //     // Send form data via AJAX
    //     fetch('handle_action.php?action=student_submit_exam', {
    //         method: 'POST',
    //         body: formData
    //     })
    //     .then(response => response.text()) // Or response.json()
    //     .then(result => {
    //         // Handle the response (e.g., show success message, redirect to results)
    //         alert(result); // Basic alert
    //         // Redirect to results page or dashboard
    //         // window.location.href = 'handle_action.php?action=student_taken_exams';
    //     })
    //     .catch(error => {
    //         console.error('Error submitting exam:', error);
    //         alert('An error occurred during exam submission.');
    //     });
    // });
</script>

<?php
// If exam data was not found or not active, the error message will be displayed by the PHP logic above.
?>
