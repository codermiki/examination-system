<?php
include_once __DIR__ . '/../../config.php';
include_once __DIR__ . '/../../includes/db/db.config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['email'], $_SESSION['role'], $_SESSION['user_id']) || $_SESSION['role'] !== 'Instructor') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatepass'])) {
    $newPass = $_POST['pass1'] ?? '';
    $confPass = $_POST['pass2'] ?? '';

    // Validate passwords
    if (empty($newPass) || empty($confPass)) {
        $message = '<p class="error">Error: Please fill in both password fields.</p>';
    } elseif ($newPass !== $confPass) {
        $message = '<p class="error">Error: Passwords do not match.</p>';
    } elseif (strlen($newPass) < 8) {
        $message = '<p class="error">Error: Password must be at least 8 characters long.</p>';
    } elseif (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
        $message = '<p class="error">Error: Password must contain at least one uppercase letter, one lowercase letter, and one number.</p>';
    } else {
        try {
            // Hash the password before storing
            $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR); // Changed to PARAM_STR since user_id is varchar

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = '<p class="success">Password changed successfully!</p>';
                    // Clear password fields after successful update
                    $newPass = $confPass = '';
                } else {
                    $message = '<p class="error">Error: Could not update password. User not found.</p>';
                    error_log("Password update failed: User ID " . $userId . " not found.");
                }
            } else {
                error_log("Password update DB error for user ID " . $userId . ": " . implode(" ", $stmt->errorInfo()));
                $message = '<p class="error">Error updating password. Please try again.</p>';
            }
        } catch (PDOException $e) {
            error_log("Password update exception for user ID " . $userId . ": " . $e->getMessage());
            $message = '<p class="error">An unexpected error occurred. Please try again.</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --success: #4bb543;
            --error: #d9534f;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border: #dee2e6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            border: 1px solid var(--border);
            border-radius: 5px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: var(--primary);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: rgba(75, 181, 67, 0.2);
            color: var(--success);
            border: 1px solid rgba(75, 181, 67, 0.3);
        }

        .alert-error {
            background-color: rgba(217, 83, 79, 0.2);
            color: var(--error);
            border: 1px solid rgba(217, 83, 79, 0.3);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: var(--gray);
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Update Password</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert <?= strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success' ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" id="passwordForm">
                    <div class="form-group">
                        <label for="pass1">New Password</label>
                        <input type="password" id="pass1" name="pass1" class="form-control" required minlength="8"
                            value="<?= htmlspecialchars($newPass ?? '') ?>">
                        <div class="password-strength">Must be at least 8 characters with uppercase, lowercase, and
                            number</div>
                    </div>

                    <div class="form-group">
                        <label for="pass2">Confirm Password</label>
                        <input type="password" id="pass2" name="pass2" class="form-control" required minlength="8"
                            value="<?= htmlspecialchars($confPass ?? '') ?>">
                    </div>

                    <button type="submit" name="updatepass" class="btn">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Client-side password validation
        document.getElementById('passwordForm').addEventListener('submit', function (e) {
            const pass1 = document.getElementById('pass1').value;
            const pass2 = document.getElementById('pass2').value;

            if (pass1 !== pass2) {
                alert('Passwords do not match!');
                e.preventDefault();
                return false;
            }

            if (pass1.length < 8) {
                alert('Password must be at least 8 characters long!');
                e.preventDefault();
                return false;
            }

            if (!/[A-Z]/.test(pass1) || !/[a-z]/.test(pass1) || !/[0-9]/.test(pass1)) {
                alert('Password must contain at least one uppercase letter, one lowercase letter, and one number!');
                e.preventDefault();
                return false;
            }

            return true;
        });
    </script>
</body>

</html>