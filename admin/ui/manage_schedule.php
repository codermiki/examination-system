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
                    <?php foreach ($exams as $exam): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($exam['course_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($exam['exam_title']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($exam['scheduled_date']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($exam['scheduled_date']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($exam['status']) ?>
                            </td>
                            <td>
                                <div class="btn-container">
                                    <button id="edit_btn" class="open-update-modal-btn"
                                        data-exam_id=<?= htmlspecialchars($exam['exam_id']) ?>
                                        data-course_name=<?= htmlspecialchars($exam['course_name']) ?>
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


<?php include 'instructor_modal.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const deleteButtons = document.querySelectorAll(".delete-btn");

        deleteButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const instructor_id = btn.getAttribute("data-Iid");
                const course_id = btn.getAttribute("data-cid");

                if (confirm("Are you sure you want to delete this Instructor?")) {
                    fetch("/softexam/api/unassignInstructor", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            instructor_id,
                            course_id
                        })
                    })
                        .then((res) => res.json())
                        .then(data => {
                            if (data?.message) {
                                window.location.reload();
                            } else {
                                alert("Failed to delete student.");
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            alert("An error occurred.");
                        });
                }
            });
        });
    });
</script>