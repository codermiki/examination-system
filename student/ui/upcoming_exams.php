<?php
// includes/student/student_upcoming_exams.php

// This file displays the list of upcoming exams and the exam schedule for the student.

// Include necessary configuration or database files
include_once '../config.php';
include_once '../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is a student
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in student to view this page.</p>';
    exit();
}

$studentId = $_SESSION['user_id']; // Get the logged-in student's user_id
$upcomingExams = []; // Array to hold upcoming exam data
$examSchedule = []; // Array to hold exam schedule data

// --- Start: PHP Logic for Fetching Upcoming Exams and Schedule ---

try {
    // Fetch upcoming exams for courses the student is enrolled in (assuming student_courses table exists)
    // If you don't have a student_courses table, you might need a different logic
    // to determine which exams are relevant to the student (e.g., based on program/major).
    // For this example, let's fetch scheduled exams for courses the instructor teaches (simplified).
    // A more accurate approach would involve student enrollment in courses.

    // Fetch scheduled exams
    $sql = "SELECT es.*, c.course_name, e.exam_id, e.title, e.time_limit
            FROM exam_schedule es
            JOIN courses c ON es.course_id = c.course_id
            LEFT JOIN exams e ON es.course_id = e.course_id AND e.status = 'active'
            WHERE es.exam_date >= CURDATE()
            ORDER BY es.exam_date, es.start_time";

    $stmt = $pdo->prepare($sql);
    // If you had student_courses, you would join with it and filter by student_id
    // $sql = "SELECT es.*, c.course_name, e.exam_id, e.title, e.time_limit
    //         FROM exam_schedule es
    //         JOIN courses c ON es.course_id = c.course_id
    //         JOIN student_courses sc ON c.course_id = sc.course_id // Assuming student_courses table
    //         LEFT JOIN exams e ON es.course_id = e.course_id AND e.status = 'active' AND e.instructor_id = c.instructor_id // Adjust JOIN for exam link
    //         WHERE sc.student_id = :student_id AND es.exam_date >= CURDATE()
    //         ORDER BY es.exam_date, es.start_time";
    // $stmt = $pdo->prepare($sql);
    // $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);

    $stmt->execute();
    $examSchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter for exams that are linked and active (these are the "upcoming exams" they can potentially take)
    $upcomingExams = array_filter($examSchedule, function($schedule) {
        return !empty($schedule['exam_id']);
    });


} catch (PDOException $e) {
    error_log("Error fetching upcoming exams/schedule: " . $e->getMessage());
    $message = '<p class="error">Error loading exam schedule. Please try again later.</p>';
}

// --- End: PHP Logic for Fetching Upcoming Exams and Schedule ---

?>

<style>
    /* Basic styling for the upcoming exams page */
    .upcoming-exams-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 900px;
        margin: 20px auto;
    }

    .upcoming-exams-container h2 {
        text-align: center;
        color: #333;
        margin-bottom: 20px;
    }

    .exam-list, .schedule-list {
        margin-top: 20px;
    }

    .exam-item, .schedule-item {
        background-color: #fff;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
    }

    .exam-details, .schedule-details {
        flex-grow: 1;
        margin-right: 15px; /* Space between details and action */
    }

    .exam-item h4, .schedule-item h4 {
        margin-top: 0;
        margin-bottom: 5px;
        color: #007bff;
    }

     .exam-item p, .schedule-item p {
         margin-bottom: 5px;
         font-size: 0.9em;
         color: #555;
     }

    .exam-actions {
        flex-shrink: 0; /* Prevent actions from shrinking */
    }

    .exam-actions .take-exam-link {
        display: inline-block;
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        cursor: pointer;
        font-size: 1em;
    }

    .exam-actions .take-exam-link:hover {
        background-color: #218838;
    }

     .schedule-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
     }

     .schedule-table th, .schedule-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
     }

     .schedule-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #555;
     }

     .schedule-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
     }

     .schedule-table tbody tr:hover {
        background-color: #e9e9e9;
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

<div class="upcoming-exams-container">
    <h2>Upcoming Exams & Schedule</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <div class="exam-list">
        <h3>Exams Available to Take</h3>
        <?php if (empty($upcomingExams)): ?>
            <p>No upcoming exams available to take at this time.</p>
        <?php else: ?>
            <?php foreach ($upcomingExams as $exam): ?>
                <div class="exam-item">
                    <div class="exam-details">
                        <h4><?php echo htmlspecialchars($exam['title']); ?></h4>
                        <p>Course: <?php echo htmlspecialchars($exam['course_name']); ?></p>
                        <p>Date: <?php echo htmlspecialchars($exam['exam_date']); ?></p>
                        <p>Time: <?php echo htmlspecialchars(date('h:i A', strtotime($exam['start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($exam['end_time']))); ?></p>
                        <p>Duration: <?php echo htmlspecialchars($exam['time_limit']); ?> minutes</p>
                    </div>
                    <div class="exam-actions">
                        <a href="#" class="take-exam-link sidebar-link" data-content="student_take_exam" data-exam-id="<?php echo $exam['exam_id']; ?>">Take Exam</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="schedule-list">
        <h3>Full Upcoming Exam Schedule</h3>
         <?php if (empty($examSchedule)): ?>
            <p>No upcoming exams scheduled.</p>
        <?php else: ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Date</th>
                        <th>Time</th>
                         <th>Exam (if linked)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($examSchedule as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['exam_date']); ?></td>
                            <td><?php echo htmlspecialchars(date('h:i A', strtotime($schedule['start_time']))) . ' - ' . htmlspecialchars(date('h:i A', strtotime($schedule['end_time']))); ?></td>
                             <td>
                                 <?php if (!empty($schedule['title'])): ?>
                                     <?php echo htmlspecialchars($schedule['title']); ?> (<?php echo htmlspecialchars($schedule['time_limit']); ?> mins)
                                 <?php else: ?>
                                     Not linked to an exam yet
                                 <?php endif; ?>
                             </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>


</div>

<?php
// No JavaScript needed for this page.
?>
