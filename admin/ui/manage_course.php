<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/Course_function.php";

$courses = Course_function::fetchCourses();

?>

<div class="manage_instructor">
    <div class="container">
        <div class="card">
            <h2>COURSE LIST</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($courses)) { ?>
                        <?php foreach ($courses as $course): ?>
                            <tr data-id="<?= $course['course_id'] ?>">
                                <td><?= htmlspecialchars($course['course_id']) ?></td>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td>
                                    <!-- delete button -->
                                    <button id="delete_btn" class="delete-btn" data-Cid="<?= $course['course_id'] ?>">
                                        <img src="<?= BASE_URL ?>/assets/images/icon/bin.png" alt="delete" width="30" />
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php } else { ?>
                        <tr>
                            <td style="text-align: center" colspan="3">
                                <h4>No Courses Added Yet</h4>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const deleteButtons = document.querySelectorAll(".delete-btn");

        deleteButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const course_id = btn.getAttribute("data-Cid");

                if (confirm("Are you sure you want to delete this Course?")) {
                    fetch("/softexam/api/deleteCourse", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
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