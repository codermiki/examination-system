<?php
include_once __DIR__ . "/../../includes/functions/Feedback_function.php";
?>

<div class="feedback">
    <div class="feedback-container">
        <h2 class="list-title">FEEDBACK'S LIST</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>course</th>
                    <th>exam</th>
                    <th>message</th>
                    <th>Rate</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $feedbacks = Feedback_function::fetchFeedbacks();
                foreach ($feedbacks as $feedback):
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($feedback['name']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($feedback['course_name']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($feedback['exam_title']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($feedback['feedback_text']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($feedback['rate']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($feedback['created_at']) ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>