<?php
session_start();

$limit = 30;

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    // Guest session handling
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
} else {
    $remaining = $limit;
}

require_once "../config.php";

// Theme preference for logged in users
$theme = ['theme_preference' => 'light'];
if (isset($_SESSION['email'])) {
    $email = mysqli_real_escape_string($conn, $_SESSION['email']);
    $themeResult = mysqli_query($conn, "SELECT theme_preference FROM users WHERE email = '$email'");
    if ($themeResult && mysqli_num_rows($themeResult) > 0) {
        $theme = mysqli_fetch_assoc($themeResult);
    }
}

// Get user info if logged in
$userName = $_SESSION['name'] ?? ($_SESSION['username'] ?? 'Guest');
$isLoggedIn = isset($_SESSION['email']);
$isGuest = !$isLoggedIn;

// Number of circuits and drivers
$circuitsQuery = mysqli_query($conn, "SELECT COUNT(*) FROM circuits");
$driversQuery = mysqli_query($conn, "SELECT COUNT(*) FROM drivers");

$numberCircuits = $circuitsQuery ? mysqli_fetch_row($circuitsQuery)[0] : 0;
$numberDrivers = $driversQuery ? mysqli_fetch_row($driversQuery)[0] : 0;
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
    <title>F1 Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./user.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <button class="nav-btn nav-btn-dashboard">
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
            <strong>Guest Access:</strong> You have <span id="timer"><?= max(0, $remaining) ?></span> seconds remaining. 
            <a href="../index.php" style="color: var(--primary-red);">Login</a> or <a href="../index.php?guest=1" style="color: var(--primary-red);">extend guest session</a> for full access.
        </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1><i class="fas fa-flag-checkered"></i> Welcome, <?= htmlspecialchars($userName) ?>!</h1>
            <p>Your ultimate destination for Formula 1 statistics, race results, and championship standings.</p>
        </div>

        <!-- Stats Section (dynamic from API) -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card">
                <i class="fas fa-spinner fa-pulse"></i>
                <h3>-</h3>
                <p>Current Season</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner fa-pulse"></i>
                <h3>-</h3>
                <p>Total Races</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner fa-pulse"></i>
                <h3>-</h3>
                <p>Circuits</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-spinner fa-pulse"></i>
                <h3>-</h3>
                <p>Drivers</p>
            </div>
        </div>

        <!-- Latest Race Results Preview -->
        <div class="page-title" style="margin-top: 40px;">
            <i class="fas fa-flag-checkered"></i>
            <h1>Latest <span>Race Results</span></h1>
        </div>
        <div id="latestRaceContainer">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-pulse"></i>
                <p>Loading latest race results...</p>
            </div>
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
    
    <script>
        // Guest timer
        <?php if ($isGuest): ?>
        let timeLeft = <?= max(0, $remaining) ?>;
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
        <?php endif; ?>

        async function loadStats() {
            const currentYear = new Date().getFullYear();

            fetch('https://api.jolpi.ca/ergast/f1/current.json')
                .then(res => res.json())
                .then(seasonData => {
                    const races = seasonData.MRData.RaceTable.Races.length;
                    $('#statsContainer').html(`
                        <div class="stat-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>${currentYear}</h3>
                            <p>Current Season</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-flag-checkered"></i>
                            <h3>${races}</h3>
                            <p>Races in ${currentYear}</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-map-marked-alt"></i>
                            <h3><?= $numberCircuits ?></h3>
                            <p>Total Circuits</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <h3><?= $numberDrivers ?></h3>
                            <p>F1 Drivers</p>
                        </div>
                    `);
                })
                .catch(err => {
                    console.error('Error loading stats:', err);
                    $('#statsContainer').html(`
                        <div class="stat-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3><?= date('Y') ?></h3>
                            <p>Current Season</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-flag-checkered"></i>
                            <h3>--</h3>
                            <p>Races</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-map-marked-alt"></i>
                            <h3><?= $numberCircuits ?></h3>
                            <p>Total Circuits</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <h3><?= $numberDrivers ?></h3>
                            <p>F1 Drivers</p>
                        </div>
                    `);
                });
        }
        
        // Load latest race results
        async function loadLatestRace() {
            const currentYear = new Date().getFullYear();
            
            try {
                const res = await fetch(`https://api.jolpi.ca/ergast/f1/current/last/results.json`);
                if (!res.ok) throw new Error('Network response was not ok');
                
                const data = await res.json();
                
                const race = data.MRData.RaceTable.Races[0];
                
                if (!race || !race.Results || race.Results.length === 0) {
                    $('#latestRaceContainer').html(`
                        <div class="race-card">
                            <div class="race-header">
                                <h3>No recent races available</h3>
                            </div>
                            <div class="race-details active" style="padding: 20px; text-align: center;">
                                <p>Check back later for the latest race results.</p>
                            </div>
                        </div>
                    `);
                    return;
                }
                
                const date = new Date(race.date).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });
                
                let resultsHtml = `
                    <div class="race-card">
                        <div class="race-header">
                            <h3>${escapeHtml(race.raceName)}</h3>
                            <div class="race-date">
                                <i class="far fa-calendar-alt"></i> ${date}
                            </div>
                        </div>
                        <div class="race-details active">
                            <div class="race-info">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>${escapeHtml(race.Circuit.circuitName)}</span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-globe"></i>
                                    <span>${escapeHtml(race.Circuit.Location.locality)}, ${escapeHtml(race.Circuit.Location.country)}</span>
                                </div>
                            </div>
                            <div class="results-table-container">
                                <table class="results-table">
                                    <thead>
                                        <tr>
                                            <th>Pos</th>
                                            <th>Driver</th>
                                            <th>Constructor</th>
                                            <th>Time/Status</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                `;
                
                for (let r of race.Results.slice(0, 10)) {
                    let cls = '';
                    if (r.position === '1') cls = 'position-1';
                    else if (r.position === '2') cls = 'position-2';
                    else if (r.position === '3') cls = 'position-3';
                    
                    let timeOrStatus = r.Time ? r.Time.time : (r.status || 'DNF');
                    
                    resultsHtml += `
                        <tr class="${cls}">
                            <td><strong>${escapeHtml(r.position)}</strong></td>
                            <td>${escapeHtml(r.Driver.givenName)} ${escapeHtml(r.Driver.familyName)}</td>
                            <td>${escapeHtml(r.Constructor.name)}</td>
                            <td>${escapeHtml(timeOrStatus)}</td>
                            <td><strong>${escapeHtml(r.points)}</strong></td>
                        </tr>
                    `;
                }
                
                resultsHtml += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#latestRaceContainer').html(resultsHtml);
                
            } catch(e) {
                console.error('Error loading race results:', e);
                $('#latestRaceContainer').html(`
                    <div class="race-card">
                        <div class="race-header">
                            <h3>Unable to load race results</h3>
                        </div>
                        <div class="race-details active" style="padding: 20px; text-align: center;">
                            <p>Please try again later.</p>
                        </div>
                    </div>
                `);
            }
        }
        
        // Helper function to escape HTML
        function escapeHtml(str) {
            if (!str) return '';
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }
        
        $(document).ready(function() {
            loadStats();
            loadLatestRace();
        });
    </script>
</body>

</html>