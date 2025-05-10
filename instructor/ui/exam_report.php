<?php
// includes/instructor/exam_report.php

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include configuration and database connection
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db/db.config.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: /login.php');
    exit();
}

// Initialize variables
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
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('<div class="error">Database connection failed. Please contact administrator.</div>');
}

try {
    // Check if viewing single exam report
    $examId = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
    $showReport = ($examId !== false && $examId > 0);

    if ($showReport) {
        // Get exam details
        $stmt = $pdo->prepare("
            SELECT e.*, c.course_name 
            FROM exams e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.exam_id = ? AND e.instructor_id = ?
        ");
        $stmt->execute([$examId, $instructorId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            // Get student results from student_exam_status
            $stmt = $pdo->prepare("
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
        $stmt = $pdo->prepare("
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
function safe_output($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $showReport ? "Exam Report: " . safe_output($exam['exam_title']) : 'Exam Reports' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #10b981;
            --danger: #ef4444;
            --light: #f9fafb;
            --dark: #111827;
            --gray: #6b7280;
            --border: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: var(--dark);
            line-height: 1.5;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        h1, h2, h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        h1 {
            font-size: 1.75rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
            transform: translateY(-1px);
        }
        
        .btn-outline {
            border: 1px solid var(--border);
            color: var(--gray);
        }
        
        .btn-outline:hover {
            background-color: var(--light);
        }
        
        .grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .grid-cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }
        
        .stat-card {
            padding: 1.5rem;
            border-radius: 0.5rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }
        
        .stat-card h3 {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        tr:nth-child(even) {
            background-color: var(--light);
        }
        
        tr:hover {
            background-color: #f0f0ff;
        }
        
        .text-success {
            color: var(--secondary);
        }
        
        .text-danger {
            color: var(--danger);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .exam-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }
        
        .exam-detail-item strong {
            display: block;
            color: var(--gray);
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .container {
                padding: 0 0.5rem;
            }
            
            .card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1><?= $showReport ? "Exam Report: " . safe_output($exam['exam_title']) : 'Your Exams' ?></h1>
            
            <?php if ($message): ?>
                <div class="alert alert-error"><?= safe_output($message) ?></div>
            <?php endif; ?>
            
            <?php if ($showReport && $exam): ?>
                <a href="exam_report.php" class="btn btn-outline" style="margin-bottom: 1.5rem;">
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
                                    <th>Student</th>
                                    <th>Attempt Date</th>
                                    <th>Score</th>
                                    <th>Status</th>
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
                                <th>Exam Title</th>
                                <th>Course</th>
                                <th>Created</th>
                                <th>Attempts</th>
                                <th>Action</th>
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
                                        <a href="ui/exam_report.php?exam_id=<?= safe_output($exam['exam_id']) ?>" class="btn btn-primary">
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
</body>
</html>