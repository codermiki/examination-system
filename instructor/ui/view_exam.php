<?php
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
$exam = null; // Variable to hold the specific exam being viewed
$questions = []; // Array to hold questions for the specific exam
$instructorExams = []; // Array to hold the list of exams for selection

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id
// $instructorId = 2; // Uncomment for testing with a fixed instructor ID if needed

// --- Start: PHP Logic for Displaying Page (List or Single Exam View) ---

// Determine whether to show the list or the single exam view
$showSingleExamView = false;
$examIdFromGet = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);

if ($examIdFromGet) {
    // Try to fetch the specific exam for viewing
    try {
        $sql = "SELECT e.*, c.course_name
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.exam_id = :exam_id AND e.instructor_id = :instructor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            $showSingleExamView = true; // Found the exam, show the details
            // Fetch its questions
            $sql_q = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC";
            $stmt_q = $pdo->prepare($sql_q);
            $stmt_q->bindParam(':exam_id', $examIdFromGet, PDO::PARAM_INT);
            $stmt_q->execute();
            $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            // Fetch choices for multiple-choice questions
            foreach ($questions as &$question) { // Use & to modify the original array elements
                if ($question['question_type'] === 'multiple_choice') {
                    $sql_c = "SELECT * FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC";
                    $stmt_c = $pdo->prepare($sql_c);
                    $stmt_c->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt_c->execute();
                    // Store choices within the question array
                    $question['choices'] = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question); // Break reference

        } else {
            // Exam ID provided but not found or not owned by instructor
            $message = '<p class="error">Exam not found or you do not have permission to view it.</p>';
             // Ensure we don't try to show the single view if exam wasn't found
            $showSingleExamView = false;
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam details for viewing: " . $e->getMessage());
        $message = '<p class="error">Error loading exam details. Please try again later.</p>';
         // Ensure we don't try to show the single view on DB error
        $showSingleExamView = false;
    }
}

// If no exam_id provided or exam not found/error, fetch the list of exams for this instructor
if (!$showSingleExamView) {
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
    <title><?php echo $showSingleExamView ? 'View Exam Details' : 'View Exams'; ?></title>
    <style>
        /* General Container Styling */
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

        .page-container h1, .page-container h2, .page-container h3 {
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

        /* Exam List Table Styling */
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

         /* Single Exam View Styling */
         .exam-details {
            margin-bottom: 20px;
            padding: 20px; /* Increased padding */
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
         }

        .exam-details p {
            margin-bottom: 10px; /* More space below paragraphs */
            line-height: 1.5;
        }

        .exam-details strong {
            color: #555;
            display: inline-block; /* Align strong text nicely */
            min-width: 120px; /* Give labels a minimum width */
        }

        .question-list {
            margin-top: 30px; /* More space above question list */
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .question-item {
            margin-bottom: 25px;
            padding: 20px; /* Increased padding */
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f2f2f2; /* Light grey background */
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .question-item h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #007bff;
            border-bottom: none; /* Remove border from h4 */
            padding-bottom: 0;
        }

        .question-item .question-text {
            font-weight: bold;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .question-item .question-type {
            font-style: italic;
            color: #666;
            margin-bottom: 10px;
            font-size: 0.9em;
        }

        .options-list {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }

        .options-list li {
            margin-bottom: 8px; /* More space between list items */
            padding: 8px;
            border-bottom: 1px solid #eee;
            background-color: #fff; /* White background for options */
            border-radius: 3px;
        }

        .options-list li strong {
            color: #333;
             min-width: auto; /* Reset min-width for options */
             display: inline;
        }

        .correct-answer {
            color: #28a745;
            font-weight: bold;
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

            <h1>View Exams</h1>

            <?php
            // Display feedback message if any
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>

            <?php if ($showSingleExamView && $exam): // If a specific exam was successfully loaded for viewing ?>

                <a href="view_exam.php" class="back-link">&larr; Back to Exam List</a>

                <h2>Exam Details: <?php echo htmlspecialchars($exam['title']); ?></h2>

                <div class="exam-details">
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($exam['course_name']); ?></p>
                    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
                    <p><strong>Duration:</strong> <?php echo htmlspecialchars($exam['time_limit']); ?> minutes</p>
                    <p><strong>Total Marks:</strong> <?php echo htmlspecialchars($exam['total_marks']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($exam['status'])); ?></p>
                    <p><strong>Created At:</strong> <?php echo htmlspecialchars($exam['created_at']); ?></p>
                </div>

                <div class="question-list">
                    <h3>Questions (<?php echo count($questions); ?>)</h3>
                    <?php if (empty($questions)): ?>
                        <p>No questions found for this exam.</p>
                    <?php else: ?>
                        <?php $questionNumber = 1; ?>
                        <?php foreach ($questions as $question): ?>
                            <div class="question-item">
                                <h4>Question <?php echo $questionNumber++; ?></h4>
                                <p class="question-text"><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                                <p class="question-type">Type: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $question['question_type']))); ?></p>

                                <?php if ($question['question_type'] === 'multiple_choice' && isset($question['choices'])): ?>
                                    <p>Options:</p>
                                    <ul class="options-list">
                                        <?php foreach ($question['choices'] as $choice): ?>
                                            <li>
                                                <?php if ($choice['is_correct']): ?>
                                                    <span class="correct-answer">Correct:</span>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($choice['choice_text']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif ($question['question_type'] === 'true_false'): ?>
                                    <p><strong>Correct Answer:</strong> <span class="correct-answer"><?php echo htmlspecialchars(ucfirst($question['correct_answer'])); ?></span></p>
                                <?php elseif ($question['question_type'] === 'blank_space'): ?>
                                     <p><strong>Correct Answer(s):</strong> <span class="correct-answer"><?php echo nl2br(htmlspecialchars(str_replace('|', ', ', $question['correct_answer']))); ?></span></p>
                                     <p><small>Blank answers are separated by commas. In the question text, <code>[BLANK]</code> indicates a blank space.</small></p>
                                <?php endif; ?>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            <?php else: // If no specific exam is being viewed (or wasn't found), show the list ?>


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
                                        <a href="ui/view_exam.php?exam_id=<?php echo htmlspecialchars($instExam['exam_id']); ?>">View Details</a>
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
