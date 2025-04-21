<html>

<head>
    <link rel="stylesheet" href="../assets/css/assign_instructor.css">
</head>

<body>
    <div class="wrap">
        <div class="wrap-header">
            <h2>Assign Instructor</h2>
            <button id="close_instructor_btn" class="close-btn">&times;</button>
        </div>
        <form class="assign-form">
            <label for="course">Instructor</label>
            <select id="course" required>
                <option>Select instructor</option>
            </select>
            <label for="course">Course Assigned</label>
            <select id="course" required>
                <option>Select course</option>
            </select>

            <div class="form-actions">
                <button id="cancel_instructor_btn" type="button" class="close">Cancel</button>
                <button type="submit" class="add">Add Now</button>
            </div>
        </form>
    </div>

    <script>
        const close_instructor_btn = document.querySelector('#close_instructor_btn');
        const cancel_instructor_btn = document.querySelector('#cancel_instructor_btn');
        const assign_instructor = document.querySelector('.assign_instructor');
        const assign_instructor_toggler = document.querySelector('#assign_instructor_toggler');

        close_instructor_btn.addEventListener('click', () => {
            assign_instructor.classList.remove('show');
        });

        cancel_instructor_btn.addEventListener('click', () => {
            assign_instructor.classList.remove('show');
        });

        function showAssignInstructor(e) {
            e.preventDefault();
            assign_instructor.classList.add('show');
        }

        document.addEventListener('click', (e) => {
            if (!assign_instructor.contains(e.target) && !assign_instructor_toggler.contains(e.target)) {
                assign_instructor.classList.remove('show');
            }
        });
    </script>

</body>

</html>