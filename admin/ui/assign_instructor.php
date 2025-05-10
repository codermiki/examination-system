<?php
include_once __DIR__ . "/../../includes/functions/Course_function.php";
?>

<div class="outer-wrapper">
    <div class="wrap">
        <div class="wrap-header">
            <h2>Assign Instructor</h2>
        </div>
        <form class="assign-form" id="assignInstructorForm">
            <label for="instructor">Instructor</label>
            <select id="instructor" required>
                <option value="">Select instructor</option>
                <!-- instructors list goes here -->
            </select>

            <label for="course">Course Assigned</label>
            <select id="course" required>
                <option value="">-- Select Course --</option>
                <?php
                $courses = Course_function::fetchCourses();
                foreach ($courses as $course):
                    ?>
                    <option value=<?= htmlspecialchars($course['course_id']) ?>><?= htmlspecialchars($course['course_name']) ?></option>
                <?php endforeach ?>
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
                data.forEach(instructor => {
                    const option = document.createElement("option");
                    option.value = instructor.user_id;
                    option.textContent = `${instructor.name} (${instructor.email})`;
                    instructorSelect.appendChild(option);
                });
            });
    });

    // Submit form
    document.getElementById("assignInstructorForm").addEventListener("submit", function (e) {
        e.preventDefault();
        const user_id = document.getElementById("instructor").value;
        const course_id = document.getElementById("course").value;

        if (!user_id || !course_id) {
            alert("Please select both instructor and course.");
            return;
        }

        fetch("./data/instructors.json")
            .then(res => res.json())
            .then(data => {
                let instructor = data.filter((instructor) => instructor.user_id == user_id);

                fetch("/softexam/api/assignInstructor", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ instructor, course_id })
                })
                    .then(res => res.json())
                    .then(response => {
                        if (response?.error) {
                            document.getElementById("message").textContent = response?.error;
                            return;
                        }
                        document.getElementById("message").textContent = response?.message;
                        window.location.replace("index.php?page=manage_instructor")

                    }).catch(err => {
                        document.getElementById("message").textContent = "Failed to assign"
                    })
            }).catch(err => {
                document.getElementById("message").textContent = "Failed to assign"
            });
    });
</script>