<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

$currentYear = date('Y');
$availableSeasons = range(1950, $currentYear);
$selectedSeason = isset($_GET['season']) ? (int)$_GET['season'] : $currentYear;
?>
<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('f1-theme');
            if (savedTheme === 'dark-theme') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | Championship Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-flag-checkered"></i>
            </div>
            <div class="logo-text">F1 Dashboard</div>
        </div>
        <div class="user-badge" onclick="window.location.href='../userOptions.php'">
            <i class="fas fa-user-shield"></i>
            <span><?= htmlspecialchars($_SESSION["name"]) ?></span>
        </div>
    </div>

    <div class="container">
        <!-- Navigation -->
        <div class="nav-container">
            <a href="admin_page.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
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
            <span style="opacity: 0.7;">
                <i class="fas fa-info-circle"></i> Data from Ergast API
            </span>
        </div>

        <!-- Standings Container -->
        <div id="standingsContainer">
            <div class="standings-container">
                <!-- Driver Standings -->
                <div class="standings-card">
                    <div class="standings-header">
                        <h2><i class="fas fa-id-badge"></i> Driver's Championship</h2>
                        <p>Loading season <?= $selectedSeason ?>...</p>
                    </div>
                    <div class="standings-table-container">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-pulse"></i>
                            <p>Loading standings from API...</p>
                        </div>
                    </div>
                </div>

                <!-- Constructor Standings -->
                <div class="standings-card">
                    <div class="standings-header">
                        <h2><i class="fas fa-users"></i> Constructor's Championship</h2>
                        <p>Loading season <?= $selectedSeason ?>...</p>
                    </div>
                    <div class="standings-table-container">
                        <div class="loading-spinner">
                            <i class="fas fa-spinner fa-pulse"></i>
                            <p>Loading standings from API...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        const savedTheme = localStorage.getItem('f1-theme');
        if (savedTheme) {
            body.classList.add(savedTheme);
            if (savedTheme === 'dark-theme') {
                themeToggle.classList.add('dark');
            }
        }

        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            themeToggle.classList.toggle('dark');

            if (body.classList.contains('dark-theme')) {
                localStorage.setItem('f1-theme', 'dark-theme');
            } else {
                localStorage.setItem('f1-theme', '');
            }
        });

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

                const drivers = driverData.MRData.StandingsTable.StandingsLists[0].DriverStandings;
                const constructors = constructorData.MRData.StandingsTable.StandingsLists[0].ConstructorStandings;

                displayStandings(season, drivers, constructors);

            } catch (e) {
                $('#standingsContainer').html(`
            <div class="loading-spinner">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error loading data</p>
            </div>
        `);
            }
        }

        function displayStandings(season, drivers, constructors) {

            let html = `<div class="standings-container">`;

            // ===== DRIVERS =====
            html += `
        <div class="standings-card">
            <div class="standings-header">
                <h2><i class="fas fa-id-badge"></i> Driver's Championship</h2>
                <p>Season ${season}</p>
            </div>
            <div class="standings-table-container">
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Driver</th>
                            <th>Nationality</th>
                            <th>Constructor</th>
                            <th>Points</th>
                            <th>Wins</th>
                        </tr>
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
                <td class="driver-name">
                    ${d.Driver.givenName} ${d.Driver.familyName}
                    ${d.position == 1 ? `<span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>` : ''}
                </td>
                <td>${d.Driver.nationality}</td>
                <td>${d.Constructors[0].name}</td>
                <td class="points">${d.points}</td>
                <td>${d.wins}</td>
            </tr>
        `;
            }

            html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

            // ===== CONSTRUCTORS =====
            html += `
        <div class="standings-card">
            <div class="standings-header">
                <h2><i class="fas fa-users"></i> Constructor's Championship</h2>
                <p>Season ${season}</p>
            </div>
            <div class="standings-table-container">
                <table class="standings-table">
                    <thead>
                        <tr>
                            <th>Pos</th>
                            <th>Constructor</th>
                            <th>Nationality</th>
                            <th>Points</th>
                            <th>Wins</th>
                        </tr>
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
                <td class="constructor-name">
                    ${c.Constructor.name}
                    ${c.position == 1 ? `<span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>` : ''}
                </td>
                <td>${c.Constructor.nationality}</td>
                <td class="points">${c.points}</td>
                <td>${c.wins}</td>
            </tr>
        `;
            }

            html += `
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    `;

            $('#standingsContainer').html(html);
        }

        // events
        $('#refreshStandingsBtn').click(() => {
            loadStandings($('#seasonSelect').val());
        });

        $('#seasonSelect').keydown(e => {
            if (e.key === "Enter") {
                loadStandings($('#seasonSelect').val());
            }
        });

        $(document).ready(() => {
            loadStandings($('#seasonSelect').val());
        });
    </script>
</body>

</html>