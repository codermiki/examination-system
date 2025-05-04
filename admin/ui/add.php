<?php
// admin/add_course.php
require '../includes/db/db.config.php';

// Load courses from external JSON (mocked example)
$externalCoursesJson = file_get_contents('data/external_courses.json');
$externalCourses = json_decode($externalCoursesJson, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $conn->prepare("INSERT INTO courses (name, code) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    foreach ($_POST['course_ids'] as $courseId) {
        $course = $externalCourses[$courseId];
        $stmt->execute([$course['name'], $course['code']]);
    }
    echo "<div style='color:green;'>Courses added successfully.</div>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Courses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background-color: #f9f9f9;
        }

        h2 {
            color: #333;
        }

        form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .course-list {
            margin-bottom: 15px;
        }

        .course-item {
            margin: 8px 0;
        }

        button {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>
    <h2>Add Courses from Registrar System</h2>
    <form method="POST">
        <div class="course-list">
            <?php foreach ($externalCourses as $id => $course): ?>
                <div class="course-item">
                    <input type="checkbox" name="course_ids[]" value="<?= $id ?>">
                    <?= htmlspecialchars($course['name']) ?> (<?= htmlspecialchars($course['code']) ?>)
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit">Add Selected Courses</button>
    </form>
</body>

</html>