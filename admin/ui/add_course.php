<div class="outer-wrapper">
    <div class="form-container">
        <div class="wrap-header">
            <h2>Add Courses</h2>
        </div>
        <form id="courseForm">
            <div class="section">
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
            <p class="success" id="message"></p>
            <button type="submit">Add Selected Courses</button>
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
                        <input type="checkbox" name="course_id" value="${course.course_id}" class="course-checkbox">
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
        const selectedCourses = Array.from(
            document.querySelectorAll('input[name="course_id"]:checked')
        ).map((cb) => {
            const selectCourse = courses?.find((element) => element.course_id == cb.value)
            return selectCourse;
        });

        if (selectedCourses.length === 0) {
            alert("Please select at least one course.");
            return;
        }
        fetch("/softexam/api/addCourse", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ courses: selectedCourses }),
        })
            .then((res) => res.json())
            .then((response) => {
                if (response?.error) {
                    document.getElementById("message").textContent = response?.error;
                }
                if (response?.message) {
                    document.getElementById("message").textContent = response?.message;
                }
                window.location.replace("index.php?page=manage_course");
            });
    });
</script>