<?php
include_once '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
}
if (!($_SESSION['role'] == 'admin')) {
    header('Location: ../');
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../');
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>online examination admin portal</title>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/sideBar.css">
</head>

<body>
    <?php
    include "../includes/layout/header.php";
    ?>

    <main class="main__container">
        <section class="left__panel">
            <?php
            include "../includes/layout/SideBar.php";
            ?>
        </section>
        <section id="main-content" class="right__panel">
            <!-- dashboard -->
            <div class="dashboard">
                <?php
                include "./ui/dashboard.php";
                ?>
            </div>

            <!-- assign student -->
            <div class="assign_student">
                <?php
                include "./ui/assign_student.php";
                ?>
            </div>

            <!-- assign instructor -->
            <div class="assign_instructor">

                <?php
                include "./ui/assign_instructor.php";
                ?>
            </div>

            <!-- add course -->
            <div class="add_course">
                <?php
                include "./ui/add_course.php";
                ?>
            </div>
        </section>
    </main>

    <?php
    include "../includes/layout/footer.php";
    ?>
    <script>
        function loadPage(page) {
            fetch(`/ui/${page}.php`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('main-content').innerHTML = `<p style="color:red;">Failed to load ${page}</p>`;
                });
        }
    </script>
</body>

</html>