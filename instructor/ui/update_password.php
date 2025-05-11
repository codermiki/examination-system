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
$messageType = ''; // success or error
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updatepass'])) {
    $newPass = $_POST['pass1'] ?? '';
    $confPass = $_POST['pass2'] ?? '';

    // Validate passwords
    if (empty($newPass) || empty($confPass)) {
        $message = 'Please fill in both password fields.';
        $messageType = 'error';
    } elseif ($newPass !== $confPass) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } elseif (strlen($newPass) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $messageType = 'error';
    } elseif (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/[0-9]/', $newPass)) {
        $message = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
        $messageType = 'error';
    } else {
        try {
            // Hash the password before storing
            $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);

            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $message = 'Password changed successfully!';
                    $messageType = 'success';
                    // Clear password fields after successful update
                    $newPass = $confPass = '';
                } else {
                    $message = 'Could not update password. User not found.';
                    $messageType = 'error';
                    error_log("Password update failed: User ID " . $userId . " not found.");
                }
            } else {
                error_log("Password update DB error for user ID " . $userId . ": " . implode(" ", $stmt->errorInfo()));
                $message = 'Error updating password. Please try again.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            error_log("Password update exception for user ID " . $userId . ": " . $e->getMessage());
            $message = 'An unexpected error occurred. Please try again.';
            $messageType = 'error';
        }
    }
}
?>


<!-- <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"> -->

<div class="container">
    <div class="card-updatePass ">
        <div class="card-header-updatePass">
            <h2>Update Password</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <span class="alert-icon"><?= $messageType === 'success' ? '✓' : '✗' ?></span>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" id="passwordForm">
                <div class="form-group">
                    <label for="pass1">New Password</label>
                    <div class="input-group">
                        <input type="password" id="pass1" name="pass1" class="form-control" required
                            minlength="8" value="<?= htmlspecialchars($newPass ?? '') ?>" placeholder="Enter New Password">
                        <span class="toggle-password" onclick="togglePasswordVisibility('pass1')">👁️</span>
                    </div>
                    <div class="password-strength">Must be at least 8 characters with uppercase, lowercase, and number</div>
                </div>

                <div class="form-group">
                    <label for="pass2">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" id="pass2" name="pass2" class="form-control" required
                            minlength="8" value="<?= htmlspecialchars($confPass ?? '') ?>" placeholder="Confirm The Password">
                        <span class="toggle-password" onclick="togglePasswordVisibility('pass2')">👁️</span>
                    </div>
                </div>

                <button type="submit" name="updatepass" class="updatePassbtn">Update Password</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        field.type = field.type === 'password' ? 'text' : 'password';
    }

    // Client-side password validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const pass1 = document.getElementById('pass1').value;
        const pass2 = document.getElementById('pass2').value;
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-error';
        errorDiv.innerHTML = '<span class="alert-icon">✗</span>';

        let isValid = true;

        if (pass1 !== pass2) {
            errorDiv.innerHTML += 'Passwords do not match!';
            isValid = false;
        } else if (pass1.length < 8) {
            errorDiv.innerHTML += 'Password must be at least 8 characters long!';
            isValid = false;
        } else if (!/[A-Z]/.test(pass1) || !/[a-z]/.test(pass1) || !/[0-9]/.test(pass1)) {
            errorDiv.innerHTML += 'Password must contain at least one uppercase letter, one lowercase letter, and one number!';
            isValid = false;
        }

        if (!isValid) {
            // Remove existing error messages
            const existingAlerts = document.querySelectorAll('.alert-error');
            existingAlerts.forEach(alert => alert.remove());

            // Insert new error message
            const cardBody = document.querySelector('.card-body');
            cardBody.insertBefore(errorDiv, cardBody.firstChild);

            e.preventDefault();
            return false;
        }

        return true;
    });

    // Real-time password strength indicator
    document.getElementById('pass1').addEventListener('input', function() {
        const password = this.value;
        const strengthIndicator = document.querySelector('.password-strength');

        if (password.length === 0) {
            strengthIndicator.textContent = 'Must be at least 8 characters with uppercase, lowercase, and number';
            strengthIndicator.style.color = 'var(--gray)';
            return;
        }

        let strength = 0;
        let messages = [];

        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;

        if (password.length < 8) {
            messages.push('at least 8 characters');
        }
        if (!/[A-Z]/.test(password)) {
            messages.push('one uppercase letter');
        }
        if (!/[a-z]/.test(password)) {
            messages.push('one lowercase letter');
        }
        if (!/[0-9]/.test(password)) {
            messages.push('one number');
        }

        if (strength === 4) {
            strengthIndicator.textContent = 'Strong password ✓';
            strengthIndicator.style.color = 'var(--success)';
        } else if (strength >= 2) {
            strengthIndicator.textContent = 'Moderate password (' + messages.join(', ') + ' missing)';
            strengthIndicator.style.color = 'orange';
        } else {
            strengthIndicator.textContent = 'Weak password (' + messages.join(', ') + ' missing)';
            strengthIndicator.style.color = 'var(--error)';
        }
    });
</script>