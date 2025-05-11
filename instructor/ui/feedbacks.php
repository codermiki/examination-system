<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

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

try {
    $sql = "SELECT
                f.id AS feedback_id,
                f.student_id,
                f.exam_id,
                f.feedback_text,
                f.rate,
                f.created_at,
                u.name AS student_name,
                c.course_name,
                e.exam_title
            FROM feedbacks f
            JOIN users u ON f.student_id = u.user_id
            JOIN exams e ON f.exam_id = e.exam_id
            JOIN courses c ON e.course_id = c.course_id 
            WHERE e.instructor_id = :instructor_id
            ORDER BY f.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instructor's feedbacks: " . $e->getMessage());
    $message = '<div class="message error">Error loading feedbacks. Please try again later.</div>';
}
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