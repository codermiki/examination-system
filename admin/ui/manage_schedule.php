<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/Exam_function.php";

$exams = Exam_function::scheduledExams();
?>

<div class="manage_instructor">
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
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam):
                        // "2025-05-16 14:00:00"
                        $date = new DateTime($exam['scheduled_date']);
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
                                <?= htmlspecialchars($exam['status']) ?>
                            </td>
                            <td>
                                <div class="btn-container">
                                    <button id="edit_btn" class="open-update-modal-btn"
                                        data-exam_id=<?= htmlspecialchars($exam['exam_id']) ?>
                                        data-course_name=<?= htmlspecialchars($exam['course_name']) ?>
                                        data-exam_date=<?= htmlspecialchars($date->format('Y-m-d')) ?>
                                        data-exam_time=<?= htmlspecialchars($date->format('H:i:s')) ?>
                                        data-status="<?= htmlspecialchars($exam['status']) ?>">
                                        <img src="<?= BASE_URL ?>/assets/images/icon/edit.png" alt="update" width="28" />
                                    </button>
                                    <!-- delete button -->
                                    <button id="delete_btn" class="delete-btn" data-exam_id=<?= $exam['exam_id'] ?>>
                                        <img src="<?= BASE_URL ?>/assets/images/icon/bin.png" alt="delete" width="30" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<?php include 'schedule_modal.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const deleteButtons = document.querySelectorAll(".delete-btn");

        deleteButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const exam_id = btn.getAttribute("data-exam_id");

                if (confirm("Are you sure you want to delete this Schedule?")) {
                    fetch("/softexam/api/deleteSchedule", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            exam_id,
                        })
                    })
                        .then((res) => res.json())
                        .then(data => {
                            if (data?.message) {
                                window.location.reload();
                            } else {
                                alert("Failed to delete schedule.");
                            }
                        })
                        .catch(err => {
                            alert("An error occurred.");
                        });
                }
            });
        });
    });
</script>