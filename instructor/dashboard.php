<html>

<head>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="container">
        <h1>Welcome to softexam Admin - Panel</h1>
        <div class="cards">
            <div class="card">
                <h2 id="students">...</h2>
                <p>Total Students</p>
            </div>
            <div class="card">
                <h2 id="instructors">...</h2>
                <p>Total Instructors</p>
            </div>
            <div class="card">
                <h2 id="courses">...</h2>
                <p>Total Courses</p>
            </div>
            <div class="card">
                <h2 id="exams">...</h2>
                <p>Upcoming Exams</p>
            </div>
        </div>
    </div>

    <script>
        // Simulate fetching data from server
        const data = {
            total_students: 350,
            total_instructors: 28,
            total_courses: 45,
            upcoming_exams: 10,
        };

        // Update card values
        document.getElementById("students").textContent = data.total_students;
        document.getElementById("instructors").textContent =
            data.total_instructors;
        document.getElementById("courses").textContent = data.total_courses;
        document.getElementById("exams").textContent = data.upcoming_exams;

        // OR fetch from server like:
        // fetch('get_dashboard_stats.php').then(res => res.json()).then(data => {
        //   document.getElementById('students').textContent = data.total_students;
        //   document.getElementById('instructors').textContent = data.total_instructors;
        //   document.getElementById('courses').textContent = data.total_courses;
        //   document.getElementById('exams').textContent = data.upcoming_exams;
        // });
    </script>
</body>

</html>