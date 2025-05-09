<?php

// Include necessary configuration or database files
include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is a student
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in student to add feedback.</p>';
    exit();
}

$studentId = $_SESSION['user_id']; // Get the logged-in student's user_id
$message = ''; // Variable to store feedback messages
$courses = []; // Array to hold courses for the dropdown
$exams = []; // Array to hold exams for the dropdown (optional, if feedback is exam-specific)


// --- Start: PHP Logic for Handling Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate feedback data from POST
    $courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
    $examId = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT); // Exam ID is optional
    $messageText = trim($_POST['feedback_message'] ?? '');
    $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);

    // Basic validation
    if ($courseId === false || $courseId <= 0 || empty($messageText) || $rating === false || $rating < 1 || $rating > 5) {
        $message = '<p class="error">Error: Please select a course, provide a message, and a rating between 1 and 5.</p>';
    } else {
        // Data seems valid, proceed with database insertion
        try {
            // Insert into feedbacks table
            $sql = "INSERT INTO feedbacks (student_id, course_id, exam_id, message, rating) VALUES (:student_id, :course_id, :exam_id, :message, :rating)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
            $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
            // Bind exam_id, handle null if not provided
            if ($examId === null || $examId <= 0) {
                 $stmt->bindValue(':exam_id', null, PDO::PARAM_NULL);
            } else {
                 $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            }
            $stmt->bindParam(':message', $messageText, PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $message = '<p class="success">Feedback submitted successfully. Thank you!</p>';
                // Clear form fields after successful submission (optional)
                // $messageText = ''; $rating = ''; // This would require JS if using AJAX
            } else {
                 throw new Exception("Error inserting feedback data: " . implode(" ", $stmt->errorInfo()));
            }

        } catch (PDOException $e) {
            error_log("Error submitting feedback: " . $e->getMessage());
            $message = '<p class="error">An error occurred while submitting feedback. Please try again.</p>';
        } catch (Exception $e) {
             error_log("Feedback submission error: " . $e->getMessage());
             $message = '<p class="error">An error occurred while submitting feedback. Please try again.</p>';
        }
    }
}
// --- End: PHP Logic for Handling Form Submission ---

// --- Start: PHP Logic for Fetching Courses and Exams (for dropdowns) ---
try {
    // Fetch courses the student is enrolled in (assuming student_courses table exists)
    // If you don't have a student_courses table, you might fetch all courses or courses with exams.
    // For this example, fetching all courses.
    $sql = "SELECT course_id, course_name FROM courses ORDER BY course_name";
    $stmt = $pdo->query($sql);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch exams the student has taken (optional, for exam-specific feedback)
    $sql = "SELECT DISTINCT e.exam_id, e.title
            FROM exams e
            JOIN student_exams se ON e.exam_id = se.exam_id
            WHERE se.student_id = :student_id AND se.submitted_at IS NOT NULL
            ORDER BY e.title";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    error_log("Error fetching courses/exams for feedback form: " . $e->getMessage());
    $message .= '<p class="error">Could not load courses or exams. Please try again.</p>';
}
// --- End: PHP Logic for Fetching Courses and Exams ---

?>

<style>
    /* Basic styling for the feedback form */
    .feedback-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 20px auto;
    }

    .feedback-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    .form-group select,
    .form-group textarea,
    .form-group input[type="number"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    textarea {
        resize: vertical;
    }

     .rating-input {
         width: 80px !important; /* Make rating input smaller */
     }


    button[type="submit"] {
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

    button[type="submit"]:hover {
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

<div class="feedback-container">
    <h2>Add Feedback</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <form action="handle_action.php?action=student_add_feedback_submit" method="POST">
        <div class="form-group">
            <label for="course_id">Select Course:</label>
            <select id="course_id" name="course_id" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

         <div class="form-group">
            <label for="exam_id">Select Exam (Optional):</label>
            <select id="exam_id" name="exam_id">
                <option value="">-- Select Exam (Optional) --</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo htmlspecialchars($exam['exam_id']); ?>">
                        <?php echo htmlspecialchars($exam['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


        <div class="form-group">
            <label for="feedback_message">Your Feedback:</label>
            <textarea id="feedback_message" name="feedback_message" rows="6" required></textarea>
        </div>

        <div class="form-group">
            <label for="rating">Rating (1-5):</label>
            <input type="number" id="rating" name="rating" class="rating-input" min="1" max="5" required>
        </div>

        <button type="submit">Submit Feedback</button>
    </form>
</div>

<?php
// No JavaScript needed for this basic form.
?>
