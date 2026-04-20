<?php
session_start();

$loggedIn = isset($_SESSION['user_id']) || isset($_SESSION['email']);
$userEmail = $_SESSION['email'] ?? '';
$userName = $_SESSION['name'] ?? '';

$message = '';
$error = '';

if (isset($_POST['submit'])) {

    require_once 'config.php';

    $email = trim($_POST['email'] ?? '');
    $text  = trim($_POST['message'] ?? '');

    if (empty($text)) {
        $error = "Please enter your request.";
    } elseif (empty($email)) {
        $error = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    if (empty($error)) {

        $guest = $loggedIn ? 0 : 1;

        $stmt = $conn->prepare("INSERT INTO requests (guest, text, email) VALUES (?, ?, ?)");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iss", $guest, $text, $email);

        if ($stmt->execute()) {
            $message = "Your request has been sent successfully!";
            $_POST = array();
        } else {
            $error = "Error sending request. Please try again.";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contact Administrator | F1 Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login_register.css">
    <link rel="shortcut icon" type="image/x-icon" href="./pictures/flagIcon.png" />
    <style>
        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            transform: translateY(-50%);
            color: var(--primary-red);
            z-index: 1;
        }

        .form-group textarea+i {
            top: 20px;
            transform: none;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group textarea {
            padding-top: 15px;
            resize: vertical;
            min-height: 150px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary-red);
            outline: none;
            box-shadow: 0 0 0 3px rgba(225, 6, 0, 0.1);
        }

        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(225, 6, 0, 0.3);
        }

        .info-box {
            background: var(--header-bg);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid var(--primary-red);
        }

        .info-box i {
            color: var(--primary-red);
            margin-right: 10px;
        }

        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border-left: 4px solid #2ecc71;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #2ecc71;
        }

        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border-left: 4px solid #e74c3c;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: #e74c3c;
        }

        .back {
            align-items: center;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            font-size: 1rem;
            font-weight: 600;
            gap: 8px;
            padding: 12px 24px;
            transition: all 0.3s;
            background-color: var(--gold);
            color: black;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon"><i class="fas fa-flag-checkered"></i></div>
                <div>
                    <div class="logo-text">F1 Dashboard</div>
                </div>
            </a>
            <div class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <div class="toggle-circle"></div>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="login-container" style="max-width: 600px;">
            <div class="form-header">
                <h1>Contact <span>Administrator</span></h1>
                <p>Send us your question or report an issue</p>
            </div>

            <div class="form-content" style="padding: 30px;">
                <!-- Success message -->
                <?php if ($message): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Error message -->
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- Info box -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <?php if ($loggedIn): ?>
                        <strong>Logged in as: <?= htmlspecialchars($userName ?: $userEmail) ?></strong><br>
                        Your email address will be automatically included.
                    <?php else: ?>
                        <strong>You are accessing as a guest.</strong><br>
                        Please enter your email address so we can respond to you.
                    <?php endif; ?>
                </div>

                <!-- Form -->
                <form method="POST">
                    <div class="form-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Your email address"
                            value="<?= htmlspecialchars($loggedIn ? $userEmail : ($_POST['email'] ?? '')) ?>"
                            <?= $loggedIn ? 'readonly' : 'required' ?>>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-comment" style="top:26px;"></i>
                        <textarea name="message" placeholder="Please describe your request or issue in detail..."
                            required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Request
                    </button>
                </form>
                <button onclick="history.back()" class="back">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-text">© 2026 F1 Dashboard | All Rights Reserved</div>
    </footer>

    <script>
        // Dark mode
        const toggle = document.getElementById('themeToggle');
        const body = document.body;

        const savedTheme = localStorage.getItem('f1-theme');
        if (savedTheme === 'dark-theme') {
            body.classList.add('dark-theme');
            toggle.classList.add('dark');
        }

        toggle.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            toggle.classList.toggle('dark');
            localStorage.setItem('f1-theme', body.classList.contains('dark-theme') ? 'dark-theme' : '');
        });
    </script>
</body>

</html>