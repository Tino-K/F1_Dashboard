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
    <title>F1 Dashboard | Races Admin</title>
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
                <button class="nav-btn nav-btn-dashboard">
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

        <div class="page-title">
            <i class="fas fa-stopwatch"></i>
            <h1>Race <span>Management</span></h1>
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
            <button class="refresh-btn" id="refreshRacesBtn">
                <i class="fas fa-sync-alt"></i> Load Races
            </button>
            <span style="opacity: 0.7;">
                <i class="fas fa-info-circle"></i> Data from Ergast API
            </span>
        </div>

        <!-- Races Container -->
        <div id="racesContainer">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-pulse"></i>
                <p>Loading races for <?= $selectedSeason ?> season...</p>
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

        async function loadRaces(season) {

            $('#racesContainer').html(`
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-pulse"></i>
            <p>Loading races for ${season} season...</p>
        </div>
    `);

            try {
                const res = await fetch(`https://api.jolpi.ca/ergast/f1/${season}.json`);
                const data = await res.json();

                const races = data.MRData.RaceTable.Races;

                if (!races || races.length === 0) {
                    $('#racesContainer').html(`
                <div class="no-races">
                    <i class="fas fa-calendar-times" style="font-size: 3rem;"></i>
                    <p>No races found for season ${season}.</p>
                </div>
            `);
                    return;
                }

                displayRaces(season, races);

            } catch (e) {
                $('#racesContainer').html(`
            <div class="loading-spinner">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error loading races. Please try again.</p>
            </div>
        `);
            }
        }


        // DISPLAY (same UI, cleaner)
        function displayRaces(season, races) {

            let html = '';

            for (let race of races) {

                const date = new Date(race.date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                html += `
            <div class="race-card" data-round="${race.round}" data-season="${season}">
                
                <div class="race-header" onclick="toggleRaceDetails(this)">
                    <h3>Round ${race.round}: ${race.raceName}</h3>
                    
                    <div class="race-date">
                        <i class="far fa-calendar-alt"></i>
                        ${date}
                    </div>
                </div>

                <div class="race-details">

                    <div class="race-info">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${race.Circuit.circuitName}</span>
                        </div>

                        <div class="info-item">
                            <i class="fas fa-globe"></i>
                            <span>${race.Circuit.Location.locality}, ${race.Circuit.Location.country}</span>
                        </div>
                    </div>

                    <div class="results-container" id="results-${race.round}">
                        
                        <div class="loading-spinner" style="display: none;">
                            <i class="fas fa-spinner fa-pulse"></i>
                            <p>Loading results...</p>
                        </div>

                        <button class="action-btn" onclick="loadRaceResults(${season}, ${race.round}, this)">
                            <i class="fas fa-trophy"></i> Show Results
                        </button>

                    </div>

                </div>

            </div>
        `;
            }

            $('#racesContainer').html(html);
        }


        // toggle stays same
        function toggleRaceDetails(el) {
            el.nextElementSibling.classList.toggle('active');
        }


        // RESULTS (cleaned but same classes)
        async function loadRaceResults(season, round, button) {

            const container = $(button).closest('.results-container');

            if (container.find('.results-table').length > 0) {
                container.find('.results-table').toggle();
                return;
            }

            container.find('.loading-spinner').show();
            $(button).hide();

            try {
                const res = await fetch(`https://api.jolpi.ca/ergast/f1/${season}/${round}/results.json`);
                const data = await res.json();

                const results = data.MRData.RaceTable.Races[0]?.Results;

                container.find('.loading-spinner').hide();

                if (!results || results.length === 0) {
                    container.html(`
                <div style="padding: 20px; text-align: center; color: var(--text-color);">
                    No results available for this race yet.
                </div>
            `);
                    return;
                }

                let html = `
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

                for (let r of results) {

                    let cls = '';
                    if (r.position === '1') cls = 'position-1';
                    else if (r.position === '2') cls = 'position-2';
                    else if (r.position === '3') cls = 'position-3';

                    html += `
                <tr class="${cls}">
                    <td><strong>${r.position}</strong></td>
                    <td>${r.Driver.givenName} ${r.Driver.familyName} (${r.Driver.code || ''})</td>
                    <td>${r.Constructor.name}</td>
                    <td>${r.Time ? r.Time.time : (r.status || 'DNF')}</td>
                    <td><strong>${r.points}</strong></td>
                </tr>
            `;
                }

                html += `
                    </tbody>
                </table>
            </div>
        `;

                container.append(html);

            } catch (e) {
                container.html(`
            <div style="padding: 20px; text-align: center; color: var(--text-color);">
                Error loading results. Please try again.
            </div>
        `);
            }
        }


        // events
        $('#refreshRacesBtn').click(() => {
            loadRaces($('#seasonSelect').val());
        });

        $('#seasonSelect').keydown(e => {
            if (e.key === "Enter") {
                loadRaces($('#seasonSelect').val());
            }
        });

        $(document).ready(() => {
            loadRaces($('#seasonSelect').val());
        });
    </script>
</body>

</html>