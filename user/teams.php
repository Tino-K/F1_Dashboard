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

// Theme preference
$theme = ['theme_preference' => 'light'];
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $themeResult = mysqli_query($conn, "SELECT theme_preference FROM users WHERE email = '$email'");
    if ($themeResult && mysqli_num_rows($themeResult) > 0) {
        $theme = mysqli_fetch_assoc($themeResult);
    }
}

// Fetch teams with driver counts
$result = mysqli_query($conn, "SELECT t.*, COUNT(d.id) as driver_count
                               FROM teams t
                               LEFT JOIN drivers d ON t.id = d.team_id
                               GROUP BY t.id
                               ORDER BY t.name ASC");

if (!$result) {
    die("Database query failed: " . mysqli_error($conn));
}

$totalTeams = mysqli_num_rows($result);
$userName = $_SESSION['name'] ?? 'Guest';

// Get drivers for each team
$driversByTeam = [];
$driversQuery = mysqli_query($conn, "SELECT * FROM drivers WHERE team_id IS NOT NULL ORDER BY number ASC");

if ($driversQuery) {
    while ($driver = mysqli_fetch_assoc($driversQuery)) {
        $teamId = $driver['team_id'];
        if (!isset($driversByTeam[$teamId])) {
            $driversByTeam[$teamId] = [];
        }
        $driversByTeam[$teamId][] = $driver;
    }
}

// Team colors and branding - Fetch colors from teams table
$teamColors = [];
$colorsQuery = mysqli_query($conn, "SELECT id, color FROM teams");
if ($colorsQuery) {
    while ($colorRow = mysqli_fetch_assoc($colorsQuery)) {
        $teamColors[$colorRow['id']] = $colorRow['color'];
    }
}
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
    <title>F1 Dashboard | Teams</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./user.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        /* Teams Grid - Official F1 Style */
        .teams-grid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px 60px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 40px;
        }

        /* Team Card - Official F1 Card Design */
        .team-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .team-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(#000000, 0.15);
        }

        /* Car Image Section */
        .car-section {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: var(--bg-tertiary);
        }

        .team-car-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
        }

        .team-card:hover .team-car-image {
            transform: scale(1.05);
        }

        .car-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.7) 100%);
        }

        /* Team Info Section */
        .team-info-section {
            padding: 24px;
        }

        .team-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
        }

        .team-logo-wrapper {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            transition: all 0.3s ease;
            background: var(--bg-tertiary);
        }

        .team-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .team-name-wrapper {
            flex: 1;
        }

        .team-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }

        .engine-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            padding: 4px 10px;
            background: var(--bg-tertiary);
            border-radius: 20px;
            color: var(--text-secondary);
        }

        .engine-badge i {
            font-size: 0.7rem;
        }

        /* Drivers Section - Official F1 Style */
        .drivers-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-light);
        }

        .drivers-title span:first-child {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 1px;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .driver-count {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .drivers-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .driver-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .driver-item:hover {
            background: var(--bg-primary);
            transform: translateX(4px);
        }

        .driver-image {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
        }

        .driver-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-red), var(--gold));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .driver-placeholder i {
            font-size: 24px;
            color: white;
        }

        .driver-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .driver-number {
            font-size: 0.7rem;
            color: var(--primary-red);
            font-weight: 700;
            margin-left: 6px;
        }

        .empty-drivers {
            text-align: center;
            padding: 32px;
            background: var(--bg-tertiary);
            border-radius: 12px;
            color: var(--text-secondary);
        }

        .empty-drivers i {
            font-size: 32px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Image Modal */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .image-modal.active {
            display: flex;
        }

        .modal-image {
            max-width: 90vw;
            max-height: 90vh;
            object-fit: contain;
            border-radius: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .teams-grid {
                grid-template-columns: 1fr;
                gap: 24px;
                padding: 0 16px 40px;
            }

            .team-header {
                flex-direction: column;
                text-align: center;
            }

            .team-name-wrapper {
                text-align: center;
            }
        }
    </style>
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
                <i class="fas fa-user-clock"></i>
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
                <button class="nav-btn nav-btn-default">
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
                <button class="nav-btn nav-btn-dashboard">
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
            <i class="fas fa-users"></i>
            <h1>Formula 1 <span>Teams</span></h1>
        </div>

        <!-- Teams Grid -->
        <div class="teams-grid">
            <?php
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)):
                $teamDrivers = isset($driversByTeam[$row['id']]) ? $driversByTeam[$row['id']] : [];
                $teamColor = isset($teamColors[$row['id']]) ? $teamColors[$row['id']] : '#000000';
            ?>
                <div class="team-card">
                    <div class="car-section">
                        <?php if (!empty($row['car_image'])): ?>
                            <img src="<?= htmlspecialchars($row['car_image']) ?>" alt="<?= htmlspecialchars($row['name']) ?> F1 Car" class="team-car-image">
                        <?php else: ?>
                            <div style="height: 100%; background: linear-gradient(135deg, <?= $teamColor ?>, <?= $teamColor ?>80); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-car" style="font-size: 64px; color: white; opacity: 0.8;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="car-overlay"></div>
                    </div>

                    <!-- Team Info -->
                    <div class="team-info-section">
                        <div class="team-header">
                            <div class="team-logo-wrapper" style="background: <?= $teamColor ?>50;">
                                <?php if (!empty($row['logo'])): ?>
                                    <img src="<?= htmlspecialchars($row['logo']) ?>" alt="<?= htmlspecialchars($row['name']) ?> Logo" class="team-logo">
                                <?php else: ?>
                                    <i class="fas fa-flag-checkered" style="font-size: 32px; color: <?= $teamColor ?>;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="team-name-wrapper">
                                <h2 class="team-name"><?= htmlspecialchars($row['name']) ?></h2>
                            </div>
                        </div>

                        <!-- Drivers Section -->
                        <div class="drivers-title">
                            <span> <i class="fas fa-id-badge"></i>
                                </i> Drivers</span>
                        </div>

                        <div class="drivers-list">
                            <?php if (empty($teamDrivers)): ?>
                                <div class="empty-drivers">
                                    <i class="fas fa-user-slash"></i>
                                    <p>Driver lineup TBA</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($teamDrivers as $driver): ?>
                                    <div class="driver-item">
                                        <?php if (!empty($driver['image'])): ?>
                                            <img src="<?= htmlspecialchars($driver['image']) ?>" alt="<?= htmlspecialchars($driver['name'] . ' ' . $driver['surname']) ?>" class="driver-image">
                                        <?php else: ?>
                                            <div class="driver-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="driver-name">
                                            <?= htmlspecialchars($driver['name'] . ' ' . $driver['surname']) ?>
                                            <span class="driver-number">#<?= $driver['number'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
                if (timeLeft > 0) {
                    timeLeft--;
                    document.getElementById("timer").textContent = timeLeft;
                }
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