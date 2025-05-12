<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';
include_once __DIR__ . '/../../includes/functions/instructorPage/Feedback_function.php';

if (!isset($_SESSION['email'])) {
    header('Location: ../../login.php');
    exit();
}

$stmt = $conn->prepare("SELECT role FROM users WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['email']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'Instructor') {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$feedbacks = [];

$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['email']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$instructorId = $user['user_id'];


$feedbacks = Feedback_function::fetchFeedbacks($instructorId);

?>

<div class="container">
    <!-- <header class="page-header">
        
    </header> -->
    <h1 class="page-title">Student Feedbacks</h1>

    <?php if (!empty($message)): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <?php if (!empty($feedbacks)): ?>
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Exam</th>
                        <th>Feedback</th>
                        <th>Date</th>
                        <th>Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedbacks as $feedback): ?>
                        <tr>
                            <td data-label="Student"><?php echo htmlspecialchars($feedback['student_name']); ?></td>
                            <td data-label="Course"><?php echo htmlspecialchars($feedback['course_name']); ?></td>
                            <td data-label="Exam"><?php echo htmlspecialchars($feedback['exam_title']); ?></td>
                            <td data-label="Feedback">
                                <?php if (!empty($feedback['feedback_text'])): ?>
                                    <?php echo nl2br(htmlspecialchars($feedback['feedback_text'])); ?>
                                <?php else: ?>
                                    <span style="color: var(--medium-gray);">No feedback text</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Date"><?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?></td>
                            <td data-label="Rating">
                                <?php if (!empty($feedback['rate'])): ?>
                                    <span class="rating rating-<?php echo $feedback['rate']; ?>">
                                        <?php echo $feedback['rate']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--medium-gray);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3 class="empty-state-text">No feedbacks received yet</h3>
                <p>Student feedbacks will appear here once they submit their evaluations.</p>
            </div>
        <?php endif; ?>
    </div>
</div>