<?php

include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in instructor to import exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages

// Handle file upload and processing when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['examFile'])) {
    $uploadDir = '../uploads/exams/'; // Directory where uploaded files will be stored
    $allowedTypes = [
        'application/json', // JSON
        'text/csv',         // CSV
        'application/xml',  // XML
        'text/xml'          // XML
    ];
    $maxFileSize = 5 * 1024 * 1024; // 5MB max file size
    $instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

    $fileName = basename($_FILES['examFile']['name']);
    $fileType = mime_content_type($_FILES['examFile']['tmp_name']);
    $fileSize = $_FILES['examFile']['size'];
    $tempFilePath = $_FILES['examFile']['tmp_name'];
    $uploadFilePath = $uploadDir . uniqid() . '_' . $fileName;

    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Validate file
    if ($fileSize > $maxFileSize) {
        $message = '<p class="error">Error: File size exceeds the maximum allowed size of 5MB.</p>';
    } elseif (!in_array($fileType, $allowedTypes)) {
        $message = '<p class="error">Error: Invalid file type. Allowed types are CSV, JSON, and XML.</p>';
    } elseif ($_FILES['examFile']['error'] !== UPLOAD_ERR_OK) {
        $message = '<p class="error">Error uploading file. Please try again. Error code: ' . $_FILES['examFile']['error'] . '</p>';
    } else {
        // Move uploaded file to the destination directory
        if (move_uploaded_file($tempFilePath, $uploadFilePath)) {
            $message = '<p class="success">File uploaded successfully. Processing exam data...</p>';

            $fileContent = file_get_contents($uploadFilePath);
            $importSuccess = false;

            try {
                // Start a database transaction
                $conn->beginTransaction();

                if ($fileType === 'application/json') {
                    $examData = json_decode($fileContent, true);

                    // Validate JSON structure
                    if ($examData === null) {
                        throw new Exception("Error parsing JSON file: " . json_last_error_msg());
                    }

                    // Check required top-level fields
                    $requiredFields = ['exam_title', 'exam_description', 'duration_minutes', 'total_marks', 'course_id', 'questions'];
                    foreach ($requiredFields as $field) {
                        if (!isset($examData[$field])) {
                            throw new Exception("Invalid JSON structure. Missing required field: " . $field);
                        }
                    }

                    // Verify instructor is assigned to the course
                    $stmt = $conn->prepare("SELECT 1 FROM assigned_instructors WHERE instructor_id = ? AND course_id = ? AND status = 'Active'");
                    $stmt->execute([$instructorId, $examData['course_id']]);
                    if (!$stmt->fetch()) {
                        throw new Exception("You are not assigned to course ID " . $examData['course_id'] . " or the course doesn't exist.");
                    }

                    // Insert into exams table
                    $stmt = $conn->prepare("INSERT INTO exams (course_id, instructor_id, exam_title, exam_description, duration_minutes, total_marks, status) 
                                          VALUES (?, ?, ?, ?, ?, ?, 'Inactive')");
                    $stmt->execute([
                        $examData['course_id'],
                        $instructorId,
                        $examData['exam_title'],
                        $examData['exam_description'],
                        $examData['duration_minutes'],
                        $examData['total_marks']
                    ]);

                    $examId = $conn->lastInsertId();

                    // Validate and insert questions
                    if (!is_array($examData['questions']) || empty($examData['questions'])) {
                        throw new Exception("No questions found in the exam data.");
                    }

                    foreach ($examData['questions'] as $question) {
                        $requiredQuestionFields = ['question_text', 'question_type', 'correct_answer', 'marks'];
                        foreach ($requiredQuestionFields as $field) {
                            if (!isset($question[$field])) {
                                throw new Exception("Invalid question structure. Missing required field: " . $field);
                            }
                        }

                        // Validate question type
                        $allowedQuestionTypes = ['multiple_choice', 'true_false', 'fill_blank'];
                        if (!in_array($question['question_type'], $allowedQuestionTypes)) {
                            throw new Exception("Invalid question type: " . $question['question_type']);
                        }

                        // Validate marks is numeric
                        if (!is_numeric($question['marks'])) {
                            throw new Exception("Marks must be a numeric value for question: " . $question['question_text']);
                        }

                        // Insert question
                        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer, marks) 
                                                VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $examId,
                            $question['question_text'],
                            $question['question_type'],
                            $question['correct_answer'] ?? '',
                            $question['marks']
                        ]);
                        $questionId = $conn->lastInsertId();

                        // Insert options for multiple choice questions
                        if ($question['question_type'] === 'multiple_choice') {
                            if (!isset($question['options']) || !is_array($question['options']) || empty($question['options'])) {
                                throw new Exception("Multiple choice question must have options: " . $question['question_text']);
                            }

                            foreach ($question['options'] as $option) {
                                if (empty(trim($option))) {
                                    throw new Exception("Option text cannot be empty for question: " . $question['question_text']);
                                }

                                $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text) VALUES (?, ?)");
                                $stmt->execute([$questionId, $option]);
                            }
                        }
                    }

                    $conn->commit();
                    $message = '<p class="success">Exam "' . htmlspecialchars($examData['exam_title']) . '" imported successfully with ' . count($examData['questions']) . ' questions. Exam ID: ' . $examId . '</p>';
                    $importSuccess = true;
                } elseif ($fileType === 'text/csv') {
                    $message = '<p class="error">CSV import is not implemented in this version.</p>';
                    $conn->rollBack();
                } elseif ($fileType === 'application/xml' || $fileType === 'text/xml') {
                    $message = '<p class="error">XML import is not implemented in this version.</p>';
                    $conn->rollBack();
                } else {
                    throw new Exception("Unsupported file type.");
                }
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log("Exam import error: " . $e->getMessage());
                $message = '<p class="error">Error processing exam file: ' . htmlspecialchars($e->getMessage()) . '</p>';

                // Clean up any created exam if the transaction failed mid-process
                if (isset($examId) && !$importSuccess) {
                    try {
                        $conn->prepare("DELETE FROM exams WHERE exam_id = ?")->execute([$examId]);
                    } catch (Exception $cleanupError) {
                        error_log("Cleanup error: " . $cleanupError->getMessage());
                    }
                }
            } finally {
                // Only delete the file if processing is complete (success or failure)
                if (file_exists($uploadFilePath)) {
                    unlink($uploadFilePath);
                }
            }
        } else {
            $message = '<p class="error">Error moving uploaded file.</p>';
        }
    }
}
?>

<style>
    .import-exam-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 20px auto;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    input[type="file"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    button[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    button[type="submit"]:hover {
        background-color: #45a049;
    }

    .message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .success {
        background-color: #dff0d8;
        color: #3c763d;
        border: 1px solid #d6e9c6;
    }

    .error {
        background-color: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }

    pre {
        background-color: #f5f5f5;
        padding: 10px;
        border-radius: 4px;
        overflow-x: auto;
    }
</style>

<div class="import-exam-container">
    <h2>Import Exam</h2>

    <?php
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="examFile">Choose Exam File (JSON recommended):</label>
            <input type="file" id="examFile" name="examFile" accept=".json" required>
        </div>

        <button type="submit">Import Exam</button>
    </form>

    <p><small>Accepted file format: JSON. Maximum file size: 5MB.</small></p>
    <p><small><strong>JSON Example Structure:</strong></small></p>
    <pre><code class="language-json">{
  "exam_title": "Midterm Exam - Programming",
  "exam_description": "This exam covers basic programming concepts",
  "duration_minutes": 90,
  "total_marks": 100,
  "course_id": "C001",
  "questions": [
    {
      "question_text": "Which of the following is a programming language?",
      "question_type": "multiple_choice",
      "correct_answer": "Python",
      "marks": 5,
      "options": [
        "Python",
        "HTTP",
        "CSS",
        "MySQL"
      ]
    },
    {
      "question_text": "HTML is used to style web pages.",
      "question_type": "true_false",
      "correct_answer": "False",
      "marks": 5
    },
    {
      "question_text": "The keyword used to define a function in Python is ____.",
      "question_type": "fill_blank",
      "correct_answer": "def",
      "marks": 5
    }
  ]
}</code></pre>
</div>