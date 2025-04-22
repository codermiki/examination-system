<?php
include_once '../config.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit(); // Always exit after a header redirect
}

// Optional: Redirect users to their specific dashboard if this index.php is general
// If this index.php is specifically for instructors, keep the instructor check below
// if ($_SESSION['role'] === 'admin') {
//     header('Location: ../admin/index.php');
//     exit();
// } elseif ($_SESSION['role'] === 'student') {
//     header('Location: ../student/index.php');
//     exit();
// }


// If this index.php is specifically for instructors, keep this check
if ($_SESSION['role'] !== 'instructor') {
    header('Location: ../'); // Redirect to a general dashboard or login
    exit(); // Always exit after a header redirect
}


if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ../');
    exit(); // Always exit after a header redirect
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
   <!-- <script src="../assets/js/script.js"></script>   -->
    <script>
        // script.js
document.addEventListener('DOMContentLoaded', () => {
    // Select all links in the sidebar with the class 'sidebar-link'
    const sidebarLinks = document.querySelectorAll('.inner__left_panel .sidebar-link');
    // Select the right panel where content will be loaded
    const rightPanel = document.getElementById('main-content');//rightPanel

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
                        // rightPanel.innerHTML = html;
                        // Create a temporary element to parse the HTML string
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;

                        // Clear the right panel before adding new content
                        rightPanel.innerHTML = '';

                        // Append all child nodes from the temporary element to the right panel
                        while (tempDiv.firstChild) {
                            rightPanel.appendChild(tempDiv.firstChild);
                        }

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
                            // Append the new script tag to the body or right panel to execute it
                            // Appending to the right panel is often suitable for scoped scripts
                            rightPanel.appendChild(newScript);
                            // Remove the original script tag (optional, but keeps the DOM clean)
                            script.remove();
                        });
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
