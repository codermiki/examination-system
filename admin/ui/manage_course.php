<div class="manage_instructor">
    <div class="container">
        <h1>MANAGE Courses</h1>
        <div class="card">
            <h2>ASSIGNED COURSE LIST</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Year Level</th>
                        <th>Semester</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Sample data; replace this with DB fetch:
                    $assigned_courses = [
                        ['id' => 1, 'course_id' => 'BSCRIM', 'year' => 3, 'semester' => 1],
                        ['id' => 2, 'course_id' => 'BSIT', 'year' => 2, 'semester' => 2],
                        ['id' => 3, 'course_id' => 'WEB DESIGN', 'year' => 4, 'semester' => 1],
                    ];

                    foreach ($assigned_courses as $course): ?>
                        <tr data-id="<?= $course['id'] ?>">
                            <td><?= htmlspecialchars($course['course_id']) ?></td>
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
            <label>Course ID</label>
            <input type="text" id="course_id" disabled />

            <label>Year Level</label>
            <input type="number" id="year" min="1" max="5" />

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
                document.getElementById("course_id").value = row[0].textContent.trim();
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

            const id = document.getElementById("courseRowId").value;
            const year = document.getElementById("year").value;
            const semester = document.getElementById("semester").value;

            // Simulate update (replace with real AJAX later)
            alert(`Updated course ID ${id} to Year: ${year}, Semester: ${semester}`);
            modal.style.display = "none";
        };
    });
</script>