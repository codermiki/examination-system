<?php
// includes/instructor/feedbacks.php

// Include necessary configuration or database files
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['email'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if user is instructor
$stmt = $pdo->prepare("SELECT role FROM users WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['email']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'Instructor') {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$feedbacks = [];

// Get instructor ID from email
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
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

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching instructor's feedbacks: " . $e->getMessage());
    $message = '<div class="message error">Error loading feedbacks. Please try again later.</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedbacks</title>
    <style>
        :root {
            --primary-color: #4a6baf;
            --primary-light: #6a8fd8;
            --primary-dark: #2c4a8a;
            --secondary-color: #f5f7fa;
            --accent-color: #ff7043;
            --text-color: #333;
            --light-gray: #e9ecef;
            --medium-gray: #ced4da;
            --dark-gray: #495057;
            --success-color: #4caf50;
            --error-color: #f44336;
            --warning-color: #ff9800;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--secondary-color);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light-gray);
        }

        .page-title {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .message {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: var(--border-radius);
            font-size: 1rem;
            text-align: center;
        }

        .message.success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .message.error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .feedback-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .feedback-table th, 
        .feedback-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .feedback-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        .feedback-table tr:nth-child(even) {
            background-color: rgba(74, 107, 175, 0.05);
        }

        .feedback-table tr:hover {
            background-color: rgba(74, 107, 175, 0.1);
        }

        .feedback-table td:last-child {
            text-align: center;
        }

        .rating {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: bold;
            color: white;
        }

        .rating-5 { background-color: var(--success-color); }
        .rating-4 { background-color: #8bc34a; }
        .rating-3 { background-color: var(--warning-color); }
        .rating-2 { background-color: #ff5722; }
        .rating-1 { background-color: var(--error-color); }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--dark-gray);
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--medium-gray);
            margin-bottom: 15px;
        }

        .empty-state-text {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .feedback-table {
                display: block;
                overflow-x: auto;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .card {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .feedback-table thead {
                display: none;
            }
            
            .feedback-table tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid var(--light-gray);
                border-radius: var(--border-radius);
                padding: 10px;
            }
            
            .feedback-table td {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                border-bottom: 1px dotted var(--medium-gray);
            }
            
            .feedback-table td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--primary-color);
                margin-right: 10px;
            }
            
            .feedback-table td:last-child {
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="page-header">
            <h1 class="page-title">Student Feedbacks</h1>
        </header>

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
</body>
</html>