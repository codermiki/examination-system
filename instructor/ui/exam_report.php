<?php

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db/db.config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: /login.php');
    exit();
}

$message = '';
$exam = null;
$studentResults = [];
$instructorExams = [];
$stats = [
    'average' => 0,
    'pass_rate' => 0,
    'high_score' => 0,
    'attempt_count' => 0
];
$instructorId = $_SESSION['user_id']; // Using string ID as per your schema

// Database connection check
if (!isset($conn) || !($conn instanceof PDO)) {
    die('<div class="error">Database connection failed. Please contact administrator.</div>');
}

try {
    // Check if viewing single exam report
    $examId = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
    $showReport = ($examId !== false && $examId > 0);

    if ($showReport) {
        // Get exam details
        $stmt = $conn->prepare("
            SELECT e.*, c.course_name 
            FROM exams e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.exam_id = ? AND e.instructor_id = ?
        ");
        $stmt->execute([$examId, $instructorId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            // Get student results from student_exam_status
            $stmt = $conn->prepare("
                SELECT u.user_id, u.name, 
                       s.score, s.taken_on as attempt_date,
                       CASE WHEN s.score >= (e.total_marks * 0.6) THEN 'passed' ELSE 'failed' END as status
                FROM student_exam_status s
                JOIN users u ON s.student_id = u.user_id
                JOIN exams e ON s.exam_id = e.exam_id
                WHERE s.exam_id = ? AND s.has_taken = 1
                ORDER BY s.score DESC
            ");
            $stmt->execute([$examId]);
            $studentResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate statistics
            if (!empty($studentResults)) {
                $scores = array_column($studentResults, 'score');
                $stats['attempt_count'] = count($studentResults);
                $stats['average'] = array_sum($scores) / $stats['attempt_count'];
                $stats['high_score'] = max($scores);
                $passingScore = $exam['total_marks'] * 0.6; // Assuming 60% is passing
                $passed = count(array_filter($scores, fn($score) => $score >= $passingScore));
                $stats['pass_rate'] = ($passed / $stats['attempt_count']) * 100;
            }
        } else {
            $message = 'Exam not found or access denied.';
            $showReport = false;
        }
    }

    // Get exam list if not showing single report
    if (!$showReport) {
        $stmt = $conn->prepare("
            SELECT e.exam_id, e.exam_title as title, e.exam_description as description, 
                   c.course_name, e.created_at, 
                   COUNT(s.exam_id) as attempt_count
            FROM exams e
            JOIN courses c ON e.course_id = c.course_id
            LEFT JOIN student_exam_status s ON e.exam_id = s.exam_id AND s.has_taken = 1
            WHERE e.instructor_id = ?
            GROUP BY e.exam_id
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$instructorId]);
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $message = 'A database error occurred. Please try again later.';
}

// Helper function to safely output text
function safe_output($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
?>


<div class="container">
    <div class="card">
        <h1><?= $showReport ? "Exam Report: " . safe_output($exam['exam_title']) : 'Your Exams' ?></h1>

        <?php if ($message): ?>
            <div class="alert alert-error"><?= safe_output($message) ?></div>
        <?php endif; ?>

        <?php if ($showReport && $exam): ?>
            <a href="index.php?page=exam_report" class="btn btn-outline" style="margin-bottom: 1.5rem;">
                &larr; Back to Exams
            </a>

            <div style="margin-bottom: 2rem;">
                <h2>Exam Details</h2>
                <div class="exam-details-grid">
                    <div class="exam-detail-item">
                        <strong>Course</strong>
                        <p><?= safe_output($exam['course_name']) ?></p>
                    </div>
                    <div class="exam-detail-item">
                        <strong>Description</strong>
                        <p><?= !empty($exam['exam_description']) ? nl2br(safe_output($exam['exam_description'])) : 'No description provided' ?></p>
                    </div>
                    <div class="exam-detail-item">
                        <strong>Total Marks</strong>
                        <p><?= safe_output($exam['total_marks']) ?></p>
                    </div>
                    <div class="exam-detail-item">
                        <strong>Duration</strong>
                        <p><?= safe_output($exam['duration_minutes']) ?> minutes</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($studentResults)): ?>
                <div style="margin-bottom: 2rem;">
                    <h2>Performance Overview</h2>
                    <div class="grid grid-cols-4">
                        <div class="stat-card">
                            <h3>Students Attempted</h3>
                            <p><?= $stats['attempt_count'] ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Average Score</h3>
                            <p><?= number_format($stats['average'], 1) ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Pass Rate</h3>
                            <p><?= number_format($stats['pass_rate'], 1) ?>%</p>
                        </div>
                        <div class="stat-card">
                            <h3>Highest Score</h3>
                            <p><?= $stats['high_score'] ?></p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2>Student Results</h2>
                    <table>
                        <thead>
                            <tr>
                                <th style="color: white;">Student</th>
                                <th style="color: white;">Attempt Date</th>
                                <th style="color: white;">Score</th>
                                <th style="color: white;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studentResults as $result): ?>
                                <tr>
                                    <td><?= safe_output($result['name']) ?></td>
                                    <td><?= date('M j, Y g:i a', strtotime($result['attempt_date'])) ?></td>
                                    <td><?= safe_output($result['score']) ?> / <?= safe_output($exam['total_marks']) ?></td>
                                    <td class="<?= $result['status'] === 'passed' ? 'text-success' : 'text-danger' ?>">
                                        <?= ucfirst(safe_output($result['status'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No students have attempted this exam yet.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <?php if (!empty($instructorExams)): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="color: white;">Exam Title</th>
                            <th style="color: white;">Course</th>
                            <th style="color: white;">Created</th>
                            <th style="color: white;">Attempts</th>
                            <th style="color: white;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($instructorExams as $exam): ?>
                            <tr>
                                <td><?= safe_output($exam['title']) ?></td>
                                <td><?= safe_output($exam['course_name']) ?></td>
                                <td><?= date('M j, Y', strtotime($exam['created_at'])) ?></td>
                                <td><?= safe_output($exam['attempt_count']) ?></td>
                                <td>
                                    <a href="index.php?page=exam_report&exam_id=<?= safe_output($exam['exam_id']) ?>" class="btn btn-primary">
                                        View Report
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't created any exams yet.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>