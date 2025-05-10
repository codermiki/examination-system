<?php
// includes/instructor/edit_exam.php

// This file handles the functionality for instructors to first select an exam
// from a list (displayed in a table) and then edit the details and questions of the selected exam.

// Include necessary configuration or database files
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php'; // Assuming this file sets up the $pdo connection

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and is an instructor
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    echo '<p>Access denied. You must be a logged-in instructor.</p>';
    exit();
}

$message = ''; // Variable to store feedback messages
$exam = null; // Variable to hold the specific exam being edited
$questions = []; // Array to hold questions for the specific exam
$courses = []; // Array to hold courses for the dropdown
$instructorExams = []; // Array to hold the list of exams for selection

$instructorId = $_SESSION['user_id']; // Get the logged-in instructor's user_id
// $instructorId = 2; // Uncomment for testing with a fixed instructor ID if needed

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
                 // FIX: Changed to use only positional parameters
                 $deleteStudentAnswersStmt = $pdo->prepare("DELETE sa FROM student_answers sa JOIN questions q ON sa.question_id = q.question_id WHERE q.exam_id = ? AND sa.question_id IN ($placeholders)");
                 $bindParamsSA = [$examIdToUpdate]; // Start with exam_id
                 $bindParamsSA = array_merge($bindParamsSA, $questionIdsToDelete); // Add question IDs
                 $deleteStudentAnswersStmt->execute($bindParamsSA);


                 // Delete choices related to these questions
                 // FIX: Changed to use only positional parameters
                 $deleteChoicesStmt = $pdo->prepare("DELETE c FROM choices c JOIN questions q ON c.question_id = q.question_id WHERE q.exam_id = ? AND c.question_id IN ($placeholders)");
                 $bindParamsC = [$examIdToUpdate]; // Start with exam_id
                 $bindParamsC = array_merge($bindParamsC, $questionIdsToDelete); // Add question IDs
                 $deleteChoicesStmt->execute($bindParamsC);

                 // Delete the questions themselves
                 // FIX: Changed to use only positional parameters
                 $deleteQuestionsStmt = $pdo->prepare("DELETE FROM questions WHERE exam_id = ? AND question_id IN ($placeholders)");
                 $bindParamsQ = [$examIdToUpdate]; // Start with exam_id
                 $bindParamsQ = array_merge($bindParamsQ, $questionIdsToDelete); // Add question IDs
                 $deleteQuestionsStmt->execute($bindParamsQ);
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
                             // FIX: Changed to use only positional parameters
                             $deleteChoicesStmt = $pdo->prepare("DELETE FROM choices WHERE question_id = ? AND choice_id IN ($placeholders)");
                             $bindParamsC = [$questionId]; // Start with question_id
                             $bindParamsC = array_merge($bindParamsC, $choiceIdsToDelete); // Add choice IDs
                             $deleteChoicesStmt->execute($bindParamsC);
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
                                     if (!$stmt->execute()) { // Corrected variable name here//InsertChoice
                                         throw new Exception("Error inserting new choice for question " . $questionId . ": " . implode(" ", $stmtInsertChoice->errorInfo()));
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
            $message = '<p class="error">Error updating exam. Please check the details and try again. Details: ' . htmlspecialchars($e->getMessage()) . '</p>';
            // To show the form again with errors, we need to re-fetch the exam data
            // This part is added below in the GET logic section
        }
    }
    // If validation failed or DB error occurred, we need to ensure $examId is set
    // so the page tries to reload the form below.
    $examIdToLoad = $examIdToUpdate; // Keep examId for reloading form on error
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
$examIdToLoad = $examIdToLoad ?? $examIdFromGet; // Use the one from POST if set, otherwise from GET

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
                    // Assign a temporary 'value' key to choices for JS to reference
                    foreach($question['choices'] as $choiceIndex => &$choice) {
                         $choice['value'] = 'option_' . ($choiceIndex + 1); // Assign value like 'option_1'
                        if ($choice['is_correct']) {
                            $correctChoiceValue = $choice['value']; // Store the value of the correct one
                        }
                    }
                    unset($choice); // Break reference

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
             // Ensure we don't try to show the form if exam wasn't found
            $showEditForm = false;
        }
    } catch (PDOException $e) {
        error_log("Error fetching exam details for editing: " . $e->getMessage());
        if (empty($message)) {
            $message = '<p class="error">Error loading exam details. Please try again later.</p>';
        }
         // Ensure we don't try to show the form on DB error
        $showEditForm = false;
    }
}

// If no exam_id provided or exam not found, fetch the list of exams for this instructor
if (!$showEditForm) {
     try {
        $sql = "SELECT exam_id, title, description, created_at
                FROM exams
                WHERE instructor_id = :instructor_id
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':instructor_id', $instructorId, PDO::PARAM_INT);
        $stmt->execute();
        $instructorExams = $stmt->fetchAll(PDO::FETCH_ASSOC); // $instructorExams will be set if exams are found

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
    <style>
        /* General Container Styling */
        .page-container {
            background-color: #f9f9f9;
            padding: 30px; /* Increased padding */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Softer, larger shadow */
            max-width: 900px;
            margin: 30px auto; /* Increased margin */
            font-family: sans-serif; /* Use a common sans-serif font */
            color: #333;
        }

        .page-container h1, .page-container h2, .page-container h3 {
             color: #0056b3; /* A consistent blue for headings */
             text-align: center;
             margin-bottom: 25px; /* More space below headings */
        }

        .page-container h2 {
            border-bottom: 2px solid #eee; /* Subtle separator */
            padding-bottom: 10px;
        }


        /* Form Group Styling */
        .form-group {
            margin-bottom: 20px; /* Increased space between form groups */
        }

        .form-group label {
            display: block;
            margin-bottom: 8px; /* More space below label */
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px; /* Increased padding */
            border: 1px solid #ccc;
            border-radius: 5px; /* Slightly more rounded corners */
            box-sizing: border-box;
            font-size: 1em; /* Standard font size */
        }

         textarea {
             resize: vertical; /* Allow vertical resizing */
         }


        /* Question Section Styling */
        .question-section {
            margin-top: 30px; /* Space above the question section */
            border-top: 1px solid #eee;
            padding-top: 20px;
        }

        .question-item {
            background-color: #fff; /* White background for question items */
            border: 1px solid #ddd; /* Lighter border */
            padding: 20px; /* Increased padding */
            margin-bottom: 20px; /* Space between questions */
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Subtle shadow for items */
        }

        .question-item h4 {
            margin-top: 0;
            margin-bottom: 15px; /* Space below question title */
            color: #007bff; /* Blue for question title */
            border-bottom: none; /* Remove border from h4 */
            padding-bottom: 0;
            display: flex; /* Use flexbox for title and remove button */
            justify-content: space-between;
            align-items: center;
        }

         .question-item h4 .remove-item-button {
             margin-left: 15px; /* Space between title and remove button */
         }


        .question-options {
            margin-top: 15px; /* Space above options */
            padding-left: 25px; /* Increased padding */
            border-left: 3px solid #007bff; /* Blue left border */
        }

        .option-group, .blank-answer-group {
            margin-bottom: 12px; /* Space between options/blanks */
            display: flex;
            align-items: center;
            gap: 10px; /* Space between elements in the group */
        }

        .option-group input[type="radio"] {
            margin-right: 5px;
        }

        .option-group label {
             margin-bottom: 0; /* Remove bottom margin for inline labels */
             font-weight: normal; /* Normal weight for option labels */
             color: #333;
             min-width: 60px; /* Give "Correct:" label a minimum width */
        }

        .option-group input[type="text"],
        .blank-answer-group input[type="text"] {
            flex-grow: 1;
            padding: 8px; /* Slightly less padding than main inputs */
            border-radius: 4px;
        }

         .blank-answer-group label {
             min-width: 120px; /* Give blank answer label a minimum width */
              margin-bottom: 0;
              font-weight: normal;
              color: #333;
         }


        /* Buttons Styling */
        .add-question-button, .add-option-button, .add-blank-button, button[type="submit"] {
            display: inline-block; /* For add buttons */
            background-color: #28a745; /* Green for add buttons */
            color: white;
            padding: 10px 20px; /* Increased padding */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 15px; /* Space above buttons */
            margin-right: 10px; /* Space between add buttons */
            transition: background-color 0.3s ease; /* Smooth hover effect */
        }

         .add-option-button {
             background-color: #007bff; /* Blue for add option */
         }

         .add-blank-button {
             background-color: #ffc107; /* Yellow for add blank */
             color: #333;
         }


        .add-question-button:hover, .add-option-button:hover, .add-blank-button:hover {
            opacity: 0.9;
        }

        button[type="submit"] {
            display: block; /* Make submit button full width */
            width: 100%;
            background-color: #007bff; /* Blue for submit button */
             margin-top: 30px; /* More space above submit button */
             font-size: 1.1em;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .remove-item-button {
            background-color: #dc3545; /* Red for remove buttons */
            color: white;
            padding: 6px 12px; /* Adjusted padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
            flex-shrink: 0; /* Prevent button from shrinking in flex container */
        }

        .remove-item-button:hover {
            background-color: #c82333;
        }

        /* Message Styling */
        .message {
            padding: 12px; /* Increased padding */
            margin-bottom: 20px; /* More space below messages */
            border-radius: 5px;
            font-size: 1em;
            line-height: 1.4;
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

        /* Exam List Table Styling */
        .exam-table {
            width: 100%;
            border-collapse: collapse; /* Collapse borders */
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); /* Subtle shadow for the table */
            background-color: #fff; /* White background */
            border-radius: 5px; /* Rounded corners for the table */
            overflow: hidden; /* Hide overflowing content for rounded corners */
        }

        .exam-table th, .exam-table td {
            padding: 12px; /* Increased padding */
            text-align: left;
            border-bottom: 1px solid #ddd; /* Lighter border */
        }

        .exam-table th {
            background-color: #f2f2f2; /* Light grey background for headers */
            font-weight: bold;
            color: #555;
        }

        .exam-table tbody tr:hover {
            background-color: #f9f9f9; /* Subtle hover effect */
        }

        .exam-table td a {
            font-weight: bold;
            text-decoration: none;
            color: #007bff; /* Blue link color */
        }

        .exam-table td a:hover {
            text-decoration: underline;
        }

        .exam-table td small {
            color: #666; /* Slightly darker grey for description */
        }


         /* Back link styling */
         .back-link {
             display: inline-block;
             margin-bottom: 20px;
             color: #007bff;
             text-decoration: none;
             font-size: 1em;
         }
         .back-link:hover {
             text-decoration: underline;
         }


    </style>
</head>
<body>

    <?php // include_once '../includes/layout/InstructorSidebar.php'; // Example ?>

    <main>
        <div class="page-container">

            <h1><?php echo $showEditForm ? 'Edit Exam' : 'Select Exam to Edit'; ?></h1>

            <?php
            // Display feedback message if any
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>

            <?php if ($showEditForm && $exam): // If a specific exam was successfully loaded for editing ?>

                <a href="edit_exam.php" class="back-link">&larr; Back to Exam List</a>

                <form id="editExamForm" method="POST" action="edit_exam.php">
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
                            <h4>
                                New Question ${questionCounter}
                                <button type="button" class="remove-item-button" onclick="removeQuestion('${questionItemId}')">Remove</button>
                            </h4>
                            <input type="hidden" name="questions[${questionCounter}][question_id]" value="">
                            <div class="form-group">
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
                                ${generateOptionsHtml('multiple_choice', questionCounter, null)}
                            </div>
                        `; // Removed the remove button from here to place it in the h4

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
                            <h4>
                                Question ${questionCounter}
                                <button type="button" class="remove-item-button" onclick="removeQuestion('${questionItemId}')">Remove</button>
                            </h4>
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
                        `; // Removed the remove button from here to place it in the h4
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
                                // Use the 'value' key assigned in PHP for existing choices
                                const optionValue = choice.value; // e.g., 'option_1'
                                const isChecked = (optionValue === correctChoiceFormValue);
                                html += generateSingleMCOptionHtml(questionIndex, optionCounter, choice.choice_text, isChecked);
                            });
                        }
                        return html;
                    }

                    // Helper for a single MC option row
                    function generateSingleMCOptionHtml(questionIndex, optionIndex, text, isChecked) {
                         const optionValue = `option_${optionIndex}`; // Generate value based on position for new options
                         return `
                            <div class="option-group">
                                <input type="radio" name="questions[${questionIndex}][correct_answer]" value="${optionValue}" id="mc_${questionIndex}_${optionValue}" ${isChecked ? 'checked' : ''} required>
                                <label for="mc_${questionIndex}_${optionValue}">Correct:</label>
                                <input type="text" name="questions[${questionIndex}][options][${optionValue}][text]" value="${htmlspecialchars(text)}" placeholder="Option ${optionIndex} Text" required>
                                <input type="hidden" name="questions[${questionIndex}][options][${optionValue}][value]" value="${optionValue}"> <button type="button" class="remove-item-button" onclick="removeOption(this)">Remove</button>
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

                <!-- <h2>Select an Exam to Edit</h2>  -->

                <?php if (!empty($instructorExams)): ?>
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($instructorExams as $instExam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($instExam['title']); ?></td>
                                    <td>
                                        <?php if (!empty($instExam['description'])): ?>
                                            <small><?php echo htmlspecialchars(substr($instExam['description'], 0, 100)) . (strlen($instExam['description']) > 100 ? '...' : ''); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('M d, Y', strtotime($instExam['created_at']))); ?></td>
                                    <td>
                                        <a href="ui/edit_exam.php?exam_id=<?php echo htmlspecialchars($instExam['exam_id']); ?>">Edit Exam</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif (empty($message)): // Avoid showing this if there was an error loading the list ?>
                    <p>You have not created any exams yet.</p>
                    <a href="ui/create_exam.php">Create Exam</a>
                    <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

    <?php // include_once '../includes/layout/footer.php'; // Example ?>

</body>
</html>
