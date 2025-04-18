<?php
// includes/instructor/create_exam.php

// This file contains the HTML, CSS, and JavaScript for the "Create Exam" form
// with advanced UI elements and dynamic question adding, including new question types.

// You might need to include database connection or other necessary files here
// include_once '../../config/database.php';

// Check if the user is an instructor (additional security check)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    echo '<p>Access denied. You must be an instructor to create exams.</p>';
    exit();
}

// PHP logic for handling form submission would go here
// This is a basic example; you would likely use AJAX for form submission as well
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the form data (e.g., save to database)
    // You would access form data using $_POST['fieldName']
    // Example: $examTitle = $_POST['examTitle'];

    // You will need to process the dynamically added question data as well.
    // The structure of $_POST will depend on how you name your dynamic fields.
    // For example, question text might be in $_POST['questions'][0]['text'], options in $_POST['questions'][0]['options'], etc.
    // For new types like 'blank_space', 'math_equation', 'coding', the structure will be different.

    // Perform validation and database insertion here

    // Send a response back (you could send JSON for AJAX form submission)
    echo '<p>Exam creation form submitted (backend processing needed).</p>';

} else {
    // Display the Create Exam form HTML
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
</style>

<div class="create-exam-container">
    <h2>Create New Exam</h2>

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
            <label for="examDuration">Duration (minutes):</label>
            <input type="number" id="examDuration" name="examDuration" required min="1">
        </div>

        <div class="question-section">
            <h3>Questions</h3>
            <div id="questionsContainer">
                </div>

            <button type="button" class="add-question-button" onclick="addQuestion()">Add Question</button>
        </div>

        <button type="submit">Create Exam</button>
    </form>
</div>

<script>
    let questionCounter = 0;

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
                    <option value="math_equation">Math Equation</option>
                    <option value="coding">Coding</option>
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
            case 'math_equation':
                 html = `
                    <div class="form-group">
                        <label for="math_equation_answer_${questionId}">Correct Answer:</label>
                        <input type="text" id="math_equation_answer_${questionId}" name="questions[${questionId}][correct_answer]" placeholder="Enter the correct numerical or symbolic answer" required>
                    </div>
                    <p><small>Enter the mathematical equation in the question text. Evaluating complex equations requires additional backend logic.</small></p>
                 `;
                 break;
            case 'coding':
                 html = `
                    <div class="form-group">
                        <label for="coding_expected_output_${questionId}">Expected Output (Optional):</label>
                        <textarea id="coding_expected_output_${questionId}" name="questions[${questionId}][expected_output]" rows="4" placeholder="Enter the expected output for the code"></textarea>
                    </div>
                    <p><small>Provide the coding problem description in the question text. Evaluating student code requires a secure execution environment on the backend.</small></p>
                 `;
                 break;
            // Add cases for other question types
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

<?php
}
?>
