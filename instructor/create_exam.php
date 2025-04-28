<?php
// includes/instructor/create_exam.php

// This file contains the HTML, CSS, JavaScript for the "Create Exam" form
// and PHP logic to save the exam data to the database.

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
    echo '<p>Access denied. You must be a logged-in instructor to create exams.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages

// --- Start: PHP Logic for Handling Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id

    // Get and validate exam details
    $examTitle = trim($_POST['examTitle'] ?? '');
    $examDescription = trim($_POST['examDescription'] ?? '');
    $examDuration = filter_var($_POST['examDuration'] ?? 0, FILTER_VALIDATE_INT);
    // You'll need a way to select the course for the exam in the form.
    // For now, let's assume a 'course_id' is submitted via a hidden field or a dropdown.
    // Add a form field for course selection in the HTML below.
    $courseId = filter_var($_POST['course_id'] ?? 0, FILTER_VALIDATE_INT);
    // You might also need total_marks in the form
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
                $questionId = $conn->lastInsertId(); // Get the ID of the newly inserted question

                // Handle choices for multiple choice questions
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

<style>
    /* Basic styling for the form - you should integrate this with your main CSS */
    .create-exam-container {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        margin: 20px auto;
    }

    .create-exam-container h2 {
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

<div class="create-exam-container">
    <h2>Create New Exam</h2>

    <?php
    // Display feedback message if any
    if (!empty($message)) {
        echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
    }
    ?>

    <form id="createExamForm" method="POST" action="handle_action.php?action=instructor_create_exam_submit">

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

            <button type="button" class="add-question-button">Add Question</button> </div>

        <button type="submit">Create Exam</button>
    </form>
</div>

<script>
    let questionCounter = 0;

    // Wait for the DOM to be fully loaded before adding event listeners
    document.addEventListener('DOMContentLoaded', () => {
        console.log('create_exam.php DOMContentLoaded'); // Debugging line

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

        // Add event listener to the "Add Question" button
        addQuestionButton.addEventListener('click', addQuestion);

         console.log('Event listener added to .add-question-button'); // Debugging line

    });


    function addQuestion() {
        console.log('addQuestion called'); // Debugging line

        questionCounter++;

        const questionsContainer = document.getElementById('questionsContainer');
        console.log('questionsContainer:', questionsContainer); // Debugging line


        // Check if the container exists before proceeding
        if (!questionsContainer) {
            console.error('Error: #questionsContainer not found!');
            return; // Stop execution if container is not found
        }


        const questionItem = document.createElement('div');
        questionItem.classList.add('question-item');
        // Use a unique ID for the question item based on the counter
        const questionItemId = `question_item_${questionCounter}`;
        questionItem.setAttribute('id', questionItemId);
        questionItem.setAttribute('data-question-id', questionCounter);

        questionItem.innerHTML = `
            <h4>Question ${questionCounter}</h4>
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

    function generateOptionsHtml(type, questionId) {
        let html = '';
        switch (type) {
            case 'multiple_choice':
                html = `
                    <div id="mc_options_${questionId}">
                         <p>Options:</p>
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
                    </div>
                    <button type="button" class="add-option-button" onclick="addMultipleChoiceOption(${questionId})">Add Option</button>
                `;
                break;
            case 'true_false':
                html = `
                    <p>Correct Answer:</p>
                    <div class="option-group">
                        <input type="radio" name="questions[${questionId}][correct_answer]" value="true" id="tf_${questionId}_true" required>
                        <label for="tf_${questionId}_true">True</label>
                    </div>
                    <div class="option-group">
                        <input type="radio" name="questions[${questionId}][correct_answer]" value="false" id="tf_${questionId}_false">
                        <label for="tf_${questionId}_false">False</label>
                    </div>
                `;
                break;
            case 'blank_space':
                 html = `
                    <p>Blank Answers (Use [BLANK] in the question text for each blank):</p>
                    <div id="blank_answers_${questionId}">
                        <div class="blank-answer-group">
                             <label for="blank_${questionId}_answer_1">Blank 1 Answer:</label>
                             <input type="text" id="blank_${questionId}_answer_1" name="questions[${questionId}][answers][]" placeholder="Correct answer for blank 1" required>
                             <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove Blank</button>
                        </div>
                    </div>
                    <button type="button" class="add-blank-button" onclick="addBlankAnswer(${questionId})">Add Blank Answer</button>
                    <p><small>Use <code>[BLANK]</code> in the question text to indicate where a blank space should appear for the student.</small></p>
                 `;
                 break;
            // Removed math_equation and coding as they are not directly supported by the DB schema
            default:
                html = '<p>Select a question type to add options or answers.</p>';
                break;
        }
        return html;
    }

    function changeQuestionType(questionId, type) {
        const optionsContainer = document.getElementById(`options_container_${questionId}`);
        optionsContainer.innerHTML = generateOptionsHtml(type, questionId);
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
            <input type="radio" name="questions[${questionId}][correct_answer]" value="${optionValue}" id="mc_${questionId}_${optionValue}">
            <label for="mc_${questionId}_${optionValue}">Correct:</label>
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

    function removeQuestion(questionId) {
        const questionItem = document.querySelector(`.question-item[data-question-id="${questionId}"]`);
        if (questionItem) {
            questionItem.remove();
            // Optional: Re-number questions after removal if needed for display
            // This would require iterating through remaining questions and updating their numbers and input names
        }
    }


    // Optional: You might want to handle the form submission with AJAX as well
    // This prevents a full page reload after submitting the form
    // document.getElementById('createExamForm').addEventListener('submit', function(e) {
    //     e.preventDefault(); // Prevent default form submission

    //     const formData = new FormData(this); // Get form data

    //     // Send form data via AJAX
    //     fetch('handle_action.php?action=instructor_create_exam_submit', {
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


