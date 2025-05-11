<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/Student_function.php";
include_once __DIR__ . "/../../includes/functions/Course_function.php";

$students = Student_function::fetchAssignedStudents();
$courses = Course_function::fetchCourses();

?>

<!-- student list -->
<div class="manage_instructor">
    <div class="container">
        <div class="card">
            <h2>STUDENT LIST</h2>
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
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($student['name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['course_name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['email']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($student['status']) ?>
                            </td>
                            <td>
                                <div class="btn-container">
                                    <button id="edit_btn" class="open-update-modal-btn"
                                        data-id="<?= htmlspecialchars($student['user_id']) ?>"
                                        data-name="<?= htmlspecialchars($student['name']) ?>"
                                        data-course="<?= $student['course_id'] ?>"
                                        data-email="<?= htmlspecialchars($student['email']) ?>"
                                        data-status="<?= htmlspecialchars($student['status']) ?>">

                                        <img src="<?= BASE_URL ?>/assets/images/icon/edit.png" alt="update" width="28" />

                                    </button>
                                    <!-- delete button -->
                                    <button id="delete_btn" class="delete-btn" data-sid="<?= $student['user_id'] ?>"
                                        data-cid="<?= $student['course_id'] ?>">
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

<!-- Modal and JS stay the same -->
<?php include 'student_modal.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const deleteButtons = document.querySelectorAll(".delete-btn");

        deleteButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const student_id = btn.getAttribute("data-sid");
                const course_id = btn.getAttribute("data-cid");

                if (confirm("Are you sure you want to delete this student?")) {
                    fetch("/softexam/api/unassignStudent", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            student_id,
                            course_id
                        })
                    })
                        .then(res => {
                            console.log(res);
                            return res.json();
                        })
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