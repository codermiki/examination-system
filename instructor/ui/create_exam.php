<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Instructor' || !isset($_SESSION['user_id'])) {
    // Redirect to login page or show an error message
    // For now, just outputting an error and exiting.
    echo '<p class="text-danger text-center" style="margin-top: 5rem;">Access denied. You must be a logged-in instructor to create exams. Please <a href="login.php">login</a>.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$message_type = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instructorId = $_SESSION['user_id'];

    $examTitle = trim($_POST['examTitle'] ?? '');
    $examDescription = trim($_POST['examDescription'] ?? '');
    $examDuration = filter_var($_POST['examDuration'] ?? 0, FILTER_VALIDATE_INT);
    $courseId = $_POST['course_id'] ?? '';
    // total_marks will be calculated based on sum of question marks if dynamic calculation is implemented
    // For now, keeping it as a manual input or could be dynamically calculated before submission via JS
    $totalMarks = filter_var($_POST['total_marks'] ?? 0, FILTER_VALIDATE_INT);


    // Basic validation
    if (empty($examTitle)) {
        $message = 'Error: Exam title is required.';
        $message_type = 'error';
    } elseif ($examDuration === false || $examDuration <= 0) {
        $message = 'Error: Exam duration must be a positive number.';
        $message_type = 'error';
    } elseif (empty($courseId)) {
        $message = 'Error: Please select a course.';
        $message_type = 'error';
    } elseif ($totalMarks === false || $totalMarks <= 0) { // This validation might change if marks are auto-calculated
        $message = 'Error: Total marks must be a positive number.';
        $message_type = 'error';
    } elseif (!isset($_POST['questions']) || !is_array($_POST['questions']) || count($_POST['questions']) === 0) {
        $message = 'Error: Please add at least one question.';
        $message_type = 'error';
    } else {
        // Further validation for each question
        $current_total_marks = 0;
        foreach ($_POST['questions'] as $index => $question) {
            if (empty(trim($question['text'] ?? ''))) {
                $message = "Error: Question text for Question " . ($index + 1) . " cannot be empty.";
                $message_type = 'error';
                break;
            }
            $q_marks = filter_var($question['marks'] ?? 0, FILTER_VALIDATE_INT);
            if ($q_marks === false || $q_marks <= 0) {
                $message = "Error: Marks for Question " . ($index + 1) . " must be a positive number.";
                $message_type = 'error';
                break;
            }
            $current_total_marks += $q_marks;

            $questionType = $question['type'] ?? '';
            if ($questionType === 'multiple_choice') {
                if (!isset($question['options']) || count($question['options']) < 2) {
                    $message = "Error: Multiple choice Question " . ($index + 1) . " must have at least two options.";
                    $message_type = 'error';
                    break;
                }
                if (!isset($question['correct_answer']) || $question['correct_answer'] === '') {
                    $message = "Error: Please select a correct answer for multiple choice Question " . ($index + 1) . ".";
                    $message_type = 'error';
                    break;
                }
                foreach ($question['options'] as $opt_idx => $optionText) {
                    if (empty(trim($optionText))) {
                        $message = "Error: Option " . ($opt_idx + 1) . " for Question " . ($index + 1) . " cannot be empty.";
                        $message_type = 'error';
                        break 2; // Break out of both loops
                    }
                }
            } elseif ($questionType === 'true_false') {
                if (!isset($question['correct_answer']) || !in_array($question['correct_answer'], ['True', 'False'])) {
                    $message = "Error: Invalid correct answer for True/False Question " . ($index + 1) . ".";
                    $message_type = 'error';
                    break;
                }
            } elseif ($questionType === 'fill_blank') {
                if (!isset($question['answers']) || count($question['answers']) === 0) {
                    $message = "Error: Fill in the Blank Question " . ($index + 1) . " must have at least one answer.";
                    $message_type = 'error';
                    break;
                }
                foreach ($question['answers'] as $ans_idx => $ansText) {
                    if (empty(trim($ansText))) {
                        $message = "Error: Blank answer " . ($ans_idx + 1) . " for Question " . ($index + 1) . " cannot be empty.";
                        $message_type = 'error';
                        break 2; // Break out of both loops
                    }
                }
            }
        }

        if ($message_type !== 'error') {
            try {
                $conn->beginTransaction();

                $stmt = $conn->prepare("INSERT INTO exams (course_id, instructor_id, exam_title, exam_description, duration_minutes, total_marks, status)
                                     VALUES (:course_id, :instructor_id, :title, :description, :duration, :total_marks, 'Inactive')");
                $stmt->bindParam(':course_id', $courseId, PDO::PARAM_STR);
                $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
                $stmt->bindParam(':title', $examTitle, PDO::PARAM_STR);
                $stmt->bindParam(':description', $examDescription, PDO::PARAM_STR);
                $stmt->bindParam(':duration', $examDuration, PDO::PARAM_INT);
                $stmt->bindParam(':total_marks', $totalMarks, PDO::PARAM_INT); // Or $current_total_marks if dynamically calculated

                if (!$stmt->execute()) {
                    throw new Exception("Error inserting exam data: " . implode(" ", $stmt->errorInfo()));
                }
                $examId = $conn->lastInsertId();

                foreach ($_POST['questions'] as $question_data) {
                    $questionText = trim($question_data['text'] ?? '');
                    $questionType = $question_data['type'] ?? '';
                    $marks = filter_var($question_data['marks'] ?? 0, FILTER_VALIDATE_INT);
                    $correctAnswer = '';

                    if ($questionType === 'true_false') {
                        $correctAnswer = $question_data['correct_answer'] ?? '';
                    } elseif ($questionType === 'fill_blank') {
                        if (isset($question_data['answers']) && is_array($question_data['answers'])) {
                            $correctAnswer = implode('|', array_map('trim', array_filter($question_data['answers'], 'trim')));
                        }
                    } elseif ($questionType === 'multiple_choice') {
                        // Correct answer for MC is the index of the correct option
                        $correctOptionIndex = $question_data['correct_answer'] ?? null;
                        if ($correctOptionIndex !== null && isset($question_data['options'][$correctOptionIndex])) {
                            // Storing the text of the correct option.
                            // Alternatively, store the index or a reference. DB stores text.
                            $correctAnswer = trim($question_data['options'][$correctOptionIndex]);
                        } else {
                            throw new Exception("Correct answer not properly set for a multiple-choice question.");
                        }
                    }

                    $stmtQ = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, correct_answer, marks)
                                          VALUES (:exam_id, :question_text, :question_type, :correct_answer, :marks)");
                    $stmtQ->bindParam(':exam_id', $examId, PDO::PARAM_INT);
                    $stmtQ->bindParam(':question_text', $questionText, PDO::PARAM_STR);
                    $stmtQ->bindParam(':question_type', $questionType, PDO::PARAM_STR);
                    $stmtQ->bindParam(':correct_answer', $correctAnswer, PDO::PARAM_STR);
                    $stmtQ->bindParam(':marks', $marks, PDO::PARAM_INT);

                    if (!$stmtQ->execute()) {
                        throw new Exception("Error inserting question: " . implode(" ", $stmtQ->errorInfo()));
                    }
                    $questionId = $conn->lastInsertId();

                    if ($questionType === 'multiple_choice' && isset($question_data['options']) && is_array($question_data['options'])) {
                        foreach ($question_data['options'] as $optionText) {
                            $trimmedOptionText = trim($optionText);
                            if (!empty($trimmedOptionText)) {
                                $stmtOpt = $conn->prepare("INSERT INTO question_options (question_id, option_text)
                                                      VALUES (:question_id, :option_text)");
                                $stmtOpt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
                                $stmtOpt->bindParam(':option_text', $trimmedOptionText, PDO::PARAM_STR);
                                if (!$stmtOpt->execute()) {
                                    throw new Exception("Error inserting option: " . implode(" ", $stmtOpt->errorInfo()));
                                }
                            }
                        }
                    }
                }

                $conn->commit();
                $message = 'Exam "' . htmlspecialchars($examTitle) . '" created successfully!';
                $message_type = 'success';
                // Clear form data after successful submission by redirecting or clearing POST
                $_POST = array(); // Simple way to clear POST data to prevent re-submission issues
                // A redirect is often better: header('Location: create_exam.php?status=success'); exit();


            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log("Exam creation error: " . $e->getMessage());
                $message = 'Error creating exam: ' . htmlspecialchars($e->getMessage());
                $message_type = 'error';
            }
        }
    }
}

// Fetch courses assigned to this instructor
$courses = [];
try {
    $instructorId = $_SESSION['user_id']; // Ensure user_id is set in session
    $sql = "SELECT c.course_id, c.course_name
            FROM courses c
            JOIN assigned_instructors ai ON c.course_id = ai.course_id
            WHERE ai.instructor_id = :instructor_id AND ai.status = 'Active'
            ORDER BY c.course_name";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_STR);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instructor courses: " . $e->getMessage());
    $message = ($message ? $message . "<br>" : "") . 'Could not load courses. Please try again.';
    $message_type = 'error';
}
?>
<div class="container">
    <div class="create-exam-container">
        <div class="form-header">
            <h2><i class="fas fa-file-alt"></i> Create New Exam</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message-area <?= $message_type === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form id="createExamForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?page=create_exam">
            <div class="form-row">
                <div class="form-col form-col-md-6">
                    <label for="examTitle" class="form-label">Exam Title <span class="text-danger">*</span></label>
                    <input type="text" id="examTitle" name="examTitle" required value="<?= htmlspecialchars($_POST['examTitle'] ?? '') ?>">
                </div>
                <div class="form-col form-col-md-6">
                    <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                    <select id="course_id" name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= htmlspecialchars($course['course_id']) ?>" <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="examDescription" class="form-label">Description (Optional)</label>
                <textarea id="examDescription" name="examDescription" rows="3"><?= htmlspecialchars($_POST['examDescription'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-col form-col-md-6">
                    <label for="examDuration" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                    <input type="number" id="examDuration" name="examDuration" required min="1" value="<?= htmlspecialchars($_POST['examDuration'] ?? '') ?>">
                </div>
                <div class="form-col form-col-md-6">
                    <label for="total_marks" class="form-label">Total Marks <span class="text-danger">*</span></label>
                    <input type="number" id="total_marks" name="total_marks" required min="1" value="<?= htmlspecialchars($_POST['total_marks'] ?? '') ?>" readonly>
                    <small class="form-text-muted">Total marks are calculated automatically from questions.</small>
                </div>
            </div>

            <div class="question-section">
                <div class="question-section-header">
                    <h4><i class="fas fa-question-circle"></i>Questions</h4>
                    <button type="button" class="btn btn-add-question" onclick="addQuestion()">
                        <i class="fas fa-plus"></i>Add Question
                    </button>
                </div>
                <div id="questionsContainer">
                    <?php if (isset($_POST['questions']) && is_array($_POST['questions'])): ?>
                        <?php foreach ($_POST['questions'] as $idx => $qData): ?>
                            <?php /* PHP loop for pre-filling is empty, JS handles it */ ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn btn-submit-exam">
                    <i class="fas fa-save"></i>Create Exam
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let questionCounter = 0;
    // let existingQuestions = 0; // This variable wasn't used in the original JS

    document.addEventListener('DOMContentLoaded', () => {
        const postQuestions = <?php echo json_encode($_POST['questions'] ?? []); ?>;
        if (postQuestions.length > 0) {
            postQuestions.forEach(qData => {
                addQuestion(qData);
            });
        } else {
            // addQuestion(); // Optionally add one empty question by default
        }
        updateTotalMarks(); // Initial calculation
    });

    function updateTotalMarks() {
        let total = 0;
        document.querySelectorAll('.question-marks-input').forEach(input => {
            const marks = parseInt(input.value, 10);
            if (!isNaN(marks) && marks > 0) {
                total += marks;
            }
        });
        const totalMarksInput = document.getElementById('total_marks');
        if (totalMarksInput) {
            totalMarksInput.value = total;
        }
    }


    function addQuestion(data = null) {
        questionCounter++;
        const questionsContainer = document.getElementById('questionsContainer');
        const questionIdSuffix = `new_${questionCounter}`;

        const questionItem = document.createElement('div');
        questionItem.className = 'question-item'; // mb-4 removed, handled by question-item margin-bottom
        questionItem.id = `question_item_${questionIdSuffix}`;
        questionItem.setAttribute('data-question-id', questionIdSuffix);

        const questionText = data && data.text ? data.text : '';
        const questionType = data && data.type ? data.type : 'multiple_choice';
        const questionMarks = data && data.marks ? data.marks : '1';

        let questionTypeDisplay = 'Multiple Choice';
        if (questionType === 'true_false') questionTypeDisplay = 'True/False';
        else if (questionType === 'fill_blank') questionTypeDisplay = 'Fill in the Blank';

        questionItem.innerHTML = `
                <div class="question-item-header">
                    <h5>Question <span class="question-number">${questionsContainer.children.length + 1}</span></h5>
                    <span class="badge-question-type">${questionTypeDisplay}</span>
                </div>

                <div class="mb-3">
                    <label for="question_text_${questionIdSuffix}" class="form-label">Question Text <span class="text-danger">*</span></label>
                    <textarea id="question_text_${questionIdSuffix}"
                           name="questions[${questionIdSuffix}][text]" required rows="2">${questionText}</textarea>
                </div>

                <div class="form-row mb-3"> 
                    <div class="form-col" style="flex-basis: 66.66%; max-width: 66.66%;"> 
                        <label for="question_type_${questionIdSuffix}" class="form-label">Question Type</label>
                        <select class="question-type-select" id="question_type_${questionIdSuffix}"
                                name="questions[${questionIdSuffix}][type]"
                                onchange="changeQuestionType('${questionIdSuffix}', this.value, this)">
                            <option value="multiple_choice" ${questionType === 'multiple_choice' ? 'selected' : ''}>Multiple Choice</option>
                            <option value="true_false" ${questionType === 'true_false' ? 'selected' : ''}>True/False</option>
                            <option value="fill_blank" ${questionType === 'fill_blank' ? 'selected' : ''}>Fill in the Blank</option>
                        </select>
                    </div>
                     <div class="form-col" style="flex-basis: 33.33%; max-width: 33.33%;"> 
                        <label for="question_marks_${questionIdSuffix}" class="form-label">Marks <span class="text-danger">*</span></label>
                        <input type="number" class="question-marks-input" id="question_marks_${questionIdSuffix}"
                               name="questions[${questionIdSuffix}][marks]" min="1" value="${questionMarks}" required onchange="updateTotalMarks()">
                    </div>
                </div>

                <div class="question-options" id="options_container_${questionIdSuffix}">
                </div>

                <div class="question-actions"> 
                    <button type="button" class="btn btn-sm btn-remove-question" onclick="removeQuestion('${questionIdSuffix}')">
                        <i class="fas fa-trash"></i>Remove Question
                    </button>
                </div>
            `;

        questionsContainer.appendChild(questionItem);
        changeQuestionType(questionIdSuffix, questionType, questionItem.querySelector('.question-type-select'), data);
        updateQuestionNumbers();
        updateTotalMarks();
    }

    function generateOptionsHtml(type, questionIdSuffix, data = null) {
        let html = '';
        const namePrefix = `questions[${questionIdSuffix}]`;

        switch (type) {
            case 'multiple_choice':
                let optionsHtml = '';
                const options = (data && data.options) ? data.options : ["", ""];
                const correctAnswerIndex = (data && data.correct_answer !== undefined && data.correct_answer !== null && data.correct_answer !== '') ? parseInt(data.correct_answer) : -1;


                options.forEach((optText, index) => {
                    // Ensure optText is a string for htmlspecialchars equivalent
                    const safeOptText = (typeof optText === 'string' || typeof optText === 'number') ? String(optText).replace(/"/g, "&quot;") : '';
                    optionsHtml += `
                            <div class="option-group">
                                <input type="radio" name="${namePrefix}[correct_answer]" value="${index}" ${correctAnswerIndex === index ? 'checked' : ''} required class="form-check-input">
                                <input type="text" name="${namePrefix}[options][]" class="input-sm" 
                                       placeholder="Option ${index + 1} text" value="${safeOptText}" required style="flex-grow:1; padding: 0.25rem 0.5rem; font-size: .875rem;">
                                <button type="button" class="btn btn-sm btn-remove-option" onclick="removeOption(this, '${questionIdSuffix}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>`;
                });

                html = `
                        <label class="form-label">Options & Correct Answer: <span class="text-danger">*</span></label>
                        <div id="mc_options_${questionIdSuffix}">
                            ${optionsHtml}
                        </div>
                        <button type="button" class="btn btn-sm btn-add-option" onclick="addMultipleChoiceOption('${questionIdSuffix}')" style="margin-top: 0.5rem;">
                            <i class="fas fa-plus"></i>Add Option
                        </button>
                    `;
                break;
            case 'true_false':
                const correctAnswerTF = (data && data.correct_answer) ? data.correct_answer : 'True';
                html = `
                        <label class="form-label">Correct Answer: <span class="text-danger">*</span></label>
                        <div class="option-group">
                            <input type="radio" class="form-check-input" name="${namePrefix}[correct_answer]" value="True" id="tf_${questionIdSuffix}_true" ${correctAnswerTF === 'True' ? 'checked' : ''} required>
                            <label class="form-check-label" for="tf_${questionIdSuffix}_true">True</label>
                        </div>
                        <div class="option-group">
                            <input type="radio" class="form-check-input" name="${namePrefix}[correct_answer]" value="False" id="tf_${questionIdSuffix}_false" ${correctAnswerTF === 'False' ? 'checked' : ''}>
                            <label class="form-check-label" for="tf_${questionIdSuffix}_false">False</label>
                        </div>
                    `;
                break;
            case 'fill_blank':
                let answersHtml = '';
                const answers = (data && data.answers && Array.isArray(data.answers)) ? data.answers : (data && data.answers && typeof data.answers === 'string' ? data.answers.split('|') : [""]);


                answers.forEach((ansText, index) => {
                    const safeAnsText = (typeof ansText === 'string' || typeof ansText === 'number') ? String(ansText).replace(/"/g, "&quot;") : '';
                    answersHtml += `
                            <div class="blank-answer-group">
                                <input type="text" name="${namePrefix}[answers][]" class="input-sm"
                                       placeholder="Correct answer for blank ${index + 1}" value="${safeAnsText}" required style="flex-grow:1; padding: 0.25rem 0.5rem; font-size: .875rem;">
                                <button type="button" class="btn btn-sm btn-remove-option" onclick="removeOption(this, '${questionIdSuffix}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>`;
                });
                html = `
                        <label class="form-label">Blank Answers <span class="text-danger">*</span> (Use [BLANK] in question text):</label>
                        <div id="blank_answers_${questionIdSuffix}">
                            ${answersHtml}
                        </div>
                        <button type="button" class="btn btn-sm btn-add-blank" onclick="addBlankAnswer('${questionIdSuffix}')" style="margin-top: 0.5rem;">
                            <i class="fas fa-plus"></i>Add Blank Answer
                        </button>
                        <p style="font-size: 0.875em; color: #6c757d; margin-top: 0.5rem;">
                            In the question text, use <code>[BLANK]</code> for each blank you define below.
                        </p>
                    `;
                break;
            default:
                html = '<p style="color: #6c757d;">Select a question type to see options.</p>';
                break;
        }
        return html;
    }

    function changeQuestionType(questionIdSuffix, type, selectElement, data = null) {
        const optionsContainer = document.getElementById(`options_container_${questionIdSuffix}`);
        const questionItem = document.getElementById(`question_item_${questionIdSuffix}`);
        const badge = questionItem.querySelector('.badge-question-type');

        // When changing type, if data exists, it might be for a different type.
        // We pass data only if it's the initial load (addQuestion call)
        // or if we are sure data structure matches the new type.
        // For simplicity, if `selectElement` is provided (meaning it's a user change),
        // we don't pass `data` to `generateOptionsHtml` to avoid mismatched structures.
        let optionsData = data;
        if (selectElement && selectElement.value !== (data ? data.type : '')) {
            optionsData = null; // User changed type, reset options from data
        }


        optionsContainer.innerHTML = generateOptionsHtml(type, questionIdSuffix, optionsData);

        let typeText = 'Multiple Choice';
        if (type === 'true_false') typeText = 'True/False';
        else if (type === 'fill_blank') typeText = 'Fill in Blank';
        if (badge) badge.textContent = typeText;
    }

    function addMultipleChoiceOption(questionIdSuffix) {
        const mcOptionsContainer = document.getElementById(`mc_options_${questionIdSuffix}`);
        if (!mcOptionsContainer) return;
        const optionCount = mcOptionsContainer.querySelectorAll('.option-group').length;
        const namePrefix = `questions[${questionIdSuffix}]`;

        const optionGroup = document.createElement('div');
        optionGroup.className = 'option-group';
        optionGroup.innerHTML = `
                <input type="radio" name="${namePrefix}[correct_answer]" value="${optionCount}" required class="form-check-input">
                <input type="text" name="${namePrefix}[options][]" class="input-sm"
                       placeholder="Option ${optionCount + 1} text" required style="flex-grow:1; padding: 0.25rem 0.5rem; font-size: .875rem;">
                <button type="button" class="btn btn-sm btn-remove-option" onclick="removeOption(this, '${questionIdSuffix}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
        mcOptionsContainer.appendChild(optionGroup);
    }

    function addBlankAnswer(questionIdSuffix) {
        const blankAnswersContainer = document.getElementById(`blank_answers_${questionIdSuffix}`);
        if (!blankAnswersContainer) return;
        const blankCount = blankAnswersContainer.querySelectorAll('.blank-answer-group').length;
        const namePrefix = `questions[${questionIdSuffix}]`;

        const blankGroup = document.createElement('div');
        blankGroup.className = 'blank-answer-group';
        blankGroup.innerHTML = `
                <input type="text" name="${namePrefix}[answers][]" class="input-sm"
                       placeholder="Correct answer for blank ${blankCount + 1}" required style="flex-grow:1; padding: 0.25rem 0.5rem; font-size: .875rem;">
                <button type="button" class="btn btn-sm btn-remove-option" onclick="removeOption(this, '${questionIdSuffix}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
        blankAnswersContainer.appendChild(blankGroup);
    }

    function removeOption(button, questionIdSuffix) {
        const optionGroup = button.closest('.option-group, .blank-answer-group');
        if (optionGroup) {
            optionGroup.remove();
            const mcOptionsContainer = document.getElementById(`mc_options_${questionIdSuffix}`);
            if (mcOptionsContainer && mcOptionsContainer.contains(optionGroup)) { // Check if it was an MC option
                mcOptionsContainer.querySelectorAll('.option-group').forEach((group, index) => {
                    const radio = group.querySelector('input[type="radio"]');
                    if (radio) radio.value = index;
                    const textInput = group.querySelector('input[type="text"]');
                    if (textInput) textInput.placeholder = `Option ${index + 1} text`;
                });
            }
        }
    }

    function removeQuestion(questionIdSuffix) {
        const questionItem = document.getElementById(`question_item_${questionIdSuffix}`);
        if (questionItem && confirm('Are you sure you want to remove this question?')) {
            questionItem.remove();
            updateQuestionNumbers();
            updateTotalMarks();
        }
    }

    function updateQuestionNumbers() {
        const questions = document.querySelectorAll('#questionsContainer .question-item');
        questions.forEach((q, index) => {
            const numberSpan = q.querySelector('.question-number');
            if (numberSpan) {
                numberSpan.textContent = index + 1;
            }
        });
    }

    document.getElementById('questionsContainer').addEventListener('input', function(event) { // Changed to 'input' for better responsiveness
        if (event.target.classList.contains('question-marks-input')) {
            updateTotalMarks();
        }
    });
</script>