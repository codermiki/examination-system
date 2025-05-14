<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/Exam_function.php";
$user_id = $_SESSION['user_id'];
$scheduledExams = Exam_function::scheduledExamsPerStudent($user_id);
$takenExams = Exam_function::takenExamsPerStudent($user_id);

?>

<div class="container">
    <h1>🎓Welcome to Student Dashboard</h1>

    <div class="grid">
        <!-- Available Exams -->
        <div class="card available">
            <h2>📝 Available Exam</h2>
            <ul>
                <?php if (!empty($scheduledExams)) {
                    $availableExam = $scheduledExams[0];
                    $scheduledDate = new DateTime($availableExam['scheduled_date']);
                    $formattedDate = $scheduledDate->format('Y-m-d h:i A'); ?>
                    <li>✔ <?= htmlspecialchars($formattedDate) ?> –
                        <?= htmlspecialchars($availableExam['course_name']) ?>
                    </li>

                    <form action="?page=take_exam" method="POST">
                        <input type="hidden" name="exam_id" value="<?= htmlspecialchars($availableExam['exam_id']) ?>">
                        <button type="submit" class="btn">Start Exam</button>
                    </form>

                <?php } else { ?>
                    <li>No Available Exam</li>
                <?php } ?>

            </ul>
        </div>

        <!-- Exam Schedule -->
        <div class="card schedule">
            <h2>📆 Exam Schedule</h2>
            <ul>
                <?php if (!empty($scheduledExams)) {
                    $scheduledExamCounter = 0;
                    foreach ($scheduledExams as $scheduledExam) {
                        if ($scheduledExamCounter >= 3)
                            break; ?>
                        <li>✔ <?= htmlspecialchars($scheduledExam['scheduled_date']) ?>
                            <?= htmlspecialchars($scheduledExam['course_name']) ?>
                        </li>
                        <?php
                        $scheduledExamCounter++;
                    } ?>
                    <a href="?page=exam_schedule" class="btn">More</a>
                <?php } else { ?>
                    <li>No Exam Scheduled Yet</li>
                <?php } ?>
            </ul>
        </div>

        <!-- Taken Exams -->
        <div class="card taken">
            <h2>✅ Taken Exams</h2>
            <ul>
                <?php if (!empty($takenExams)) {
                    $takenExamCounter = 0;
                    foreach ($takenExams as $takenExam) {
                        if ($takenExamCounter >= 3)
                            break;
                        ?>
                        <li>✔ <?= htmlspecialchars($takenExam['course_name']) ?> -
                            <?= htmlspecialchars($takenExam['score']) ?> /
                            <?= htmlspecialchars($takenExam['total_marks']) ?>
                        </li>
                    <?php } ?>
                    <a href="?page=taken_exams" class="btn">More</a>
                    <a href="?page=add_feedback" class="btn">Add Feedback</a>
                <?php } else { ?>
                    <li>No Exam Taken Yet</li>
                <?php } ?>
            </ul>
        </div>
    </div>
</div>