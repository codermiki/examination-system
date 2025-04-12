<?php
include_once 'config.php';

if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    header('Location: ./');
}

$_SESSION["auth"] = "login";

if (isset($_POST['login_form'])) {
    $_SESSION["auth"] = "login";
}

if (isset($_POST['signup_form'])) {
    $_SESSION["auth"] = "signup";
}

?>

<!-- login handler -->
<?php
$form_error = "";

if (isset($_POST['login'])) {
    if (!empty($_POST['username']) || !empty($_POST['password'])) {
        $form_error = "all required field must fill";
    }
    try {
        include_once "./includes/db/db.config.php";
        $sql = "SELECT name, username, password, role FROM users WHERE username = :username AND password = :password";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":username", $_POST['username']);
        $stmt->bindParam(":password", $_POST['password']);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // create user session if user register successfully
        if ($result) {
            foreach ($result as $user) {
                $_SESSION['username'] = $user['name'];
                $_SESSION['role'] = $user['role'];
            }
        }


        header('Location: ./');
        // redirect page into home
    } catch (Exception $th) {
        echo $th->getMessage();
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <link rel="stylesheet" type="text/css" href="./assets/css/login.css">
    <link rel="stylesheet" href="./assets/css/header.css">
</head>

<body>
    <?php
    include "./includes/layout/header.php";
    ?>
    <div class="container">
        <div class="form__container">
            <div class="upper__container">
                <h1>SIGN IN</h1>
            </div>
            <form action="login.php" method="post">
                <div class="input_container">
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" placeholder="Username">
                </div>

                <div class="input_container">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" placeholder="Password">
                </div>
                <div class="input_container">
                    <input class="btn" type="submit" name="login" value="login">
                </div>
            </form>
        </div>
    </div>
    <?php
    include "./includes/layout/footer.php";
    ?>
</body>

</html>