<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/Exam_function.php";
$user_id = $_SESSION['user_id'];
$exams = Exam_function::takenExamsPerStudent($user_id);

?>

<div class="manage_instructor">
    <button onclick="window.history.back()">← Back</button>

    <div class="container">
        <div class="card">
            <h2>Exam Schedule</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($exams)) { ?>
                        <?php foreach ($exams as $exam):
                            // "2025-05-16 14:00:00"
                            $date = new DateTime($exam['taken_on']);
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($exam['course_name']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($exam['exam_title']) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($date->format('Y-m-d')) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($date->format('g:i A')) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($exam['score']) ?> /
                                    <?= htmlspecialchars($exam['total_marks']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php } else { ?>
                        <tr>
                            <td style="text-align: center" colspan="5">
                                <h4>No Exams Taken Yet</h4>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>