<?php
include_once 'config.php';

if (isset($_SESSION['email']) && isset($_SESSION['role'])) {
    header('Location: ./');
}

?>

<!-- login handler -->
<?php
$form_error = "";

if (isset($_POST['login'])) {
    if (!empty($_POST['email']) || !empty($_POST['password'])) {
        $form_error = "all required field must fill";
    }
    try {
        include_once "./includes/db/db.config.php";
        $sql = "SELECT user_id, name, email, role FROM users WHERE email = :email AND password = :password";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":email", $_POST['email']);
        $stmt->bindParam(":password", $_POST['password']);

        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // create user session if user register successfully
        if ($result) {
            foreach ($result as $user) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
            }
            // redirect page into home
            header('Location: ./');
        } else {
            $form_error = "Email or Password is incorrect";
        }



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
                <?php
                if ($form_error) {
                    echo "<p class='error-msg'>{$form_error}</p>";
                }
                ?>

                <div class="input_container">
                    <label style="margin-left: 30px;" for="email">Email</label>
                    <input id="email" type="email" name="email" placeholder="Email">
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