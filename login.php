<?php
include_once 'config.php';

if (isset($_SESSION['email']) && isset($_SESSION['role'])) {
    header('Location: ./');
    exit();
}

$form_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $form_error = "All required fields must be filled.";
    } else {
        try {
            include_once "./includes/db/db.config.php";

            $stmt = $conn->prepare("SELECT user_id, name, email, password, role, must_reset_password FROM users WHERE email = :email");
            $stmt->bindParam(":email", $email);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if (!empty($user['must_reset_password']) && $user['must_reset_password']) {
                    $_SESSION['must_reset_password'] = true;
                    header("Location: reset_password.php");
                    exit();
                }

                header('Location: ./');
                exit();
            } else {
                $form_error = "Email or password is incorrect.";
            }
        } catch (PDOException $e) {
            $form_error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="stylesheet" href="./assets/css/header.css">
</head>

<body>
    <?php include "./includes/layout/header.php"; ?>

    <div class="container">
        <div class="form__container">
            <div class="upper__container">
                <h1>SIGN IN</h1>
            </div>
            <form action="login.php" method="post">
                <?php if (!empty($form_error)): ?>
                    <p class="error-msg"><?= htmlspecialchars($form_error) ?></p>
                <?php endif; ?>

                <div class="input_container">
                    <label style="margin-left: 30px;" for="email">Email</label>
                    <input id="email" type="email" name="email" placeholder="Email" required>
                </div>

                <div class="input_container">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Password" required>
                </div>

                <div class="input_container">
                    <input class="btn" type="submit" name="login" value="Login">
                </div>
            </form>
        </div>
    </div>

    <?php include "./includes/layout/footer.php"; ?>
</body>

</html>