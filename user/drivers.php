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

// Fetch drivers with team info
$result = mysqli_query($conn, "SELECT drivers.*, teams.name AS team_name, teams.logo AS team_logo, teams.color
                               FROM drivers 
                               LEFT JOIN teams ON drivers.team_id = teams.id 
                               ORDER BY drivers.surname ASC, drivers.name ASC");
$totalDrivers = mysqli_num_rows($result);
$userName = $_SESSION['name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <script>
        let savedTheme = null;
        <?php if ($isGuest): ?>
            savedTheme = localStorage.getItem("f1-theme");
        <?php else: ?>
            savedTheme = <?= json_encode($theme['theme_preference']); ?>;
        <?php endif; ?>
        if (savedTheme == 'dark') {
            document.documentElement.classList.add('dark-theme');
            document.documentElement.classList.add('dark');
        }
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | Drivers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./user.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>

<body>
    <div class="header">
        <div class="logo">
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
                <i class="fas fa-user-shield"></i>
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
                <button class="nav-btn nav-btn-dashboard">
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
            <i class="fas fa-id-badge"></i>
            <h1>Formula 1 <span>Drivers</span></h1>
        </div>

        <!-- Drivers Grid -->
        <div class="drivers-grid" id="driversGrid">
            <?php
            $driversArray = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $driversArray[] = $row;
            }
            $totalDrivers = count($driversArray);
            $itemsPerPage = 12;
            $totalPages = ceil($totalDrivers / $itemsPerPage);
            ?>

            <?php for ($i = 0; $i < min($itemsPerPage, $totalDrivers); $i++):
                $row = $driversArray[$i];
            ?>
                <div class="driver-card" data-id="<?= $row['id'] ?>">
                    <div class="driver-card-header" style="background: <?= htmlspecialchars($row['color'] ?? '#000000') ?>;"></div>
                    <div class="driver-number">#<?= $row['number'] ?></div>
                    <div class="driver-info">
                        <div class="driver-avatar">
                            <?php if ($row['image']): ?>
                                <img src="<?= htmlspecialchars($row['image']) ?>">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="driver-full-name">
                            <?= htmlspecialchars($row['name'] . ' ' . $row['surname']) ?>
                        </div>
                        <div class="team-badge">
                            <?php if ($row['team_logo']): ?>
                                <img src="<?= htmlspecialchars($row['team_logo']) ?>">
                            <?php else: ?>
                                <i class="fas fa-flag-checkered"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($row['team_name'] ?? 'No Team') ?></span>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <button class="pagination-btn" id="prevBtn">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <div class="pagination-numbers" id="paginationNumbers">
                <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                    <div class="page-number <?= $i === 1 ? 'active' : '' ?>" data-page="<?= $i ?>"><?= $i ?></div>
                <?php endfor; ?>
                <?php if ($totalPages > 5): ?>
                    <span>...</span>
                    <div class="page-number" data-page="<?= $totalPages ?>"><?= $totalPages ?></div>
                <?php endif; ?>
            </div>
            <button class="pagination-btn" id="nextBtn">
                Next <i class="fas fa-chevron-right"></i>
            </button>
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

    <script>
        // Store all drivers data for pagination
        const allDrivers = <?= json_encode($driversArray) ?>;
        const itemsPerPage = 12;
        let currentPage = 1;
        const totalPages = <?= $totalPages ?>;

        function renderDrivers(page) {
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageDrivers = allDrivers.slice(start, end);

            const grid = document.getElementById('driversGrid');
            grid.innerHTML = '';

            pageDrivers.forEach(driver => {
                const card = document.createElement('div');
                card.className = 'driver-card';
                card.setAttribute('data-id', driver.id);

                card.innerHTML = `
                    <div class="driver-card-header" style="background: ${driver.color || '#e10600'};"></div>
                    <div class="driver-number">#${driver.number}</div>
                    <div class="driver-info">
                        <div class="driver-avatar">
                            ${driver.image ? `<img src="${escapeHtml(driver.image)}">` : '<i class="fas fa-user-circle"></i>'}
                        </div>
                        <div class="driver-full-name">${escapeHtml(driver.name + ' ' + driver.surname)}</div>
                        <div class="team-badge">
                            ${driver.team_logo ? `<img src="${escapeHtml(driver.team_logo)}">` : '<i class="fas fa-flag-checkered"></i>'}
                            <span>${escapeHtml(driver.team_name || 'No Team')}</span>
                        </div>
                    </div>
                `;
                grid.appendChild(card);
            });

            updatePaginationButtons(page);
            document.documentElement.scrollTop = 0;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function updatePaginationButtons(page) {
            document.querySelectorAll('.page-number').forEach(btn => {
                btn.classList.remove('active');
                if (parseInt(btn.dataset.page) === page) {
                    btn.classList.add('active');
                }
            });
        }

        function updatePaginationNumbers() {
            const container = document.getElementById('paginationNumbers');
            let html = '';

            if (totalPages <= 7) {
                for (let i = 1; i <= totalPages; i++) {
                    html += `<div class="page-number ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</div>`;
                }
            } else {
                html += `<div class="page-number ${1 === currentPage ? 'active' : ''}" data-page="1">1</div>`;

                if (currentPage > 3) {
                    html += `<span>...</span>`;
                }

                let start = Math.max(2, currentPage - 1);
                let end = Math.min(totalPages - 1, currentPage + 1);

                if (currentPage <= 3) {
                    start = 2;
                    end = 4;
                }
                if (currentPage >= totalPages - 2) {
                    start = totalPages - 3;
                    end = totalPages - 1;
                }

                for (let i = start; i <= end; i++) {
                    html += `<div class="page-number ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</div>`;
                }

                if (currentPage < totalPages - 2) {
                    html += `<span>...</span>`;
                }

                html += `<div class="page-number ${totalPages === currentPage ? 'active' : ''}" data-page="${totalPages}">${totalPages}</div>`;
            }

            container.innerHTML = html;

            document.querySelectorAll('.page-number').forEach(btn => {
                btn.addEventListener('click', () => {
                    currentPage = parseInt(btn.dataset.page);
                    renderDrivers(currentPage);
                    updatePaginationNumbers();
                });
            });
        }

        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                renderDrivers(currentPage);
                updatePaginationNumbers();
            }
        });

        document.getElementById('nextBtn').addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderDrivers(currentPage);
                updatePaginationNumbers();
            }
        });

        // Initialize pagination if totalPages > 1
        if (totalPages > 1) {
            updatePaginationNumbers();
        }
    </script>
</body>

</html>