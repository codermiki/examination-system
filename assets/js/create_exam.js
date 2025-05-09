let questionCounter = 0;

    // const logfun = () => {
    //     alert("button");
    // }

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