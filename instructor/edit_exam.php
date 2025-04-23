<?php
// includes/instructor/edit_exam.php

// This file handles the functionality for instructors to edit an existing exam.

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
    echo '<p>Access denied. You must be a logged-in instructor to edit exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold exam details
$questions = []; // Array to hold questions
$courses = []; // Array to hold courses for the dropdown

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

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

            // 2. Manage Questions and Choices
            // A common approach is to delete existing questions/choices for this exam
            // and re-insert the ones submitted in the form.
            // This is simpler than trying to track changes (added, edited, deleted questions/options).
            // IMPORTANT: Consider if there are already student answers for this exam.
            // Deleting questions might affect student results. A more complex system
            // might archive old questions or prevent editing after students have taken the exam.
            // For this example, we'll proceed with deletion and re-insertion.

            // Delete existing student answers related to this exam's questions
            $stmt = $pdo->prepare("DELETE sa FROM student_answers sa JOIN questions q ON sa.question_id = q.question_id WHERE q.exam_id = :exam_id");
            $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $stmt->execute();

            // Delete existing choices for this exam's questions
            $stmt = $pdo->prepare("DELETE c FROM choices c JOIN questions q ON c.question_id = q.question_id WHERE q.exam_id = :exam_id");
            $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $stmt->execute();

            // Delete existing questions for this exam
            $stmt = $pdo->prepare("DELETE FROM questions WHERE exam_id = :exam_id");
            $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
            $stmt->execute();


            // Now, insert the questions and choices from the submitted form data
            $allowedQuestionTypes = ['true_false', 'multiple_choice', 'blank_space'];
            foreach ($questionsData as $question) {
                $questionText = trim($question['text'] ?? '');
                $questionType = $question['type'] ?? '';
                $correctAnswer = null; // Will store correct answer for applicable types

                // Validate question data
                if (empty($questionText) || empty($questionType) || !in_array($questionType, $allowedQuestionTypes)) {
                    // Log or handle invalid question data - might skip or throw error
                     error_log("Invalid question data during update for exam ID " . $examIdToUpdate . ": " . json_encode($question));
                    continue; // Skip this invalid question
                }

                // Determine correct answer based on type
                if ($questionType === 'true_false') {
                    $correctAnswer = $question['correct_answer'] ?? null;
                    if (!in_array($correctAnswer, ['true', 'false'])) {
                         error_log("Invalid correct answer for True/False question during update for exam ID " . $examIdToUpdate);
                         continue; // Skip this invalid question
                    }
                } elseif ($questionType === 'blank_space') {
                    // For blank space, collect all submitted answers and store them as a delimited string
                    if (isset($question['answers']) && is_array($question['answers']) && count($question['answers']) > 0) {
                         $validAnswers = array_filter($question['answers'], 'trim');
                         if (count($validAnswers) > 0) {
                             $correctAnswer = implode('|', $validAnswers);
                         } else {
                              // Depending on your design, you might require at least one answer for blanks
                              // error_log("Blank space question missing answers during update for exam ID " . $examIdToUpdate);
                              // continue; // Skip if answers are required
                         }
                    } else {
                         // Depending on your design, you might require answers for blanks
                         // error_log("Blank space question missing answers during update for exam ID " . $examIdToUpdate);
                         // continue; // Skip if answers are required
                    }
                }

                // Insert into questions table
                $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
                $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    throw new Exception("Error inserting question during update: " . implode(" ", $stmt->errorInfo()));
                }
                $questionId = $pdo->lastInsertId(); // Get the ID of the newly inserted question

                // Handle choices for multiple choice questions
                if ($questionType === 'multiple_choice') {
                    if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 2) {
                         error_log("Multiple choice question missing options during update for exam ID " . $examIdToUpdate);
                         continue; // Skip this question if options are missing/insufficient
                    }
                     if (!isset($question['correct_answer'])) {
                         error_log("Multiple choice question missing correct_answer selection during update for exam ID " . $examIdToUpdate);
                         continue; // Skip this question if correct answer is not selected
                    }
                    $correctOptionValue = $question['correct_answer']; // The value that indicates the correct option

                    foreach ($question['options'] as $optionValue => $optionText) {
                        $optionText = trim($optionText);
                        if (empty($optionText)) {
                             continue; // Skip empty options
                        }

                        // Determine if this choice is the correct one
                        $isCorrect = ($optionValue === $correctOptionValue);

                        $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (:question_id, :choice_text, :is_correct)");
                        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                        $stmt->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                        $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);

                        if (!$stmt->execute()) {
                            throw new Exception("Error inserting choice during update: " . implode(" ", $stmt->errorInfo()));
                        }
                    }
                }
            }

            $pdo->commit(); // Commit the transaction if all updates/insertions were successful
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
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['exam_id']) && filter_var($_GET['exam_id'], FILTER_VALIDATE_INT)) {
    $examId = filter_var($_GET['exam_id'], FILTER_VALIDATE_INT);

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

        // If exam is found, fetch its questions
        if ($exam) {
            $sql = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC"; // Order might be different in practice
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':exam_id', $examId, PDO::PARAM_INT);
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // For each multiple-choice question, fetch its choices
            foreach ($questions as &$question) { // Use & to modify the original array elements
                if ($question['question_type'] === 'multiple_choice') {
                    $sql = "SELECT * FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC"; // Order might be different
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $question['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            unset($question); // Break the reference with the last element
        } else {
            // Exam not found or doesn't belong to instructor - set an error message
            $message = '<p class="error">Exam not found or you do not have permission to edit it.</p>';
        }

    } catch (PDOException $e) {
        // Log error and display a user-friendly message
        error_log("Error fetching exam details for editing: " . $e->getMessage()); // Log the detailed error
        $message = '<p class="error">Error loading exam details for editing. Please try again later.</p>';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
     // If it's a GET request but no exam_id is provided
     $message = '<p class="error">No exam ID provided for editing.</p>';
}
// Note: If it's a POST request (form submission), the $exam and $questions variables
// will not be populated here, as the logic for fetching is in the GET block.
// If the POST submission fails, you might want to re-fetch the data to repopulate the form.
// For simplicity, this example assumes successful POST or displays the error.
// A more robust approach would re-fetch on POST failure.


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
    // Display an error or handle appropriately
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
    let questionCounter = 0; // Initialize counter for new questions

    // Wait for the DOM to be fully loaded before adding event listeners and populating questions
    document.addEventListener('DOMContentLoaded', () => {
        console.log('edit_exam.php DOMContentLoaded'); // Debugging line

        const questionsContainer = document.getElementById('questionsContainer');
        const addQuestionButton = document.querySelector('.add-question-button');

        // Check if the container and button exist before adding listeners
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
         console.log('Event listener added to .add-question-button'); // Debugging line


        // --- Populate existing questions ---
        const existingQuestions = <?php echo json_encode($questions); ?>; // Get questions data from PHP
        console.log('Existing questions:', existingQuestions); // Debugging line

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


    // Function to add a new, empty question form
    function addQuestion() {
        console.log('addQuestion called (for new question)'); // Debugging line

        questionCounter++;

        const questionsContainer = document.getElementById('questionsContainer');
        console.log('questionsContainer:', questionsContainer); // Debugging line

        if (!questionsContainer) {
            console.error('Error: #questionsContainer not found!');
            return;
        }

        const questionItem = document.createElement('div');
        questionItem.classList.add('question-item');
        // Use a unique ID for the question item based on the counter
        const questionItemId = `question_item_${questionCounter}`;
        questionItem.setAttribute('id', questionItemId);
        questionItem.setAttribute('data-question-id', questionCounter); // Use counter for form names

        questionItem.innerHTML = `
            <h4>Question ${questionCounter}</h4>
            <input type="hidden" name="questions[${questionCounter}][new]" value="1">
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
                ${generateOptionsHtml('multiple_choice', questionCounter)}
            </div>

             <button type="button" class="remove-item-button" onclick="removeQuestion(${questionCounter})">Remove Question</button>
        `;

        questionsContainer.appendChild(questionItem);
    }

    // Function to add an existing question form, pre-filled with data
    function addExistingQuestion(questionData) {
         questionCounter++; // Increment counter for correct naming of subsequent new questions

         const questionsContainer = document.getElementById('questionsContainer');
         if (!questionsContainer) {
            console.error('Error: #questionsContainer not found!');
            return;
        }

        const questionItem = document.createElement('div');
        questionItem.classList.add('question-item');
        // Use the actual question_id for the item ID (more stable if not re-ordering)
        // Or use a counter-based ID and a data attribute for the actual question_id
        const questionItemId = `question_item_${questionCounter}`; // Use counter for DOM ID
        questionItem.setAttribute('id', questionItemId);
        questionItem.setAttribute('data-question-id', questionData.question_id); // Store actual DB ID

        questionItem.innerHTML = `
            <h4>Question ${questionCounter}</h4>
             <input type="hidden" name="questions[${questionCounter}][existing]" value="1">
            <input type="hidden" name="questions[${questionCounter}][question_id]" value="${questionData.question_id}">

            <div class="form-group">
                <label for="question_text_${questionCounter}">Question Text:</label>
                <input type="text" id="question_text_${questionCounter}" name="questions[${questionCounter}][text]" value="${htmlspecialchars(questionData.question_text)}" required>
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

         // Manually trigger change event to load correct options for existing questions
         const typeSelect = document.getElementById(`question_type_${questionCounter}`);
         if (typeSelect) {
             // Using a timeout to ensure the element is fully in the DOM and ready
             setTimeout(() => {
                  changeQuestionType(questionCounter, questionData.question_type);
             }, 0); // A small timeout can sometimes help with dynamic content
         }
    }


    // Modified generateOptionsHtml to accept existing question data
    function generateOptionsHtml(type, questionId, questionData = null) {
        let html = '';
        switch (type) {
            case 'multiple_choice':
                html = `
                    <div id="mc_options_${questionId}">
                         <p>Options:</p>
                         ${generateMultipleChoiceOptionsHtml(questionId, questionData ? questionData.choices : [], questionData ? questionData.correct_answer : null)}
                    </div>
                    <button type="button" class="add-option-button" onclick="addMultipleChoiceOption(${questionId})">Add Option</button>
                `;
                break;
            case 'true_false':
                const correctAnswerTF = questionData ? questionData.correct_answer : '';
                html = `
                    <p>Correct Answer:</p>
                    <div class="option-group">
                        <input type="radio" name="questions[${questionId}][correct_answer]" value="true" id="tf_${questionId}_true" ${correctAnswerTF === 'true' ? 'checked' : ''} required>
                        <label for="tf_${questionId}_true">True</label>
                    </div>
                    <div class="option-group">
                        <input type="radio" name="questions[${questionId}][correct_answer]" value="false" id="tf_${questionId}_false" ${correctAnswerTF === 'false' ? 'checked' : ''}>
                        <label for="tf_${questionId}_false">False</label>
                    </div>
                `;
                break;
            case 'blank_space':
                 const correctAnswersBlank = questionData && questionData.correct_answer ? questionData.correct_answer.split('|') : ['']; // Split stored answers or start with one empty
                 html = `
                    <p>Blank Answers (Use [BLANK] in the question text for each blank):</p>
                    <div id="blank_answers_${questionId}">
                         ${generateBlankAnswersHtml(questionId, correctAnswersBlank)}
                    </div>
                    <button type="button" class="add-blank-button" onclick="addBlankAnswer(${questionId})">Add Blank Answer</button>
                    <p><small>Use <code>[BLANK]</code> in the question text to indicate where a blank space should appear for the student.</small></p>
                 `;
                 break;
            default:
                html = '<p>Select a question type to add options or answers.</p>';
                break;
        }
        return html;
    }

    // Helper function to generate HTML for existing multiple choice options
    function generateMultipleChoiceOptionsHtml(questionId, choices, correctAnswerValue = null) {
        let html = '';
        if (choices.length === 0) {
            // Provide at least two empty options if none exist
             html += `
                <div class="option-group">
                    <input type="radio" name="questions[${questionId}][correct_answer]" value="option_1" id="mc_${questionId}_option_1">
                    <label for="mc_${questionId}_option_1">Correct:</label>
                    <input type="text" name="questions[${questionId}][options][option_1]" placeholder="Option 1 Text" required>
                     <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
                </div>
                 <div class="option-group">
                    <input type="radio" name="questions[${questionId}][correct_answer]" value="option_2" id="mc_${questionId}_option_2">
                    <label for="mc_${questionId}_option_2">Correct:</label>
                    <input type="text" name="questions[${questionId}][options][option_2]" placeholder="Option 2 Text" required>
                     <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
                </div>
             `;
        } else {
            choices.forEach((choice, index) => {
                // Generate a value for the radio button and input name
                const optionValue = `option_${index + 1}`; // Simple numbering based on order

                html += `
                    <div class="option-group">
                        <input type="radio" name="questions[${questionId}][correct_answer]" value="${optionValue}" id="mc_${questionId}_${optionValue}" ${choice.is_correct ? 'checked' : ''}>
                        <label for="mc_${questionId}_${optionValue}">Correct:</label>
                        <input type="text" name="questions[${questionId}][options][${optionValue}]" value="${htmlspecialchars(choice.choice_text)}" placeholder="Option ${index + 1} Text" required>
                         <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
                    </div>
                `;
            });
        }
        return html;
    }

     // Helper function to generate HTML for existing blank answers
     function generateBlankAnswersHtml(questionId, answers) {
         let html = '';
         if (answers.length === 0 || (answers.length === 1 && answers[0] === '')) {
             // Provide one empty blank answer field if none exist
             html += `
                 <div class="blank-answer-group">
                      <label for="blank_${questionId}_answer_1">Blank 1 Answer:</label>
                      <input type="text" id="blank_${questionId}_answer_1" name="questions[${questionId}][answers][]" placeholder="Correct answer for blank 1" required>
                      <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Blank</button>
                 </div>
             `;
         } else {
             answers.forEach((answer, index) => {
                 html += `
                     <div class="blank-answer-group">
                          <label for="blank_${questionId}_answer_${index + 1}">Blank ${index + 1} Answer:</label>
                          <input type="text" id="blank_${questionId}_answer_${index + 1}" name="questions[${questionId}][answers][]" value="${htmlspecialchars(answer)}" placeholder="Correct answer for blank ${index + 1}" required>
                          <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Blank</button>
                     </div>
                 `;
             });
         }
         return html;
     }


    function changeQuestionType(questionId, type) {
        const optionsContainer = document.getElementById(`options_container_${questionId}`);
        // When changing type, we don't have existing data for the new type, so pass null for questionData
        optionsContainer.innerHTML = generateOptionsHtml(type, questionId, null);
    }

    function addMultipleChoiceOption(questionId) {
        const mcOptionsContainer = document.getElementById(`mc_options_${questionId}`);
        // Calculate option count based on existing option groups
        const optionCount = mcOptionsContainer.querySelectorAll('.option-group').length + 1;
        const optionGroup = document.createElement('div');
        optionGroup.classList.add('option-group');

        // Use a consistent naming convention for option values
        const optionValue = `option_${optionCount}`;

        optionGroup.innerHTML = `
            <input type="radio" name="questions[${questionId}][correct_answer]" value="${optionValue}" id="mc_${questionId}_option_${optionCount}">
            <label for="mc_${questionId}_option_${optionCount}">Correct:</label>
            <input type="text" name="questions[${questionId}][options][${optionValue}]" placeholder="Option ${optionCount} Text" required>
             <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Option</button>
        `;
        mcOptionsContainer.appendChild(optionGroup);
    }

     function addBlankAnswer(questionId) {
        const blankAnswersContainer = document.getElementById(`blank_answers_${questionId}`);
        // Calculate blank count based on existing blank answer groups
        const blankCount = blankAnswersContainer.querySelectorAll('.blank-answer-group').length + 1;
        const blankGroup = document.createElement('div');
        blankGroup.classList.add('blank-answer-group');

        blankGroup.innerHTML = `
             <label for="blank_${questionId}_answer_${blankCount}">Blank ${blankCount} Answer:</label>
             <input type="text" id="blank_${questionId}_answer_${blankCount}" name="questions[${questionId}][answers][]" placeholder="Correct answer for blank ${blankCount}" required>
             <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Blank</button>
        `;
        blankAnswersContainer.appendChild(blankGroup);
     }


     function removeOption(button) {
        // This function is used for removing both MC options and Blank Answers
        button.parentElement.remove(); // Remove the parent .option-group or .blank-answer-group div
    }

    function removeQuestion(questionCounterId) {
        // Find the question item by the counter ID used in the DOM
        const questionItem = document.getElementById(`question_item_${questionCounterId}`);
        if (questionItem) {
            questionItem.remove();
            // Note: This doesn't re-number the displayed questions or input names
            // after removal. Re-numbering would require more complex DOM manipulation
            // and updating all subsequent input names.
        }
    }

    // Helper function to safely escape HTML entities
    function htmlspecialchars(str) {
        if (typeof str !== 'string') {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }


    // Optional: Handle the form submission with AJAX
    // This prevents a full page reload after submitting the form
    // document.getElementById('editExamForm').addEventListener('submit', function(e) {
    //     e.preventDefault(); // Prevent default form submission

    //     const formData = new FormData(this); // Get form data

    //     // Send form data via AJAX
    //     fetch('handle_action.php?action=instructor_edit_exam_submit', { // Use a specific action for submission
    //         method: 'POST',
    //         body: formData
    //     })
    //     .then(response => response.text()) // Or response.json() if your PHP returns JSON
    //     .then(result => {
    //         // Handle the response from the server (e.g., display success/error message)
    //         alert(result); // Show a success or error message (basic example)
    //         // Optionally, reload the manage exams page or the edited exam view
    //     })
    //     .catch(error => {
    //         console.error('Error submitting form:', error);
    //         alert('An error occurred during form submission.'); // Show an error message
    //     });
    // });
</script>

<?php
// If exam data was not found (e.g., invalid ID or not instructor's exam),
// the message will be displayed by the PHP logic above, and the form won't render.
?>
