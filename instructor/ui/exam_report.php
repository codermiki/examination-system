<?php
// includes/instructor/exam_report.php

// This file allows instructors to first see a list of their exams in a table,
// and then view a report for a specific exam when selected.

// Include necessary configuration or database files
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php'; // Assuming this file sets up the $pdo database connection

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    // Redirect to login or show access denied message
    header('Location: ../../login.php'); // Adjust the path as needed
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold the specific exam being reported on
$studentResults = []; // Array to hold student results for this exam
$instructorExams = []; // Array to hold the list of exams for selection

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id
// $instructorId = 2; // Uncomment for testing with a fixed instructor ID if needed

// --- Start: PHP Logic for Displaying Page (List or Single Exam Report) ---

// Determine whether to show the list or the single exam report
$showSingleExamReport = false;
$examIdFromGet = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);

if ($examIdFromGet) {
    // Try to fetch the specific exam for reporting
    try {
        // 1. Fetch exam details, ensuring it belongs to the instructor
        $sqlExam = "SELECT e.*, c.course_name
                    FROM exams e
                    JOIN courses c ON e.course_id = c.course_id
                    WHERE e.exam_id = :exam_id AND e.instructor_id = :instructor_id";
        $stmtExam = $pdo->prepare($sqlExam);
        $stmtExam->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
        $stmtExam->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmtExam->execute();
        $exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

        // If exam is found, fetch student results
        if ($exam) {
            $showSingleExamReport = true; // Found the exam, show the report

            // 2. Fetch student results for this exam
            // This query assumes you have a 'student_attempts' table
            // linking students (via user_id) to exams (via exam_id)
            // and storing the 'score' or 'marks_obtained'.
            // Adjust table/column names based on your actual database schema.
            $sqlResults = "SELECT u.user_id, u.first_name, u.last_name, sa.score, sa.attempt_date
                           FROM student_attempts sa
                           JOIN users u ON sa.user_id = u.user_id
                           WHERE sa.exam_id = :exam_id
                           ORDER BY sa.score DESC, u.last_name ASC"; // Order by score descending, then name

            $stmtResults = $pdo->prepare($sqlResults);
            $stmtResults->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
            $stmtResults->execute();
            $studentResults = $stmtResults->fetchAll(PDO::FETCH_ASSOC);

             // Optional: Calculate average score
             $averageScore = 0;
             if (!empty($studentResults)) {
                 $totalScores = array_sum(array_column($studentResults, 'score'));
                 $averageScore = $totalScores / count($studentResults);
             }


        } else {
            // Exam ID provided but not found or not owned by instructor
            $message = '<p class="error">Exam not found or you do not have permission to view this report.</p>';
             // Ensure we don't try to show the single report if exam wasn't found
            $showSingleExamReport = false;
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam report data: " . $e->getMessage()); // Log the detailed error
        $message = '<p class="error">Error loading exam report details. Please try again later.</p>';
         // Ensure we don't try to show the single report on DB error
        $showSingleExamReport = false;
    }
}

// If no exam_id provided or exam not found/error, fetch the list of exams for this instructor
if (!$showSingleExamReport) {
     try {
        $sql = "SELECT e.exam_id, e.title, e.description, c.course_name, e.created_at
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.instructor_id = :instructor_id
                ORDER BY e.created_at DESC"; // Order exams by creation date

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->execute();
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC); // $instructorExams will be set if exams are found

    } catch (PDOException $e) {
        error_log("Error fetching instructor's exams for list: " . $e->getMessage());
        // Only append to message if it wasn't already set by a single exam fetch error
        if (empty($message)) {
             $message = '<p class="error">Error loading your exams list. Please try again later.</p>';
        } else {
             $message .= '<p class="error">Could not fully load your exams list.</p>';
        }
    }
}
// --- End: PHP Logic for Displaying Page ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $exam ? 'Exam Report: ' . htmlspecialchars($exam['title']) : 'Exam Reports'; ?></title>
    <style>
        /* General Container Styling (Similar to other instructor pages) */
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

        /* Message Styling */
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

         /* Exam Details Section Styling */
         .exam-details {
            margin-bottom: 30px; /* More space below details */
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
         }

        .exam-details p {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .exam-details strong {
            color: #555;
            display: inline-block;
            min-width: 150px; /* Give labels more width */
        }

        /* Student Results Table Styling (Similar to exam list table) */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            background-color: #fff;
            border-radius: 5px;
            overflow: hidden;
        }

        .results-table th, .results-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .results-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }

        .results-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .results-table td {
            color: #333;
        }

        /* Styling for score column */
        .results-table td:nth-child(3) { /* Assuming score is the 3rd column */
            font-weight: bold;
        }

        /* Optional: Styling for average score */
        .average-score {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #e9e9e9;
            font-weight: bold;
        }

         /* Exam List Table Styling (Copied from edit_exam.php for consistency) */
        .exam-table {
            width: 100%;
            border-collapse: collapse; /* Collapse borders */
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Subtle shadow for the table */
            background-color: #fff; /* White background */
            border-radius: 5px; /* Rounded corners for the table */
            overflow: hidden; /* Hide overflowing content for rounded corners */
        }

        .exam-table th, .exam-table td {
            padding: 12px; /* Increased padding */
            text-align: left;
            border-bottom: 1px solid #ddd; /* Lighter border */
        }

        .exam-table th {
            background-color: #f2f2f2; /* Light grey background for headers */
            font-weight: bold;
            color: #555;
        }

        .exam-table tbody tr:hover {
            background-color: #f9f9f9; /* Subtle hover effect */
        }

        .exam-table td a {
            font-weight: bold;
            text-decoration: none;
            color: #007bff; /* Blue link color */
        }

        .exam-table td a:hover {
            text-decoration: underline;
        }

        .exam-table td small {
            color: #666; /* Slightly darker grey for description */
        }

        /* Back link styling */
         .back-link {
             display: inline-block;
             margin-bottom: 20px;
             color: #007bff;
             text-decoration: none;
             font-size: 1em;
         }
         .back-link:hover {
             text-decoration: underline;
         }


    </style>
</head>
<body>

    <?php // include_once '../includes/layout/InstructorSidebar.php'; // Example ?>

    <main>
        <div class="page-container">

            <h1>Exam Reports</h1>

            <?php
            // Display feedback message if any
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>

            <?php if ($showSingleExamReport && $exam): // If a specific exam report was successfully loaded ?>

                <a href="exam_report.php" class="back-link">&larr; Back to Exam List</a>

                <h2>Report for Exam: <?php echo htmlspecialchars($exam['title']); ?></h2>

                <div class="exam-details">
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($exam['course_name']); ?></p>
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam['time_limit']); ?> minutes</p>
                    <p><strong>Total Possible Marks:</strong> <?php echo htmlspecialchars($exam['total_marks']); ?></p>
                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($exam['created_at']); ?></p>
                </div>

                <div class="student-results-section">
                    <h3>Student Results (<?php echo count($studentResults); ?> students)</h3>

                    <?php if (!empty($studentResults)): ?>

                        <?php if (isset($averageScore)): ?>
                            <div class="average-score">
                                Average Score: <?php echo htmlspecialchars(number_format($averageScore, 2)); ?> / <?php echo htmlspecialchars($exam['total_marks']); ?>
                            </div>
                        <?php endif; ?>

                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Attempt Date</th>
                                    <th>Score</th>
                                    </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentResults as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($result['attempt_date']))); ?></td>
                                        <td><?php echo htmlspecialchars($result['score']); ?> / <?php echo htmlspecialchars($exam['total_marks']); ?></td>
                                        </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No students have taken this exam yet.</p>
                    <?php endif; ?>
                </div>

            <?php else: // If no specific exam is being reported on (or wasn't found), show the list ?>


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
                                        <a href="ui/exam_report.php?exam_id=<?php echo htmlspecialchars($instExam['exam_id']); ?>">View Report</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif (empty($message)): // Avoid showing this if there was an error loading the list ?>
                    <p>You have not created any exams yet.</p>

                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

    <?php // include_once '../includes/layout/footer.php'; // Example ?>

</body>
</html>
