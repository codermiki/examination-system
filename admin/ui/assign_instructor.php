<div class="outer-wrapper">

    <div class="wrap">
        <div class="wrap-header">
            <h2>Assign Instructor</h2>
        </div>
        <form class="assign-form" id="assignInstructorForm">
            <label for="instructor">Instructor</label>
            <select id="instructor" required>
                <option value="">Select instructor</option>
            </select>

            <label for="course">Course Assigned</label>
            <select id="course" required>
                <option value="">Select course</option>
            </select>

            <div class="form-actions">
                <button type="submit" class="add" id="add">Add Now</button>
            </div>
            <p class="success" id="message"></p>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Load instructors
        fetch("./data/instructors.json")
            .then(res => res.json())
            .then(data => {
                const instructorSelect = document.getElementById("instructor");
                data.forEach(inst => {
                    const option = document.createElement("option");
                    option.value = inst.user_id;
                    option.textContent = `${inst.name} (${inst.email})`;
                    instructorSelect.appendChild(option);
                });
            });

        // Load courses
        fetch("./data/courses.json")
            .then(res => res.json())
            .then(data => {
                const courseSelect = document.getElementById("course");
                data.forEach(course => {
                    const option = document.createElement("option");
                    option.value = course.course_id;
                    option.textContent = course.course_name;
                    courseSelect.appendChild(option);
                });
            });
    });

    // Submit form
    document.getElementById("assignInstructorForm").addEventListener("submit", function (e) {
        e.preventDefault();
        const instructor_id = document.getElementById("instructor").value;
        const course_id = document.getElementById("course").value;

        if (!instructor_id || !course_id) {
            alert("Please select both instructor and course.");
            return;
        }

        fetch("/softexam/api/assignInstructor", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ instructor_id, course_id })
        })
            .then(res => res.json())
            .then(response => {
                document.getElementById("message").textContent = response.message || response.error;
                window.location.replace("index.php?page=manage_instructor")
            }).catch(err => {
                console.log(err.message)
            })
    });
</script>