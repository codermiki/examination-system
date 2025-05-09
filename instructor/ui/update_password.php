<?php

include_once '../config.php';
include_once '../includes/db/db.config.php'; 


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor' || !isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); 
    exit();
}

$message = ''; // Variable to store feedback messages
$userId = $_SESSION['user_id']; 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatepass'])) {
    $newPass = $_POST['pass1'] ?? '';
    $confPass = $_POST['pass2'] ?? '';


    if (empty($newPass) || empty($confPass)) {
        $message = '<p class="error">Error: Please fill in both password fields.</p>';
    } elseif ($newPass !== $confPass) {
        $message = '<p class="error">Error: Passwords do not match.</p>';
    } elseif (strlen($newPass) < 8) { 
        $message = '<p class="error">Error: Password must be at least 8 characters long.</p>';
    }
    else {

        try {
            // // --- IMPORTANT SECURITY FIX: Hash the password before storing ---
            // // Use password_hash() with a strong algorithm (like PASSWORD_DEFAULT)
            // $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);


            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $stmt->bindParam(':password', $newPass, PDO::PARAM_STR); 
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT); 

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = '<p class="success">Password changed successfully!</p>';
                } else {
                    $message = '<p class="error">Error: Could not update password. User not found.</p>';
                     error_log("Password update failed: User ID " . $userId . " not found.");
                }
            } else {
                error_log("Password update DB error for user ID " . $userId . ": " . implode(" ", $stmt->errorInfo()));
                $message = '<p class="error">Error updating password. Please try again.</p>';
            }

        } catch (Exception $e) {
            error_log("Password update exception for user ID " . $userId . ": " . $e->getMessage());
            $message = '<p class="error">An unexpected error occurred. Please try again.</p>';
        }
    }
}

?>

<style>
    .container {
        height: 90vh; /* Consider if this fixed height is appropriate */
        width: 80%; /* Consider responsive units like percentages or max-width */
        max-width: 800px;
        margin: 20px auto;
        padding: 10px;
        background-color: #f9f9f9; /* Changed to match other forms */
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        font-family: Arial, Helvetica, sans-serif;
        display: flex; /* Use flexbox for centering content */
        flex-direction: column;
        align-items: center; /* Center horizontally */
        justify-content: flex-start; /* Align items to the top */
    }

    .form__container {
        width: 100%;
        max-width: 450px;
        border-radius: 15px; /* Consistent border-radius */
        display: flex;
        flex-direction: column;
        gap: 10px;
        background-color: #ffffff;
        padding: 20px; /* Added padding */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Added shadow */
    }

    .form__container h3 {
        font-size: 30px;
        font-weight: 900;
        text-align: center;
        color: #333; /* Darker color for heading */
        margin-bottom: 20px; /* Added space below heading */
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 15px; /* Increased gap */
        padding: 0; /* Removed padding from form itself */
    }

    .input_container {
        display: flex;
        flex-direction: column; /* Stack label and input */
        gap: 5px; /* Space between label and input */
        margin: 0; /* Removed margin */
    }

     .input_container label {
         font-weight: bold;
         color: #555;
     }


    .input_container input[type="password"] {
        width: 100%; /* Make input take full width */
        padding: 10px;
        outline: none;
        border: 1px solid #ccc; /* Added border */
        border-radius: 4px; /* Added border-radius */
        box-sizing: border-box; /* Include padding in width */
    }

     /* Style for the submit button */
    .form__container input[type="submit"] {
        display: block; /* Make button full width */
        width: 100%;
        margin-top: 20px; /* Space above button */
        padding: 12px 20px; /* Increased padding */
        cursor: pointer;
        border: none;
        font-size: 1.1em; /* Increased font size */
        font-weight: 400;
        background-color: #007bff; /* Primary button color */
        border-radius: 4px; /* Consistent border-radius */
        color: #ffffff;
        transition: background-color 0.2s ease-in-out;
    }

    .form__container input[type="submit"]:hover {
        background-color: #0056b3; /* Darker hover color */
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

<div class="container">
    <div class="form__container">
        <div class="upper__container">
            <h3>Update Password</h3>
        </div>

        <?php
        // Display feedback message if any
        if (!empty($message)) {
            echo '<div class="message ' . (strpos($message, 'Error') !== false ? 'error' : 'success') . '">' . $message . '</div>';
        }
        ?>

        <form action="index.php?page=update_password" method="POST">
            <div class="input_container">
                <label for="pass1">Enter New Password:</label>
                <input type="password" id="pass1" required name="pass1">
            </div>
            <div class="input_container">
                <label for="pass2">Confirm Password:</label>
                <input type="password" id="pass2" required name="pass2">
            </div>
            <div class="input_container">
                <input type="submit" name="updatepass" value="Save Changes">
            </div>
        </form>
    </div>
</div>

<?php
// Closing PHP tag omitted as it's the last block of PHP code.
