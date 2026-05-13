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
    <title>F1 Dashboard | Races</title>
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

        <?php if ($isGuest): ?>
        <div class="timer-warning">
            <i class="fas fa-hourglass-half"></i>
            <strong>Guest Access:</strong> You have <span id="timer"><?= $remaining ?></span> seconds remaining.
        </div>
        <?php endif; ?>

        <div class="page-title">
            <i class="fas fa-stopwatch"></i>
            <h1>Race <span>Schedule & Results</span></h1>
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
        </div>

        <!-- Races Container -->
        <div id="racesContainer">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-pulse"></i>
                <p>Loading races for <?= $selectedSeason ?> season...</p>
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

        function displayRaces(season, races) {
            let html = '';
            for (let race of races) {
                const date = new Date(race.date).toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric'
                });
                html += `
                    <div class="race-card" data-round="${race.round}" data-season="${season}">
                        <div class="race-header" onclick="toggleRaceDetails(this)">
                            <h3>Round ${race.round}: ${race.raceName}</h3>
                            <div class="race-date">
                                <i class="far fa-calendar-alt"></i> ${date}
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

        function toggleRaceDetails(el) {
            el.nextElementSibling.classList.toggle('active');
        }

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

        $('#refreshRacesBtn').click(() => {
            loadRaces($('#seasonSelect').val());
        });
        $('#seasonSelect').keydown(e => {
            if (e.key === "Enter") loadRaces($('#seasonSelect').val());
        });
        $(document).ready(() => {
            loadRaces($('#seasonSelect').val());
        });
    </script>
</body>

</html>