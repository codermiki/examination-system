<?php
include_once '../config.php';
include_once '../includes/db/db.config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in instructor to create exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instructorId = $_SESSION['user_id']; 

    $examTitle = trim($_POST['examTitle'] ?? '');
    $examDescription = trim($_POST['examDescription'] ?? '');
    $examDuration = filter_var($_POST['examDuration'] ?? 0, FILTER_VALIDATE_INT);

    $courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);

    $totalMarks = filter_var($_POST['total_marks'] ?? 0, FILTER_VALIDATE_INT);


    // Basic validation
    if (empty($examTitle) || $examDuration === false || $examDuration <= 0 || $courseId === false || $courseId <= 0 || $totalMarks === false || $totalMarks < 0) {
        $message = '<p class="error">Error: Please fill in all required exam details correctly.</p>';
    } elseif (!isset($_POST['questions']) || !is_array($_POST['questions']) || count($_POST['questions']) === 0) {
        $message = '<p class="error">Error: Please add at least one question.</p>';
    } else {
        // Data seems valid, proceed with database insertion
        try {
            // Start a database transaction
            $pdo->beginTransaction();

            // 1. Insert into exams table
            $stmt = $pdo->prepare("INSERT INTO exams (course_id, instructor_id, title, description, time_limit, total_marks, status) VALUES (:course_id, :instructor_id, :title, :description, :time_limit, :total_marks, 'inactive')");
            $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
            $stmt->bindParam(':title', $examTitle, PDO::PARAM_STR);
            $stmt->bindParam(':description', $examDescription, PDO::PARAM_STR);
            $stmt->bindParam(':time_limit', $examDuration, PDO::PARAM_INT);
            $stmt->bindParam(':total_marks', $totalMarks, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Error inserting exam data: " . implode(" ", $stmt->errorInfo()));
            }
            $examId = $pdo->lastInsertId(); // Get the ID of the newly inserted exam

            // 2. Insert questions and choices
            $allowedQuestionTypes = ['true_false', 'multiple_choice', 'blank_space'];
            foreach ($_POST['questions'] as $question) {
                $questionText = trim($question['text'] ?? '');
                $questionType = $question['type'] ?? '';
                $correctAnswer = null; // Will store correct answer for applicable types

                // Validate question data
                if (empty($questionText) || empty($questionType) || !in_array($questionType, $allowedQuestionTypes)) {
                    throw new Exception("Invalid question data provided.");
                }

                // Determine correct answer based on type
                if ($questionType === 'true_false') {
                    $correctAnswer = $question['correct_answer'] ?? null;
                    if (!in_array($correctAnswer, ['true', 'false'])) {
                        throw new Exception("Invalid correct answer for True/False question.");
                    }
                } elseif ($questionType === 'blank_space') {
                    // For blank space, collect all submitted answers and store them as a delimited string
                    if (isset($question['answers']) && is_array($question['answers']) && count($question['answers']) > 0) {
                        // Filter out empty answers
                        $validAnswers = array_filter($question['answers'], 'trim');
                        if (count($validAnswers) > 0) {
                            $correctAnswer = implode('|', $validAnswers); // Store as pipe-separated string
                        } else {
                            // Depending on your design, you might require at least one answer for blanks
                            // throw new Exception("Blank space question requires at least one answer.");
                        }
                    } else {
                        // Depending on your design, you might require answers for blanks
                        // throw new Exception("Blank space question missing answers.");
                    }
                }

                // Insert into questions table
                $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
                $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    throw new Exception("Error inserting question: " . implode(" ", $stmt->errorInfo()));
                }
                $questionId = $pdo->lastInsertId(); // Get the ID of the newly inserted question

                if ($questionType === 'multiple_choice') {
                    if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                        throw new Exception("Multiple choice question requires at least two options.");
                    }
                    if (!isset($question['correct_answer'])) {
                        throw new Exception("Multiple choice question missing correct_answer selection.");
                    }
                    $correctOptionValue = $question['correct_answer']; // The value that indicates the correct option

                    foreach ($question['options'] as $optionValue => $optionText) {
                        $optionText = trim($optionText);
                        if (empty($optionText)) {
                            // Skip empty options or throw an error
                            continue; // Or throw new Exception("Empty option text provided for multiple choice question.");
                        }

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
                }
            }

            $pdo->commit(); // Commit the transaction if all insertions were successful
            $message = '<p class="success">Exam "' . htmlspecialchars($examTitle) . '" created successfully.</p>';

        } catch (Exception $e) {
            // Rollback the transaction if any error occurred
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Exam creation error: " . $e->getMessage()); // Log the detailed error
            $message = '<p class="error">Error creating exam: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}
// --- End: PHP Logic for Handling Form Submission ---

// --- Start: PHP Logic for Fetching Courses (for dropdown) ---
$courses = [];
try {
    // Fetch courses assigned to this instructor
    $instructorId = $_SESSION['user_id'];
    $sql = "SELECT c.course_id, c.course_name
            FROM courses c
            JOIN instructor_courses ic ON c.course_id = ic.course_id
            WHERE ic.instructor_id = :instructor_id
            ORDER BY c.course_name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instructor courses: " . $e->getMessage());
    // Display an error or handle appropriately
    $message .= '<p class="error">Could not load courses. Please try again.</p>';
}
// --- End: PHP Logic for Fetching Courses ---

?>

<div class="create-exam-container">
    <h2>Create New Exam</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <form id="createExamForm" method="POST" action="index.php?page=create_exam">

        <div class="form-group">
            <label for="examTitle">Exam Title:</label>
            <input type="text" id="examTitle" name="examTitle" required>
        </div>

        <div class="form-group">
            <label for="examDescription">Description:</label>
            <textarea id="examDescription" name="examDescription" rows="4"></textarea>
        </div>

        <div class="form-group">
            <label for="course_id">Assign to Course:</label>
            <select id="course_id" name="course_id" required>
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo htmlspecialchars($course['course_id']); ?>">
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>


        <div class="form-group">
            <label for="examDuration">Duration (minutes):</label>
            <input type="number" id="examDuration" name="examDuration" required min="1">
        </div>

        <div class="form-group">
            <label for="total_marks">Total Marks:</label>
            <input type="number" id="total_marks" name="total_marks" required min="0">
        </div>


        <div class="question-section">
            <h3>Questions</h3>
            <div id="questionsContainer">
            </div>

            <button  type="button" class="add-question-button">Add Question</button>
        </div>

        <button type="submit">Create Exam</button>
    </form>
    
    <script src="../assets/js/create_exam.js"></script>
</div>
