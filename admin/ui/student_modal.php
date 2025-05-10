<!-- Update Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update (<span id="modalName">Name</span>)</h3>
        <form id="updateForm">
            <input type="hidden" id="studentId" name="student_id" />
            <label>Course</label>
            <select id="course" name="course_id">
                <?php
                foreach ($courses as $course):
                    ?>
                    <option value=<?= $course['course_id'] ?>><?= htmlspecialchars($course['course_name']) ?></option>
                <?php endforeach ?>
            </select>

            <label>Email</label>
            <input type="email" id="email" name="email" />

            <label>Status</label>
            <select id="status" name="status">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
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
        const openButtons = document.querySelectorAll(".open-update-modal-btn");

        openButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const dataset = btn.dataset;

                document.getElementById("modalName").textContent = dataset.name;
                document.getElementById("studentId").value = dataset.id;
                document.getElementById("course").value = dataset.course;
                document.getElementById("email").value = dataset.email;
                document.getElementById("status").value = dataset.status;
                modal.style.display = "block";
            });

        });

        closeBtn.onclick = () => modal.style.display = "none";

        window.onclick = (e) => {
            if (e.target == modal) modal.style.display = "none";
        };

        form.onsubmit = (e) => {
            e.preventDefault();

            const formData = new FormData(form);

            // Convert FormData to a plain object
            const dataObj = Object.fromEntries(formData.entries());

            fetch("/softexam/api/updateAssignedStudent", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(dataObj)
            })
                .then(res => res.json())
                .then(data => {
                    if (data.message) {
                        window.location.reload();
                    } else {
                        alert("Update failed.");
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("An error occurred.");
                });

            modal.style.display = "none";
        };
    });
</script>