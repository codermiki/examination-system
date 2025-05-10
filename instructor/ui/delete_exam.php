<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor' || !isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$message = '';
$instructorExams = [];

$instructorId = $_SESSION['user_id'];

if (isset($_GET['exam_id']) && isset($_GET['confirm_delete']) && $_GET['confirm_delete'] === 'yes') {
    $examIdToDelete = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);

    if ($examIdToDelete === false || $examIdToDelete <= 0) {
        $message = '<p class="error">Invalid exam ID provided for deletion.</p>';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtCheckOwner = $pdo->prepare("SELECT exam_id FROM exams WHERE exam_id = :exam_id AND instructor_id = :instructor_id");
            $stmtCheckOwner->bindParam(':exam_id', $examIdToDelete, PDO::PARAM_INT);
            $stmtCheckOwner->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
            $stmtCheckOwner->execute();

            if ($stmtCheckOwner->rowCount() === 0) {
                $message = '<p class="error">Exam not found or you do not have permission to delete this exam.</p>';
                $pdo->rollBack();
            } else {
                $stmtDeleteAnswers = $pdo->prepare("DELETE sa FROM student_answers sa JOIN questions q ON sa.question_id = q.question_id WHERE q.exam_id = :exam_id");
                $stmtDeleteAnswers->bindParam(':exam_id', $examIdToDelete, PDO::PARAM_INT);
                $stmtDeleteAnswers->execute();

                $stmtDeleteChoices = $pdo->prepare("DELETE c FROM choices c JOIN questions q ON c.question_id = q.question_id WHERE q.exam_id = :exam_id");
                $stmtDeleteChoices->bindParam(':exam_id', $examIdToDelete, PDO::PARAM_INT);
                $stmtDeleteChoices->execute();

                $stmtDeleteQuestions = $pdo->prepare("DELETE FROM questions WHERE exam_id = :exam_id");
                $stmtDeleteQuestions->bindParam(':exam_id', $examIdToDelete, PDO::PARAM_INT);
                $stmtDeleteQuestions->execute();

                $stmtDeleteExam = $pdo->prepare("DELETE FROM exams WHERE exam_id = :exam_id");
                $stmtDeleteExam->bindParam(':exam_id', $examIdToDelete, PDO::PARAM_INT);
                $stmtDeleteExam->execute();

                $pdo->commit();
                $message = '<p class="success">Exam deleted successfully.</p>';
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error deleting exam: " . $e->getMessage());
            $message = '<p class="error">An error occurred while trying to delete the exam. Please try again.</p>';
        }
    }
}

try {
    $sql = "SELECT e.exam_id, e.title, e.description, c.course_name, e.created_at
            FROM exams e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.instructor_id = :instructor_id
            ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
    $stmt->execute();
    $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching instructor's exams for delete list: " . $e->getMessage());
    if (empty($message)) {
         $message = '<p class="error">Error loading your exams list. Please try again later.</p>';
    } else {
         $message .= '<p class="error">Could not fully load your exams list after attempting deletion.</p>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Exam</title>
    <style>
        .page-container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 30px auto;
            font-family: sans-serif;
            color: #333;
        }

        .page-container h1, .page-container h2 {
             color: #0056b3;
             text-align: center;
             margin-bottom: 25px;
        }

        .page-container h2 {
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 1em;
            line-height: 1.4;
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

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
        }

        .exam-table th, .exam-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .exam-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }

        .exam-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .exam-table td a {
            font-weight: normal;
            text-decoration: none;
            color: #dc3545;
            padding: 5px 10px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            display: inline-block;
        }

        .exam-table td a:hover {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
        }

        .exam-table td small {
            color: #666;
        }

    </style>
</head>
<body>

    <main>
        <div class="page-container">

            <h1>Delete Exam</h1>

            <?php
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>

            <?php if (!empty($instructorExams)): ?>
                <table class="exam-table">
                    <thead>
                        <tr>
                            <th>Exam Title</th>
                            <th>Course</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($instructorExams as $instExam): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($instExam['title']); ?></td>
                                <td><?php echo htmlspecialchars($instExam['course_name']); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($instExam['created_at']))); ?></td>
                                <td>
                                    <a href="ui/delete_exam.php?exam_id=<?php echo htmlspecialchars($instExam['exam_id']); ?>&confirm_delete=yes"
                                       onclick="return confirm('Are you sure you want to delete the exam titled \'<?php echo htmlspecialchars($instExam['title']); ?>\'? This action cannot be undone and will delete all associated questions, choices, and student answers.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You have not created any exams yet.</p>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>
