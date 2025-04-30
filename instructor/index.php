<?php
// import session configuration
include_once '../config.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SESSION['role'] !== 'instructor') {
    header('Location: ../');
    exit();
}


if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard</title>
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
            <!-- the #rightPanel content goes here -->
            <?php
            include "dashboard.php";
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