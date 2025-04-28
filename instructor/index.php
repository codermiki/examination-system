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
    const rightPanel = document.getElementById('main-content');

    // Check if rightPanel exists
    if (!rightPanel) {
        console.error('Error: #rightPanel not found in index.php!');
        return; // Stop if the main content area isn't found
    }

    // --- Event Delegation for Dynamically Loaded Content ---
    // Attach a single click listener to the rightPanel
    rightPanel.addEventListener('click', (event) => {
        console.log('Click event on rightPanel detected.'); // Debugging click events

        // Check if the clicked element or its parent is the "Add Question" button
        // Use closest() to check if the clicked element or any of its ancestors
        // up to the rightPanel matches the selector '.add-question-button'
        const addQuestionButton = event.target.closest('.add-question-button');
        if (addQuestionButton) {
            console.log('Add Question button clicked via delegation.'); // Debugging button click

            // Call the addQuestion function.
            // We need to ensure addQuestion is available in the global scope
            // or properly defined after the script from create_exam.php runs.
            // The previous script execution logic in the fetch .then() block
            // should make this function available.
            if (typeof addQuestion === 'function') {
                 addQuestion();
            } else {
                 console.error('Error: addQuestion function is not defined.');
                 // You might want to re-execute the script from the loaded content here
                 // if it wasn't executed correctly by the initial loading logic.
                 // However, relying on the initial script execution in the fetch .then()
                 // is the intended approach with the previous modification.
            }
        }

        // You can add more event delegation checks for other buttons/elements
        // loaded dynamically in the right panel here if needed.
        // Example:
        // const removeQuestionButton = event.target.closest('.remove-item-button');
        // if (removeQuestionButton) {
        //     // Call your removeQuestion function
        //     // removeQuestion(...);
        // }
    });
    // --- End Event Delegation ---


    // Add a click event listener to each sidebar link
    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent the default behavior of the link (navigating to a new page)

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
                        // Create a temporary element to parse the HTML string
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;

                        // Clear the right panel before adding new content
                        rightPanel.innerHTML = '';

                        // Append all child nodes from the temporary element to the right panel
                        while (tempDiv.firstChild) {
                            rightPanel.appendChild(tempDiv.firstChild);
                        }

                        // --- Manual Script Execution for Loaded Content ---
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
                         console.log('Scripts from loaded content executed.'); // Debugging script execution
                        // --- End Manual Script Execution ---


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
