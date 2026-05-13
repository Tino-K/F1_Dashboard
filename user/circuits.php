<?php
session_start();

if (!isset($_SESSION['email']) && !isset($_SESSION['guest_start'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

$limit = 30;
$isGuest = !isset($_SESSION['email']);

if ($isGuest) {
    if (!isset($_SESSION['guest_start'])) {
        $_SESSION['guest_start'] = time();
        $remaining = $limit;
    } else {
        $elapsed = time() - $_SESSION['guest_start'];
        $remaining = $limit - $elapsed;
    }
    if ($remaining <= 0) {
        unset($_SESSION['guest_start']);
        header("Location: ../index.php");
        exit();
    }
}

// Theme preference for logged in users
$theme = ['theme_preference' => 'light'];
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $themeResult = mysqli_query($conn, "SELECT theme_preference FROM users WHERE email = '$email'");
    if ($themeResult && mysqli_num_rows($themeResult) > 0) {
        $theme = mysqli_fetch_assoc($themeResult);
    }
}

$result = mysqli_query($conn, "SELECT * FROM circuits ORDER BY name ASC");
$totalCircuits = mysqli_num_rows($result);
$userName = $_SESSION['name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <script>
        (function() {
            const savedTheme = <?= json_encode($theme['theme_preference'] ?? 'light'); ?>;
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | Circuits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./user.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body>
    <div class="header">
        <div class="logo" onclick="window.location.href='user_page.php'">
            <div class="logo-icon">
                <i class="fas fa-flag-checkered"></i>
            </div>
            <div class="logo-text">F1 Dashboard</div>
        </div>
        <?php if ($isGuest): ?>
        <div class="user-badge">
            <i class="fas fa-user"></i>
            <span><?= htmlspecialchars($userName) ?></span>
        </div>
        <?php else: ?>
        <div class="user-badge" onclick="window.location.href='../UserEdit/userOptions.php'">
            <i class="fas fa-user"></i>
            <span><?= htmlspecialchars($userName) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <!-- Navigation -->
        <div class="nav-container">
            <a href="user_page.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </button>
            </a>
            <a href="circuits.php" class="nav-link">
                <button class="nav-btn nav-btn-dashboard">
                    <i class="fas fa-medal nav-icon-red"></i>
                    <span>Circuits</span>
                </button>
            </a>
            <a href="drivers.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-id-badge nav-icon-red"></i>
                    <span>Drivers</span>
                </button>
            </a>
            <a href="teams.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-users nav-icon-red"></i>
                    <span>Teams</span>
                </button>
            </a>
            <a href="races.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-stopwatch nav-icon-red"></i>
                    <span>Races</span>
                </button>
            </a>
            <a href="championship.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-trophy nav-icon-gold"></i>
                    <span>Championship</span>
                </button>
            </a>
        </div>

        <?php if ($isGuest): ?>
            <div class="timer-warning">
                <i class="fas fa-hourglass-half"></i>
                <strong>Guest Access:</strong> You have <span id="timer"><?= $remaining ?></span> seconds remaining.
            </div>
        <?php endif; ?>

        <div class="page-title">
            <i class="fas fa-medal"></i>
            <h1>Formula 1 <span>Circuits</span></h1>
        </div>

        <!-- Circuits Grid -->
        <div class="circuits-grid">
            <?php
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="circuit-card">
                    <div class="map-section">
                        <img src="<?= htmlspecialchars($row['circuitMapUrl']) ?>" alt="<?= htmlspecialchars($row['name']) ?> Circuit Map" class="circuit-map-image">
                        <div class="map-overlay"></div>
                        <div class="circuit-location-badge">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= htmlspecialchars($row['country']) ?></span>
                        </div>
                    </div>

                    <!-- Circuit Info -->
                    <div class="circuit-info-section">
                        <div class="circuit-header">
                            <h2 class="circuit-name"><?= htmlspecialchars($row['name']) ?></h2>
                            <div class="circuit-country">
                                <i class="fas fa-flag"></i>
                                <span><?= htmlspecialchars($row['country']) ?></span>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-road"></i>
                                    <span>Length</span>
                                </div>
                                <div class="stat-value"><?= $row['lengthKM'] ?> <span style="font-size: 0.7rem;">km</span></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>First GP</span>
                                </div>
                                <div class="stat-value"><?= $row['firstGP'] ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-flag-checkered"></i>
                                    <span>Laps</span>
                                </div>
                                <div class="stat-value"><?= number_format($row['numberOfLaps']) ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Race Distance</span>
                                </div>
                                <div class="stat-value"><?= $row['raceDistance'] ?> <span style="font-size: 0.7rem;">km</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-logo">
                <i class="fas fa-flag-checkered"></i>
                <span>F1 Dashboard</span>
            </div>
            <p class="footer-description">
                Your ultimate destination for Formula 1 statistics, race results,
                and championship standings. Stay updated with the latest F1 action.
            </p>
            <div class="footer-copyright">
                <p>&copy; <?= date('Y') ?> F1 Dashboard. All rights reserved.</p>
                <p class="footer-credit">Unofficial Formula 1 Fan Project</p>
            </div>
        </div>
    </footer>

    <?php if ($isGuest): ?>
        <script>
            let timeLeft = <?= $remaining ?>;
            const timer = setInterval(() => {
                timeLeft--;
                document.getElementById("timer").textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    alert('Your guest session has expired. Please login to continue.');
                    window.location.href = "../index.php";
                }
            }, 1000);
        </script>
    <?php endif; ?>
</body>

</html>