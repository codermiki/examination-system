<?php
// includes/instructor/manage_exam.php

// This file handles the functionality for instructors to manage their exams.

// Include necessary configuration or database files
// Assuming config.php establishes a $pdo database connection
include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
// Also check if user_id is set in the session
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in instructor to manage exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages

// --- Start: PHP Logic for Managing Exams ---

// This is where you would handle actions like deleting an exam
// based on GET or POST parameters.
// Example: If a delete link is clicked, the URL might be
// handle_action.php?action=instructor_manage_exam&delete_id=123

if (isset($_GET['delete_id'])) {
    $examIdToDelete = $_GET['delete_id'];
    $instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

    // Perform database deletion here
    // IMPORTANT: Ensure the instructor owns the exam before deleting
    try {
        // First, delete related data (student answers, choices, questions) to maintain referential integrity
        // Depending on your foreign key constraints (ON DELETE CASCADE), some of these might be automatic.
        // If not, you need to delete in the correct order: student_answers -> student_exams (if applicable) -> choices -> questions -> exams

        // Example deletion (assuming ON DELETE CASCADE is NOT set for simplicity, adjust if it is)
        // Delete student answers for this exam's questions
        $stmt = $conn->prepare("DELETE sa FROM student_answers sa JOIN questions q ON sa.question_id = q.question_id WHERE q.exam_id = ?");
        $stmt->execute([$examIdToDelete]);

        // Delete choices for this exam's questions
        $stmt = $conn->prepare("DELETE c FROM choices c JOIN questions q ON c.question_id = q.question_id WHERE q.exam_id = ?");
        $stmt->execute([$examIdToDelete]);

        // Delete questions for this exam
        $stmt = $conn->prepare("DELETE FROM questions WHERE exam_id = ?");
        $stmt->execute([$examIdToDelete]);

        // Finally, delete the exam itself, ensuring it belongs to the instructor
        $stmt = $conn->prepare("DELETE FROM exams WHERE exam_id = ? AND instructor_id = ?");
        $stmt->execute([$examIdToDelete, $instructorId]);

        // Check if deletion was successful
        if ($stmt->rowCount() > 0) {
            $message = '<p class="success">Exam deleted successfully.</p>';
        } else {
            // This might happen if the exam_id was invalid or didn't belong to the instructor
            $message = '<p class="error">Error deleting exam or exam not found for this instructor.</p>';
        }

    } catch (PDOException $e) {
        // Log the error and display a user-friendly message
        error_log("Error deleting exam: " . $e->getMessage()); // Log the detailed error
        $message = '<p class="error">An error occurred while trying to delete the exam. Please try again.</p>';
    }

    // Optional: Redirect to the same page after deletion to prevent re-submission on refresh
    // This requires handling the AJAX response in script.js to then reload the manage_exam content
    // header('Location: handle_action.php?action=instructor_manage_exam');
    // exit();
}

// --- End: PHP Logic ---

// --- Start: PHP Logic for Fetching Exams ---

$exams = []; // Array to hold exam data
$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

// You will need to fetch the exams created by the logged-in instructor from the database
try {
    // SQL query to fetch exams for the current instructor, joining with courses
    $sql = "SELECT e.exam_id, e.title, e.duration, e.created_at, c.course_name
            FROM exams e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.instructor_id = :instructor_id
            ORDER BY e.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
    $stmt->execute();

    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error and display a user-friendly message
    error_log("Error fetching exams: " . $e->getMessage()); // Log the detailed error
    $message = '<p class="error">Error fetching exams. Please try again later.</p>';
}

// --- End: PHP Logic for Fetching Exams ---
?>

<style>
    /* Basic styling for the manage exam table */
    .manage-exam-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 900px;
        margin: 20px auto;
    }

    .manage-exam-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .exam-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .exam-table th, .exam-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .exam-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #555;
    }

    .exam-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .exam-table tbody tr:hover {
        background-color: #e9e9e9;
    }

    .action-links a {
        margin-right: 10px;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 4px;
        display: inline-block; /* Ensure padding works */
        margin-bottom: 5px; /* Add some space between links on smaller screens */
    }

    .action-links .edit-link {
        color: #fff;
        background-color: #ffc107;
    }

     .action-links .edit-link:hover {
        background-color: #e0a800;
     }

    .action-links .delete-link {
        color: #fff;
        background-color: #dc3545;
    }

    .action-links .delete-link:hover {
        background-color: #c82333;
    }

     .action-links .view-link {
        color: #fff;
        background-color: #007bff;
     }

     .action-links .view-link:hover {
        background-color: #0056b3;
     }


    .message {
        margin-bottom: 15px;
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

<div class="manage-exam-container">
    <h2>Manage Exams</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <?php if (empty($exams)): ?>
        <p>No exams found.</p>
    <?php else: ?>
        <table class="exam-table">
            <thead>
                <tr>
                    <th>Exam Title</th>
                    <th>Course</th> <th>Duration (minutes)</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td><?php echo htmlspecialchars($exam['course_name']); ?></td> <td><?php echo htmlspecialchars($exam['duration']); ?></td>
                        <td><?php echo htmlspecialchars($exam['created_at']); ?></td>
                        <td class="action-links">
                            <a href="#" class="edit-link sidebar-link" data-content="instructor_edit_exam" data-exam-id="<?php echo $exam['exam_id']; ?>">Edit</a>
                            <a href="handle_action.php?action=instructor_manage_exam&delete_id=<?php echo $exam['exam_id']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this exam? This action cannot be undone.');">Delete</a>
                             <a href="#" class="view-link sidebar-link" data-content="instructor_view_exam" data-exam-id="<?php echo $exam['exam_id']; ?>">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php
// No JavaScript needed in this basic version for displaying the table.
// If you add dynamic features like sorting or pagination, you would add JavaScript here.
?>
