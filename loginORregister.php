    <?php
    session_start();
    require_once "config.php";

    if (isset($_POST['register'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $role = "user";
        $checkEmail = $conn->query("SELECT email FROM users WHERE email = '$email'");
        if ($checkEmail->num_rows > 0) {
            $_SESSION['register_error'] = 'Email is already registered!';
            $_SESSION['active_form'] = 'register';
        } else {
            $conn->query("INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role' )");
        }
        header("Location: index.php");
        exit();
    }

if (isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role']  = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/admin_page.php");
            } else {
                header("Location: user/user_page.php");
            }
            exit();
        }
    }

    $_SESSION['login_error'] = "Incorrect email or password!";
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}

    ?>