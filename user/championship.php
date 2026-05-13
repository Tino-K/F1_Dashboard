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

$currentYear = date('Y');
$availableSeasons = range(1950, $currentYear);
$selectedSeason = isset($_GET['season']) ? (int)$_GET['season'] : $currentYear;
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
    <title>F1 Dashboard | Championship Standings</title>
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
                <button class="nav-btn nav-btn-dashboard">
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
            <i class="fas fa-trophy"></i>
            <h1>Championship <span>Standings</span></h1>
        </div>

        <!-- Season Selector -->
        <div class="season-selector">
            <label for="seasonSelect"><strong>Select Season:</strong></label>
            <select id="seasonSelect">
                <?php foreach (array_reverse($availableSeasons) as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $selectedSeason ? 'selected' : '' ?>>
                        <?= $year ?> Formula 1 Season
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="refresh-btn" id="refreshStandingsBtn">
                <i class="fas fa-sync-alt"></i> Load Standings
            </button>
        </div>

        <!-- Standings Container -->
        <div id="standingsContainer">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-pulse"></i>
                <p>Loading standings for <?= $selectedSeason ?> season...</p>
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
        <?php if ($isGuest): ?>
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
        <?php endif; ?>

        async function loadStandings(season) {
            $('#standingsContainer').html(`
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-pulse"></i>
                    <p>Loading standings for ${season} season...</p>
                </div>
            `);

            try {
                const driverRes = await fetch(`https://api.jolpi.ca/ergast/f1/${season}/driverStandings.json`);
                const constructorRes = await fetch(`https://api.jolpi.ca/ergast/f1/${season}/constructorStandings.json`);
                const driverData = await driverRes.json();
                const constructorData = await constructorRes.json();

                const drivers = driverData.MRData.StandingsTable.StandingsLists[0]?.DriverStandings || [];
                const constructors = constructorData.MRData.StandingsTable.StandingsLists[0]?.ConstructorStandings || [];

                displayStandings(season, drivers, constructors);
            } catch (e) {
                $('#standingsContainer').html(`
                    <div class="loading-spinner">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading data. Please try again.</p>
                    </div>
                `);
            }
        }

        function displayStandings(season, drivers, constructors) {
            let html = `<div class="standings-container">`;

            // Driver Standings
            html += `
                <div class="standings-card">
                    <div class="standings-header">
                        <h2><i class="fas fa-id-badge"></i> Driver's Championship</h2>
                        <p>Season ${season}</p>
                    </div>
                    <div class="standings-table-container">
                        <table class="standings-table">
                            <thead>
                                <tr><th>Pos</th><th>Driver</th><th>Nationality</th><th>Constructor</th><th>Points</th><th>Wins</th></tr>
                            </thead>
                            <tbody>
            `;
            for (let d of drivers) {
                let cls = '';
                if (d.position == 1) cls = 'position-1';
                else if (d.position == 2) cls = 'position-2';
                else if (d.position == 3) cls = 'position-3';
                html += `
                    <tr class="${cls}">
                        <td><strong>${d.position}</strong></td>
                        <td class="driver-name">${d.Driver.givenName} ${d.Driver.familyName}${d.position == 1 ? '<span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>' : ''}</td>
                        <td>${d.Driver.nationality}</td>
                        <td>${d.Constructors[0].name}</td>
                        <td class="points">${d.points}</td>
                        <td>${d.wins}</td>
                    </tr>
                `;
            }
            html += `</tbody></table></div></div>`;

            // Constructor Standings
            html += `
                <div class="standings-card">
                    <div class="standings-header">
                        <h2><i class="fas fa-users"></i> Constructor's Championship</h2>
                        <p>Season ${season}</p>
                    </div>
                    <div class="standings-table-container">
                        <table class="standings-table">
                            <thead>
                                <tr><th>Pos</th><th>Constructor</th><th>Nationality</th><th>Points</th><th>Wins</th></tr>
                            </thead>
                            <tbody>
            `;
            for (let c of constructors) {
                let cls = '';
                if (c.position == 1) cls = 'position-1';
                else if (c.position == 2) cls = 'position-2';
                else if (c.position == 3) cls = 'position-3';
                html += `
                    <tr class="${cls}">
                        <td><strong>${c.position}</strong></td>
                        <td class="constructor-name">${c.Constructor.name}${c.position == 1 ? '<span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>' : ''}</td>
                        <td>${c.Constructor.nationality}</td>
                        <td class="points">${c.points}</td>
                        <td>${c.wins}</td>
                    </tr>
                `;
            }
            html += `</tbody></table></div></div></div>`;

            $('#standingsContainer').html(html);
        }

        $('#refreshStandingsBtn').click(() => loadStandings($('#seasonSelect').val()));
        $('#seasonSelect').keydown(e => { if (e.key === "Enter") loadStandings($('#seasonSelect').val()); });
        $(document).ready(() => loadStandings($('#seasonSelect').val()));
    </script>
</body>

</html>