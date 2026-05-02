<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

// Trenutni admin email i tema - with better error handling
$email = $_SESSION['email'] ?? null;

// Only try to get theme if email exists
$theme = ['theme_preference' => 'light']; // Default theme
if ($email) {
    $email_escaped = mysqli_real_escape_string($conn, $email);
    $themeQuery = mysqli_query($conn, "SELECT theme_preference FROM users WHERE email = '$email_escaped'");
    if ($themeQuery && mysqli_num_rows($themeQuery) > 0) {
        $theme = mysqli_fetch_assoc($themeQuery);
    }
}

// Handle POST requests FIRST before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['what'])) {
        // Mark request as taken
        if ($_POST['what'] == 'mark_taken' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $update = mysqli_query($conn, "UPDATE requests SET taken = 'taken' WHERE id = $id");
            echo json_encode(['success' => $update, 'message' => $update ? 'Updated' : 'DB error']);
            exit;
        }

        // Delete request
        if ($_POST['what'] == 'delete_request' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $delete = mysqli_query($conn, "DELETE FROM requests WHERE id = $id");
            echo json_encode(['success' => $delete, 'message' => $delete ? 'Deleted' : 'DB error']);
            exit;
        }
    }
}

// Dohvat svih zahtjeva (JOIN s users tablicom za username)
$requestsQuery = "
    SELECT r.*, u.name AS user_name
    FROM requests r
    LEFT JOIN users u ON r.email = u.email
    ORDER BY r.id DESC
";
$requestsResult = mysqli_query($conn, $requestsQuery);
$totalRequests = $requestsResult ? mysqli_num_rows($requestsResult) : 0;
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
    <title>F1 Dashboard | Requests Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <link rel="stylesheet" href="./admin.css">
</head>
<body>
    <div class="header">
        <div class="logo" onclick="window.location.href='admin_page.php'">
            <div class="logo-icon"><i class="fas fa-flag-checkered"></i></div>
            <div class="logo-text">F1 Dashboard</div>
        </div>
        <div class="user-badge" onclick="window.location.href='../UserEdit/userOptions.php'">
            <i class="fas fa-user-shield"></i>
            <span><?= htmlspecialchars($_SESSION["name"] ?? $_SESSION["username"] ?? 'Admin') ?></span>
        </div>
    </div>

    <div class="container">
        <div class="nav-container">
            <a href="admin_page.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                </button>
            </a>
            <a href="admin_requests.php" class="nav-link">
                <button class="nav-btn nav-btn-dashboard">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Requests</span>
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

        <div class="page-title">
            <i class="fas fa-clipboard-list"></i>
            <h1>Requests <span>Management</span></h1>
        </div>

        <!-- Filter traka -->
        <div class="filter-bar">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Filter by:</label>
                <select id="guestFilter" class="filter-select">
                    <option value="all">All requests</option>
                    <option value="guest">Only Guests</option>
                    <option value="user">Only Registered Users</option>
                </select>
                <select id="statusFilter" class="filter-select">
                    <option value="all">All status</option>
                    <option value="pending">Pending</option>
                    <option value="taken">Taken</option>
                </select>
            </div>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by email or text...">
                <button class="searchBtn"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <!-- Tablica zahtjeva -->
        <div class="table-container">
            <table id="requestsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Text</th>
                        <th>Email</th>
                        <th>User Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php 
                    if ($requestsResult && mysqli_num_rows($requestsResult) > 0):
                        // Reset the result pointer to beginning
                        mysqli_data_seek($requestsResult, 0);
                        while ($row = mysqli_fetch_assoc($requestsResult)): 
                    ?>
                        <tr data-id="<?= $row['id'] ?>" data-guest="<?= $row['Guest'] ?>" data-taken="<?= htmlspecialchars($row['taken'] ?? 'pending') ?>">
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td>
                                <?php if (isset($row['Guest']) && $row['Guest'] == 1): ?>
                                    <span class="guest-badge"><i class="fas fa-user-secret"></i> Guest</span>
                                <?php else: ?>
                                    <span class="user-badge-small"><i class="fas fa-user-check"></i> User</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-cell"><?= htmlspecialchars(substr($row['Text'] ?? '', 0, 100)) . ((isset($row['Text']) && strlen($row['Text']) > 100) ? '...' : '') ?></td>
                            <td class="email-cell"><?= htmlspecialchars($row['email'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['user_name'] ?? 'N/A') ?></td>
                            <td class="status-cell">
                                <?php if (isset($row['taken']) && $row['taken'] == 'taken'): ?>
                                    <span class="status-badge status-taken"><i class="fas fa-check-circle"></i> Taken</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending"><i class="fas fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions-cell">
                                <div class="action-icons">
                                    <?php if (!isset($row['taken']) || $row['taken'] != 'taken'): ?>
                                        <div class="taken-icon" title="Mark as taken" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="taken-icon taken" title="Already taken" style="opacity:0.5; cursor:default;">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="delete-icon" title="Delete request" data-id="<?= $row['id'] ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No requests found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-actions">
            <div style="color: var(--text-color); opacity: 0.8;">
                <i class="fas fa-info-circle"></i>
                <span>Total requests: <span id="totalCount"><?= $totalRequests ?></span></span>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Store all rows data for filtering
            let allRows = [];
            
            // Cache all rows data
            $('#tableBody tr').each(function() {
                const $row = $(this);
                if ($row.data('id')) { // Only process rows with valid data
                    allRows.push({
                        element: $row,
                        isGuest: $row.data('guest') == 1,
                        isTaken: $row.data('taken') === 'taken',
                        email: ($row.find('.email-cell').text() || '').toLowerCase(),
                        text: ($row.find('.text-cell').text() || '').toLowerCase()
                    });
                }
            });
            
            // Funkcija za osvježavanje prikaza (filtar + pretraga)
            function filterTable() {
                const guestFilter = $('#guestFilter').val();
                const statusFilter = $('#statusFilter').val();
                const searchTerm = $('#searchInput').val().toLowerCase();

                let visibleCount = 0;

                allRows.forEach(function(row) {
                    let guestMatch = true;
                    if (guestFilter === 'guest') guestMatch = row.isGuest;
                    if (guestFilter === 'user') guestMatch = !row.isGuest;

                    let statusMatch = true;
                    if (statusFilter === 'pending') statusMatch = !row.isTaken;
                    if (statusFilter === 'taken') statusMatch = row.isTaken;

                    let searchMatch = true;
                    if (searchTerm) {
                        searchMatch = row.email.includes(searchTerm) || row.text.includes(searchTerm);
                    }

                    if (guestMatch && statusMatch && searchMatch) {
                        row.element.show();
                        visibleCount++;
                    } else {
                        row.element.hide();
                    }
                });

                $('#totalCount').text(visibleCount);
            }

            // Refresh all rows cache (for dynamic updates)
            function refreshRowsCache() {
                allRows = [];
                $('#tableBody tr').each(function() {
                    const $row = $(this);
                    if ($row.data('id')) {
                        allRows.push({
                            element: $row,
                            isGuest: $row.data('guest') == 1,
                            isTaken: $row.data('taken') === 'taken',
                            email: ($row.find('.email-cell').text() || '').toLowerCase(),
                            text: ($row.find('.text-cell').text() || '').toLowerCase()
                        });
                    }
                });
            }

            // Event listeneri za filtere
            $('#guestFilter, #statusFilter').on('change', filterTable);
            $('#searchBtn').on('click', filterTable);
            $('#searchInput').on('keyup', function(e) {
                if (e.key === 'Enter') filterTable();
            });

            // Mark as taken (AJAX)
            $(document).on('click', '.taken-icon:not(.taken)', function() {
                const $icon = $(this);
                const requestId = $icon.data('id');
                const $row = $icon.closest('tr');

                if (!requestId) {
                    alert('Invalid request ID');
                    return;
                }

                if (!confirm('Assign this request?')) return;

                $.ajax({
                    type: "POST",
                    url: "admin_requests.php",
                    data: {
                        what: "mark_taken",
                        id: requestId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Ažuriraj status u tablici
                            $row.data('taken', 'taken');
                            const $statusCell = $row.find('.status-cell');
                            $statusCell.html('<span class="status-badge status-taken"><i class="fas fa-check-circle"></i> Taken</span>');
                            // Zamijeni ikonu
                            $icon.replaceWith('<div class="taken-icon taken" title="Already taken" style="opacity:0.5; cursor:default;"><i class="fas fa-check-double"></i></div>');
                            // Refresh cache and filter
                            refreshRowsCache();
                            filterTable();
                        } else {
                            alert('Error: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert('AJAX error while updating status.');
                    }
                });
            });

            // Delete request (AJAX)
            $(document).on('click', '.delete-icon', function() {
                const $icon = $(this);
                const requestId = $icon.data('id');
                const $row = $icon.closest('tr');

                if (!requestId) {
                    alert('Invalid request ID');
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "admin_requests.php",
                    data: {
                        what: "delete_request",
                        id: requestId
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                refreshRowsCache();
                                filterTable();
                            });
                            alert('Request deleted successfully.');
                        } else {
                            alert('Error: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        alert('AJAX error while deleting request.');
                    }
                });
            });
            
            // Initial filter
            filterTable();
        });
    </script>
</body>
</html>