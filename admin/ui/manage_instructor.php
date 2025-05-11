<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/Instructor_function.php";
include_once __DIR__ . "/../../includes/functions/Course_function.php";

$instructors = Instructor_function::fetchInstructors();
$courses = Course_function::fetchCourses();

?>

<div class="manage_instructor">
    <div class="container">
        <div class="card">
            <h2>INSTRUCTOR LIST</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instructors as $instructor): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($instructor['name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($instructor['course_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($instructor['email']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($instructor['status']) ?>
                            </td>
                            <td>
                                <div class="btn-container">
                                    <button id="edit_btn" class="open-update-modal-btn"
                                        data-id="<?= htmlspecialchars($instructor['user_id']) ?>"
                                        data-name="<?= htmlspecialchars($instructor['name']) ?>"
                                        data-course="<?= $instructor['course_id'] ?>"
                                        data-email="<?= htmlspecialchars($instructor['email']) ?>"
                                        data-status="<?= htmlspecialchars($instructor['status']) ?>">

                                        <img src="<?= BASE_URL ?>/assets/images/icon/edit.png" alt="update" width="28" />

                                    </button>
                                    <!-- delete button -->
                                    <button id="delete_btn" class="delete-btn" data-Iid="<?= $instructor['user_id'] ?>"
                                        data-cid="<?= $instructor['course_id'] ?>">
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