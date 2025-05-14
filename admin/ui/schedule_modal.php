<!-- Update Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update (<span id="modalName">Name</span>)</h3>
        <form id="updateForm">
            <input type="hidden" id="exam_id" name="exam_id" />
            <label>Schedule</label>

            <label for="scheduled_date">Exam Date and Time:</label>
            <input type="datetime-local" id="scheduled_date" name="scheduled_date" required>

            <button style="margin-top: 10px" type="submit" class="update-btn">Update Now</button>
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

                document.getElementById("modalName").textContent = dataset.course_name;
                document.getElementById("exam_id").value = dataset.exam_id;
                document.getElementById("scheduled_date").value = dataset.scheduled_date;
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

            const data = {
                exam_id: dataObj.exam_id,
                scheduled_date: dataObj.scheduled_date,
                status: dataObj.status,
            }

            fetch("/softexam/api/updateExamSchedule", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
                .then((res) => res.json())
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