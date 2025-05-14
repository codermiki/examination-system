<?php
include_once __DIR__ . "/../../includes/functions/Exam_function.php";
?>

<div class="schedule_exam">
    <div class="container">
        <div class="card">
            <h2>Schedule Exams </h2>
            <form id="scheduleExamForm">
                <div>
                    <label for="exams">Exams:</label>
                    <select id="exams" name="exams" required>
                        <option value="">-- Select Exams --</option>
                        <?php
                        $exams = Exam_function::activeExams();
                        foreach ($exams as $exam):
                            ?>
                            <option value="<?= htmlspecialchars($exam['exam_id']) ?>">
                                <?= htmlspecialchars($exam['exam_title']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label for="exam_date">Exam Date and Time:</label>
                    <input type="datetime-local" id="scheduled_date" name="scheduled_date" required>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="schedule_btn">Schedule Exam</button>
                </div>
                <p id="message"></p>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById("scheduleExamForm").addEventListener("submit", function (e) {
        e.preventDefault(); // Prevent form reload

        const exam_id = document.getElementById("exams").value;
        const scheduled_date = document.getElementById("scheduled_date").value;

        if (!exam_id || !scheduled_date) {
            alert("Please fill in all fields.");
            return;
        }

        fetch("/softexam/api/scheduleExam", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                exam_id,
                scheduled_date
            })
        })
            .then((res) => res.json())
            .then((response) => {
                if (response?.error) {
                    document.getElementById("message").textContent = response?.error;
                }

                if (response?.message) {
                    document.getElementById("message").textContent = response?.message;
                    window.location.replace("index.php?page=manage_schedule");
                }
            })
            .catch((err) => {
                document.getElementById("message").textContent = "Failed to Schedule Exam";
            });
    });
</script>