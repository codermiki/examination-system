<header>
    <nav>
        <a href="#">
            <div class="header__logo">
                <div class="logo__container">
                </div>
                <h1>softexam</h1>
            </div>
        </a>
        <div class="header__content">
            <div class="account">
                <button onclick="toggle();" type="button">
                    <?php
                    if (isset($_SESSION['username'])) {
                        switch ($_SESSION['role']) {
                            case 3:
                                echo "Admin";
                                break;
                            case 2:
                                echo "Instructor";
                                break;
                            case 1:
                                echo "Student";
                                break;
                        }
                    } else {
                        echo "Login";
                    }
                    ?>
                </button>
                <div class="user">
                    <div class="username">
                        <?php
                        if (isset($_SESSION['username'])) {
                            echo $_SESSION['username'];
                        } else {
                            echo 'Sign in to your Account';
                        }
                        ?>
                    </div>
                    <hr />
                    <div class="logout">
                        <?php
                        if (isset($_SESSION['username'])) {
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
    </nav>
</header>


<script>
    function toggle() {
        const el = document.querySelector(".user");
        el.classList.toggle("display");
    }
</script>