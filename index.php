<?php
session_start();

if (isset($_GET['guest'])) {
    // Provjeri cookie
    if (isset($_COOKIE['guest_accessed'])) {
        // Već je pristupio danas
        echo "<script>alert('Samo jednom dnevno kao guest!'); window.location.href='index.php';</script>";
        exit();
    }

    // Postavi cookie
    setcookie('guest_accessed', '1', time() + 86400, '/', '', false, true);
    $_SESSION['guest_start'] = time();

    // I cookie i header moraju biti prije outputa
    echo "<script>window.location.href='user/user_page.php';</script>";
    exit();
}

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? ''
];

$activeForm = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

function showError($error)
{
    return !empty($error) ? "<div class='error-message'><i class='fas fa-exclamation-circle'></i> $error</div>" : '';
}

function isActiveForm($formName, $activeForm)
{
    return $formName === $activeForm ? 'active' : '';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>F1 Dashboard | Login / Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login_register.css">
    <link rel="shortcut icon" type="image/x-icon" href="./pictures/flagIcon.png" />
</head>

<body>
    <!-- Header s navigacijom -->
    <header>
        <div class="header-container">
            <a href="#" class="logo">
                <div class="logo-icon">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <div>
                    <div class="logo-text">F1 Dashboard</div>
                    <div class="logo-subtitle">Formula 1 Statistics & Results</div>
                </div>
            </a>

            <div class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <div class="toggle-circle"></div>
            </div>
        </div>
    </header>

    <!-- Glavni sadržaj -->
    <div class="main-container">
        <div class="login-container">
            <!-- Form Header -->
            <div class="form-header">
                <h1>F1 <span>Dashboard</span> Access</h1>
                <p>Login to access all features</p>
            </div>

            <!-- Form Tabs -->
            <div class="form-tabs">
                <button class="tab-button <?= isActiveForm('login', $activeForm) ?>" onclick="showForm('login')">Login</button>
                <button class="tab-button <?= isActiveForm('register', $activeForm) ?>" onclick="showForm('register')">Register</button>
            </div>

            <!-- Form Content -->
            <div class="form-content">
                <!-- LOGIN FORM -->
                <div class="form-box <?= isActiveForm('login', $activeForm) ? 'active' : '' ?>" id="login-form">
                    <form action="loginORregister.php" method="post" id="loginForm">
                        <h2>Login to Your Account</h2>

                        <?= showError($errors['login']) ?>

                        <div class="form-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="Email Address" required>
                        </div>

                        <div class="form-group">
                            <div class="password-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="loginPassword" placeholder="Password" required>
                                <button type="button" class="toggle-password" data-target="loginPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="login" class="submit-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </form>
                </div>

                <!-- REGISTER FORM -->
                <div class="form-box <?= isActiveForm('register', $activeForm) ? 'active' : '' ?>" id="register-form">
                    <form action="loginORregister.php" method="post" id="registerForm">
                        <h2>Create New Account</h2>

                        <?= showError($errors['register']) ?>

                        <div class="form-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" placeholder="Full Name" required>
                        </div>

                        <div class="form-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" placeholder="Email Address" required>
                        </div>

                        <div class="form-group">
                            <div class="password-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" id="registerPassword" placeholder="Password" required
                                    oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="toggle-password" data-target="registerPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength">
                                <span class="strength-text">Strength</span>
                                <div class="strength-bar">
                                    <div class="strength-fill"></div>
                                </div>
                            </div>
                            <div class="password-rules" id="passwordRules">
                                <p>Password must contain:</p>
                                <ul>
                                    <li id="ruleLength"> At least 8 characters</li>
                                    <li id="ruleUppercase"> One uppercase letter</li>
                                    <li id="ruleLowercase"> One lowercase letter</li>
                                    <li id="ruleNumber"> One number</li>
                                </ul>
                            </div>
                        </div>

                        <button type="submit" name="register" class="submit-btn">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </form>
                </div>
            </div>
            <!-- GUEST LINK NA DNU CONTAINERA -->
            <div class="guest-link-bottom">
                <a href="?guest=1" class="guest-link">
                    <i class="fas fa-user-clock"></i>
                    <div class="guest-text">
                        <div>Quick Guest Access</div>
                        <div class="guest-time">30 seconds only</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-text">
            &copy; 2026 F1 Dashboard. All rights reserved.
        </div>
        <a href="./contact.php">
            <button class="contact-btn">
                <i class="fas fa-headset"></i> Something went wrong? Contact the admin for help!
            </button>
        </a>
    </footer>

    <script>
        // Prebacivanje između svijetle i tamne teme
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        // Provjera je li tema već postavljena u localStorage
        const savedTheme = localStorage.getItem('f1-theme');
        if (savedTheme) {
            body.classList.add(savedTheme);
            if (savedTheme === 'dark-theme') {
                themeToggle.classList.add('dark');
            }
        }

        // Dodavanje event listenera za prebacivanje teme
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            themeToggle.classList.toggle('dark');

            // Spremanje postavke teme u localStorage
            if (body.classList.contains('dark-theme')) {
                localStorage.setItem('f1-theme', 'dark-theme');
            } else {
                localStorage.setItem('f1-theme', '');
            }
        });

        // Funkcije za prebacivanje između login i register formi
        function showForm(formType) {
            // Sakrij sve forme
            const forms = document.querySelectorAll('.form-box');
            forms.forEach(form => {
                form.classList.remove('active');
            });

            // Prikaži odabranu formu
            document.getElementById(`${formType}-form`).classList.add('active');

            // Ažuriraj active tab
            const tabs = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.toLowerCase().includes(formType)) {
                    tab.classList.add('active');
                }
            });
        }

        // Funkcija za prikaz/skrivanje lozinke
        document.addEventListener('DOMContentLoaded', function() {
            // Dodaj event listenere na sve toggle gumbove
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Automatski prikaži odgovarajuću formu na temelju PHP varijable
            const activeForm = '<?= $activeForm ?>';
            if (activeForm) {
                showForm(activeForm);
            }
        });

        // Funkcija za provjeru jakosti lozinke (samo za registraciju)
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            const rules = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password)
            };

            // Ažuriraj ikone pravila
            document.getElementById('ruleLength').className = rules.length ? 'rule-valid' : 'rule-invalid';
            document.getElementById('ruleUppercase').className = rules.uppercase ? 'rule-valid' : 'rule-invalid';
            document.getElementById('ruleLowercase').className = rules.lowercase ? 'rule-valid' : 'rule-invalid';
            document.getElementById('ruleNumber').className = rules.number ? 'rule-valid' : 'rule-invalid';

            // Izračunaj jakost lozinke
            let score = 0;
            if (rules.length) score += 25;
            if (rules.uppercase) score += 25;
            if (rules.lowercase) score += 25;
            if (rules.number) score += 25;

            // Postavi klasu jakosti
            strengthBar.className = 'password-strength';
            if (score == 25) {
                strengthBar.classList.add('strength-weak');
            } else if (score == 75 || score == 50) {
                strengthBar.classList.add('strength-medium');
            } else if (score == 100) {
                strengthBar.classList.add('strength-strong');
            }
        }

        checkPasswordStrength('');
    </script>
</body>

</html>