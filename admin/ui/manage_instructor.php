<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/fetchInstructor.php";

$instructors = fetchInstructor::fetchInstructor();

?>

<div class="manage_instructor">
    <div class="container">
        <h1>MANAGE Instrutor</h1>
        <div class="card">
            <h2>INSTRUCTOR LIST</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fullname</th>
                        <th>Gender</th>
                        <th>Course</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instructors as $instructor): ?>
                        <tr>
                            <td><?= htmlspecialchars($instructor['instructor_id']) ?></td>
                            <td><?= htmlspecialchars($instructor['instructor_id']) ?></td>
                            <td><?= htmlspecialchars($instructor['course_id']) ?></td>
                            <td><?= htmlspecialchars($instructor['course_id']) ?></td>
                            <td><?= htmlspecialchars($instructor['status']) ?></td>
                            <td>
                                <!-- <button class="update-btn">Update</button> -->
                                <div class="button_container">
                                    <button id="edit_btn" class="open-update-modal-btn"
                                        data-id="<?= $instructor['instructor_id'] ?>"
                                        data-name="<?= htmlspecialchars($instructor['name']) ?>"
                                        data-course="<?= $instructor['course_id'] ?>"
                                        data-status="<?= htmlspecialchars($instructor['status']) ?>">
                                        <img src="<?= BASE_URL ?>/assets/images/icon/edit.png" alt="update" width="28" />

                                    </button>
                                    <!-- delete button -->
                                    <button id="delete_btn" class="delete-btn"
                                        data-iid="<?= $instructor['instructor_id'] ?>"
                                        data-cid="<?= $instructor['course_id'] ?>">
                                        <img src="<?= BASE_URL ?>/assets/images/icon/bin.png" alt="delete" width="30" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
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