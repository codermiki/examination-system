<?php
include_once __DIR__ . "/../../includes/functions/fetchCourse.php";
?>

<div class="manage_instructor">
    <div class="container">
        <h1>MANAGE Courses</h1>
        <div class="card">
            <h2>ASSIGNED COURSE LIST</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course Name</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $assigned_courses = fetchCourse::fetchCourse();
                    foreach ($assigned_courses as $course): ?>
                        <tr data-id="<?= $course['course_id'] ?>">
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= htmlspecialchars($course['year']) ?></td>
                            <td><?= htmlspecialchars($course['semester']) ?></td>
                            <td><button class="update-btn">Update</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update (<span id="modalCourseId">Course ID</span>)</h3>
        <form id="updateForm">
            <input type="hidden" id="courseRowId" />
            <label>Course Name</label>
            <input type="text" id="course_name" disabled />

            <label>Year Level</label>
            <select id="year">
                <option value="2">2nd year</option>
                <option value="3">3rd year</option>
                <option value="4">4th year</option>
                <option value="5">5th year</option>
            </select>

            <label>Semester</label>
            <select id="semester">
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
            </select>

            <button type="submit" class="update-btn">Update Now</button>
        </form>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("updateModal");
        const closeBtn = document.querySelector(".close");
        const form = document.getElementById("updateForm");
        const updateButtons = document.querySelectorAll(".update-btn");

        updateButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const row = btn.closest("tr").children;
                const rowId = btn.closest("tr").dataset.id;

                document.getElementById("courseRowId").value = rowId;
                document.getElementById("course_name").value = row[0].textContent.trim();
                document.getElementById("year").value = row[1].textContent.trim();
                document.getElementById("semester").value = row[2].textContent.trim();
                document.getElementById("modalCourseId").textContent = row[0].textContent.trim();

                modal.style.display = "block";
            });
        });

        closeBtn.onclick = () => {
            modal.style.display = "none";
        };

        window.onclick = (e) => {
            if (e.target == modal) modal.style.display = "none";
        };

        form.onsubmit = (e) => {
            e.preventDefault();

            const course_id = document.getElementById("courseRowId").value;
            const year = document.getElementById("year").value;
            const semester = document.getElementById("semester").value;

            // make update asynchronously using fetch 
            fetch("/softexam/api/updateCourse", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ course_id, year, semester }),
            })
                .then((res) => {
                    return res.json()
                })
                .then((response) => {
                    window.location.replace("index.php?page=manage_course");
                    modal.style.display = "none";
                });

        };
    });
</script>