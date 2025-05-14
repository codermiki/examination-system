<?php
include_once __DIR__ . "/../../includes/functions/Feedback_function.php";

$feedbacks = Feedback_function::fetchFeedbacks();

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
                <?php if (!empty($feedbacks)) { ?>
                    <?php foreach ($feedbacks as $feedback): ?>
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
                <?php } else { ?>
                    <tr>
                        <td style="text-align: center" colspan="6">
                            <h4>No Feedbacks Yet</h4>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>