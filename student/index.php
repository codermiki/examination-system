<?php
include_once '../config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
}

if (!($_SESSION['role'] == 'student')) {
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
    <title>online examination portal</title>
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

        <section class="right__panel">

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