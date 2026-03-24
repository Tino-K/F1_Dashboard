<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

// Function to fetch races from API
function fetchRacesFromAPI($season = null) {
    if (!$season) {
        $season = date('Y');
    }
    
    $url = "https://api.jolpi.ca/ergast/f1/{$season}.json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'F1 Dashboard Admin Panel');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['MRData']['RaceTable']['Races'])) {
            return $data['MRData']['RaceTable']['Races'];
        }
    }
    
    return false;
}

// Function to fetch race results from API
function fetchRaceResults($season, $round) {
    $url = "https://api.jolpi.ca/ergast/f1/{$season}/{$round}/results.json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'F1 Dashboard Admin Panel');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['MRData']['RaceTable']['Races'][0]['Results'])) {
            return $data['MRData']['RaceTable']['Races'][0]['Results'];
        }
    }
    
    return [];
}

// Handle AJAX actions
if (!empty($_POST['what'])) {
    if ($_POST['what'] === 'fetchRaces') {
        $season = isset($_POST['season']) ? (int)$_POST['season'] : date('Y');
        $races = fetchRacesFromAPI($season);
        
        if ($races !== false) {
            echo json_encode(["success" => true, "races" => $races, "season" => $season]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to fetch races for season {$season}"]);
        }
        exit;
    }
    
    if ($_POST['what'] === 'fetchRaceResults') {
        $season = (int)$_POST['season'];
        $round = (int)$_POST['round'];
        $results = fetchRaceResults($season, $round);
        
        echo json_encode(["success" => true, "results" => $results]);
        exit;
    }
}

$currentYear = date('Y');
$availableSeasons = range(1950, $currentYear);
$selectedSeason = isset($_GET['season']) ? (int)$_GET['season'] : $currentYear;
$races = fetchRacesFromAPI($selectedSeason);
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
    <style>
        .race-card {
            background: var(--card-bg);
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: all 0.3s;
        }
        
        .race-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px var(--shadow-color);
        }
        
        .race-header {
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            padding: 15px 20px;
            color: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .race-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .race-header .race-date {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .race-details {
            padding: 20px;
            display: none;
            border-top: 1px solid var(--border-color);
        }
        
        .race-details.active {
            display: block;
        }
        
        .race-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item i {
            font-size: 1.2rem;
            color: var(--primary-red);
            width: 25px;
        }
        
        .results-table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .results-table th,
        .results-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .results-table th {
            background-color: var(--header-bg);
            font-weight: 600;
            color: var(--primary-red);
        }
        
        .results-table tr:hover {
            background-color: rgba(225, 6, 0, 0.05);
        }
        
        .position-1 {
            background-color: rgba(255, 215, 0, 0.1);
            font-weight: bold;
        }
        
        .position-2 {
            background-color: rgba(192, 192, 192, 0.1);
        }
        
        .position-3 {
            background-color: rgba(205, 127, 50, 0.1);
        }
        
        .season-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .season-selector select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            background: var(--bg-color);
            color: var(--text-color);
            font-size: 1rem;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 40px;
        }
        
        .loading-spinner i {
            font-size: 3rem;
            color: var(--primary-red);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .no-races {
            text-align: center;
            padding: 40px;
            color: var(--text-color);
            opacity: 0.7;
        }
        
        .refresh-btn {
            background: var(--primary-red);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .refresh-btn:hover {
            background: var(--light-red);
            transform: translateY(-2px);
        }
    </style>
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

        <div class="user-info">
            <div class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <div class="toggle-circle"></div>
            </div>
            <div class="admin-badge">
                <i class="fas fa-user-shield"></i>
                <span><?= htmlspecialchars($_SESSION["name"]) ?></span>
            </div>
        </div>
        <button class="action-btn" onclick="window.location.href='../logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
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
            <?php if ($races !== false && !empty($races)): ?>
                <?php foreach ($races as $race): ?>
                    <div class="race-card" data-round="<?= $race['round'] ?>" data-season="<?= $selectedSeason ?>">
                        <div class="race-header" onclick="toggleRaceDetails(this)">
                            <h3>
                                <i class="fas fa-flag-checkered"></i>
                                Round <?= $race['round'] ?>: <?= htmlspecialchars($race['raceName']) ?>
                            </h3>
                            <div class="race-date">
                                <i class="far fa-calendar-alt"></i>
                                <?= date('F j, Y', strtotime($race['date'])) ?>
                            </div>
                        </div>
                        <div class="race-details">
                            <div class="race-info">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($race['Circuit']['circuitName']) ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-globe"></i>
                                    <span><?= htmlspecialchars($race['Circuit']['Location']['locality'] . ', ' . $race['Circuit']['Location']['country']) ?></span>
                                </div>
                                <?php if (isset($race['Circuit']['Location']['lat']) && isset($race['Circuit']['Location']['long'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-location-dot"></i>
                                        <span>Lat: <?= $race['Circuit']['Location']['lat'] ?>, Long: <?= $race['Circuit']['Location']['long'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="results-container" id="results-<?= $race['round'] ?>">
                                <div class="loading-spinner" style="display: none;">
                                    <i class="fas fa-spinner fa-pulse"></i> Loading results...
                                </div>
                                <button class="action-btn" onclick="loadRaceResults(<?= $selectedSeason ?>, <?= $race['round'] ?>, this)">
                                    <i class="fas fa-trophy"></i> Show Results
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($races === false): ?>
                <div class="no-races">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
                    <p>Failed to fetch races for season <?= $selectedSeason ?>. Please try again.</p>
                </div>
            <?php else: ?>
                <div class="no-races">
                    <i class="fas fa-calendar-times" style="font-size: 3rem;"></i>
                    <p>No races found for season <?= $selectedSeason ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Theme toggle
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

        // Toggle race details
        function toggleRaceDetails(element) {
            const details = element.nextElementSibling;
            details.classList.toggle('active');
        }

        // Load race results
        function loadRaceResults(season, round, button) {
            const resultsContainer = $(button).closest('.results-container');
            const loadingSpinner = resultsContainer.find('.loading-spinner');
            
            // Check if results already loaded
            if (resultsContainer.find('.results-table').length > 0) {
                resultsContainer.find('.results-table').toggle();
                return;
            }
            
            loadingSpinner.show();
            $(button).hide();
            
            $.ajax({
                type: "POST",
                url: "races.php",
                data: {
                    what: "fetchRaceResults",
                    season: season,
                    round: round
                },
                success: function(response) {
                    loadingSpinner.hide();
                    try {
                        const data = JSON.parse(response);
                        if (data.success && data.results && data.results.length > 0) {
                            let resultsHtml = '<div class="results-table-container">';
                            resultsHtml += '<table class="results-table">';
                            resultsHtml += '<thead><tr>';
                            resultsHtml += '<th>Pos</th><th>Driver</th><th>Constructor</th><th>Time/Status</th><th>Points</th>';
                            resultsHtml += '</tr></thead><tbody>';
                            
                            data.results.forEach(result => {
                                let positionClass = '';
                                if (result.position === '1') positionClass = 'position-1';
                                else if (result.position === '2') positionClass = 'position-2';
                                else if (result.position === '3') positionClass = 'position-3';
                                
                                resultsHtml += `<tr class="${positionClass}">`;
                                resultsHtml += `<td><strong>${result.position}</strong></td>`;
                                resultsHtml += `<td>${result.Driver.givenName} ${result.Driver.familyName} (${result.Driver.code})</td>`;
                                resultsHtml += `<td>${result.Constructor.name}</td>`;
                                resultsHtml += `<td>${result.Time ? result.Time.time : (result.status || 'DNF')}</td>`;
                                resultsHtml += `<td><strong>${result.points}</strong></td>`;
                                resultsHtml += `</tr>`;
                            });
                            
                            resultsHtml += '</tbody></table></div>';
                            resultsContainer.append(resultsHtml);
                        } else {
                            resultsContainer.html('<div style="padding: 20px; text-align: center; color: var(--text-color);">No results available for this race yet.</div>');
                        }
                    } catch (e) {
                        console.error('Error parsing results:', e);
                        resultsContainer.html('<div style="padding: 20px; text-align: center; color: var(--text-color);">Error loading results.</div>');
                    }
                },
                error: function() {
                    loadingSpinner.hide();
                    resultsContainer.html('<div style="padding: 20px; text-align: center; color: var(--text-color);">Error loading results. Please try again.</div>');
                }
            });
        }

        // Refresh races by season
        $('#refreshRacesBtn').click(function() {
            const season = $('#seasonSelect').val();
            
            $('#racesContainer').html('<div class="loading-spinner"><i class="fas fa-spinner fa-pulse"></i> Loading races...</div>');
            
            $.ajax({
                type: "POST",
                url: "races.php",
                data: {
                    what: "fetchRaces",
                    season: season
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success && data.races && data.races.length > 0) {
                            let racesHtml = '';
                            
                            data.races.forEach(race => {
                                racesHtml += `
                                    <div class="race-card" data-round="${race.round}" data-season="${season}">
                                        <div class="race-header" onclick="toggleRaceDetails(this)">
                                            <h3>
                                                <i class="fas fa-flag-checkered"></i>
                                                Round ${race.round}: ${escapeHtml(race.raceName)}
                                            </h3>
                                            <div class="race-date">
                                                <i class="far fa-calendar-alt"></i>
                                                ${new Date(race.date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                                            </div>
                                        </div>
                                        <div class="race-details">
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
                                            <div class="results-container" id="results-${race.round}">
                                                <div class="loading-spinner" style="display: none;">
                                                    <i class="fas fa-spinner fa-pulse"></i> Loading results...
                                                </div>
                                                <button class="action-btn" onclick="loadRaceResults(${season}, ${race.round}, this)">
                                                    <i class="fas fa-trophy"></i> Show Results
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            $('#racesContainer').html(racesHtml);
                        } else {
                            $('#racesContainer').html(`
                                <div class="no-races">
                                    <i class="fas fa-calendar-times" style="font-size: 3rem;"></i>
                                    <p>No races found for season ${season}.</p>
                                </div>
                            `);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        $('#racesContainer').html(`
                            <div class="no-races">
                                <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
                                <p>Failed to load races. Please try again.</p>
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#racesContainer').html(`
                        <div class="no-races">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
                            <p>Error connecting to API. Please try again later.</p>
                        </div>
                    `);
                }
            });
        });
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Season selector enter key
        $('#seasonSelect').keydown(function(e) {
            if (e.key === 'Enter') {
                $('#refreshRacesBtn').click();
            }
        });
    </script>
</body>
</html>