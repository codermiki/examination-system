<?php
// includes/instructor/edit_exam.php

// Include necessary configuration or database files
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
if (!isset($_SESSION['email'])) {
    echo '<p>Access denied. You must be logged in.</p>';
    exit();
}

// Check if user is instructor
$stmt = $pdo->prepare("SELECT role FROM users WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['email']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'Instructor') {
    echo '<p>Access denied. You must be an instructor.</p>';
    exit();
}

$message = '';
$exam = null;
$questions = [];
$courses = [];
$instructorExams = [];

// Get instructor ID from email
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['email']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$instructorId = $user['user_id'];

// --- Start: PHP Logic for Handling Form Submission (Updating Exam) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exam_id'])) {
    // Get and validate exam details from POST
    $examIdToUpdate = filter_var($_POST['exam_id'], FILTER_VALIDATE_INT);
    $examTitle = trim($_POST['exam_title'] ?? '');
    $examDescription = trim($_POST['exam_description'] ?? '');
    $examDuration = filter_var($_POST['exam_duration'] ?? 0, FILTER_VALIDATE_INT);
    $courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
    $totalMarks = filter_var($_POST['total_marks'] ?? 0, FILTER_VALIDATE_INT);
    $questionsData = $_POST['questions'] ?? [];

    // Basic validation
    if ($examIdToUpdate === false || $examIdToUpdate <= 0 || empty($examTitle) || 
        $examDuration === false || $examDuration <= 0 || $courseId === false || 
        $courseId <= 0 || $totalMarks === false || $totalMarks < 0) {
        $message = '<div class="message error">Error: Invalid exam ID or missing/incorrect exam details.</div>';
    } elseif (!is_array($questionsData) || count($questionsData) === 0) {
        $message = '<div class="message error">Error: Please add at least one question.</div>';
    } else {
        try {
            $pdo->beginTransaction();

            // Update exams table
            $stmt = $pdo->prepare("UPDATE exams 
                                  SET course_id = :course_id, 
                                      exam_title = :exam_title, 
                                      exam_description = :exam_description, 
                                      duration_minutes = :duration_minutes, 
                                      total_marks = :total_marks 
                                  WHERE exam_id = :exam_id 
                                  AND instructor_id = :instructor_id");
            
            $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
            $stmt->bindParam(':exam_title', $examTitle, PDO::PARAM_STR);
            $stmt->bindParam(':exam_description', $examDescription, PDO::PARAM_STR);
            $stmt->bindParam(':duration_minutes', $examDuration, PDO::PARAM_INT);
            $stmt->bindParam(':total_marks', $totalMarks, PDO::PARAM_INT);
            $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                throw new Exception("Error updating exam details: " . implode(" ", $stmt->errorInfo()));
            }

            // Fetch current questions for this exam
            $currentQuestionsStmt = $pdo->prepare("SELECT question_id FROM questions WHERE exam_id = :exam_id");
            $currentQuestionsStmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $currentQuestionsStmt->execute();
            $currentQuestionIds = $currentQuestionsStmt->fetchAll(PDO::FETCH_COLUMN);

            // Get submitted question IDs
            $submittedQuestionIds = [];
            foreach ($questionsData as $qData) {
                if (!empty($qData['question_id'])) {
                    $submittedQuestionIds[] = (int)$qData['question_id'];
                }
            }

            // Determine questions to delete
            $questionIdsToDelete = array_diff($currentQuestionIds, $submittedQuestionIds);

            // Delete removed questions and their associated data
            if (!empty($questionIdsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($questionIdsToDelete), '?'));

                // Delete student answers
                $deleteStudentAnswersStmt = $pdo->prepare("DELETE sa FROM student_answers sa 
                                                          JOIN questions q ON sa.question_id = q.question_id 
                                                          WHERE q.exam_id = ? 
                                                          AND sa.question_id IN ($placeholders)");
                $bindParamsSA = array_merge([$examIdToUpdate], $questionIdsToDelete);
                $deleteStudentAnswersStmt->execute($bindParamsSA);

                // Delete question options
                $deleteChoicesStmt = $pdo->prepare("DELETE FROM question_options 
                                                   WHERE question_id IN ($placeholders)");
                $deleteChoicesStmt->execute($questionIdsToDelete);

                // Delete the questions
                $deleteQuestionsStmt = $pdo->prepare("DELETE FROM questions 
                                                    WHERE exam_id = ? 
                                                    AND question_id IN ($placeholders)");
                $bindParamsQ = array_merge([$examIdToUpdate], $questionIdsToDelete);
                $deleteQuestionsStmt->execute($bindParamsQ);
            }

            // Process submitted questions
            $allowedQuestionTypes = ['multiple_choice', 'true_false', 'fill_blank'];
            foreach ($questionsData as $question) {
                $questionId = filter_var($question['question_id'] ?? 0, FILTER_VALIDATE_INT);
                $questionText = trim($question['text'] ?? '');
                $questionType = $question['type'] ?? '';
                $marks = filter_var($question['marks'] ?? 1, FILTER_VALIDATE_INT);
                $correctAnswer = null;

                // Validate question data
                if (empty($questionText)) {
                    continue;
                }

                if (!in_array($questionType, $allowedQuestionTypes)) {
                    $questionType = 'multiple_choice';
                }

                // Determine correct answer based on type
                if ($questionType === 'true_false') {
                    $correctAnswer = $question['correct_answer'] ?? null;
                    if (!in_array($correctAnswer, ['true', 'false'])) {
                        continue;
                    }
                } elseif ($questionType === 'fill_blank') {
                    if (isset($question['answers']) && is_array($question['answers']) && count($question['answers']) > 0) {
                        $validAnswers = array_filter($question['answers'], 'trim');
                        if (count($validAnswers) > 0) {
                            $correctAnswer = implode('|', $validAnswers);
                        }
                    }
                }

                if (!empty($questionId) && in_array($questionId, $currentQuestionIds)) {
                    // Update existing question
                    $stmt = $pdo->prepare("UPDATE questions 
                                          SET question_text = :question_text, 
                                              question_type = :question_type, 
                                              correct_answer = :correct_answer,
                                              marks = :marks
                                          WHERE question_id = :question_id 
                                          AND exam_id = :exam_id");
                    
                    $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                    $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                    $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);
                    $stmt->bindParam(':marks', $marks, PDO::PARAM_INT);
                    $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                    $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);

                    if (!$stmt->execute()) {
                        throw new Exception("Error updating question ID " . $questionId . ": " . implode(" ", $stmt->errorInfo()));
                    }

                    // Handle options for multiple choice questions
                    if ($questionType === 'multiple_choice') {
                        if (!isset($question['options'])) {
                            continue;
                        }

                        // Fetch current options
                        $currentOptionsStmt = $pdo->prepare("SELECT option_id FROM question_options 
                                                            WHERE question_id = :question_id");
                        $currentOptionsStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                        $currentOptionsStmt->execute();
                        $currentOptionIds = $currentOptionsStmt->fetchAll(PDO::FETCH_COLUMN);

                        // Get submitted option IDs
                        $submittedOptionIds = [];
                        foreach ($question['options'] as $optionData) {
                            if (isset($optionData['option_id']) && !empty($optionData['option_id'])) {
                                $submittedOptionIds[] = (int)$optionData['option_id'];
                            }
                        }

                        // Determine options to delete
                        $optionIdsToDelete = array_diff($currentOptionIds, $submittedOptionIds);

                        // Delete removed options
                        if (!empty($optionIdsToDelete)) {
                            $placeholders = implode(',', array_fill(0, count($optionIdsToDelete), '?'));
                            $deleteOptionsStmt = $pdo->prepare("DELETE FROM question_options 
                                                               WHERE question_id = ? 
                                                               AND option_id IN ($placeholders)");
                            $bindParams = array_merge([$questionId], $optionIdsToDelete);
                            $deleteOptionsStmt->execute($bindParams);
                        }

                        // Process submitted options
                        foreach ($question['options'] as $optionData) {
                            $optionId = filter_var($optionData['option_id'] ?? 0, FILTER_VALIDATE_INT);
                            $optionText = trim($optionData['text'] ?? '');

                            if (empty($optionText)) {
                                continue;
                            }

                            if (!empty($optionId) && in_array($optionId, $currentOptionIds)) {
                                // Update existing option
                                $stmt = $pdo->prepare("UPDATE question_options 
                                                      SET option_text = :option_text 
                                                      WHERE option_id = :option_id 
                                                      AND question_id = :question_id");
                                
                                $stmt->bindParam(':option_text', $optionText, PDO::PARAM_STR);
                                $stmt->bindParam(':option_id', $optionId, PDO::PARAM_INT);
                                $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Error updating option ID " . $optionId . ": " . implode(" ", $stmt->errorInfo()));
                                }
                            } else {
                                // Insert new option
                                $stmt = $pdo->prepare("INSERT INTO question_options 
                                                      (question_id, option_text) 
                                                      VALUES (:question_id, :option_text)");
                                
                                $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                                $stmt->bindParam(':option_text', $optionText, PDO::PARAM_STR);
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Error inserting new option for question " . $questionId . ": " . implode(" ", $stmt->errorInfo()));
                                }
                            }
                        }
                    }
                } else {
                    // Insert new question
                    $stmt = $pdo->prepare("INSERT INTO questions 
                                          (exam_id, question_text, question_type, correct_answer, marks) 
                                          VALUES (:exam_id, :question_text, :question_type, :correct_answer, :marks)");
                    
                    $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                    $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                    $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                    $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);
                    $stmt->bindParam(':marks', $marks, PDO::PARAM_INT);

                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting new question for exam " . $examIdToUpdate . ": " . implode(" ", $stmt->errorInfo()));
                    }

                    $newQuestionId = $pdo->lastInsertId();

                    // Handle options for new multiple choice questions
                    if ($questionType === 'multiple_choice' && isset($question['options'])) {
                        foreach ($question['options'] as $optionData) {
                            $optionText = trim($optionData['text'] ?? '');

                            if (empty($optionText)) {
                                continue;
                            }

                            $stmt = $pdo->prepare("INSERT INTO question_options 
                                                  (question_id, option_text) 
                                                  VALUES (:question_id, :option_text)");
                            
                            $stmt->bindParam(':question_id', $newQuestionId, PDO::PARAM_INT);
                            $stmt->bindParam(':option_text', $optionText, PDO::PARAM_STR);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Error inserting option for new question " . $newQuestionId . ": " . implode(" ", $stmt->errorInfo()));
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $message = '<div class="message success">Exam "' . htmlspecialchars($examTitle) . '" updated successfully.</div>';

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Exam update error: " . $e->getMessage());
            $message = '<div class="message error">Error updating exam. Please check the details and try again.</div>';
            $examIdToLoad = $examIdToUpdate;
        }
    }
}
// --- End: PHP Logic for Handling Form Submission ---

// --- Start: PHP Logic for Displaying Page (List or Edit Form) ---

// Fetch courses for the dropdown
try {
    $sql = "SELECT c.course_id, c.course_name
            FROM courses c
            JOIN assigned_instructors ai ON c.course_id = ai.course_id
            WHERE ai.instructor_id = :instructor_id
            AND ai.status = 'Active'
            ORDER BY c.course_name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instructor courses: " . $e->getMessage());
    $message .= '<div class="message error">Could not load courses list.</div>';
}

// Determine whether to show the list or the edit form
$showEditForm = false;
$examIdFromGet = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
$examIdToLoad = $examIdToLoad ?? $examIdFromGet;

if ($examIdToLoad) {
    try {
        $sql = "SELECT e.*, c.course_name
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.exam_id = :exam_id 
                AND e.instructor_id = :instructor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exam_id', $examIdToLoad, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            $showEditForm = true;
            
            // Fetch questions
            $sql_q = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC";
            $stmt_q = $pdo->prepare($sql_q);
            $stmt_q->bindParam(':exam_id', $examIdToLoad, PDO::PARAM_INT);
            $stmt_q->execute();
            $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            // Fetch options for multiple-choice questions
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'multiple_choice') {
                    $sql_c = "SELECT * FROM question_options 
                              WHERE question_id = :question_id 
                              ORDER BY option_id ASC";
                    $stmt_c = $pdo->prepare($sql_c);
                    $stmt_c->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt_c->execute();
                    $question['options'] = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Determine correct option for radio selection
                    foreach ($question['options'] as $optionIndex => &$option) {
                        $option['value'] = 'option_' . ($optionIndex + 1);
                        if ($option['option_text'] === $question['correct_answer']) {
                            $question['correct_option_value'] = $option['value'];
                        }
                    }
                    unset($option);
                }
            }
            unset($question);
        } else {
            if (empty($message)) {
                $message = '<div class="message error">Exam not found or you do not have permission to edit it.</div>';
            }
            $showEditForm = false;
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam details: " . $e->getMessage());
        if (empty($message)) {
            $message = '<div class="message error">Error loading exam details. Please try again later.</div>';
        }
        $showEditForm = false;
    }
}

// If no exam_id provided or exam not found, fetch the list of exams
if (!$showEditForm) {
    try {
        $sql = "SELECT e.exam_id, e.exam_title, e.exam_description, e.created_at, c.course_name
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.instructor_id = :instructor_id
                ORDER BY e.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
        $stmt->execute();
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching instructor exams: " . $e->getMessage());
        $message .= '<div class="message error">Could not load your exams list.</div>';
    }
}
// --- End: PHP Logic for Displaying Page ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showEditForm ? 'Edit Exam' : 'Select Exam to Edit'; ?></title>
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
            --border-radius: 6px;
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
            padding: 20px;
        }

        .page-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--medium-gray);
        }

        .page-title {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 175, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234a6baf' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        .question-section {
            background-color: var(--light-gray);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-top: 30px;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--medium-gray);
        }

        .question-item {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--medium-gray);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--light-gray);
        }

        .question-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .question-options {
            margin-top: 15px;
        }

        .option-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .option-group input[type="radio"] {
            margin-right: 5px;
        }

        .option-group label {
            min-width: 80px;
            color: var(--dark-gray);
        }

        .blank-answer-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .blank-answer-group label {
            min-width: 150px;
            color: var(--dark-gray);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--medium-gray);
            color: var(--dark-gray);
        }

        .btn-secondary:hover {
            background-color: #bdc3c7;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-size: 16px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .exam-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            background-color: white;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .exam-table th, .exam-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .exam-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }

        .exam-table tr:hover {
            background-color: rgba(74, 107, 175, 0.05);
        }

        .exam-table td a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .exam-table td a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .exam-description {
            color: var(--dark-gray);
            font-size: 14px;
        }

        .no-exams {
            text-align: center;
            padding: 30px;
            color: var(--dark-gray);
        }

        .no-exams a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .no-exams a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .card {
                padding: 15px;
            }
            
            .exam-table {
                display: block;
                overflow-x: auto;
            }
            
            .option-group, .blank-answer-group {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            
            .option-group label, .blank-answer-group label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?php echo $showEditForm ? 'Edit Exam' : 'Select Exam to Edit'; ?></h1>
        </div>

        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <?php if ($showEditForm && $exam): ?>
            <a href="edit_exam.php" class="back-link">&larr; Back to Exam List</a>

            <div class="card">
                <form id="editExamForm" method="POST" action="edit_exam.php">
                    <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['exam_id']); ?>">

                    <div class="form-group">
                        <label for="exam_title" class="form-label">Exam Title:</label>
                        <input type="text" id="exam_title" name="exam_title" class="form-control" 
                               value="<?php echo htmlspecialchars($exam['exam_title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="exam_description" class="form-label">Description:</label>
                        <textarea id="exam_description" name="exam_description" class="form-control" 
                                  rows="4"><?php echo htmlspecialchars($exam['exam_description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="course_id" class="form-label">Assign to Course:</label>
                        <select id="course_id" name="course_id" class="form-control" required>
                            <option value="">-- Select Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                        <?php echo ($course['course_id'] == $exam['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if (empty($courses)): ?>
                                <option value="" disabled>No courses assigned to you.</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="exam_duration" class="form-label">Duration (minutes):</label>
                        <input type="number" id="exam_duration" name="exam_duration" class="form-control" 
                               value="<?php echo htmlspecialchars($exam['duration_minutes']); ?>" required min="1">
                    </div>

                    <div class="form-group">
                        <label for="total_marks" class="form-label">Total Marks:</label>
                        <input type="number" id="total_marks" name="total_marks" class="form-control" 
                               value="<?php echo htmlspecialchars($exam['total_marks']); ?>" required min="0">
                    </div>

                    <div class="question-section">
                        <h3 class="section-title">Questions</h3>
                        <div id="questionsContainer"></div>
                        <button type="button" class="btn btn-secondary" id="addQuestionBtn">Add New Question</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 30px;">Update Exam</button>
                </form>
            </div>

            <script>
                let questionCounter = 0;

                document.addEventListener('DOMContentLoaded', () => {
                    const questionsContainer = document.getElementById('questionsContainer');
                    const addQuestionBtn = document.getElementById('addQuestionBtn');

                    addQuestionBtn.addEventListener('click', addQuestion);

                    // Populate existing questions
                    const existingQuestions = <?php echo json_encode($questions); ?>;

                    if (existingQuestions && Array.isArray(existingQuestions) && existingQuestions.length > 0) {
                        existingQuestions.forEach(question => {
                            addExistingQuestion(question);
                        });
                    }
                });

                function addQuestion() {
                    questionCounter++;
                    const questionsContainer = document.getElementById('questionsContainer');
                    const questionItem = document.createElement('div');
                    questionItem.className = 'question-item';
                    questionItem.id = `question_item_${questionCounter}`;
                    questionItem.dataset.questionIndex = questionCounter;

                    questionItem.innerHTML = `
                        <div class="question-header">
                            <div class="question-title">New Question ${questionCounter}</div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion('${questionItem.id}')">
                                Remove
                            </button>
                        </div>
                        <input type="hidden" name="questions[${questionCounter}][question_id]" value="">
                        <div class="form-group">
                            <label class="form-label">Question Text:</label>
                            <input type="text" name="questions[${questionCounter}][text]" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Question Type:</label>
                            <select name="questions[${questionCounter}][type]" class="form-control" 
                                    onchange="changeQuestionType(${questionCounter}, this.value)">
                                <option value="multiple_choice" selected>Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="fill_blank">Fill in the Blank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marks:</label>
                            <input type="number" name="questions[${questionCounter}][marks]" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="question-options" id="options_container_${questionCounter}">
                            ${generateOptionsHtml('multiple_choice', questionCounter, null)}
                        </div>
                    `;

                    questionsContainer.appendChild(questionItem);
                }

                function addExistingQuestion(questionData) {
                    questionCounter++;
                    const questionsContainer = document.getElementById('questionsContainer');
                    const questionItem = document.createElement('div');
                    questionItem.className = 'question-item';
                    questionItem.id = `question_item_${questionCounter}`;
                    questionItem.dataset.questionIndex = questionCounter;

                    questionItem.innerHTML = `
                        <div class="question-header">
                            <div class="question-title">Question ${questionCounter}</div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion('${questionItem.id}')">
                                Remove
                            </button>
                        </div>
                        <input type="hidden" name="questions[${questionCounter}][question_id]" value="${questionData.question_id}">
                        <div class="form-group">
                            <label class="form-label">Question Text:</label>
                            <input type="text" name="questions[${questionCounter}][text]" class="form-control" 
                                   value="${escapeHtml(questionData.question_text)}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Question Type:</label>
                            <select name="questions[${questionCounter}][type]" class="form-control" 
                                    onchange="changeQuestionType(${questionCounter}, this.value)">
                                <option value="multiple_choice" ${questionData.question_type === 'multiple_choice' ? 'selected' : ''}>Multiple Choice</option>
                                <option value="true_false" ${questionData.question_type === 'true_false' ? 'selected' : ''}>True/False</option>
                                <option value="fill_blank" ${questionData.question_type === 'fill_blank' ? 'selected' : ''}>Fill in the Blank</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Marks:</label>
                            <input type="number" name="questions[${questionCounter}][marks]" class="form-control" 
                                   value="${questionData.marks || 1}" min="1" required>
                        </div>
                        <div class="question-options" id="options_container_${questionCounter}">
                            ${generateOptionsHtml(questionData.question_type, questionCounter, questionData)}
                        </div>
                    `;

                    questionsContainer.appendChild(questionItem);
                }

                function generateOptionsHtml(type, questionIndex, questionData = null) {
                    let html = '';
                    switch (type) {
                        case 'multiple_choice':
                            const options = (questionData && Array.isArray(questionData.options)) ? questionData.options : [];
                            const correctOptionValue = questionData ? questionData.correct_option_value : null;
                            html = `
                                <div class="form-group">
                                    <label class="form-label">Options (Select Correct Answer):</label>
                                    <div id="mc_options_${questionIndex}">
                                        ${generateMultipleChoiceOptionsHtml(questionIndex, options, correctOptionValue)}
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" 
                                            onclick="addMultipleChoiceOption(${questionIndex})">
                                        Add Option
                                    </button>
                                </div>
                            `;
                            break;
                        case 'true_false':
                            const correctAnswerTF = questionData ? questionData.correct_answer : '';
                            html = `
                                <div class="form-group">
                                    <label class="form-label">Correct Answer:</label>
                                    <div class="option-group">
                                        <input type="radio" name="questions[${questionIndex}][correct_answer]" 
                                               value="true" id="tf_${questionIndex}_true" 
                                               ${correctAnswerTF === 'true' ? 'checked' : ''} required>
                                        <label for="tf_${questionIndex}_true">True</label>
                                    </div>
                                    <div class="option-group">
                                        <input type="radio" name="questions[${questionIndex}][correct_answer]" 
                                               value="false" id="tf_${questionIndex}_false" 
                                               ${correctAnswerTF === 'false' ? 'checked' : ''}>
                                        <label for="tf_${questionIndex}_false">False</label>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'fill_blank':
                            const correctAnswersBlank = (questionData && questionData.correct_answer) ? 
                                questionData.correct_answer.split('|') : [''];
                            html = `
                                <div class="form-group">
                                    <label class="form-label">Blank Answers (Use [BLANK] in question text):</label>
                                    <div id="blank_answers_${questionIndex}">
                                        ${generateBlankAnswersHtml(questionIndex, correctAnswersBlank)}
                                    </div>
                                    <button type="button" class="btn btn-secondary btn-sm" 
                                            onclick="addBlankAnswer(${questionIndex})">
                                        Add Blank Answer
                                    </button>
                                    <p style="margin-top: 10px; font-size: 14px; color: var(--dark-gray);">
                                        Use <code>[BLANK]</code> in the question text for each blank space.
                                    </p>
                                </div>
                            `;
                            break;
                        default:
                            html = '<p>Select a question type.</p>';
                            break;
                    }
                    return html;
                }

                function generateMultipleChoiceOptionsHtml(questionIndex, options, correctOptionValue) {
                    let html = '';
                    let optionCounter = 0;
                    
                    if (options.length === 0) {
                        html += generateSingleMCOptionHtml(questionIndex, ++optionCounter, '', false);
                        html += generateSingleMCOptionHtml(questionIndex, ++optionCounter, '', false);
                    } else {
                        options.forEach((option) => {
                            optionCounter++;
                            const optionValue = option.value || `option_${optionCounter}`;
                            const isChecked = (optionValue === correctOptionValue);
                            html += generateSingleMCOptionHtml(questionIndex, optionCounter, option.option_text, isChecked, option.option_id);
                        });
                    }
                    return html;
                }

                function generateSingleMCOptionHtml(questionIndex, optionIndex, text, isChecked, optionId = '') {
                    const optionValue = `option_${optionIndex}`;
                    return `
                        <div class="option-group">
                            <input type="radio" name="questions[${questionIndex}][correct_answer]" 
                                   value="${optionValue}" id="mc_${questionIndex}_${optionValue}" 
                                   ${isChecked ? 'checked' : ''} required>
                            <label for="mc_${questionIndex}_${optionValue}">Correct:</label>
                            <input type="text" name="questions[${questionIndex}][options][${optionValue}][text]" 
                                   class="form-control" value="${escapeHtml(text)}" 
                                   placeholder="Option ${optionIndex} Text" required>
                            <input type="hidden" name="questions[${questionIndex}][options][${optionValue}][option_id]" 
                                   value="${optionId}">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                                Remove
                            </button>
                        </div>
                    `;
                }

                function generateBlankAnswersHtml(questionIndex, answers) {
                    let html = '';
                    let answerCounter = 0;
                    
                    if (answers.length === 0 || (answers.length === 1 && answers[0] === '')) {
                        html += generateSingleBlankAnswerHtml(questionIndex, ++answerCounter, '');
                    } else {
                        answers.forEach((answer) => {
                            html += generateSingleBlankAnswerHtml(questionIndex, ++answerCounter, answer);
                        });
                    }
                    return html;
                }

                function generateSingleBlankAnswerHtml(questionIndex, answerIndex, text) {
                    return `
                        <div class="blank-answer-group">
                            <label>Blank ${answerIndex} Answer:</label>
                            <input type="text" name="questions[${questionIndex}][answers][]" 
                                   class="form-control" value="${escapeHtml(text)}" 
                                   placeholder="Correct answer for blank ${answerIndex}" required>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeOption(this)">
                                Remove
                            </button>
                        </div>
                    `;
                }

                function changeQuestionType(questionIndex, type) {
                    const optionsContainer = document.getElementById(`options_container_${questionIndex}`);
                    optionsContainer.innerHTML = generateOptionsHtml(type, questionIndex, null);
                }

                function addMultipleChoiceOption(questionIndex) {
                    const mcOptionsContainer = document.getElementById(`mc_options_${questionIndex}`);
                    const optionCount = mcOptionsContainer.querySelectorAll('.option-group').length + 1;
                    const optionHtml = generateSingleMCOptionHtml(questionIndex, optionCount, '', false);
                    mcOptionsContainer.insertAdjacentHTML('beforeend', optionHtml);
                }

                function addBlankAnswer(questionIndex) {
                    const blankAnswersContainer = document.getElementById(`blank_answers_${questionIndex}`);
                    const blankCount = blankAnswersContainer.querySelectorAll('.blank-answer-group').length + 1;
                    const blankHtml = generateSingleBlankAnswerHtml(questionIndex, blankCount, '');
                    blankAnswersContainer.insertAdjacentHTML('beforeend', blankHtml);
                }

                function removeOption(button) {
                    button.closest('.option-group, .blank-answer-group').remove();
                }

                function removeQuestion(questionItemId) {
                    const questionItem = document.getElementById(questionItemId);
                    if (questionItem) {
                        questionItem.remove();
                    }
                }

                function escapeHtml(str) {
                    if (typeof str !== 'string') return '';
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return str.replace(/[&<>"']/g, m => map[m]);
                }
            </script>

        <?php else: ?>
            <?php if (!empty($instructorExams)): ?>
                <div class="card">
                    <h2 class="section-title">Your Exams</h2>
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Course</th>
                                <th>Description</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructorExams as $instExam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($instExam['exam_title']); ?></td>
                                    <td><?php echo htmlspecialchars($instExam['course_name']); ?></td>
                                    <td>
                                        <?php if (!empty($instExam['exam_description'])): ?>
                                            <div class="exam-description">
                                                <?php echo htmlspecialchars(substr($instExam['exam_description'], 0, 100)); ?>
                                                <?php echo strlen($instExam['exam_description']) > 100 ? '...' : ''; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($instExam['created_at']))); ?></td>
                                    <td>
                                        <a href="ui/edit_exam.php?exam_id=<?php echo htmlspecialchars($instExam['exam_id']); ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (empty($message)): ?>
                <div class="card no-exams">
                    <p>You have not created any exams yet.</p>
                    <p><a href="ui/create_exam.php">Create New Exam</a></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>