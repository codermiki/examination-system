<html>

<head>
    <link rel="stylesheet" href="../assets/css/assign_student.css">
</head>

<body>
    <div class="form-container">
        <div class="wrap-header">
            <h2>Assign Student</h2>
        </div>
        <form id="assignForm">
            <div class="section">
                <!-- Left box: Year & Semester + Assign button -->
                <div class="box left">
                    <h3>Select Year & Semester</h3>
                    <select id="year">
                        <option value="">-- Choose Year --</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>

                    <br>
                    <br>

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
                        <p>Please select Year and Semester first and student list will display Here...</p>
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
        const studentList = document.getElementById("studentList");

        // Load courses when year or semester changes
        [yearSelect, semesterSelect].forEach((select) => {
            select.addEventListener("change", () => {
                const year = yearSelect.value;
                const semester = semesterSelect.value;

                if (year && semester) {
                    fetch("./data/get_student.json")
                        .then((res) => res.json())
                        .then((data) => {
                            const selectAllCheckbox =
                                document.getElementById("selectAll");
                            if (data.length === 0) {
                                studentList.innerHTML = "<p>No students available.</p>";
                                return;
                            }
                            studentList.innerHTML = "";
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
                } else {
                    studentList.innerHTML = "<p>Please select Year and Semester first and student list will display Here...</p>";
                }

                if (year && semester) {
                    fetch(`./data/courses.json`)
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

                fetch("/softexam/api/assignStudent", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ course_id, student_ids }),
                })
                    .then((res) => {
                        console.log(res)
                        return res.json()
                    })
                    .then((response) => {
                        document.getElementById("message").textContent =
                            response.message;
                        // console.log(response)
                        window.location.replace("index.php?page=manage_student")
                    });
            });



    </script>
</body>

</html>