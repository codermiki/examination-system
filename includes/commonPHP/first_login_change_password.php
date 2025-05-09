<?php
// includes/commonPHP/first_login_change_password.php

// This file is displayed when a user logs in for the first time
// and needs to change their default password.

// Corrected include paths based on file location in includes/commonPHP/
include_once '../../config.php'; // Go up two directories to reach the root
// Assuming db.config.php is in includes/db/
include_once '../db/db.config.php'; // Go up one directory, then into db/


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: Ensure the user is logged in and user_id is set
// We don't strictly check role here, as any user type might need this
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    // If not logged in, redirect to login
    header('Location: ../../login.php'); // Corrected redirect path to login from commonPHP
    exit();
}

$message = ''; // Variable to store feedback messages
$userId = $_SESSION['user_id']; // Get the logged-in user's user_id
$userRole = $_SESSION['role'] ?? 'unknown'; // Get the user's role

// --- Start: PHP Logic to Check if Password Change is Needed ---
// This check should ideally happen in your login processing file (e.g., login.php)
// and redirect the user here if needed. However, we'll include a check here
// as a fallback to prevent displaying the form if the flag is already false.
// You should ensure your login logic redirects to this page *before* redirecting
// to the main dashboard if needs_password_change is TRUE.

try {
    // Use the $pdo connection object established in config.php or db.config.php
    $stmt = $pdo->prepare("SELECT needs_password_change FROM users WHERE user_id = :user_id LIMIT 1");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['needs_password_change'] == 0) {
        // If user not found or flag is false, redirect to their dashboard
        // Determine the correct dashboard based on role
        $dashboard = '../../'; // Default redirect (root)
        if ($userRole === 'admin') {
            $dashboard = '../../admin/'; // Assuming admin dashboard is in ./admin/ from root
        } elseif ($userRole === 'instructor') {
            $dashboard = '../../instructor/'; // Assuming instructor dashboard is in ./instructor/ from root
        } elseif ($userRole === 'student') {
            $dashboard = '../../student/'; // Assuming student dashboard is in ./student/ from root
        }
        header('Location: ' . $dashboard);
        exit();
    }

} catch (PDOException $e) {
    error_log("Error checking needs_password_change flag for user ID " . $userId . ": " . $e->getMessage());
    // Display an error or redirect to a generic error page
    echo '<p class="error">Error checking account status. Please try again later.</p>';
    exit();
}
// --- End: PHP Logic to Check if Password Change is Needed ---


// --- Start: PHP Logic for Handling Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_default_password'])) {
    $newPass = $_POST['pass1'] ?? '';
    $confPass = $_POST['pass2'] ?? '';

    // Basic validation
    if (empty($newPass) || empty($confPass)) {
        $message = '<p class="error">Error: Please fill in both password fields.</p>';
    } elseif (strlen($newPass) < 8) { // Check if the new password is at least 8 characters long
        $message = '<p class="error">Error: Password must be at least 8 characters long.</p>';
    } elseif ($newPass !== $confPass) { // Check if passwords match - moved after length check
        $message = '<p class="error">Error: Passwords do not match.</p>';
    }
    else {
        // Passwords match and meet length requirement, proceed to update
        try {
            // Hash the new password
            $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

            // Prepare the SQL statement to update the password AND the needs_password_change flag
            // Added WHERE needs_password_change = TRUE to ensure we only update if the flag is set
            $stmt = $pdo->prepare("UPDATE users SET password = :password, needs_password_change = FALSE WHERE user_id = :user_id AND needs_password_change = TRUE");
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR); // Bind the HASHED password
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT); // Bind the user ID from the session

            if ($stmt->execute()) {
                // Check if any rows were affected (password and flag were updated)
                if ($stmt->rowCount() > 0) {
                    // Password updated successfully, redirect to the appropriate dashboard
                    $message = '<p class="success">Password changed successfully! Redirecting...</p>'; // Optional: show message before redirect

                    // Determine the correct dashboard based on role
                    $dashboard = '../../'; // Default redirect (root)
                    if ($userRole === 'admin') {
                        $dashboard = '../../admin/';
                    } elseif ($userRole === 'instructor') {
                        $dashboard = '../../instructor/';
                    } elseif ($userRole === 'student') {
                        $dashboard = '../../student/';
                    }

                    // Redirect after a short delay (optional, allows message to be seen)
                    // header('Refresh: 3; URL=' . $dashboard); // Redirect after 3 seconds
                    header('Location: ' . $dashboard); // Immediate redirect
                    exit();

                } else {
                    // This might happen if the needs_password_change flag was already false
                    // or user_id not found (shouldn't happen if the initial check passed)
                    $message = '<p class="error">Error: Could not update password. It seems your password has already been changed or user not found.</p>';
                     error_log("First login password update failed: User ID " . $userId . ", needs_password_change was not TRUE or user not found.");
                }
            } else {
                // Log the specific database error
                error_log("First login password update DB error for user ID " . $userId . ": " . implode(" ", $stmt->errorInfo()));
                $message = '<p class="error">Error updating password. Please try again.</p>';
            }

        } catch (Exception $e) {
            // Catch any exceptions thrown (e.g., from PDO errors)
            error_log("First login password update exception for user ID " . $userId . ": " . $e->getMessage());
            $message = '<p class="error">An unexpected error occurred. Please try again.</p>';
        }
    }
}
// --- End: PHP Logic for Handling Form Submission ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Default Password</title>
    <style>
        /* Basic styling for the form - you should integrate this with your main CSS */
        /* Note: It's generally better to put CSS in a separate .css file */
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f0f0f0; /* Light grey background */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 90%;
            max-width: 500px; /* Adjusted max-width */
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center; /* Center content */
        }

        .form__container {
             width: 100%; /* Make form container take full width of parent */
             display: flex;
             flex-direction: column;
             gap: 15px; /* Increased gap */
             padding: 0; /* Removed padding */
        }

        .form__container h3 {
            font-size: 24px; /* Adjusted font size */
            font-weight: 900;
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 0;
        }

        .input_container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin: 0;
            text-align: left; /* Align labels to the left */
        }

         .input_container label {
             font-weight: bold;
             color: #555;
         }


        .input_container input[type="password"] {
            width: 100%;
            padding: 10px;
            outline: none;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

         /* Style for the submit button */
        .form__container input[type="submit"] {
            display: block;
            width: 100%;
            margin-top: 20px;
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            font-size: 1.1em;
            font-weight: 400;
            background-color: #28a745; /* Green button color */
            color: #ffffff;
            border-radius: 4px;
            transition: background-color 0.2s ease-in-out;
        }

        .form__container input[type="submit"]:hover {
            background-color: #218838; /* Darker green hover color */
        }

        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="form__container">
            <div class="upper__container">
                <h3>Change Default Password</h3>
            </div>

            <?php
            // Display feedback message if any
            if (!empty($message)) {
                echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
            }
            ?>

            <p>Your account was created with a default password. Please change it to continue.</p>

            <form action="../../handle_action.php?action=first_login_update_password" method="POST">
                <div class="input_container">
                    <label for="pass1">Enter New Password:</label>
                    <input type="password" id="pass1" required name="pass1">
                </div>
                <div class="input_container">
                    <label for="pass2">Confirm Password:</label>
                    <input type="password" id="pass2" required name="pass2">
                </div>
                <div class="input_container">
                    <input type="submit" name="change_default_password" value="Change Password">
                </div>
            </form>
        </div>
    </div>

</body>
</html>

<?php
// Closing PHP tag omitted as it's the last block of PHP code.
