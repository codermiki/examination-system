<?php
// import session configuration
include_once '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
}

if (isset($_SESSION['must_reset_password'])) {
    if ($_SESSION['must_reset_password'] == true) {
        header("Location: ../");
        exit();
    }
}


if (!($_SESSION['role'] == 'Student')) {
    header('Location: ../');
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../');
}

$page = $_GET["page"] ?? "dashboard";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>student dashboard | softexam</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/sideBar.css">
    <link rel="stylesheet" href="../assets/css/student.css">
</head>

<body>
    <?php
    include "../includes/layout/header.php";
    ?>

    <main class="main__container">
        <section id="main-content" class="right__panel">
            <?php
            switch ($page) {
                case 'dashboard':
                    include "./ui/dashboard.php";
                    break;

                case 'update_password':
                    include "./ui/update_password.php";
                    break;

                case 'upcoming_exams':
                    include "./ui/upcoming_exams.php";
                    break;

                case 'exam_schedule':
                    include "./ui/exam_schedule.php";
                    break;

                case 'take_exam':
                    include "./ui/take_exam.php";
                    break;

                case 'taken_exams':
                    include "./ui/taken_exams.php";
                    break;

                case 'add_feedback':
                    include "./ui/add_feedback.php";
                    break;

                default:
                    echo "Page Not Found";
                    break;
            }
            ?>
        </section>
    </main>
    <?php
    include "../includes/layout/footer.php";
    ?>

    <script>

    </script>

</body>

</html>