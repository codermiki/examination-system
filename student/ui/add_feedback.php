<?php
include_once '../config.php';
include_once '../includes/db/db.config.php';
include __DIR__ . "/../../includes/functions/Exam_function.php";

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Student' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in student to add feedback.</p>';
    exit();
}

$studentId = $_SESSION['user_id'];
$message = '';
$exams = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = filter_var($_POST['exam_id'] ?? null, FILTER_VALIDATE_INT);
    $feedback_text = trim($_POST['feedback_message'] ?? '');
    $rating = filter_var($_POST['rating'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($feedback_text) || $rating === false || $rating < 1 || $rating > 5) {
        $message = '<p class="error">Error: Please select an Exam, provide a message, and a rating between 1 and 5.</p>';
    } else {
        try {
            $sql = "INSERT INTO feedbacks (student_id, exam_id, feedback_text, rate) VALUES (:student_id, :exam_id, :feedback_text, :rate)";
            $stmt = $conn->prepare($sql);

            $result = $stmt->execute([
                ':student_id' => $studentId,
                ':exam_id' => $examId,
                ':feedback_text' => $feedback_text,
                ':rate' => $rating
            ]);

            $message = $result
                ? '<p class="success">Feedback submitted successfully. Thank you!</p>'
                : '<p class="error">An error occurred while submitting feedback. Please try again.</p>';
        } catch (PDOException $e) {
            error_log("Error submitting feedback: " . $e->getMessage());
            $message = '<p class="error">An error occurred while submitting feedback. Please try again.</p>';
        }
    }
}

try {
    $exams = Exam_function::takenExamsPerStudent($studentId);
} catch (PDOException $e) {
    error_log("Error fetching exams for feedback form");
    $message .= '<p class="error">Could not load exams. Please try again.</p>';
}
?>

<style>
    /* feedback.css */
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
        width: 80px !important;
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
<button style="margin: 10px;" onclick="window.location.href='/softexam/student'">← Back</button>

<div class="feedback-container">
    <h2>Add Feedback</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="?page=add_feedback" method="POST">
        <div class="form-group">
            <label for="exam_id">Select Exam (Optional):</label>
            <select id="exam_id" name="exam_id">
                <option value="">-- Select Exam</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo htmlspecialchars($exam['exam_id']); ?>">
                        <?php echo htmlspecialchars($exam['exam_title']); ?>
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