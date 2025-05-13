<?php
include_once 'config.php';
include_once 'includes/db/db.config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ./");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $message = "Password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("UPDATE users SET password = :password, must_reset_password = FALSE WHERE user_id = :user_id");
            $stmt->execute([
                ':password' => $hashed,
                ':user_id' => $_SESSION['user_id']
            ]);

            unset($_SESSION['must_reset_password']);
            header("Location: ./");
            exit();
        } catch (PDOException $e) {
            $message = "An error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/reset.css">
</head>

<body>
    <div class="reset-container">
        <h2>Reset Your Password</h2>

        <?php if (!empty($message)): ?>
            <div class="error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" id="resetForm">
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>

            <button type="submit">Reset Password</button>
        </form>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function (e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (newPass.length < 6) {
                alert('Password must be at least 6 characters.');
                e.preventDefault();
            } else if (newPass !== confirmPass) {
                alert('Passwords do not match.');
                e.preventDefault();
            }
        });
    </script>
</body>

</html>