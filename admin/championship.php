<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

// Function to fetch driver standings from API
function fetchDriverStandings($season = null) {
    if (!$season) {
        $season = date('Y');
    }
    
    $url = "https://api.jolpi.ca/ergast/f1/{$season}/driverStandings.json";
    
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
        if (isset($data['MRData']['StandingsTable']['StandingsLists'][0]['DriverStandings'])) {
            return $data['MRData']['StandingsTable']['StandingsLists'][0]['DriverStandings'];
        }
    }
    
    return false;
}

// Function to fetch constructor standings from API
function fetchConstructorStandings($season = null) {
    if (!$season) {
        $season = date('Y');
    }
    
    $url = "https://api.jolpi.ca/ergast/f1/{$season}/constructorStandings.json";
    
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
        if (isset($data['MRData']['StandingsTable']['StandingsLists'][0]['ConstructorStandings'])) {
            return $data['MRData']['StandingsTable']['StandingsLists'][0]['ConstructorStandings'];
        }
    }
    
    return false;
}

// Function to fetch last race results
function fetchLastRaceResults($season = null) {
    if (!$season) {
        $season = date('Y');
    }
    
    $url = "https://api.jolpi.ca/ergast/f1/{$season}/last/results.json";
    
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
            return [
                'race' => $data['MRData']['RaceTable']['Races'][0],
                'results' => $data['MRData']['RaceTable']['Races'][0]['Results']
            ];
        }
    }
    
    return false;
}

// Handle AJAX actions
if (!empty($_POST['what'])) {
    if ($_POST['what'] === 'fetchStandings') {
        $season = isset($_POST['season']) ? (int)$_POST['season'] : date('Y');
        $driverStandings = fetchDriverStandings($season);
        $constructorStandings = fetchConstructorStandings($season);
        
        echo json_encode([
            "success" => true,
            "driverStandings" => $driverStandings,
            "constructorStandings" => $constructorStandings,
            "season" => $season
        ]);
        exit;
    }
}

$currentYear = date('Y');
$availableSeasons = range(1950, $currentYear);
$selectedSeason = isset($_GET['season']) ? (int)$_GET['season'] : $currentYear;

$driverStandings = fetchDriverStandings($selectedSeason);
$constructorStandings = fetchConstructorStandings($selectedSeason);
$lastRace = fetchLastRaceResults($selectedSeason);
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
    <style>
        .standings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .standings-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px var(--shadow-color);
        }
        
        .standings-header {
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            padding: 20px;
            color: white;
        }
        
        .standings-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .standings-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .standings-table-container {
            overflow-x: auto;
            padding: 20px;
        }
        
        .standings-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .standings-table th,
        .standings-table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .standings-table th {
            background-color: var(--header-bg);
            font-weight: 600;
            color: var(--primary-red);
        }
        
        .standings-table tr:hover {
            background-color: rgba(225, 6, 0, 0.05);
        }
        
        .position-1 {
            background-color: rgba(255, 215, 0, 0.15);
            font-weight: bold;
        }
        
        .position-2 {
            background-color: rgba(192, 192, 192, 0.1);
        }
        
        .position-3 {
            background-color: rgba(205, 127, 50, 0.1);
        }
        
        .champion-badge {
            display: inline-block;
            background: var(--gold);
            color: #333;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 8px;
        }
        
        .last-race-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px var(--shadow-color);
            margin-top: 20px;
        }
        
        .last-race-header {
            background: linear-gradient(135deg, var(--gold), var(--dark-gold));
            padding: 20px;
            color: #333;
        }
        
        .last-race-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .last-race-content {
            padding: 20px;
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
        
        .points {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .driver-name {
            font-weight: 600;
        }
        
        .constructor-name {
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .standings-container {
                grid-template-columns: 1fr;
            }
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
                        <h2><i class="fas fa-helmet-safety"></i> Driver's Championship</h2>
                        <p>Season <?= $selectedSeason ?></p>
                    </div>
                    <div class="standings-table-container">
                        <?php if ($driverStandings !== false && !empty($driverStandings)): ?>
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
                                    <?php foreach ($driverStandings as $index => $driver): 
                                        $positionClass = '';
                                        if ($driver['position'] == 1) $positionClass = 'position-1';
                                        elseif ($driver['position'] == 2) $positionClass = 'position-2';
                                        elseif ($driver['position'] == 3) $positionClass = 'position-3';
                                    ?>
                                        <tr class="<?= $positionClass ?>">
                                            <td><strong><?= $driver['position'] ?></strong></td>
                                            <td class="driver-name">
                                                <?= htmlspecialchars($driver['Driver']['givenName'] . ' ' . $driver['Driver']['familyName']) ?>
                                                <?php if ($driver['position'] == 1): ?>
                                                    <span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($driver['Driver']['nationality']) ?></td>
                                            <td><?= htmlspecialchars($driver['Constructors'][0]['name']) ?></td>
                                            <td class="points"><?= $driver['points'] ?></td>
                                            <td><?= $driver['wins'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($driverStandings === false): ?>
                            <div class="loading-spinner">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Failed to load driver standings for <?= $selectedSeason ?>.</p>
                            </div>
                        <?php else: ?>
                            <div class="loading-spinner">
                                <i class="fas fa-chart-line"></i>
                                <p>No driver standings available for <?= $selectedSeason ?>.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Constructor Standings -->
                <div class="standings-card">
                    <div class="standings-header">
                        <h2><i class="fas fa-flag-checkered"></i> Constructor's Championship</h2>
                        <p>Season <?= $selectedSeason ?></p>
                    </div>
                    <div class="standings-table-container">
                        <?php if ($constructorStandings !== false && !empty($constructorStandings)): ?>
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
                                    <?php foreach ($constructorStandings as $index => $constructor): 
                                        $positionClass = '';
                                        if ($constructor['position'] == 1) $positionClass = 'position-1';
                                        elseif ($constructor['position'] == 2) $positionClass = 'position-2';
                                        elseif ($constructor['position'] == 3) $positionClass = 'position-3';
                                    ?>
                                        <tr class="<?= $positionClass ?>">
                                            <td><strong><?= $constructor['position'] ?></strong></td>
                                            <td class="constructor-name">
                                                <?= htmlspecialchars($constructor['Constructor']['name']) ?>
                                                <?php if ($constructor['position'] == 1): ?>
                                                    <span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($constructor['Constructor']['nationality']) ?></td>
                                            <td class="points"><?= $constructor['points'] ?></td>
                                            <td><?= $constructor['wins'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($constructorStandings === false): ?>
                            <div class="loading-spinner">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Failed to load constructor standings for <?= $selectedSeason ?>.</p>
                            </div>
                        <?php else: ?>
                            <div class="loading-spinner">
                                <i class="fas fa-chart-line"></i>
                                <p>No constructor standings available for <?= $selectedSeason ?>.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Last Race Results -->
            <?php if ($lastRace && isset($lastRace['race'])): ?>
                <div class="last-race-card">
                    <div class="last-race-header">
                        <h2><i class="fas fa-flag-checkered"></i> Last Race: <?= htmlspecialchars($lastRace['race']['raceName']) ?></h2>
                        <p><?= date('F j, Y', strtotime($lastRace['race']['date'])) ?> - Round <?= $lastRace['race']['round'] ?></p>
                    </div>
                    <div class="last-race-content">
                        <div class="race-info" style="margin-bottom: 20px;">
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($lastRace['race']['Circuit']['circuitName']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-globe"></i>
                                <span><?= htmlspecialchars($lastRace['race']['Circuit']['Location']['locality'] . ', ' . $lastRace['race']['Circuit']['Location']['country']) ?></span>
                            </div>
                        </div>
                        
                        <?php if (isset($lastRace['results']) && !empty($lastRace['results'])): ?>
                            <div class="results-table-container">
                                <h3>Race Results</h3>
                                <table class="standings-table">
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
                                        <?php foreach (array_slice($lastRace['results'], 0, 10) as $result): 
                                            $positionClass = '';
                                            if ($result['position'] == 1) $positionClass = 'position-1';
                                            elseif ($result['position'] == 2) $positionClass = 'position-2';
                                            elseif ($result['position'] == 3) $positionClass = 'position-3';
                                        ?>
                                            <tr class="<?= $positionClass ?>">
                                                <td><strong><?= $result['position'] ?></strong></td>
                                                <td><?= htmlspecialchars($result['Driver']['givenName'] . ' ' . $result['Driver']['familyName']) ?></td>
                                                <td><?= htmlspecialchars($result['Constructor']['name']) ?></td>
                                                <td><?= $result['Time']['time'] ?? ($result['status'] ?? 'N/A') ?></td>
                                                <td class="points"><?= $result['points'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
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

        // Refresh standings
        $('#refreshStandingsBtn').click(function() {
            const season = $('#seasonSelect').val();
            
            $('#standingsContainer').html('<div class="loading-spinner"><i class="fas fa-spinner fa-pulse"></i> Loading standings...</div>');
            
            $.ajax({
                type: "POST",
                url: "championship.php",
                data: {
                    what: "fetchStandings",
                    season: season
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            let html = '<div class="standings-container">';
                            
                            // Driver Standings
                            html += '<div class="standings-card">';
                            html += '<div class="standings-header">';
                            html += '<h2><i class="fas fa-helmet-safety"></i> Driver\'s Championship</h2>';
                            html += '<p>Season ' + data.season + '</p>';
                            html += '</div><div class="standings-table-container">';
                            
                            if (data.driverStandings && data.driverStandings.length > 0) {
                                html += '<table class="standings-table"><thead><tr>';
                                html += '<th>Pos</th><th>Driver</th><th>Nationality</th><th>Constructor</th><th>Points</th><th>Wins</th>';
                                html += '</tr></thead><tbody>';
                                
                                data.driverStandings.forEach(driver => {
                                    let positionClass = '';
                                    if (driver.position == 1) positionClass = 'position-1';
                                    else if (driver.position == 2) positionClass = 'position-2';
                                    else if (driver.position == 3) positionClass = 'position-3';
                                    
                                    html += `<tr class="${positionClass}">`;
                                    html += `<td><strong>${driver.position}</strong></td>`;
                                    html += `<td class="driver-name">${escapeHtml(driver.Driver.givenName)} ${escapeHtml(driver.Driver.familyName)}`;
                                    if (driver.position == 1) html += `<span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>`;
                                    html += `</td>`;
                                    html += `<td>${escapeHtml(driver.Driver.nationality)}</td>`;
                                    html += `<td>${escapeHtml(driver.Constructors[0].name)}</td>`;
                                    html += `<td class="points">${driver.points}</td>`;
                                    html += `<td>${driver.wins}</td>`;
                                    html += `</tr>`;
                                });
                                
                                html += '</tbody></table>';
                            } else {
                                html += '<div class="loading-spinner"><p>No driver standings available.</p></div>';
                            }
                            
                            html += '</div></div>';
                            
                            // Constructor Standings
                            html += '<div class="standings-card">';
                            html += '<div class="standings-header">';
                            html += '<h2><i class="fas fa-flag-checkered"></i> Constructor\'s Championship</h2>';
                            html += '<p>Season ' + data.season + '</p>';
                            html += '</div><div class="standings-table-container">';
                            
                            if (data.constructorStandings && data.constructorStandings.length > 0) {
                                html += '<table class="standings-table"><thead><tr>';
                                html += '<th>Pos</th><th>Constructor</th><th>Nationality</th><th>Points</th><th>Wins</th>';
                                html += '</tr></thead><tbody>';
                                
                                data.constructorStandings.forEach(constructor => {
                                    let positionClass = '';
                                    if (constructor.position == 1) positionClass = 'position-1';
                                    else if (constructor.position == 2) positionClass = 'position-2';
                                    else if (constructor.position == 3) positionClass = 'position-3';
                                    
                                    html += `<tr class="${positionClass}">`;
                                    html += `<td><strong>${constructor.position}</strong></td>`;
                                    html += `<td class="constructor-name">${escapeHtml(constructor.Constructor.name)}`;
                                    if (constructor.position == 1) html += `<span class="champion-badge"><i class="fas fa-crown"></i> Leader</span>`;
                                    html += `</td>`;
                                    html += `<td>${escapeHtml(constructor.Constructor.nationality)}</td>`;
                                    html += `<td class="points">${constructor.points}</td>`;
                                    html += `<td>${constructor.wins}</td>`;
                                    html += `</tr>`;
                                });
                                
                                html += '</tbody></table>';
                            } else {
                                html += '<div class="loading-spinner"><p>No constructor standings available.</p></div>';
                            }
                            
                            html += '</div></div></div>';
                            
                            $('#standingsContainer').html(html);
                        } else {
                            $('#standingsContainer').html('<div class="loading-spinner"><i class="fas fa-exclamation-triangle"></i><p>Failed to load standings.</p></div>');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        $('#standingsContainer').html('<div class="loading-spinner"><i class="fas fa-exclamation-triangle"></i><p>Error loading standings. Please try again.</p></div>');
                    }
                },
                error: function() {
                    $('#standingsContainer').html('<div class="loading-spinner"><i class="fas fa-exclamation-triangle"></i><p>Error connecting to API. Please try again later.</p></div>');
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
                $('#refreshStandingsBtn').click();
            }
        });
    </script>
</body>
</html>