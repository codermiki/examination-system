<html>

<head>
    <link rel="stylesheet" href="../assets/css/assign_student.css">
</head>

<body>
    <div class="form-container">
        <div class="wrap-header">
            <h2>Assign Student</h2>
            <button id="close_student_btn" class="close-btn">&times;</button>
        </div>
        <form id="assignForm">
            <div class="section">
                <!-- Left box: Year & Semester + Assign button -->
                <div class="box left">
                    <h3>Select Year & Semester</h3>
                    <label for="year">Academic Year:</label>
                    <select id="year">
                        <option value="">-- Choose Year --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>

                    <label for="semester">Semester:</label>
                    <select id="semester">
                        <option value="">-- Choose Semester --</option>
                        <option value="1">1st Semester</option>
                        <option value="2">2nd Semester</option>
                    </select>

                    <button type="submit">Assign Students</button>
                    <p class="success" id="message"></p>
                </div>

                <!-- Right box: Course & Students -->
                <div class="box right">
                    <h3>Course & Students</h3>

                    <label for="course">Select Course:</label>
                    <select id="course" name="course_id">
                        <option value="">-- Select Course --</option>
                    </select>

                    <label><input type="checkbox" id="selectAll" />
                        <strong>Select All Students</strong></label>
                    <div class="student-list" id="studentList">
                        <!-- Student checkboxes inserted here -->
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        const yearSelect = document.getElementById("year");
        const semesterSelect = document.getElementById("semester");
        const courseSelect = document.getElementById("course");
        const assign_student = document.querySelector('.assign_student');
        const close_student_btn = document.querySelector('#close_student_btn');
        const assign_student_toggler = document.querySelector('#assign_student_toggler');


        // Load students
        document.addEventListener("DOMContentLoaded", () => {
            fetch("./data/get_student.json")
                .then((res) => res.json())
                .then((data) => {
                    const studentList = document.getElementById("studentList");
                    const selectAllCheckbox =
                        document.getElementById("selectAll");
                    if (data.length === 0) {
                        studentList.innerHTML = "<p>No students available.</p>";
                        return;
                    }
                    data.forEach((student) => {
                        const label = document.createElement("label");
                        label.innerHTML = `
                <input type="checkbox" name="student_ids" value="${student.user_id}" class="student-checkbox">
                ${student.name} (${student.email})
                `;
                        studentList.appendChild(label);
                    });

                    selectAllCheckbox.addEventListener("change", () => {
                        const checkboxes =
                            document.querySelectorAll(".student-checkbox");
                        checkboxes.forEach(
                            (cb) => (cb.checked = selectAllCheckbox.checked)
                        );
                    });
                });
        });

        // Load courses when year or semester changes
        [yearSelect, semesterSelect].forEach((select) => {
            select.addEventListener("change", () => {
                const year = yearSelect.value;
                const semester = semesterSelect.value;

                if (year && semester) {
                    fetch(`./data/get_course.json`)
                        .then((res) => res.json())
                        .then((data) => {
                            courseSelect.innerHTML =
                                '<option value="">-- Select Course --</option>';
                            data.forEach((course) => {
                                const option = document.createElement("option");
                                option.value = course.course_id;
                                option.textContent = `${course.course_name}`;
                                courseSelect.appendChild(option);
                            });
                        });
                }
            });
        });

        // Submit form
        document
            .getElementById("assignForm")
            .addEventListener("submit", (e) => {
                e.preventDefault();

                const course_id = courseSelect.value;
                const checkedBoxes = document.querySelectorAll(
                    'input[name="student_ids"]:checked'
                );
                const student_ids = Array.from(checkedBoxes).map(
                    (cb) => cb.value
                );

                if (!course_id || student_ids.length === 0) {
                    alert("Please select a course and at least one student.");
                    return;
                }

                fetch("assign_students_to_course.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ course_id, student_ids }),
                })
                    .then((res) => res.json())
                    .then((response) => {
                        document.getElementById("message").textContent =
                            response.message;
                    });
            });


        close_student_btn.addEventListener('click', () => {
            assign_student.classList.remove('show');
        });


        function showAssignStudent(e) {
            e.preventDefault();
            assign_student.classList.add('show');
        }

        document.addEventListener('click', (e) => {
            if (!assign_student.contains(e.target) && !assign_student_toggler.contains(e.target)) {
                assign_student.classList.remove('show');
            }
        });
    </script>
</body>

</html>