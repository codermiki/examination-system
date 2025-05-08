<?php
// include_once __DIR__ . '/../../config.php'; 
// include_once '../../includes/db/db.config.php'; // include_once __DIR__ . 
include_once __DIR__ . '/../../includes/db/db.config.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    // Redirect to login or show error
    // For example: header('Location: /login.php'); exit();
    echo '<p>Access denied. You must be a logged-in instructor.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold the specific exam being edited
$questions = []; // Array to hold questions for the specific exam
$courses = []; // Array to hold courses for the dropdown
$instructorExams = []; // Array to hold the list of exams for selection

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

// --- Start: PHP Logic for Handling Form Submission (Updating Exam) ---
// This block executes ONLY when the form is submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exam_id'])) {
    // Get and validate exam details from POST
    $examIdToUpdate = filter_var($_POST['exam_id'], FILTER_VALIDATE_INT);
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
            $pdo->beginTransaction();

            // 1. Update exams table
            $stmt = $pdo->prepare("UPDATE exams SET course_id = :course_id, title = :title, description = :description, time_limit = :time_limit, total_marks = :total_marks WHERE exam_id = :exam_id AND instructor_id = :instructor_id");
            // Bind parameters... (same as your original code)
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

            // 2. Manage Questions and Choices (Delete and Re-insert Approach)
            // WARNING: This approach deletes existing questions/choices/answers.
            // Consider implications if students have already taken the exam.

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
            foreach ($questionsData as $qIndex => $question) { // Use index for unique IDs if needed
                $questionText = trim($question['text'] ?? '');
                $questionType = $question['type'] ?? '';
                $correctAnswer = null;

                if (empty($questionText) || empty($questionType) || !in_array($questionType, $allowedQuestionTypes)) {
                    error_log("Invalid question data during update for exam ID " . $examIdToUpdate . ": " . json_encode($question));
                    continue; // Skip invalid question
                }

                // Determine correct answer string based on type (same logic as your original code)
                if ($questionType === 'true_false') {
                    $correctAnswer = $question['correct_answer'] ?? null;
                    if (!in_array($correctAnswer, ['true', 'false'])) continue;
                } elseif ($questionType === 'blank_space') {
                     if (isset($question['answers']) && is_array($question['answers'])) {
                         $validAnswers = array_filter(array_map('trim', $question['answers']));
                         if (count($validAnswers) > 0) $correctAnswer = implode('|', $validAnswers);
                         // else continue; // Decide if blank answers are mandatory
                     } // else continue;
                } elseif ($questionType === 'multiple_choice') {
                    // Correct answer for MC is stored with the choice (is_correct=1)
                    // The 'correct_answer' field in the questions table can be NULL for MC
                    // or store the *value* of the correct radio button if needed for reference
                    // $correctAnswer = $question['correct_answer'] ?? null; // Optional reference
                }


                // Insert into questions table
                $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer) VALUES (:exam_id, :question_text, :question_type, :correct_answer)");
                // Bind parameters...
                 $stmt->bindParam(':exam_id', $examIdToUpdate, PDO::PARAM_INT);
                 $stmt->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                 $stmt->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                 $stmt->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR); // Might be NULL for MC

                if (!$stmt->execute()) {
                    throw new Exception("Error inserting question during update: " . implode(" ", $stmt->errorInfo()));
                }
                $questionId = $pdo->lastInsertId();

                // Handle choices for multiple choice questions
                if ($questionType === 'multiple_choice') {
                    if (!isset($question['options']) || !is_array($question['options']) || count($question['options']) < 1 || !isset($question['correct_answer'])) {
                         error_log("MC question missing options/correct answer during update for exam ID " . $examIdToUpdate);
                         continue; // Skip incomplete MC question
                    }
                    $correctOptionValue = $question['correct_answer']; // e.g., 'option_1'

                    foreach ($question['options'] as $optionKey => $optionText) { // $optionKey is e.g., 'option_1'
                        $optionText = trim($optionText);
                        if (empty($optionText)) continue;

                        $isCorrect = ($optionKey === $correctOptionValue);

                        $stmt = $pdo->prepare("INSERT INTO choices (question_id, choice_text, is_correct) VALUES (:question_id, :choice_text, :is_correct)");
                        // Bind parameters...
                        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                        $stmt->bindParam(':choice_text', $optionText, PDO::PARAM_STR);
                        $stmt->bindParam(':is_correct', $isCorrect, PDO::PARAM_BOOL);

                        if (!$stmt->execute()) {
                            throw new Exception("Error inserting choice during update: " . implode(" ", $stmt->errorInfo()));
                        }
                    }
                }
            } // End foreach question

            $pdo->commit();
            $message = '<p class="success">Exam updated successfully.</p>';
            // Optional: Redirect after successful update
            // header('Location: manage_exams.php'); // Or back to edit page with success message
            // exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Exam update error: " . $e->getMessage());
            $message = '<p class="error">Error updating exam. Please check the details and try again. Details: ' . htmlspecialchars($e->getMessage()) . '</p>';
            // To show the form again with errors, we need to re-fetch the exam data
            // This part is added below in the GET logic section
        }
    }
    // If validation failed or DB error occurred, we need to ensure $examId is set
    // so the page tries to reload the form below.
    $examId = $examIdToUpdate; // Keep examId for reloading form on error
}
// --- End: PHP Logic for Handling Form Submission ---


// --- Start: PHP Logic for Displaying Page (List or Edit Form) ---

// Fetch courses for the dropdown (needed in edit view)
try {
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
    $message .= '<p class="error">Could not load courses list.</p>'; // Append error
}

// Determine whether to show the list or the edit form
$showEditForm = false;
$examIdFromGet = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
// Use $examId set from POST error handling OR from GET request
$examIdToLoad = $examId ?? $examIdFromGet;

if ($examIdToLoad) {
    // Try to fetch the specific exam for editing
    try {
        $sql = "SELECT e.*, c.course_name
                FROM exams e
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.exam_id = :exam_id AND e.instructor_id = :instructor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exam_id', $examIdToLoad, PDO::PARAM_INT);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->execute();
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            $showEditForm = true; // Found the exam, show the form
            // Fetch its questions
            $sql_q = "SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY question_id ASC";
            $stmt_q = $pdo->prepare($sql_q);
            $stmt_q->bindParam(':exam_id', $examIdToLoad, PDO::PARAM_INT);
            $stmt_q->execute();
            $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

            // Fetch choices for multiple-choice questions
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'multiple_choice') {
                    $sql_c = "SELECT * FROM choices WHERE question_id = :question_id ORDER BY choice_id ASC";
                    $stmt_c = $pdo->prepare($sql_c);
                    $stmt_c->bindParam(':question_id', $question['question_id'], PDO::PARAM_INT);
                    $stmt_c->execute();
                    // Store choices within the question array
                    $question['choices'] = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
                    // Find the correct choice's value (e.g., 'option_1') to pre-select radio
                    $correctChoiceValue = null;
                    foreach($question['choices'] as $choiceIndex => $choice) {
                        if ($choice['is_correct']) {
                            // Generate the value used in the form (e.g., 'option_1', 'option_2')
                            $correctChoiceValue = 'option_' . ($choiceIndex + 1);
                            break;
                        }
                    }
                    // Add this value to the question data for easier JS access
                    $question['correct_choice_form_value'] = $correctChoiceValue;
                }
            }
            unset($question); // Break reference

        } else {
            // Exam ID provided but not found or not owned by instructor
            if (empty($message)) { // Avoid overwriting POST error messages
                 $message = '<p class="error">Exam not found or you do not have permission to edit it.</p>';
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam details for editing: " . $e->getMessage());
        if (empty($message)) {
            $message = '<p class="error">Error loading exam details. Please try again later.</p>';
        }
    }
} else {
    // No exam_id provided (or invalid), fetch the list of exams for this instructor
    try {
        $sql = "SELECT exam_id, title, description, created_at
                FROM exams
                WHERE instructor_id = :instructor_id
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->execute();
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching instructor exams list: " . $e->getMessage());
        $message .= '<p class="error">Could not load your exams list.</p>'; // Append error
    }
}
// --- End: PHP Logic for Displaying Page ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showEditForm ? 'Edit Exam' : 'Select Exam to Edit'; ?></title>
    <link rel="stylesheet" href="../assets/css/instructor_style.css"> <style>
        /* Basic styling for clarity */
        .edit-exam-container, .select-exam-container { padding: 20px; max-width: 900px; margin: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .question-item { border: 1px solid #eee; padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #f9f9f9; }
        .question-item h4 { margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .question-options { margin-top: 10px; padding-left: 20px; border-left: 3px solid #eee; }
        .option-group, .blank-answer-group { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .option-group input[type="radio"] { margin-right: 5px; }
        .option-group input[type="text"], .blank-answer-group input[type="text"] { flex-grow: 1; } /* Allow text input to take available space */
        .add-question-button, .add-option-button, .add-blank-button, button[type="submit"] {
            background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px;
        }
        .add-question-button:hover, .add-option-button:hover, .add-blank-button:hover, button[type="submit"]:hover { background-color: #0056b3; }
        .remove-item-button { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 0.8em; }
        .remove-item-button:hover { background-color: #c82333; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .exam-list { list-style: none; padding: 0; }
        .exam-list li { border: 1px solid #ddd; margin-bottom: 10px; padding: 15px; border-radius: 4px; }
        .exam-list li a { font-weight: bold; text-decoration: none; color: #007bff; }
        .exam-list li a:hover { text-decoration: underline; }
        .exam-list li p { margin: 5px 0 0 0; color: #555; }
    </style>
</head>
<body>

    <?php // include_once '../includes/layout/InstructorSidebar.php'; // Example ?>

    <main> <div class="<?php echo $showEditForm ? 'edit-exam-container' : 'select-exam-container'; ?>">

            <h1><?php echo $showEditForm ? 'Edit Exam' : 'Select Exam to Edit'; ?></h1>

            <?php
            // Display feedback message if any
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>

            <?php if ($showEditForm && $exam): // If a specific exam was successfully loaded for editing ?>

                <form id="editExamForm" method="POST" action="edit_exam.php"> <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['exam_id']); ?>">

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
                             <?php if (empty($courses)): ?>
                                <option value="" disabled>No courses assigned to you.</option>
                            <?php endif; ?>
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

                <script>
                    let questionCounter = 0; // Initialize counter

                    // Function to safely escape HTML entities for use in JS values
                    function htmlspecialchars(str) {
                        if (typeof str !== 'string') return '';
                        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                        return str.replace(/[&<>"']/g, m => map[m]);
                    }

                    // Wait for the DOM to be fully loaded
                    document.addEventListener('DOMContentLoaded', () => {
                        const questionsContainer = document.getElementById('questionsContainer');
                        const addQuestionButton = document.querySelector('.add-question-button');

                        if (!questionsContainer || !addQuestionButton) {
                            console.error('Required elements (#questionsContainer or .add-question-button) not found!');
                            return;
                        }

                        addQuestionButton.addEventListener('click', addQuestion);

                        // --- Populate existing questions ---
                        // Pass PHP questions array to JS
                        const existingQuestions = <?php echo json_encode($questions); ?>;

                        if (existingQuestions && Array.isArray(existingQuestions) && existingQuestions.length > 0) {
                            existingQuestions.forEach(question => {
                                addExistingQuestion(question);
                            });
                            // Ensure counter is beyond the existing items for new additions
                            // questionCounter is incremented inside addExistingQuestion
                        } else {
                            // Maybe add one empty question if none exist? Optional.
                            // addQuestion();
                        }
                        // --- End Populate existing questions ---
                    });

                    // Function to add a new, empty question form
                    function addQuestion() {
                        questionCounter++; // Increment for unique IDs/names

                        const questionsContainer = document.getElementById('questionsContainer');
                        const questionItem = document.createElement('div');
                        questionItem.classList.add('question-item');
                        const questionItemId = `question_item_${questionCounter}`;
                        questionItem.setAttribute('id', questionItemId);
                        // Use the counter for the array index in the form name
                        questionItem.setAttribute('data-question-index', questionCounter);

                        questionItem.innerHTML = `
                            <h4>New Question ${questionCounter}</h4>
                            <input type="hidden" name="questions[${questionCounter}][question_id]" value=""> <div class="form-group">
                                <label for="question_text_${questionCounter}">Question Text:</label>
                                <input type="text" id="question_text_${questionCounter}" name="questions[${questionCounter}][text]" required>
                            </div>

                            <div class="form-group question-type-select">
                                <label for="question_type_${questionCounter}">Question Type:</label>
                                <select id="question_type_${questionCounter}" name="questions[${questionCounter}][type]" onchange="changeQuestionType(${questionCounter}, this.value)">
                                    <option value="multiple_choice" selected>Multiple Choice</option>
                                    <option value="true_false">True/False</option>
                                    <option value="blank_space">Fill in the Blank</option>
                                </select>
                            </div>

                            <div class="question-options" id="options_container_${questionCounter}">
                                ${generateOptionsHtml('multiple_choice', questionCounter, null)} </div>

                            <button type="button" class="remove-item-button" onclick="removeQuestion('${questionItemId}')">Remove Question</button>
                        `;
                        questionsContainer.appendChild(questionItem);
                    }

                    // Function to add an existing question form, pre-filled with data
                    function addExistingQuestion(questionData) {
                        questionCounter++; // Use counter for unique DOM IDs and form names

                        const questionsContainer = document.getElementById('questionsContainer');
                        const questionItem = document.createElement('div');
                        questionItem.classList.add('question-item');
                        const questionItemId = `question_item_${questionCounter}`; // Use counter for DOM ID
                        questionItem.setAttribute('id', questionItemId);
                        questionItem.setAttribute('data-question-index', questionCounter); // Use counter for form names

                        questionItem.innerHTML = `
                            <h4>Question ${questionCounter}</h4>
                            <input type="hidden" name="questions[${questionCounter}][question_id]" value="${questionData.question_id}"> <div class="form-group">
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

                            <button type="button" class="remove-item-button" onclick="removeQuestion('${questionItemId}')">Remove Question</button>
                        `;
                        questionsContainer.appendChild(questionItem);
                    }

                    // Generate HTML for options/answers based on type and existing data
                    function generateOptionsHtml(type, questionIndex, questionData = null) {
                        let html = '';
                        switch (type) {
                            case 'multiple_choice':
                                const choices = (questionData && Array.isArray(questionData.choices)) ? questionData.choices : [];
                                const correctChoiceFormValue = questionData ? questionData.correct_choice_form_value : null; // Get pre-calculated value
                                html = `
                                    <div id="mc_options_${questionIndex}">
                                        <p>Options (Select Correct Answer):</p>
                                        ${generateMultipleChoiceOptionsHtml(questionIndex, choices, correctChoiceFormValue)}
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
                                const correctAnswersBlank = (questionData && questionData.correct_answer) ? questionData.correct_answer.split('|') : [''];
                                html = `
                                    <p>Blank Answers (Use [BLANK] in question text):</p>
                                    <div id="blank_answers_${questionIndex}">
                                        ${generateBlankAnswersHtml(questionIndex, correctAnswersBlank)}
                                    </div>
                                    <button type="button" class="add-blank-button" onclick="addBlankAnswer(${questionIndex})">Add Blank Answer</button>
                                    <p><small>Use <code>[BLANK]</code> in the question text for each blank space.</small></p>
                                `;
                                break;
                            default:
                                html = '<p>Select a question type.</p>';
                                break;
                        }
                        return html;
                    }

                    // Generate HTML for multiple choice options
                    function generateMultipleChoiceOptionsHtml(questionIndex, choices, correctChoiceFormValue) {
                        let html = '';
                        let optionCounter = 0;
                        if (choices.length === 0) {
                            // Add 2 empty options by default for new MC questions
                            html += generateSingleMCOptionHtml(questionIndex, ++optionCounter, '', false);
                            html += generateSingleMCOptionHtml(questionIndex, ++optionCounter, '', false);
                        } else {
                            choices.forEach((choice) => {
                                optionCounter++;
                                const optionValue = `option_${optionCounter}`; // Generate value based on position
                                const isChecked = (optionValue === correctChoiceFormValue);
                                html += generateSingleMCOptionHtml(questionIndex, optionCounter, choice.choice_text, isChecked);
                            });
                        }
                        return html;
                    }

                    // Helper for a single MC option row
                    function generateSingleMCOptionHtml(questionIndex, optionIndex, text, isChecked) {
                         const optionValue = `option_${optionIndex}`;
                         return `
                            <div class="option-group">
                                <input type="radio" name="questions[${questionIndex}][correct_answer]" value="${optionValue}" id="mc_${questionIndex}_${optionValue}" ${isChecked ? 'checked' : ''} required>
                                <label for="mc_${questionIndex}_${optionValue}">Correct:</label>
                                <input type="text" name="questions[${questionIndex}][options][${optionValue}]" value="${htmlspecialchars(text)}" placeholder="Option ${optionIndex} Text" required>
                                <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove</button>
                            </div>`;
                    }

                     // Generate HTML for blank answer fields
                    function generateBlankAnswersHtml(questionIndex, answers) {
                        let html = '';
                        let answerCounter = 0;
                        if (answers.length === 0 || (answers.length === 1 && answers[0] === '')) {
                            // Add 1 empty field by default
                            html += generateSingleBlankAnswerHtml(questionIndex, ++answerCounter, '');
                        } else {
                            answers.forEach((answer) => {
                                html += generateSingleBlankAnswerHtml(questionIndex, ++answerCounter, answer);
                            });
                        }
                        return html;
                    }

                    // Helper for a single blank answer row
                    function generateSingleBlankAnswerHtml(questionIndex, answerIndex, text) {
                        return `
                            <div class="blank-answer-group">
                                <label for="blank_${questionIndex}_answer_${answerIndex}">Blank ${answerIndex} Answer:</label>
                                <input type="text" id="blank_${questionIndex}_answer_${answerIndex}" name="questions[${questionIndex}][answers][]" value="${htmlspecialchars(text)}" placeholder="Correct answer for blank ${answerIndex}" required>
                                <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove</button>
                            </div>`;
                    }

                    // Function called when question type dropdown changes
                    function changeQuestionType(questionIndex, type) {
                        const optionsContainer = document.getElementById(`options_container_${questionIndex}`);
                        // Regenerate options for the new type, passing null for data as it's a type change
                        optionsContainer.innerHTML = generateOptionsHtml(type, questionIndex, null);
                    }

                    // Function to add a new MC option dynamically
                    function addMultipleChoiceOption(questionIndex) {
                        const mcOptionsContainer = document.getElementById(`mc_options_${questionIndex}`);
                        const optionCount = mcOptionsContainer.querySelectorAll('.option-group').length + 1;
                        const optionHtml = generateSingleMCOptionHtml(questionIndex, optionCount, '', false);
                        mcOptionsContainer.insertAdjacentHTML('beforeend', optionHtml);
                    }

                    // Function to add a new blank answer field dynamically
                    function addBlankAnswer(questionIndex) {
                        const blankAnswersContainer = document.getElementById(`blank_answers_${questionIndex}`);
                        const blankCount = blankAnswersContainer.querySelectorAll('.blank-answer-group').length + 1;
                        const blankHtml = generateSingleBlankAnswerHtml(questionIndex, blankCount, '');
                        blankAnswersContainer.insertAdjacentHTML('beforeend', blankHtml);
                    }

                    // Function to remove an option or blank answer group
                    function removeOption(button) {
                        button.parentElement.remove();
                    }

                    // Function to remove a whole question item
                    function removeQuestion(questionItemId) {
                        const questionItem = document.getElementById(questionItemId);
                        if (questionItem) {
                            questionItem.remove();
                            // Note: Re-numbering display ("Question X") dynamically is complex and often omitted.
                            // The form submission relies on the array keys `questions[INDEX]`.
                        }
                    }

                </script>

            <?php else: // If no specific exam is being edited (or wasn't found), show the list ?>

                <?php if (!empty($instructorExams)): ?>
                    <ul class="exam-list">
                        <?php foreach ($instructorExams as $instExam): ?>
                            <li>
                                <a href="ui/edit_exam.php?exam_id=<?php echo htmlspecialchars($instExam['exam_id']); ?>">
                                    <?php echo htmlspecialchars($instExam['title']); ?>
                                </a>
                                (Created: <?php echo htmlspecialchars(date('M d, Y', strtotime($instExam['created_at']))); ?>)
                                <?php if (!empty($instExam['description'])): ?>
                                    <p><small><?php echo htmlspecialchars(substr($instExam['description'], 0, 150)) . (strlen($instExam['description']) > 150 ? '...' : ''); ?></small></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif (empty($message)): // Avoid showing this if there was an error loading the list ?>
                    <p>You have not created any exams yet.</p>
                    <?php endif; ?>

            <?php endif; ?>

        </div> </main>

    <?php // include_once '../includes/layout/footer.php'; // Example ?>

</body>
</html>
