<?php
include_once '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
}
if (!($_SESSION['role'] == 'instructor')) {
    header('Location: ../');
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../');
}
// get page from url
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';  // Default page

// Sanitize and whitelist allowed pages
$allowedPages = ['dashboard', 'add_exam', 'manage_exam'];
$page = in_array($page, $allowedPages) ? $page : '404';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>instructor</title>
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
            <?php
            if ($page === '404') {
                echo "<h2>Page not found!</h2>";
            } else {
                include "ui/{$page}.php";
            }
            ?>
        </section>
    </main>
    <?php
    include "../includes/layout/footer.php";
    ?>
</body>

</html>