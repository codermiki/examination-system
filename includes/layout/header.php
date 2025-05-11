<?php
include __DIR__ . "/../../constants.php";
?>

<header>
    <nav>
        <a href="./">
            <div class="header__logo">
            </div>
        </a>
        <div class="header__content">
            <div class="account">
                <button id="toggler" onclick="toggle();" type="button">
                    <?php
                    if (isset($_SESSION['email'])) {
                        switch ($_SESSION['role']) {
                            case 'Admin':
                                echo "Admin";
                                break;
                            case 'Instructor':
                                echo "Instructor";
                                break;
                            case 'Student':
                                echo "Student";
                                break;
                        }
                    } else {
                        echo "Login";
                    }
                    ?>
                    &#65088;
                </button>
                <div id="drop_down" class="drop-down">
                    <div class="user">
                        <div class="email">
                            <?php
                            if (isset($_SESSION['email'])) {
                                echo $_SESSION['email'];
                            } else {
                                echo 'Sign in to your Account';
                            }
                            ?>
                        </div>
                        <hr />
                        <div class="logout">
                            <?php
                            if (isset($_SESSION['email'])) {
                                ?>
                                <form action="./" method="post">
                                    <input class="btn" type="submit" name="logout" id="" value="logout">
                                </form>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>


<script>
    const el = document.querySelector("#drop_down");
    const togglerBtn = document.querySelector("#toggler");
    function toggle() {
        el.classList.toggle("display");
    }
    document.addEventListener("click", function (e) {
        if (!el.contains(e.target) && !togglerBtn.contains(e.target)) {
            el.classList.remove("display");
        }
    });
</script>