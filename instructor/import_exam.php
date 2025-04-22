<?php
// includes/instructor/import_exam.php

// This file handles the functionality for importing exams from a file.

// Include necessary configuration or database files
// include_once '../../config/database.php'; // Example database connection
include_once '../config.php'; // Assuming config.php is needed for session/role checks

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    echo '<p>Access denied. You must be an instructor to import exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages

// Handle file upload and processing when the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['examFile'])) {
    $uploadDir = '../uploads/exams/'; // Directory where uploaded files will be stored
    $allowedTypes = ['text/csv', 'application/json', 'application/xml', 'text/xml']; // Allowed file types
    $maxFileSize = 5 * 1024 * 1024; // 5MB max file size

    $fileName = basename($_FILES['examFile']['name']);
    $fileType = mime_content_type($_FILES['examFile']['tmp_name']); // Get actual file type
    $fileSize = $_FILES['examFile']['size'];
    $tempFilePath = $_FILES['examFile']['tmp_name'];
    $uploadFilePath = $uploadDir . $fileName;

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
            // File uploaded successfully, now process it
            $message = '<p class="success">File uploaded successfully. Processing exam data...</p>';

            // --- Start: Placeholder for file processing and database insertion ---

            // You will need to implement the logic here to:
            // 1. Read the content of the uploaded file ($uploadFilePath).
            // 2. Parse the file content based on its type ($fileType).
            //    - For CSV: Use fgetcsv() or a library.
            //    - For JSON: Use json_decode().
            //    - For XML: Use simplexml_load_file() or DOMDocument.
            // 3. Validate the extracted exam data structure and content.
            // 4. Insert the validated exam data (exam details, questions, options, answers)
            //    into your database tables. Ensure data integrity and handle potential errors
            //    during database operations.
            // 5. Provide feedback based on the processing result (e.g., number of questions imported, errors encountered).

            // Example placeholder:
            // $examData = file_get_contents($uploadFilePath);
            // if ($fileType === 'application/json') {
            //     $examObject = json_decode($examData);
            //     // Process JSON data and insert into DB
            // } elseif ($fileType === 'text/csv') {
            //     // Process CSV data and insert into DB
            // }
            // ... database insertion logic ...

            $message .= '<p>Placeholder: File processing and database insertion logic goes here.</p>';

            // --- End: Placeholder ---

            // Optional: Delete the uploaded file after processing
            // unlink($uploadFilePath);

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
</div>

<?php
// No JavaScript needed in this basic version for file upload form itself,
// as the form submission is handled by PHP.
// If you implement AJAX file upload, you would add JavaScript here.
?>
