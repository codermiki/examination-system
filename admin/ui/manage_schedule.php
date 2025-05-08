<div class="manage_schedule">
    <div class="container">
        <h1>Manage Exam Schedule</h1>
        <div class="card">
            <h2>Scheduled Exams</h2>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Exam Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Sample data, replace with database fetch
                    $schedules = [
                        [
                            "course" => "BSIT",
                            "year_level" => "2nd Year",
                            "exam_date" => "2025-06-10",
                            "time" => "09:00 AM",
                            "duration" => "60",
                            "status" => "Scheduled"
                        ],
                        [
                            "course" => "BSCRIM",
                            "year_level" => "3rd Year",
                            "exam_date" => "2025-06-12",
                            "time" => "01:00 PM",
                            "duration" => "90",
                            "status" => "Scheduled"
                        ]
                    ];

                    foreach ($schedules as $exam) {
                        echo "<tr>
                            <td>{$exam['course']}</td>
                            <td>{$exam['year_level']}</td>
                            <td>{$exam['exam_date']}</td>
                            <td>{$exam['time']}</td>
                            <td>{$exam['duration']} min</td>
                            <td>{$exam['status']}</td>
                            <td>
                                <button class='update-btn'>Update</button>
                                <button class='delete-btn'>Delete</button>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Optional: Modal for updating (reusing previous modal logic) -->
<!-- Update Exam Modal -->
<div id="updateModal" class="modal"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; padding:20px; margin:5% auto; width:400px; position:relative;">
        <span class="close"
            style="position:absolute; right:10px; top:10px; font-size:20px; cursor:pointer;">&times;</span>
        <h2>Update Exam Schedule</h2>
        <form id="updateForm" action="update_exam_schedule.php" method="POST">
            <input type="hidden" id="schedule_id" name="schedule_id" />

            <label>Course:
                <input type="text" id="course" name="course" required />
            </label><br />

            <label>Year Level:
                <input type="text" id="year_level" name="year_level" required />
            </label><br />

            <label>Exam Date:
                <input type="date" id="exam_date" name="exam_date" required />
            </label><br />

            <label>Time:
                <input type="time" id="exam_time" name="exam_time" required />
            </label><br />

            <label>Duration (minutes):
                <input type="number" id="duration" name="duration" required />
            </label><br />

            <label>Status:
                <select id="status" name="status">
                    <option value="Scheduled">Scheduled</option>
                    <option value="Completed">Completed</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </label><br /><br />

            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("updateModal");
        const closeBtn = document.querySelector(".close");
        const form = document.getElementById("updateForm");

        document.querySelectorAll(".update-btn").forEach((btn) => {
            btn.addEventListener("click", function () {
                const row = btn.closest("tr").children;

                // Simulated ID; replace with actual ID in real setup
                document.getElementById("schedule_id").value = btn.getAttribute("data-id") || "1";
                document.getElementById("course").value = row[0].textContent.trim();
                document.getElementById("year_level").value = row[1].textContent.trim();
                document.getElementById("exam_date").value = row[2].textContent.trim();
                document.getElementById("exam_time").value = row[3].textContent.trim();
                document.getElementById("duration").value = parseInt(row[4].textContent);
                document.getElementById("status").value = row[5].textContent.trim();

                modal.style.display = "block";
            });
        });

        closeBtn.onclick = () => modal.style.display = "none";
        window.onclick = (e) => {
            if (e.target === modal) modal.style.display = "none";
        };
    });
</script>