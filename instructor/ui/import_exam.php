<?php

include_once '../config.php';
include_once '../includes/db/db.config.php';

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
    // Updated allowed types based on common exam data formats and your schema
    $allowedTypes = [
        'application/json', // JSON
        'text/csv',         // CSV
        'application/xml',  // XML
        'text/xml'          // XML
    ];
    $maxFileSize = 5 * 1024 * 1024; // 5MB max file size
    $instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

    $fileName = basename($_FILES['examFile']['name']);
    $fileType = mime_content_type($_FILES['examFile']['tmp_name']); // Get actual file type
    $fileSize = $_FILES['examFile']['size'];
    $tempFilePath = $_FILES['examFile']['tmp_name'];
    $uploadFilePath = $uploadDir . uniqid() . '_' . $fileName; // Use uniqid to prevent filename conflicts

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

            // --- Start: File processing and database insertion ---

            $fileContent = file_get_contents($uploadFilePath);
            $importSuccess = false; // Flag to track overall import success

            try {
                // Start a database transaction
                $pdo->beginTransaction();

                if ($fileType === 'application/json') {
                    $examData = json_decode($fileContent, true); // Decode JSON into associative array

                    // Validate basic JSON structure (you'll need more robust validation)
                    if ($examData === null) {
                        throw new Exception("Error parsing JSON file.");
                    }
                    if (!isset($examData['title'], $examData['description'], $examData['time_limit'], $examData['total_marks'], $examData['course_id'], $examData['questions'])) {
                         throw new Exception("Invalid JSON structure. Missing required fields.");
                    }

                    // Insert into exams table
                    $stmt = $pdo->prepare("INSERT INTO exams (course_id, instructor_id, title, description, time_limit, total_marks, status) VALUES (:course_id, :instructor_id, :title, :description, :time_limit, :total_marks, 'inactive')");
                    $stmt->bindParam(':course_id', $examData['course_id'], PDO::PARAM_INT);
                    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
                    $stmt->bindParam(':title', $examData['title'], PDO::PARAM_STR);
                    $stmt->bindParam(':description', $examData['description'], PDO::PARAM_STR);
                    $stmt->bindParam(':time_limit', $examData['time_limit'], PDO::PARAM_INT);
                    $stmt->bindParam(':total_marks', $examData['total_marks'], PDO::PARAM_INT);

                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting exam data: " . implode(" ", $stmt->errorInfo()));
                    }
                    $examId = $pdo->lastInsertId(); // Get the ID of the newly inserted exam

                    // Insert questions and choices
                    if (is_array($examData['questions'])) {
                        foreach ($examData['questions'] as $question) {
                            if (!isset($question['question_text'], $question['question_type'])) {
                                throw new Exception("Invalid question structure. Missing required fields.");
                            }

                            // Validate question type against allowed ENUM values
                            $allowedQuestionTypes = ['true_false', 'multiple_choice', 'blank_space'];
                            if (!in_array($question['question_type'], $allowedQuestionTypes)) {
                                throw new Exception("Invalid question type: " . htmlspecialchars($question['question_type']));
                            }

                            $correctAnswer = $question['correct_answer'] ?? null; // Correct answer is optional for some types

                            // Insert into questions table
                            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
                            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                            $stmt->bindParam(':question_text', $question['question_text'], PDO::PARAM_STR);
                            $stmt->bindParam(':question_type', $question['question_type'], PDO::PARAM_STR);
                            $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);

                            if (!$stmt->execute()) {
                                throw new Exception("Error inserting question: " . implode(" ", $stmt->errorInfo()));
                            }
                            $questionId = $pdo->lastInsertId(); // Get the ID of the newly inserted question

                            // Handle choices for multiple choice questions
                            if ($question['question_type'] === 'multiple_choice' && isset($question['options']) && is_array($question['options'])) {
                                if (!isset($question['correct_answer'])) {
                                     throw new Exception("Multiple choice question missing correct_answer field.");
                                }
                                $correctOptionValue = $question['correct_answer']; // The value that indicates the correct option

                                foreach ($question['options'] as $optionValue => $optionText) {
                                    // Determine if this choice is the correct one
                                    $isCorrect = ($optionValue === $correctOptionValue);

                                    $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (:question_id, :choice_text, :is_correct)");
                                    $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                                    $stmt->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                                    $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);

                                    if (!$stmt->execute()) {
                                        throw new Exception("Error inserting choice: " . implode(" ", $stmt->errorInfo()));
                                    }
                                }
                            } elseif ($question['question_type'] === 'blank_space') {
                                // For blank space, the 'correct_answer' column in the questions table
                                // should store the correct answer(s). If multiple blanks, you might
                                // store them as a delimited string (e.g., "answer1|answer2").
                                // The current schema has `correct_answer` as TEXT, which can work.
                                // Ensure your JSON structure for blank_space provides the correct_answer.
                                if (!isset($question['correct_answer'])) {
                                     // Depending on your design, you might require a correct answer for blanks
                                     // throw new Exception("Blank space question missing correct_answer field.");
                                }
                                // The correct answer is already handled by the main question insert.
                            } elseif ($question['question_type'] === 'true_false') {
                                // For true/false, the 'correct_answer' column stores 'true' or 'false'.
                                // Ensure your JSON structure for true_false provides the correct_answer ('true' or 'false').
                                if (!isset($question['correct_answer']) || !in_array($question['correct_answer'], ['true', 'false'])) {
                                    throw new Exception("True/False question missing or invalid correct_answer field.");
                                }
                                // The correct answer is already handled by the main question insert.
                            }
                            // Add handling for 'math_equation' and 'coding' if you decide to add them to the DB schema
                            // The current DB schema only supports 'true_false', 'multiple_choice', 'blank_space'.
                        }
                    }

                    $pdo->commit(); // Commit the transaction if all insertions were successful
                    $message = '<p class="success">Exam "' . htmlspecialchars($examData['title']) . '" imported successfully with ' . count($examData['questions']) . ' questions.</p>';
                    $importSuccess = true;

                } elseif ($fileType === 'text/csv') {
                    // --- Placeholder for CSV parsing ---
                    $message .= '<p>CSV file uploaded. Implement CSV parsing and database insertion logic here.</p>';
                    // Example:
                    // $handle = fopen($uploadFilePath, "r");
                    // if ($handle) {
                    //     while (($data = fgetcsv($handle)) !== FALSE) {
                    //         // Process CSV row and insert into DB
                    //     }
                    //     fclose($handle);
                    // }
                    // --- End Placeholder ---
                     $pdo->rollBack(); // Rollback if not fully implemented
                     $message = '<p class="error">CSV import is not fully implemented yet.</p>'; // Indicate not fully implemented
                } elseif ($fileType === 'application/xml' || $fileType === 'text/xml') {
                     // --- Placeholder for XML parsing ---
                    $message .= '<p>XML file uploaded. Implement XML parsing and database insertion logic here.</p>';
                    // Example:
                    // $xml = simplexml_load_file($uploadFilePath);
                    // if ($xml) {
                    //     // Process XML data and insert into DB
                    // }
                    // --- End Placeholder ---
                     $pdo->rollBack(); // Rollback if not fully implemented
                     $message = '<p class="error">XML import is not fully implemented yet.</p>'; // Indicate not fully implemented
                } else {
                    // Should not happen due to file type validation, but as a fallback
                    throw new Exception("Unsupported file type.");
                }

            } catch (Exception $e) {
                // Rollback the transaction if any error occurred
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Exam import error: " . $e->getMessage()); // Log the detailed error
                $message = '<p class="error">Error processing exam file: ' . htmlspecialchars($e->getMessage()) . '</p>';
            } finally {
                 // Optional: Delete the uploaded file after processing (whether successful or not)
                 if (file_exists($uploadFilePath)) {
                     unlink($uploadFilePath);
                 }
            }

            // --- End: File processing and database insertion ---

        } else {
            $message = '<p class="error">Error moving uploaded file.</p>';
        }
    }
}
?>

<style>
    /* Basic styling for the import form */
    .import-exam-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        margin: 20px auto;
    }

    .import-exam-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    .form-group input[type="file"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        background-color: #fff;
    }

    button[type="submit"] {
        display: block;
        width: 100%;
        background-color: #28a745;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1.1em;
        margin-top: 20px;
    }

    button[type="submit"]:hover {
        background-color: #218838;
    }

    .message {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
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
</style>

<div class="import-exam-container">
    <h2>Import Exam</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <form action="handle_action.php?action=instructor_import_exam" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="examFile">Choose Exam File (CSV, JSON, or XML):</label>
            <input type="file" id="examFile" name="examFile" accept=".csv, .json, .xml" required>
        </div>

        <button type="submit">Import Exam</button>
    </form>

    <p><small>Accepted file formats: CSV, JSON, XML. Maximum file size: 5MB.</small></p>
    <p><small>Please ensure your file is correctly formatted. Refer to documentation for required file structure.</small></p>
   <!-- <p><small><strong>JSON Example Structure:</strong></small></p>
     <pre><code class="language-json">{
  "title": "Sample Exam",
  "description": "A brief description.",
  "time_limit": 60,
  "total_marks": 100,
  "course_id": 1, // Ensure this course_id exists in your database
  "questions": [
    {
      "question_text": "What is PHP?",
      "question_type": "multiple_choice",
      "correct_answer": "option_a", // Refers to the value of the correct option
      "options": {
        "option_a": "A server-side scripting language",
        "option_b": "A database system",
        "option_c": "A frontend framework"
      }
    },
    {
      "question_text": "The capital of France is Paris.",
      "question_type": "true_false",
      "correct_answer": "true"
    },
    {
      "question_text": "SQL stands for [BLANK] Query Language.",
      "question_type": "blank_space",
      "correct_answer": "Structured" // For multiple blanks, you might use "Answer1|Answer2"
    }
    // Add more questions here
  ]
}
</code></pre> -->
</div>

<?php
// No JavaScript needed in this basic version for file upload form itself,
// as the form submission is handled by PHP.
// If you implement AJAX file upload, you would add JavaScript here.
?>
