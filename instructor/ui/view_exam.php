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
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            $showSingleExamView = true;
            // Get questions
            $sql_q = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC";
            $stmt_q = $conn->prepare($sql_q);
            $stmt_q->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
            $stmt_q->execute();
            $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            // Get choices for MC questions
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'multiple_choice') {
                    $sql_c = "SELECT * FROM question_options WHERE question_id = :question_id ORDER BY option_id ASC";
                    $stmt_c = $conn->prepare($sql_c);
                    $stmt_c->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt_c->execute();
                    $question['choices'] = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question);
        } else {
            $message = '<div class="alert error"><span class="icon">⚠️</span> Exam not found or access denied.</div>';
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam: " . $e->getMessage());
        $message = '<div class="alert error"><span class="icon">⚠️</span> Error loading exam details.</div>';
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
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
        $stmt->execute();
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching exams: " . $e->getMessage());
        $message = '<div class="alert error"><span class="icon">⚠️</span> Error loading exam list.</div>';
    }
}
?>

<div class="container">
    <?php if (!empty($message)) echo $message; ?>

    <?php if ($showSingleExamView && $exam): ?>
        <div class="card">
            <div class="card-header">
                <h2><span class="icon">📝</span> Exam Details</h2>
                <a href="index.php?page=view_exam" class="btn btn-outline btn-sm">
                    <span class="icon">←</span> Back to Exams
                </a>
            </div>
            <div class="card-body">
                <div class="exam-details">
                    <div class="detail-card">
                        <h4><span class="icon">📚</span> Course</h4>
                        <p><?= htmlspecialchars($exam['course_name']) ?></p>
                    </div>
                    <div class="detail-card">
                        <h4><span class="icon">⏱️</span> Duration</h4>
                        <p><?= htmlspecialchars($exam['duration_minutes']) ?> minutes</p>
                    </div>
                    <div class="detail-card">
                        <h4><span class="icon">⭐</span> Total Marks</h4>
                        <p><?= htmlspecialchars($exam['total_marks']) ?></p>
                    </div>
                    <div class="detail-card">
                        <h4><span class="icon">🔌</span> Status</h4>
                        <p>
                            <span class="badge <?= $exam['status'] === 'Active' ? 'badge-success' : 'badge-danger' ?>">
                                <?= htmlspecialchars(ucfirst($exam['status'])) ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="detail-card" style="margin-bottom: 2rem;">
                    <h4><span class="icon">📄</span> Description</h4>
                    <p><?= nl2br(htmlspecialchars($exam['exam_description'])) ?></p>
                </div>

                <h3 style="margin: 2rem 0 1rem;"><span class="icon">❓</span> Questions (<?= count($questions) ?>)</h3>

                <?php if (empty($questions)): ?>
                    <div class="empty-state">
                        <span class="icon">❔</span>
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
                                                        <span class="icon">✓</span> Correct
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
                                        <a href="index.php?page=view_exam&exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn btn-outline btn-sm">
                                            <span class="icon">👁️</span> View
                                        </a>
                                        <a href="index.php?page=edit_exam&exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn btn-warning btn-sm">
                                            <span class="icon">✏️</span> Edit
                                        </a>
                                        <a href="ui/delete_exam.php?exam_id=<?= htmlspecialchars($exam['exam_id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this exam? This action cannot be undone.');">
                                            <span class="icon">🗑️</span> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="icon">📋</span>
                        <p>You haven't created any exams yet.</p>
                        <p style="margin-top:1rem;"><a href="create_exam.php" class="btn btn-primary">Create New Exam</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>