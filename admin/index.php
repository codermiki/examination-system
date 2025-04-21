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

        <section class="right__panel" id="rightPanel">
        <?php
        include "dashboard.php";
        ?>
        </section>
    </main>

    <?php
    include "../includes/layout/footer.php";
    ?>

<script>
        // script.js
document.addEventListener('DOMContentLoaded', () => {
    // Select all links in the sidebar with the class 'sidebar-link'
    const sidebarLinks = document.querySelectorAll('.inner__left_panel .sidebar-link');
    // Select the right panel where content will be loaded
    const rightPanel = document.getElementById('rightPanel');

    // Add a click event listener to each sidebar link
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent the default behavior of the link (navigating to a new page)

            // Get the value of the 'data-content' attribute
            const contentType = link.getAttribute('data-content');

            // Check if the data-content attribute exists and is not empty
            if (contentType) {
                // Optional: Display a loading indicator in the right panel
                rightPanel.innerHTML = '<p>Loading...</p>';

                // Make an AJAX request to the server to load the content
                // The request is sent to 'handle_action.php' with the 'action' parameter
                fetch(`handle_action.php?action=${contentType}`)
                    .then(response => {
                        // Check if the HTTP response was successful
                        if (!response.ok) {
                            // If not successful, throw an error with the status
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        // Parse the response body as plain text (assuming the server returns HTML)
                        return response.text();
                    })
                    .then(html => {
                        // Update the inner HTML of the right panel with the received content
                        rightPanel.innerHTML = html;
                    })
                    .catch(error => {
                        // Log any errors to the console
                        console.error('Error loading content:', error);
                        // Display an error message in the right panel
                        rightPanel.innerHTML = '<p>Error loading content.</p>';
                    });
            }
        });
    });
});

    </script>
</body>

</html>