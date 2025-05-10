<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor' || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$exam = null;
$questions = [];
$instructorExams = [];
$instructorId = $_SESSION['user_id'];

// Check if viewing single exam
$showSingleExamView = false;
$examIdFromGet = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);

if ($examIdFromGet) {
    try {
        $sql = "SELECT e.*, c.course_name 
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.exam_id = :exam_id AND e.instructor_id = :instructor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            $showSingleExamView = true;
            // Get questions
            $sql_q = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC";
            $stmt_q = $pdo->prepare($sql_q);
            $stmt_q->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
            $stmt_q->execute();
            $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            // Get choices for MC questions
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'multiple_choice') {
                    $sql_c = "SELECT * FROM question_options WHERE question_id = :question_id ORDER BY option_id ASC";
                    $stmt_c = $pdo->prepare($sql_c);
                    $stmt_c->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt_c->execute();
                    $question['choices'] = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question);
        } else {
            $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Exam not found or access denied.</div>';
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam: " . $e->getMessage());
        $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Error loading exam details.</div>';
    }
}

// Get exam list if not viewing single exam
if (!$showSingleExamView) {
    try {
        $sql = "SELECT e.exam_id, e.exam_title as title, e.exam_description as description, 
                       c.course_name, e.created_at, e.status
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.instructor_id = :instructor_id
                ORDER BY e.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
        $stmt->execute();
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching exams: " . $e->getMessage());
        $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Error loading exam list.</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $showSingleExamView ? 'Exam Details' : 'My Exams' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert.error {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert.success {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background-color: rgba(67, 97, 238, 0.1);
        }
        
        .btn-warning {
            background-color: var(--warning);
            color: white;
            border: 1px solid var(--warning);
        }
        .btn-warning:hover {
            background-color: #e0a800; /* Darken warning color on hover */
            border-color: #e0a800;
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            border: 1px solid var(--danger);
        }
        .btn-danger:hover {
            background-color: #c82333; /* Darken danger color on hover */
            border-color: #bd2130;
        }


        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: var(--primary);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 500;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background-color: rgba(67, 97, 238, 0.03);
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success);
        }

        .badge-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger);
        }

        .exam-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
        }

        .detail-card h4 {
            font-size: 0.875rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-card p {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .question-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .question-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--accent);
        }

        .question-card h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--dark);
            display: flex;
            gap: 0.5rem;
        }

        .question-card .question-type {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .options-list {
            list-style: none;
            margin-top: 1rem;
        }

        .options-list li {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background-color: var(--light);
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .options-list li::before {
            content: '○';
            color: var(--primary);
            font-size: 0.75rem;
        }

        .correct-answer {
            color: var(--success);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
        }
        
        .actions-cell {
            white-space: nowrap; /* Prevents buttons from wrapping to the next line */
        }

        .actions-cell .btn {
            margin-right: 5px; /* Adds a small space between buttons */
        }
        .actions-cell .btn:last-child {
            margin-right: 0; /* No margin for the last button */
        }


        @media (max-width: 768px) {
            .container {
                padding: 0 0.75rem;
            }
            
            .exam-details {
                grid-template-columns: 1fr;
            }
            
            .table {
                display: block;
                overflow-x: auto; /* Allows horizontal scrolling for the table on small screens */
            }
            .actions-cell {
                white-space: normal; /* Allow buttons to wrap on smaller screens if necessary */
            }
            .actions-cell .btn {
                margin-bottom: 5px; /* Add some space below buttons if they wrap */
                display: block; /* Make buttons take full width if they wrap */
                width: 100%;
                text-align: center;
            }
            .actions-cell .btn:last-child {
                 margin-bottom: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($message)) echo $message; ?>

        <?php if ($showSingleExamView && $exam): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-clipboard-list"></i> Exam Details</h2>
                    <a href="view_exam.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Exams
                    </a>
                </div>
                <div class="card-body">
                    <div class="exam-details">
                        <div class="detail-card">
                            <h4><i class="fas fa-book"></i> Course</h4>
                            <p><?= htmlspecialchars($exam['course_name']) ?></p>
                        </div>
                        <div class="detail-card">
                            <h4><i class="fas fa-clock"></i> Duration</h4>
                            <p><?= htmlspecialchars($exam['duration_minutes']) ?> minutes</p>
                        </div>
                        <div class="detail-card">
                            <h4><i class="fas fa-star"></i> Total Marks</h4>
                            <p><?= htmlspecialchars($exam['total_marks']) ?></p>
                        </div>
                        <div class="detail-card">
                            <h4><i class="fas fa-power-off"></i> Status</h4>
                            <p>
                                <span class="badge <?= $exam['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= htmlspecialchars(ucfirst($exam['status'])) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="detail-card" style="margin-bottom: 2rem;">
                        <h4><i class="fas fa-align-left"></i> Description</h4>
                        <p><?= nl2br(htmlspecialchars($exam['exam_description'])) ?></p>
                    </div>

                    <h3 style="margin: 2rem 0 1rem;"><i class="fas fa-question-circle"></i> Questions (<?= count($questions) ?>)</h3>

                    <?php if (empty($questions)): ?>
                        <div class="empty-state">
                            <i class="fas fa-question"></i>
                            <p>No questions found for this exam.</p>
                        </div>
                    <?php else: ?>
                        <div class="question-list">
                            <?php foreach ($questions as $i => $question): ?>
                                <div class="question-card">
                                    <h3>Q<?= $i + 1 ?>: <?= htmlspecialchars($question['question_text']) ?></h3>
                                    <span class="question-type">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $question['question_type']))) ?>
                                    </span>
                                    <p><strong>Marks:</strong> <?= htmlspecialchars($question['marks']) ?></p>

                                    <?php if ($question['question_type'] === 'multiple_choice' && isset($question['choices'])): ?>
                                        <p><strong>Options:</strong></p>
                                        <ul class="options-list">
                                            <?php foreach ($question['choices'] as $choice): ?>
                                                <li>
                                                    <?= htmlspecialchars($choice['option_text']) ?>
                                                    <?php if (strtolower($choice['option_text']) === strtolower($question['correct_answer'])): ?>
                                                        <span class="correct-answer">
                                                            <i class="fas fa-check"></i> Correct
                                                        </span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif ($question['question_type'] === 'true_false'): ?>
                                        <p><strong>Correct Answer:</strong> 
                                            <span class="correct-answer">
                                                <?= htmlspecialchars(ucfirst($question['correct_answer'])) ?>
                                            </span>
                                        </p>
                                    <?php elseif ($question['question_type'] === 'fill_blank'): ?>
                                        <p><strong>Correct Answer:</strong> 
                                            <span class="correct-answer">
                                                <?= htmlspecialchars($question['correct_answer']) ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <!-- <div class="card-header">
                    <h2><i class="fas fa-clipboard-list"></i> My Exams</h2>
                </div> -->
                <div class="card-body">
                    <?php if (!empty($instructorExams)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Exam Title</th>
                                    <th>Course</th>
                                    <th>Created</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instructorExams as $exam): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($exam['title']) ?></strong>
                                            <p style="color: var(--gray); font-size: 0.875rem; margin-top: 0.25rem;">
                                                <?= htmlspecialchars(substr($exam['description'], 0, 60)) . (strlen($exam['description']) > 60 ? '...' : '') ?>
                                            </p>
                                        </td>
                                        <td><?= htmlspecialchars($exam['course_name']) ?></td>
                                        <td><?= htmlspecialchars(date('M j, Y', strtotime($exam['created_at']))) ?></td>
                                        <td>
                                            <span class="badge <?= $exam['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>">
                                                <?= htmlspecialchars(ucfirst($exam['status'])) ?>
                                            </span>
                                        </td>
                                        
                                        <td class="actions-cell"> 
                                            <a href="ui/view_exam.php?exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="ui/edit_exam.php?exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="ui/delete_exam.php?exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this exam? This action cannot be undone.');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <p>You haven't created any exams yet.</p>
                            <p style="margin-top:1rem;"><a href="create_exam.php" class="btn btn-primary">Create New Exam</a></p> 
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>