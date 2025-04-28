<html>

<head>
    <link rel="stylesheet" href="../assets/css/add_course.css">
</head>

<body>
    <div class="form-container">
        <div class="wrap-header">
            <h2>Add Courses to Year & Semester</h2>
            <button id="close_course_btn" class="close-btn">&times;</button>
        </div>
        <form id="courseForm">
            <div class="section">
                <!-- Left box: Year & Semester + Add button -->
                <div class="box">
                    <h3>Select Year & Semester</h3>

                    <label for="year">Academic Year:</label>
                    <select id="year" required>
                        <option value="">-- Choose Year --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
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

    <script>
        const add_course = document.querySelector('.add_course');
        const close_course_btn = document.querySelector('#close_course_btn');
        const add_course_toggler = document.querySelector('#add_course_toggler');
        // Load all available courses
        document.addEventListener("DOMContentLoaded", () => {
            fetch("get_all_courses.php")
                .then((res) => res.json())
                .then((data) => {
                    const courseList = document.getElementById("courseList");
                    const selectAllCheckbox =
                        document.getElementById("selectAll");

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
                        const checkboxes =
                            document.querySelectorAll(".course-checkbox");
                        checkboxes.forEach(
                            (cb) => (cb.checked = selectAllCheckbox.checked)
                        );
                    });
                });
        });

        // Handle form submit
        document
            .getElementById("courseForm")
            .addEventListener("submit", (e) => {
                e.preventDefault();

                const year = document.getElementById("year").value;
                const semester = document.getElementById("semester").value;
                const selectedCourses = Array.from(
                    document.querySelectorAll('input[name="course_ids"]:checked')
                ).map((cb) => cb.value);

                if (!year || !semester || selectedCourses.length === 0) {
                    alert(
                        "Please select year, semester and at least one course."
                    );
                    return;
                }

                fetch("assign_courses_to_year_semester.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        year,
                        semester,
                        course_ids: selectedCourses,
                    }),
                })
                    .then((res) => res.json())
                    .then((response) => {
                        document.getElementById("message").textContent =
                            response.message;
                    });
            });

        // Close button functionality
        close_course_btn.addEventListener('click', () => {
            add_course.classList.remove('show');
        });


        function showAddCourse(e) {
            e.preventDefault();
            add_course.classList.add('show');
        }

        document.addEventListener('click', (e) => {
            if (!add_course.contains(e.target) && !add_course_toggler.contains(e.target)) {
                add_course.classList.remove('show');
            }
        });

    </script>

</body>

</html>