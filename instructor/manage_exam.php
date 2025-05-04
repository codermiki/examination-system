<?php
include_once '../config.php';
include_once '../includes/db/db.config.php'; // Include if needed for DB connection details


if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in instructor to edit exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold exam details
$questions = []; // Array to hold questions fetched from DB
$courses = []; // Array to hold courses for the dropdown

$instructorId = $_SESSION['user_id'];
$instructorId=2;// Get the logged-in instructor's user_id

// --- Start: PHP Logic for Handling Form Submission (Updating Exam) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate exam details from POST
    $examIdToUpdate = filter_var($_POST['exam_id'] ?? 0, FILTER_VALIDATE_INT);
    $examTitle = trim($_POST['examTitle'] ?? '');
    $examDescription = trim($_POST['examDescription'] ?? '');
    $examDuration = filter_var($_POST['examDuration'] ?? 0, FILTER_VALIDATE_INT);
    $courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
    $totalMarks = filter_var($_POST['total_marks'] ?? 0, FILTER_VALIDATE_INT);
    $questionsData = $_POST['questions'] ?? []; // Array of questions from the form

    // Basic validation
    if ($examIdToUpdate === false || $examIdToUpdate <= 0 || empty($examTitle) || $examDuration === false || $examDuration <= 0 || $courseId === false || $courseId <= 0 || $totalMarks === false || $totalMarks < 0) {
        $message = '<p class="error">Error: Invalid exam ID or missing/incorrect exam details.</p>';
    } elseif (!is_array($questionsData) || count($questionsData) === 0) {
         $message = '<p class="error">Error: Please add at least one question.</p>';
    } else {
        // Data seems valid, proceed with database update
        try {
            // Start a database transaction
            $pdo->beginTransaction();

            // 1. Update exams table
            // Ensure the exam belongs to the instructor
            $stmt = $pdo->prepare("UPDATE exams SET course_id = :course_id, title = :title, description = :description, time_limit = :time_limit, total_marks = :total_marks WHERE exam_id = :exam_id AND instructor_id = :instructor_id");
            $stmt->bindParam(':course_id', $courseId, PDO::PARAM_INT);
            $stmt->bindParam(':title', $examTitle, PDO::PARAM_STR);
            $stmt->bindParam(':description', $examDescription, PDO::PARAM_STR);
            $stmt->bindParam(':time_limit', $examDuration, PDO::PARAM_INT);
            $stmt->bindParam(':total_marks', $totalMarks, PDO::PARAM_INT);
            $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                 throw new Exception("Error updating exam details: " . implode(" ", $stmt->errorInfo()));
            }

            // --- Start: Manage Questions and Choices (Update/Insert/Delete) ---

            // Fetch current questions for this exam from the database
            $currentQuestionsStmt = $pdo->prepare("SELECT question_id FROM questions WHERE exam_id = :exam_id");
            $currentQuestionsStmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $currentQuestionsStmt->execute();
            $currentQuestionIds = $currentQuestionsStmt->fetchAll(PDO::FETCH_COLUMN); // Get an array of current question IDs

            // Get submitted question IDs
            $submittedQuestionIds = [];
            foreach ($questionsData as $qData) {
                // Only consider questions that have an existing question_id (not new ones)
                if (!empty($qData['question_id'])) {
                    $submittedQuestionIds[] = (int)$qData['question_id'];
                }
            }

            // Determine questions to delete (present in DB but not in submitted data)
            $questionIdsToDelete = array_diff($currentQuestionIds, $submittedQuestionIds);

            // Delete removed questions and their associated data
            if (!empty($questionIdsToDelete)) {
                 $placeholders = implode(',', array_fill(0, count($questionIdsToDelete), '?'));

                 // Delete student answers related to these questions FIRST
                 $deleteStudentAnswersStmt = $pdo->prepare("DELETE sa FROM student_answers sa JOIN questions q ON sa.question_id = q.question_id WHERE q.exam_id = :exam_id AND sa.question_id IN ($placeholders)");
                 $deleteStudentAnswersStmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                 foreach ($questionIdsToDelete as $index => $id) {
                     $deleteStudentAnswersStmt->bindValue(($index + 1), $id, PDO::PARAM_INT);
                 }
                 $deleteStudentAnswersStmt->execute();


                 // Delete choices related to these questions
                 $deleteChoicesStmt = $pdo->prepare("DELETE c FROM choices c JOIN questions q ON c.question_id = q.question_id WHERE q.exam_id = :exam_id AND c.question_id IN ($placeholders)");
                 $deleteChoicesStmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                 foreach ($questionIdsToDelete as $index => $id) {
                     $deleteChoicesStmt->bindValue(($index + 1), $id, PDO::PARAM_INT);
                 }
                 $deleteChoicesStmt->execute();

                 // Delete the questions themselves
                 $deleteQuestionsStmt = $pdo->prepare("DELETE FROM questions WHERE exam_id = :exam_id AND question_id IN ($placeholders)");
                 $deleteQuestionsStmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                  foreach ($questionIdsToDelete as $index => $id) {
                     $deleteQuestionsStmt->bindValue(($index + 1), $id, PDO::PARAM_INT);
                 }
                 $deleteQuestionsStmt->execute();
            }


            // Process submitted questions (Update existing, Insert new)
            $allowedQuestionTypes = ['true_false', 'multiple_choice', 'blank_space'];
            foreach ($questionsData as $question) {
                $questionId = filter_var($question['question_id'] ?? 0, FILTER_VALIDATE_INT);
                $questionText = trim($question['text'] ?? '');
                $questionType = $question['type'] ?? '';
                $correctAnswer = null; // Will store correct answer for applicable types

                // Validate question data
                if (empty($questionText) || empty($questionType) || !in_array($questionType, $allowedQuestionTypes)) {
                     error_log("Invalid question data submitted for exam ID " . $examIdToUpdate . ": " . json_encode($question));
                    continue; // Skip this invalid question
                }

                // Determine correct answer based on type
                if ($questionType === 'true_false') {
                    $correctAnswer = $question['correct_answer'] ?? null;
                    if (!in_array($correctAnswer, ['true', 'false'])) {
                         error_log("Invalid correct answer for True/False question submitted for exam ID " . $examIdToUpdate);
                         continue;
                    }
                } elseif ($questionType === 'blank_space') {
                    if (isset($question['answers']) && is_array($question['answers']) && count($question['answers']) > 0) {
                         $validAnswers = array_filter($question['answers'], 'trim');
                         if (count($validAnswers) > 0) {
                             $correctAnswer = implode('|', $validAnswers);
                         }
                    }
                }

                if (!empty($questionId) && in_array($questionId, $currentQuestionIds)) {
                    // This is an existing question - UPDATE it
                    $stmt = $pdo->prepare("UPDATE questions SET question_text = :question_text, question_type = :question_type, correct_answer = :correct_answer WHERE question_id = :question_id AND exam_id = :exam_id");
                    $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                    $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                    $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);
                    $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                    $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);

                    if (!$stmt->execute()) {
                        throw new Exception("Error updating question ID " . $questionId . ": " . implode(" ", $stmt->errorInfo()));
                    }

                    // Manage choices for existing multiple choice questions
                    if ($questionType === 'multiple_choice') {
                        if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                             error_log("Existing MC question ID " . $questionId . " missing options during update.");
                             continue;
                        }
                         if (!isset($question['correct_answer'])) {
                             error_log("Existing MC question ID " . $questionId . " missing correct_answer selection during update.");
                             continue;
                        }
                        $correctOptionValue = $question['correct_answer']; // The value indicating the correct option

                        // Fetch current choices for this question
                        $currentChoicesStmt = $pdo->prepare("SELECT choice_id FROM choices WHERE question_id = :question_id");
                        $currentChoicesStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                        $currentChoicesStmt->execute();
                        $currentChoiceIds = $currentChoicesStmt->fetchAll(PDO::FETCH_COLUMN);

                        // Get submitted choice IDs
                        $submittedChoiceIds = [];
                        if (isset($question['options']) && is_array($question['options'])) {
                             foreach ($question['options'] as $optionData) {
                                 // Assuming optionData is an array with 'choice_id' and 'text'
                                 if (isset($optionData['choice_id']) && !empty($optionData['choice_id'])) {
                                     $submittedChoiceIds[] = (int)$optionData['choice_id'];
                                 }
                             }
                        }


                        // Determine choices to delete
                        $choiceIdsToDelete = array_diff($currentChoiceIds, $submittedChoiceIds);

                        // Delete removed choices
                        if (!empty($choiceIdsToDelete)) {
                             $placeholders = implode(',', array_fill(0, count($choiceIdsToDelete), '?'));
                             $deleteChoicesStmt = $pdo->prepare("DELETE FROM choices WHERE question_id = :question_id AND choice_id IN ($placeholders)");
                             $deleteChoicesStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                             foreach ($choiceIdsToDelete as $index => $id) {
                                 $deleteChoicesStmt->bindValue(($index + 1), $id, PDO::PARAM_INT);
                             }
                             $deleteChoicesStmt->execute();
                        }

                        // Process submitted options (Update existing, Insert new)
                         if (isset($question['options']) && is_array($question['options'])) {
                             foreach ($question['options'] as $optionData) {
                                 $choiceId = filter_var($optionData['choice_id'] ?? 0, FILTER_VALIDATE_INT);
                                 $optionText = trim($optionData['text'] ?? '');
                                 $isCorrect = ($optionData['value'] ?? null) === $correctOptionValue; // Determine correctness based on submitted value vs correct value

                                 if (empty($optionText)) {
                                     continue; // Skip empty options
                                 }

                                 if (!empty($choiceId) && in_array($choiceId, $currentChoiceIds)) {
                                     // Existing choice - UPDATE it
                                     $stmt = $pdo->prepare("UPDATE choices SET choice_text = :choice_text, is_correct = :is_correct WHERE choice_id = :choice_id AND question_id = :question_id");
                                     $stmt->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                                     $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
                                     $stmt->bindParam(':choice_id', $choiceId, PDO::PARAM_INT);
                                     $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                                     if (!$stmt->execute()) {
                                         throw new Exception("Error updating choice ID " . $choiceId . " for question " . $questionId . ": " . implode(" ", $stmt->errorInfo()));
                                     }
                                 } else {
                                     // New choice - INSERT it
                                     $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (:question_id, :choice_text, :is_correct)");
                                     $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                                     $stmt->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                                     $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
                                     if (!$stmt->execute()) {
                                         throw new Exception("Error inserting new choice for question " . $questionId . ": " . implode(" ", $stmt->errorInfo()));
                                     }
                                 }
                             }
                         }


                    } else {
                         // If the question type changed from multiple_choice, delete its old choices
                         $deleteOldChoicesStmt = $pdo->prepare("DELETE FROM choices WHERE question_id = :question_id");
                         $deleteOldChoicesStmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                         $deleteOldChoicesStmt->execute();
                    }

                } else {
                    // This is a new question - INSERT it
                    $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
                    $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                    $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                    $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                    $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);

                    if (!$stmt->execute()) {
                        throw new Exception("Error inserting new question for exam " . $examIdToUpdate . ": " . implode(" ", $stmt->errorInfo()));
                    }
                    $newQuestionId = $pdo->lastInsertId(); // Get the ID of the newly inserted question

                    // Handle choices for new multiple choice questions
                    if ($questionType === 'multiple_choice') {
                         if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                             error_log("New MC question missing options during insert for exam ID " . $examIdToUpdate);
                             continue;
                        }
                         if (!isset($question['correct_answer'])) {
                             error_log("New MC question missing correct_answer selection during insert for exam ID " . $examIdToUpdate);
                             continue;
                        }
                        $correctOptionValue = $question['correct_answer'];

                         if (isset($question['options']) && is_array($question['options'])) {
                             $stmtInsertChoice = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (:question_id, :choice_text, :is_correct)");
                             foreach ($question['options'] as $optionData) {
                                 $optionText = trim($optionData['text'] ?? '');
                                 $isCorrect = ($optionData['value'] ?? null) === $correctOptionValue;

                                 if (empty($optionText)) {
                                     continue;
                                 }

                                 $stmtInsertChoice->bindParam(':question_id', $newQuestionId, PDO::PARAM_INT);
                                 $stmtInsertChoice->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                                 $stmtInsertChoice->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);
                                 if (!$stmtInsertChoice->execute()) {
                                     throw new Exception("Error inserting choice for new question " . $newQuestionId . ": " . implode(" ", $stmtInsertChoice->errorInfo()));
                                 }
                             }
                         }
                    }
                }
            }

            // --- End: Manage Questions and Choices ---


            $pdo->commit(); // Commit the transaction if all updates/inserts/deletes were successful
            $message = '<p class="success">Exam "' . htmlspecialchars($examTitle) . '" updated successfully.</p>';

        } catch (Exception $e) {
            // Rollback the transaction if any error occurred
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Exam update error: " . $e->getMessage()); // Log the detailed error
            $message = '<p class="error">Error updating exam: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
    }
}
// --- End: PHP Logic for Handling Form Submission ---


// --- Start: PHP Logic for Fetching Exam Details to Populate Form ---

// Check if exam_id is provided in the GET request for initial form display
// Or if there was a POST submission failure, re-fetch the data to repopulate the form
if (($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['exam_id']) && filter_var($_GET['exam_id'], FILTER_VALIDATE_INT)) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($examIdToUpdate) && $examIdToUpdate > 0)) {

    $examId = ($_SERVER['REQUEST_METHOD'] === 'GET') ? filter_var($_GET['exam_id'], FILTER_VALIDATE_INT) : $examIdToUpdate;

    if ($examId > 0) {
        try {
            // Fetch exam details, ensuring it belongs to the instructor
            $sql = "SELECT e.*, c.course_name
                    FROM exams e
                    JOIN courses c ON e.course_id = c.course_id
                    WHERE e.exam_id = :exam_id AND e.instructor_id = :instructor_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
            $stmt->execute();
            $exam = $stmt->fetch(PDO::FETCH_ASSOC);

            // Add debugging output
            if ($exam) {
                error_log("Edit Exam: Fetched Exam ID: " . $exam['exam_id'] . ", Title: " . $exam['title']);
            } else {
                 error_log("Edit Exam: No exam found for ID: " . $examId . " and Instructor ID: " . $instructorId);
            }


            // If exam is found, fetch its questions
            if ($exam) {
                $sql = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                $stmt->execute();
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                 error_log("Edit Exam: Fetched " . count($questions) . " questions for exam ID: " . $examId);


                // For each multiple-choice question, fetch its choices
                foreach ($questions as &$question) { // Use & to modify the original array elements
                    if ($question['question_type'] === 'multiple_choice') {
                        $sql = "SELECT * FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC";
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                        $stmt->execute();
                        $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                         error_log("Edit Exam: Fetched " . count($question['choices']) . " choices for question ID: " . $question['question_id']);
                    }
                }
                unset($question); // Break the reference with the last element
            } else {
                // Exam not found or doesn't belong to instructor - set an error message
                $message = '<p class="error">Exam not found or you do not have permission to edit it.</p>';
            }

        } catch (PDOException $e) {
            error_log("Error fetching exam details for editing: " . $e->getMessage());
            $message = '<p class="error">Error loading exam details for editing. Please try again later.</p>';
        }
    } else {
         // If it's a GET request but no exam_id is provided
         $message = '<p class="error">No exam ID provided for editing.</p>';
    }
}


// --- End: PHP Logic for Fetching Exam Details ---

// --- Start: PHP Logic for Fetching Courses (for dropdown) ---
$courses = [];
try {
    // Fetch courses assigned to this instructor
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
    error_log("Error fetching instructor courses for edit form: " . $e->getMessage());
    $message .= '<p class="error">Could not load courses. Please try again.</p>';
}
// --- End: PHP Logic for Fetching Courses ---

?>

<style>
    /* Basic styling for the form - you should integrate this with your main CSS */
    .edit-exam-container { /* Changed class name for clarity */
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        margin: 20px auto;
    }

    .edit-exam-container h2 {
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

    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; /* Include padding and border in element's total width and height */
    }

    textarea {
        resize: vertical; /* Allow vertical resizing */
    }

    .question-section {
        margin-top: 30px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .question-item {
        background-color: #fff;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 4px;
    }

    .question-item h4 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #007bff;
    }

    .question-options {
        margin-top: 10px;
        padding-left: 20px;
    }

    .question-options .option-group {
        margin-bottom: 10px;
    }

    .question-options input[type="text"] {
        width: calc(100% - 70px); /* Adjust width considering label/checkbox */
        display: inline-block;
        vertical-align: middle;
        margin-right: 10px;
    }

    .question-options label {
         display: inline-block;
         margin-right: 10px;
         vertical-align: middle;
         font-weight: normal;
    }

    .add-question-button, .add-option-button, .remove-item-button, .add-blank-button {
        display: inline-block;
        background-color: #28a745;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1em;
        margin-top: 10px;
        margin-right: 5px;
    }

     .add-option-button {
         background-color: #007bff;
     }

     .remove-item-button {
         background-color: #dc3545;
     }

     .add-blank-button {
         background-color: #ffc107; /* Example color for add blank */
         color: #333;
     }


    .add-question-button:hover, .add-option-button:hover, .remove-item-button:hover, .add-blank-button:hover {
        opacity: 0.9;
    }

    button[type="submit"] {
        display: block;
        width: 100%;
        background-color: #007bff;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1.1em;
        margin-top: 20px;
    }

    button[type="submit"]:hover {
        background-color: #0056b3;
    }

    .question-type-select {
        margin-bottom: 15px;
    }

    .blank-answer-group {
        margin-bottom: 10px;
        padding-left: 10px;
        border-left: 2px solid #eee;
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

<div class="edit-exam-container">
    <?php if ($exam): // Only display the form if exam data was fetched ?>
        <h2>Edit Exam: <?php echo htmlspecialchars($exam['title']); ?></h2>

        <?php
        // Display feedback message if any
        if (!empty($message)) {
            echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
        }
        ?>

        <form id="editExamForm" method="POST" action="handle_action.php?action=instructor_edit_exam_submit">
            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['exam_id']); ?>">

            <div class="form-group">
                <label for="examTitle">Exam Title:</label>
                <input type="text" id="examTitle" name="examTitle" value="<?php echo htmlspecialchars($exam['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="examDescription">Description:</label>
                <textarea id="examDescription" name="examDescription" rows="4"><?php echo htmlspecialchars($exam['description']); ?></textarea>
            </div>

             <div class="form-group">
                <label for="course_id">Assign to Course:</label>
                <select id="course_id" name="course_id" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo htmlspecialchars($course['course_id']); ?>"
                                <?php echo ($course['course_id'] == $exam['course_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="form-group">
                <label for="examDuration">Duration (minutes):</label>
                <input type="number" id="examDuration" name="examDuration" value="<?php echo htmlspecialchars($exam['time_limit']); ?>" required min="1">
            </div>

             <div class="form-group">
                <label for="total_marks">Total Marks:</label>
                <input type="number" id="total_marks" name="total_marks" value="<?php echo htmlspecialchars($exam['total_marks']); ?>" required min="0">
            </div>


            <div class="question-section">
                <h3>Questions</h3>
                <div id="questionsContainer">
                    </div>

                <button type="button" class="add-question-button">Add New Question</button>
            </div>

            <button type="submit">Update Exam</button>
        </form>

    <?php else: // Display message if exam data could not be fetched ?>
        <?php
        // Display feedback message if any (already set in the PHP logic)
        if (!empty($message)) {
            echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
        }
        ?>
    <?php endif; ?>

</div>

<script>
    let questionCounter = 0; // Initialize counter for dynamically added questions

    // Wait for the DOM to be fully loaded before adding event listeners and populating questions
    document.addEventListener('DOMContentLoaded', () => {
        console.log('edit_exam.php DOMContentLoaded');

        const questionsContainer = document.getElementById('questionsContainer');
        const addQuestionButton = document.querySelector('.add-question-button');

        // Check if the container and button exist
        if (!questionsContainer) {
            console.error('Error: #questionsContainer not found!');
            return;
        }
         if (!addQuestionButton) {
            console.error('Error: .add-question-button not found!');
            return;
         }

        // Add event listener to the "Add New Question" button
        addQuestionButton.addEventListener('click', addQuestion);
        console.log('Event listener added to .add-question-button');


        // --- Populate existing questions ---
        // Get questions data from PHP, decode JSON
        const existingQuestions = <?php echo json_encode($questions); ?>;
        console.log('Existing questions:', existingQuestions);

        if (existingQuestions && existingQuestions.length > 0) {
            existingQuestions.forEach(question => {
                addExistingQuestion(question);
            });
            // Set the questionCounter to the number of existing questions
            // so newly added questions continue the numbering
            questionCounter = existingQuestions.length;
        }
        // --- End Populate existing questions ---

    });


    // Function to add a new, empty question form block
    function addQuestion() {
        console.log('addQuestion called (for new question)');

        questionCounter++;

        const questionsContainer = document.getElementById('questionsContainer');
        console.log('questionsContainer:', questionsContainer);

        if (!questionsContainer) {
            console.error('Error: #questionsContainer not found!');
            return;
        }

        const questionItem = document.createElement('div');
        questionItem.classList.add('question-item');
        // Use a unique ID for the question item based on the counter
        const questionItemId = `question_item_${questionCounter}`;
        questionItem.setAttribute('id', questionItemId);
        // Use counter for form names index, NO data-db-question-id for new questions
        questionItem.setAttribute('data-question-index', questionCounter);

        questionItem.innerHTML = `
            <h4>Question ${questionCounter}</h4>
            <input type="hidden" name="questions[${questionCounter}][is_new]" value="1">
            <input type="hidden" name="questions[${questionCounter}][question_id]" value="">

            <div class="form-group">
                <label for="question_text_${questionCounter}">Question Text:</label>
                <input type="text" id="question_text_${questionCounter}" name="questions[${questionCounter}][text]" required>
            </div>

            <div class="form-group question-type-select">
                <label for="question_type_${questionCounter}">Question Type:</label>
                <select id="question_type_${questionCounter}" name="questions[${questionCounter}][type]" onchange="changeQuestionType(${questionCounter}, this.value)">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="blank_space">Fill in the Blank</option>
                    </select>
            </div>

            <div class="question-options" id="options_container_${questionCounter}">
                ${generateOptionsHtml('multiple_choice', questionCounter)} </div>

             <button type="button" class="remove-item-button" onclick="removeQuestion(${questionCounter})">Remove Question</button>
        `;

        // Append the new question block to the container
        questionsContainer.appendChild(questionItem);
    }

    // Function to add an existing question form, pre-filled with data
    function addExistingQuestion(questionData) {
         questionCounter++; // Increment counter for correct naming of subsequent new questions

         const questionsContainer = document.getElementById('questionsContainer');
         if (!questionsContainer) {
            console.error('Error in addExistingQuestion: #questionsContainer not found!');
            return;
        }

        const questionItem = document.createElement('div');
        questionItem.classList.add('question-item');
        // Use a unique ID for the question item based on the counter
        const questionItemId = `question_item_${questionCounter}`; // Use counter for DOM ID
        questionItem.setAttribute('id', questionItemId);
        // Use counter for form names index, AND store the actual DB question_id
        questionItem.setAttribute('data-question-index', questionCounter);
        questionItem.setAttribute('data-db-question-id', questionData.question_id);


        questionItem.innerHTML = `
            <h4>Question ${questionCounter}</h4>
            <input type="hidden" name="questions[${questionCounter}][question_id]" value="${questionData.question_id}">
             <input type="hidden" name="questions[${questionCounter}][is_existing]" value="1">


            <div class="form-group">
                <label for="question_text_${questionCounter}">Question Text:</label>
                <input type="text" id="question_text_${questionCounter}" name="questions[${questionCounter}][text]" value="${escapeHtml(questionData.question_text)}" required>
            </div>

            <div class="form-group question-type-select">
                <label for="question_type_${questionCounter}">Question Type:</label>
                <select id="question_type_${questionCounter}" name="questions[${questionCounter}][type]" onchange="changeQuestionType(${questionCounter}, this.value)">
                    <option value="multiple_choice" ${questionData.question_type === 'multiple_choice' ? 'selected' : ''}>Multiple Choice</option>
                    <option value="true_false" ${questionData.question_type === 'true_false' ? 'selected' : ''}>True/False</option>
                    <option value="blank_space" ${questionData.question_type === 'blank_space' ? 'selected' : ''}>Fill in the Blank</option>
                    </select>
            </div>

            <div class="question-options" id="options_container_${questionCounter}">
                ${generateOptionsHtml(questionData.question_type, questionCounter, questionData)}
            </div>

             <button type="button" class="remove-item-button" onclick="removeQuestion(${questionCounter})">Remove Question</button>
        `;

        questionsContainer.appendChild(questionItem);

         // No need to manually trigger change event here; generateOptionsHtml is called directly with data
    }


    // Function to generate HTML for options/answers based on question type
    // Includes logic to pre-fill with existing data
    function generateOptionsHtml(type, questionIndex, questionData = null) {
        let html = '';
        switch (type) {
            case 'multiple_choice':
                 // Pass questionIndex (for form names) and questionData (for existing choices/correct answer)
                html = `
                    <div id="mc_options_${questionIndex}">
                         <p>Options:</p>
                         ${generateMultipleChoiceOptionsHtml(questionIndex, questionData ? questionData.choices : [], questionData ? questionData.correct_answer : null)}
                    </div>
                    <button type="button" class="add-option-button" onclick="addMultipleChoiceOption(${questionIndex})">Add Option</button>
                `;
                break;
            case 'true_false':
                const correctAnswerTF = questionData ? questionData.correct_answer : '';
                html = `
                    <p>Correct Answer:</p>
                    <div class="option-group">
                        <input type="radio" name="questions[${questionIndex}][correct_answer]" value="true" id="tf_${questionIndex}_true" ${correctAnswerTF === 'true' ? 'checked' : ''} required>
                        <label for="tf_${questionIndex}_true">True</label>
                    </div>
                    <div class="option-group">
                        <input type="radio" name="questions[${questionIndex}][correct_answer]" value="false" id="tf_${questionIndex}_false" ${correctAnswerTF === 'false' ? 'checked' : ''}>
                        <label for="tf_${questionIndex}_false">False</label>
                    </div>
                `;
                break;
            case 'blank_space':
                 const correctAnswersBlank = questionData && questionData.correct_answer ? questionData.correct_answer.split('|') : ['']; // Split stored answers or start with one empty
                 html = `
                    <p>Blank Answers (Use [BLANK] in the question text for each blank):</p>
                    <div id="blank_answers_${questionIndex}">
                         ${generateBlankAnswersHtml(questionIndex, correctAnswersBlank)}
                    </div>
                    <button type="button" class="add-blank-button" onclick="addBlankAnswer(${questionIndex})">Add Blank Answer</button>
                    <p><small>Use <code>[BLANK]</code> in the question text to indicate where a blank space should appear for the student.</small></p>
                 `;
                 break;
            default:
                html = '<p>Select a question type to add options or answers.</p>';
                break;
        }
        return html;
    }

     // Helper function to generate HTML for multiple choice options (for new or existing)
     function generateMultipleChoiceOptionsHtml(questionIndex, choices = [], correctAnswerValue = null) {
         let html = '';
         // If no existing choices, provide at least two empty options for a new MC question
         if (choices.length === 0) {
              html += `
                 <div class="option-group">
                     <input type="hidden" name="questions[${questionIndex}][options][option_1][choice_id]" value=""> <input type="radio" name="questions[${questionIndex}][correct_answer]" value="option_1" id="mc_${questionIndex}_option_1">
                     <label for="mc_${questionIndex}_option_1">Correct:</label>
                     <input type="text" name="questions[${questionIndex}][options][option_1][text]" placeholder="Option 1 Text" required>
                      <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
                 </div>
                  <div class="option-group">
                     <input type="hidden" name="questions[${questionIndex}][options][option_2][choice_id]" value=""> <input type="radio" name="questions[${questionIndex}][correct_answer]" value="option_2" id="mc_${questionIndex}_option_2">
                     <label for="mc_${questionIndex}_option_2">Correct:</label>
                     <input type="text" name="questions[${questionIndex}][options][option_2][text]" placeholder="Option 2 Text" required>
                      <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
                 </div>
              `;
         } else {
             // Populate with existing choices
             choices.forEach((choice, index) => {
                 // Use choice_id as the value for the radio button and a key for the option data
                 const optionValue = `choice_${choice.choice_id}`; // Use a key based on choice_id or index + 1 if no ID yet
                 const isCorrect = choice.is_correct == 1; // Check if is_correct is true (DB returns 1 or 0)

                 html += `
                     <div class="option-group">
                         <input type="hidden" name="questions[${questionIndex}][options][${optionValue}][choice_id]" value="${choice.choice_id}">
                         <input type="radio" name="questions[${questionIndex}][correct_answer]" value="${optionValue}" id="mc_${questionIndex}_${optionValue}" ${isCorrect ? 'checked' : ''}>
                         <label for="mc_${questionIndex}_${optionValue}">Correct:</label>
                         <input type="text" name="questions[${questionIndex}][options][${optionValue}][text]" value="${escapeHtml(choice.choice_text)}" placeholder="Option ${index + 1} Text" required>
                          <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
                     </div>
                 `;
             });
         }
         return html;
     }

     // Helper function to generate HTML for blank answer fields (for new or existing)
     function generateBlankAnswersHtml(questionIndex, answers = ['']) {
         let html = '';
         answers.forEach((answer, index) => {
              html += `
                 <div class="blank-answer-group">
                      <label for="blank_${questionIndex}_answer_${index + 1}">Blank ${index + 1} Answer:</label>
                      <input type="text" id="blank_${questionIndex}_answer_${index + 1}" name="questions[${questionIndex}][answers][]" value="${escapeHtml(answer)}" placeholder="Correct answer for blank ${index + 1}" required>
                      <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Blank</button>
                 </div>
              `;
         });
         return html;
     }


    // Function to change the options/answers section when question type is changed
    function changeQuestionType(questionIndex, type) {
        const optionsContainer = document.getElementById(`options_container_${questionIndex}`);
        // When changing type, we don't have existing data for the *new* type, so pass null for questionData
        optionsContainer.innerHTML = generateOptionsHtml(type, questionIndex, null);
    }

    // Function to add a new option field for multiple choice questions
    function addMultipleChoiceOption(questionIndex) {
        const mcOptionsContainer = document.getElementById(`mc_options_${questionIndex}`);
        // Calculate option count based on existing option groups
        const optionCount = mcOptionsContainer.querySelectorAll('.option-group').length + 1;
        const optionGroup = document.createElement('div');
        optionGroup.classList.add('option-group');

        // Use a consistent naming convention for option values (e.g., new_option_1, new_option_2)
        // This helps distinguish new options from existing ones in PHP
        const optionValue = `new_option_${optionCount}`;

        optionGroup.innerHTML = `
            <input type="hidden" name="questions[${questionIndex}][options][${optionValue}][choice_id]" value="">
            <input type="radio" name="questions[${questionIndex}][correct_answer]" value="${optionValue}" id="mc_${questionIndex}_${optionValue}">
            <label for="mc_${questionIndex}_${optionValue}">Correct:</label>
            <input type="text" name="questions[${questionIndex}][options][${optionValue}][text]" placeholder="Option ${optionCount} Text" required>
             <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
        `;
        mcOptionsContainer.appendChild(optionGroup);
    }

     // Function to add a new blank answer field for fill-in-the-blank questions
     function addBlankAnswer(questionIndex) {
        const blankAnswersContainer = document.getElementById(`blank_answers_${questionIndex}`);
        // Calculate blank count based on existing blank answer groups
        const blankCount = blankAnswersContainer.querySelectorAll('.blank-answer-group').length + 1;
        const blankGroup = document.createElement('div');
        blankGroup.classList.add('blank-answer-group');

        blankGroup.innerHTML = `
             <label for="blank_${questionIndex}_answer_${blankCount}">Blank ${blankCount} Answer:</label>
             <input type="text" id="blank_${questionIndex}_answer_${blankCount}" name="questions[${questionIndex}][answers][]" value="${escapeHtml(answer)}" placeholder="Correct answer for blank ${blankCount}" required>
             <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Blank</button>
        `;
        blankAnswersContainer.appendChild(blankGroup);
     }


     // Function to remove an option or blank answer group
     function removeOption(button) {
        // This function is used for removing both MC options and Blank Answers
        button.parentElement.remove(); // Remove the parent .option-group or .blank-answer-group div
    }

    // Function to remove a question item block
    function removeQuestion(questionIndex) {
        // Find the question item by the data-question-index attribute
        const questionItem = document.querySelector(`.question-item[data-question-index="${questionIndex}"]`);
        if (questionItem) {
            questionItem.remove();
            // Note: This doesn't automatically re-number the displayed questions or update input names
            // after removal. Re-numbering would require more complex DOM manipulation
            // and updating all subsequent input names.
        }
    }

    // Helper function to escape HTML entities for displaying fetched data in input fields
    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }


    // Optional: You might want to handle the form submission with AJAX as well
    // This prevents a full page reload after submitting the form
    // document.getElementById('editExamForm').addEventListener('submit', function(e) {
    //     e.preventDefault(); // Prevent default form submission

    //     const formData = new FormData(this); // Get form data

    //     // Send form data via AJAX
    //     fetch('handle_action.php?action=instructor_edit_exam_submit', {
    //         method: 'POST',
    //         body: formData
    //     })
    //     .then(response => response.text()) // Or response.json() if your PHP returns JSON
    //     .then(result => {
    //         // Handle the response from the server
    //         alert(result); // Show a success or error message (basic example)
    //         // Optionally, load another page or update the view after successful submission
    //     })
    //     .catch(error => {
    //         console.error('Error submitting form:', error);
    //         alert('An error occurred during form submission.'); // Show an error message
    //     });
    // });
</script>

<?php
// Closing PHP tag omitted as it's the last block of PHP code.
