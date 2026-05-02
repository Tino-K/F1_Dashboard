<?php
session_start();

$loggedIn = isset($_SESSION['email']);
$userEmail = $_SESSION['email'] ?? '';
$userName = $_SESSION['name'] ?? '';

$message = '';
$error = '';

if (isset($_POST['submit'])) {

    require_once 'config.php';

    $email = trim($_POST['email'] ?? '');
    $text  = trim($_POST['message'] ?? '');

    // Validation
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
                        <strong>Logged in as: <?= htmlspecialchars($userName) ?></strong><br>
                        Your email address is automatically included.
                    <?php else: ?>
                        <strong>You are accessing as a guest.</strong><br>
                        Please enter your email address so we can respond to you.
                    <?php endif; ?>
                </div>

                <!-- Form -->
                <form method="POST" id="contactForm">
                    <div class="form-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" placeholder="Your email address"
                            value="<?= htmlspecialchars($loggedIn ? $userEmail : ($_POST['email'] ?? '')) ?>" required>
                    </div>

                    <div class="form-group">
                        <i class="fas fa-comment" style="top:26px;"></i>
                        <textarea name="message" id="message" placeholder="Please describe your request or issue in detail..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Request
                    </button>
                </form>
                <button onclick="goBack()" class="back">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-text">© 2026 F1 Dashboard | All Rights Reserved</div>
    </footer>

    <script>
        // Go back
        if (document.referrer && document.referrer !== window.location.href) {
            localStorage.setItem('previousPage', document.referrer);
        }

        function goBack() {
            const previousPage = localStorage.getItem('previousPage');

            if (previousPage && previousPage !== window.location.href) {
                window.location.href = previousPage;
                localStorage.removeItem('previousPage');
            } else if (document.referrer) {
                window.location.href = document.referrer;
            } else {
                window.location.href = 'index.php';
            }
        }
        window.addEventListener('beforeunload', function() {
            const previousPage = localStorage.getItem('previousPage');
            if (!previousPage && document.referrer) {
                localStorage.setItem('previousPage', document.referrer);
            }
        });

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