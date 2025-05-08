<div class="outer-wrapper">
    <div class="form-container">
        <div class="wrap-header">
            <h2>Add Courses to Year & Semester</h2>
        </div>
        <form id="courseForm">
            <div class="section">
                <!-- Left box: Year & Semester + Add button -->
                <div class="box">
                    <h3>Select Year & Semester</h3>

                    <label for="year">Academic Year:</label>
                    <select id="year" required>
                        <option value="">-- Choose Year --</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>

                    <label for="semester">Semester:</label>
                    <select id="semester" required>
                        <option value="">-- Choose Semester --</option>
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>

                    <button type="submit">Add Selected Courses</button>
                    <p class="success" id="message"></p>
                </div>

                <!-- Right box: Course checkboxes -->
                <div class="box">
                    <h3>Available Courses</h3>
                    <label><input type="checkbox" id="selectAll" />
                        <strong>Select All Courses</strong></label>
                    <div class="course-list" id="courseList">
                        <!-- Course checkboxes will be inserted here -->
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let courses = [];
    document.addEventListener("DOMContentLoaded", () => {
        fetch("./data/courses.json") // Path to your JSON file
            .then((res) => res.json())
            .then((data) => {
                const courseList = document.getElementById("courseList");
                const selectAllCheckbox = document.getElementById("selectAll");
                courses = data;
                data.forEach((course) => {
                    const label = document.createElement("label");
                    label.innerHTML = `
                        <input type="checkbox" name="course_ids" value="${course.course_id}" class="course-checkbox">
                        ${course.course_name}
                    `;
                    courseList.appendChild(label);
                });

                // Handle "Select All"
                selectAllCheckbox.addEventListener("change", () => {
                    const checkboxes = document.querySelectorAll(".course-checkbox");
                    checkboxes.forEach((cb) => (cb.checked = selectAllCheckbox.checked));
                });
            });
    });

    document.getElementById("courseForm").addEventListener("submit", (e) => {
        e.preventDefault();
        const year = document.getElementById("year").value;
        const semester = document.getElementById("semester").value;
        const selectedCourses = Array.from(
            document.querySelectorAll('input[name="course_ids"]:checked')
        ).map((cb) => {
            const selectCourse = courses.find((element) => element.course_id == cb.value)
            return selectCourse;
        });

        if (!year || !semester || selectedCourses.length === 0) {
            alert("Please select year, semester and at least one course.");
            return;
        }
        fetch("/softexam/api/addCourse", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ year, semester, course_ids: selectedCourses }),
        })
            .then((res) => {
                return res.json()
            })
            .then((response) => {
                document.getElementById("message").textContent = response.message;
                window.location.replace("index.php?page=manage_course");
            });
    });
</script>