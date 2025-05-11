<!-- Update Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update (<span id="modalName">Name</span>)</h3>
        <form id="updateForm">
            <input type="hidden" id="exam_id" name="exam_id" />
            <label>Schedule</label>

            <label for="exam_date">Exam Date:</label>
            <input type="date" id="exam_date" name="exam_date" required>


            <label for="exam_time">Exam Time:</label>
            <input type="time" id="exam_time" name="exam_time" required>

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
                document.getElementById("exam_date").value = dataset.exam_date;
                document.getElementById("exam_time").value = dataset.exam_time;
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
                scheduled_date: `${dataObj.exam_date} ${dataObj.exam_time}`,
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