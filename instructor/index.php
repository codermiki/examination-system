<?php
// import session configuration
include_once '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_SESSION['must_reset_password'])) {
    if ($_SESSION['must_reset_password'] == true) {
        header("Location: ../");
        exit();
    }
}

if ($_SESSION['role'] !== 'Instructor') {
    header('Location: ../');
    exit();
}


if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../');
    exit();
}
$page = $_GET["page"] ?? "dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/sideBar.css">
    <link rel="stylesheet" href="../assets/css/edit_exam.css">
    <link rel="stylesheet" href="../assets/css/instructor.css">
    <link rel="stylesheet" href="../assets/css/create_exam.css">
    <link rel="stylesheet" href="../assets/css/view_exam.css">
    <link rel="stylesheet" href="../assets/css/exam_report.css">
    <link rel="stylesheet" href="../assets/css/edit_exam.css">
    <link rel="stylesheet" href="../assets/css/feedbacks.css">
    <link rel="stylesheet" href="../assets/css/updatePass.css">
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/dashboardInstructor.css">
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
            <!-- the #rightPanel content goes here -->
            <?php
            switch ($page) {
                case 'dashboard':
                    include "./ui/dashboard.php";
                    break;

                case 'create_exam':
                    include "./ui/create_exam.php";
                    break;
                case 'view_exam':
                    include "./ui/view_exam.php";
                    break;

                case 'import_exam':
                    include "./ui/import_exam.php";
                    break;

                case 'edit_exam':
                    include "./ui/edit_exam.php";
                    break;

                case 'delete_exam':
                    include "./ui/delete_exam.php";
                    break;

                case 'manage_questions':
                    include "./ui/manage_questions.php";
                    break;

                case 'exam_report':
                    include "./ui/exam_report.php";
                    break;

                case 'feedbacks':
                    include "./ui/feedbacks.php";
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
        // run the script on the page loaded
        document.addEventListener('DOMContentLoaded', () => {
            // Select all links in the sidebar with the class 'sidebar-link'
            const sidebarLinks = document.querySelectorAll('.inner__left_panel .sidebar-link');

            // Select the right panel where content will be loaded
            const rightPanel = document.getElementById('main-content');

            // Add a click event listener to each sidebar link
            sidebarLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    // Prevent the default behavior of the link 
                    e.preventDefault();

                    const contentType = link.getAttribute('data-content');
                    // Check if the data-content attribute exists and is not empty
                    if (contentType) {
                        // Optional: Display a loading indicator in the right panel
                        rightPanel.innerHTML = '<p>Loading...</p>';

                        // Make an AJAX request to the server to load the content
                        fetch(`handle_action.php?action=${contentType}`)
                            .then(response => {
                                // Check if the HTTP response was successful
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }
                                return response.text();
                            })
                            .then(html => {
                                // Create a temporary element to parse the HTML string
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = html;
                                rightPanel.innerHTML = '';

                                // append the content to right Panel
                                rightPanel.appendChild(tempDiv);

                                // Find and execute script tags within the loaded content
                                const scripts = rightPanel.querySelectorAll('script');
                                scripts.forEach(script => {
                                    const newScript = document.createElement('script');
                                    // Copy attributes from the original script tag
                                    script.getAttributeNames().forEach(attrName => {
                                        newScript.setAttribute(attrName, script.getAttribute(attrName));
                                    });
                                    // Set the script content
                                    newScript.textContent = script.textContent;
                                    // Append the new script tag to the right panel to execute it
                                    rightPanel.appendChild(newScript);
                                    // Remove the original script tag (optional, but keeps the DOM clean)
                                    script.remove();
                                });
                            })
                            .catch(error => {
                                rightPanel.innerHTML = '<p>Something wrong.</p>';
                            });
                    }
                });
            });
        });
    </script>



</body>

</html>