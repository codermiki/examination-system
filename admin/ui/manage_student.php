<?php
include_once __DIR__ . "/../../constants.php";
include_once __DIR__ . "/../../includes/functions/fetchStudent.php";
include_once __DIR__ . "/../../includes/functions/fetchCourse.php";
$course = $_GET['course'] ?? null;
$year = $_GET['year'] ?? null;
$semester = $_GET['semester'] ?? null;

$students = fetchStudent::fetchAssignedStudent();
$courses = fetchCourse::fetchCourse();

?>

<!-- student list -->
<div class="manage_instructor">
    <div class="container">
        <h1>MANAGE Student</h1>
        <div class="card">
            <h2>STUDENT LIST</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <?php
                            $matched = array_filter($courses, function ($course) use ($stu) {
                                return $course["course_id"] === $stu['course_id'];
                            });
                            $course = reset($matched);
                            ?>

                            <td><?= htmlspecialchars($stu['name']) ?></td>
                            <td><?= htmlspecialchars($stu['gender']) ?></td>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($stu['year']) ?></td>
                            <td><?= htmlspecialchars($stu['semester']) ?></td>
                            <td><?= htmlspecialchars($stu['email']) ?></td>
                            <td><?= htmlspecialchars($stu['status']) ?></td>
                            <td>
                                <div class="button_container">

                                    <button id="edit_btn" class="open-update-modal-btn" data-id="<?= $stu['student_id'] ?>"
                                        data-name="<?= htmlspecialchars($stu['name']) ?>"
                                        data-course="<?= $stu['course_id'] ?>"
                                        data-email="<?= htmlspecialchars($stu['email']) ?>"
                                        data-status="<?= htmlspecialchars($stu['status']) ?>">
                                        <img src="<?= BASE_URL ?>/assets/images/icon/edit.png" alt="update" width="28" />

                                    </button>
                                    <!-- delete button -->
                                    <button id="delete_btn" class="delete-btn" data-sid="<?= $stu['student_id'] ?>"
                                        data-cid="<?= $stu['course_id'] ?>">
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