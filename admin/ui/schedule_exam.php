<div class="schedule_exam">
    <div class="container">
        <h1>Schedule Exam</h1>
        <div class="card">
            <h2>New Exam Schedule</h2>
            <form action="save_exam_schedule.php" method="POST">
                <div>
                    <label for="course">Course:</label>
                    <select id="course" name="course" required>
                        <option value="">-- Select Course --</option>
                        <option value="BSCRIM">BSCRIM</option>
                        <option value="BSIT">BSIT</option>
                        <option value="BSBA">BSBA</option>
                        <!-- Add more as needed -->
                    </select>
                </div>

                <div>
                    <label for="year_level">Year Level:</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">-- Select Year --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>
                </div>

                <div>
                    <label for="exam_date">Exam Date:</label>
                    <input type="date" id="exam_date" name="exam_date" required>
                </div>

                <div>
                    <label for="exam_time">Exam Time:</label>
                    <input type="time" id="exam_time" name="exam_time" required>
                </div>

                <div>
                    <label for="duration">Duration (minutes):</label>
                    <input type="number" id="duration" name="duration" required min="15" max="180">
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="schedule_btn">Schedule Exam</button>
                </div>
            </form>
        </div>
    </div>
</div>
