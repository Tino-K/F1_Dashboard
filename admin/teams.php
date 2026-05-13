<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

// Theme
$email = $_SESSION['email'];
$theme = mysqli_fetch_assoc(mysqli_query($conn, "SELECT users.theme_preference FROM users WHERE users.email = '$email'"));

// Handle AJAX actions: delete, update, or add
if (!empty($_POST['what'])) {
    header('Content-Type: application/json');
    
    if ($_POST['what'] === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        $check = mysqli_query($conn, "SELECT id FROM drivers WHERE team_id = $id LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(["success" => false, "message" => "Cannot delete team with assigned drivers"]);
            exit;
        }
        
        mysqli_query($conn, "DELETE FROM teams WHERE id = $id");
        echo json_encode(["success" => true]);
        exit;
    }

    if ($_POST['what'] === 'edit' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $logo = mysqli_real_escape_string($conn, $_POST['logo']);
        $car_image = mysqli_real_escape_string($conn, $_POST['car_image']);
        $color = mysqli_real_escape_string($conn, $_POST['color']);

        $update = mysqli_query($conn, "UPDATE teams 
            SET name='$name', logo='$logo', car_image='$car_image', color='$color'
            WHERE id=$id");

        if ($update) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
        }
        exit;
    }

    if ($_POST['what'] === 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $logo = mysqli_real_escape_string($conn, $_POST['logo']);
        $car_image = mysqli_real_escape_string($conn, $_POST['car_image']);
        $color = mysqli_real_escape_string($conn, $_POST['color']);

        $insert = mysqli_query($conn, "INSERT INTO teams (name, logo, car_image, color) 
            VALUES ('$name', '$logo', '$car_image', '$color')");

        if ($insert) {
            $newId = mysqli_insert_id($conn);
            echo json_encode(["success" => true, "id" => $newId]);
        } else {
            echo json_encode(["success" => false, "message" => mysqli_error($conn)]);
        }
        exit;
    }
}

// Fetch all teams ordered by name alphabetically
$result = mysqli_query($conn, "SELECT * FROM teams ORDER BY name ASC");
$totalTeams = mysqli_num_rows($result);

// Get drivers for each team
$driversByTeam = [];
$driversQuery = mysqli_query($conn, "SELECT id, name, surname, team_id, image FROM drivers WHERE team_id IS NOT NULL ORDER BY surname ASC, name ASC");
while ($driver = mysqli_fetch_assoc($driversQuery)) {
    $teamId = $driver['team_id'];
    if (!isset($driversByTeam[$teamId])) {
        $driversByTeam[$teamId] = [];
    }
    $driversByTeam[$teamId][] = $driver;
}
?>
<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <script>
        (function() {
            const savedTheme = <?= json_encode($theme['theme_preference']); ?>;
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | Teams</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <style>
        .team-color-preview {
            width: 40px;
            height: 40px;
            border-radius: 4px;
        }
        .input-color-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .input-color-group {
            width: 60px;
            height: 40px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--bg-secondary);
            cursor: pointer;
        }
        .input-color-group span {
            font-family: monospace;
            font-size: 14px;
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 4px;
            color: var(--text-color);
        }
        .color-cell {
            text-align: center;
            vertical-align: middle;
        }
        .edit_color {
            width: 60px;
            height: 40px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-flag-checkered"></i>
            </div>
            <div class="logo-text">F1 Dashboard</div>
        </div>
        <div class="user-badge" onclick="window.location.href='../UserEdit/userOptions.php'">
            <i class="fas fa-user-shield"></i>
            <span><?= htmlspecialchars($_SESSION["name"]) ?></span>
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
                <button class="nav-btn nav-btn-default">
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

        <div class="page-title">
            <i class="fas fa-users"></i>
            <h1>Team <span>Management</span></h1>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Logo</th>
                        <th>Car Image</th>
                        <th>Color</th>
                        <th>Drivers</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="teamsTableBody">
                    <?php while ($row = mysqli_fetch_assoc($result)):
                        $teamDrivers = isset($driversByTeam[$row['id']]) ? $driversByTeam[$row['id']] : [];
                        $teamColor = !empty($row['color']) ? htmlspecialchars($row['color']) : '#000000';
                    ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td class="team-name">
                                <span><?= htmlspecialchars($row['name']) ?></span>
                            </td>
                            <td class="logo">
                                <?php if ($row['logo']): ?>
                                    <img src="<?= htmlspecialchars($row['logo']) ?>" alt="Team Logo" class="team-logo-preview" style="max-height: 40px; background-color: <?= htmlspecialchars($row['color']) ?>;">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size: 30px; color: var(--border-color);"></i>
                                <?php endif; ?>
                            </td>
                            <td class="car-image">
                                <?php if ($row['car_image']): ?>
                                    <img src="<?= htmlspecialchars($row['car_image']) ?>" alt="Car Image" class="car-image-preview" style="max-height: 40px;">
                                <?php else: ?>
                                    <i class="fas fa-car" style="font-size: 30px; color: var(--border-color);"></i>
                                <?php endif; ?>
                            </td>
                            <td class="color-cell">
                                <div class="team-color-preview" style="background-color: <?= $teamColor ?>;" title="<?= $teamColor ?>"></div>
                            </td>
                            <td class="drivers-list-cell">
                                <div class="drivers-list">
                                    <?php if (empty($teamDrivers)): ?>
                                        <div class="no-drivers">
                                            <i class="fas fa-user-slash"></i> No drivers assigned
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($teamDrivers as $driver): ?>
                                            <div class="driver-item">
                                                <img src="<?= htmlspecialchars($driver['image']) ?>" alt="Driver image" class="driver-image-preview" style="max-height: 30px;">
                                                <div class="driver-name-line">
                                                    <span class="driver-first-name"><?= htmlspecialchars($driver['name']) ?></span>
                                                    <span class="driver-last-name"><?= htmlspecialchars($driver['surname']) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="actions">
                                <div class="action-icons">
                                    <div class="edit-icon" title="Edit team">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="delete-icon" title="Delete team">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-actions">
            <div style="color: var(--text-color); opacity: 0.8;">
                <i class="fas fa-info-circle"></i>
                <span>Total teams: <?= $totalTeams; ?></span>
            </div>
            <div class="add-user">
                <button class="action-btn add-btn" id="addTeamBtn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Team</span>
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="addTeamModal">
        <div class="modal" style="max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Team</h3>
            </div>
            <div class="modal-body" style="width: 100%;">
                <form id="addTeamForm">
                    <div class="form-group">
                        <label for="newTeamName">Team Name</label>
                        <input type="text" id="newTeamName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="newTeamLogo">Team Logo URL</label>
                        <textarea id="newTeamLogo" name="logo" placeholder="https://example.com/logo.png" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="newTeamCarImage">Car Image URL</label>
                        <textarea id="newTeamCarImage" name="car_image" placeholder="https://example.com/car.jpg" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="newTeamColor">Team Color (Hex)</label>
                        <div class="input-color-group">
                            <input type="text" id="newTeamColor" name="color" placeholder="#000000" value="#000000">
                            <div class="team-color-preview" style="width: 40px; height: 40px; background-color: #000000;"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="action-btn" id="cancelAddBtn">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </button>
                <button class="action-btn add-btn" id="confirmAddBtn">
                    <i class="fas fa-check"></i>
                    <span>Add Team</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Live color preview in add modal
        const colorText = document.getElementById('newTeamColor');
        const colorPreview = document.querySelector('#addTeamModal .team-color-preview');

        if (colorText && colorPreview) {
            colorText.addEventListener('input', function() {
                let hex = this.value;
                if (hex.match(/^#[0-9A-Fa-f]{6}$/) || hex.match(/^#[0-9A-Fa-f]{3}$/)) {
                    colorPreview.style.backgroundColor = hex;
                }
            });
        }

        const addTeamBtn = document.getElementById('addTeamBtn');
        const addTeamModal = document.getElementById('addTeamModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const confirmAddBtn = document.getElementById('confirmAddBtn');
        const addTeamForm = document.getElementById('addTeamForm');

        addTeamBtn.addEventListener('click', () => {
            addTeamModal.style.display = 'flex';
            addTeamForm.reset();
            if (colorText) {
                colorText.value = '#000000';
                if (colorPreview) colorPreview.style.backgroundColor = '#000000';
            }
        });

        cancelAddBtn.addEventListener('click', () => {
            addTeamModal.style.display = 'none';
        });

        addTeamModal.addEventListener('click', (e) => {
            if (e.target === addTeamModal) {
                addTeamModal.style.display = 'none';
            }
        });

        confirmAddBtn.addEventListener('click', () => {
            const name = $('#newTeamName').val().trim();
            const logo = $('#newTeamLogo').val().trim();
            const car_image = $('#newTeamCarImage').val().trim();
            const color = $('#newTeamColor').val();

            if (!name || !logo || !car_image || !color) {
                alert('Please fill in all fields');
                return;
            }

            $.ajax({
                type: "POST",
                url: "teams.php",
                data: {
                    what: "add",
                    name: name,
                    logo: logo,
                    car_image: car_image,
                    color: color
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        alert('Team added successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error adding team: ' + xhr.responseText);
                }
            });
        });

        addTeamForm.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddBtn.click();
            }
        });

        // Edit functionality
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");

            if (tr.find(".edit_name").length) return;

            const name = tr.find(".team-name span").text().trim();
            const logo = tr.find(".logo img").attr("src") || '';
            const car_image = tr.find(".car-image img").attr("src") || '';
            const colorHex = tr.find(".color-cell .team-color-preview").attr('title') || '#cccccc';

            tr.find(".team-name").html('<input type="text" class="edit_name" value="' + name.replace(/"/g, '&quot;') + '" style="width:100%;">');
            tr.find(".logo").html('<textarea class="edit_logo" style="width:100%;">' + logo + '</textarea>');
            tr.find(".car-image").html('<textarea class="edit_car_image" style="width:100%;">' + car_image + '</textarea>');
            tr.find(".color-cell").html('<input type="text" class="edit_color" value="' + colorHex + '" style="width:100px;">');

            tr.find(".action-icons").html(`
                <div class="save-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="cancel-icon">
                    <i class="fas fa-times"></i>
                </div>
            `);

            tr.find(".save-icon").off("click").on("click", function() {
                const newName = tr.find(".edit_name").val();
                const newLogo = tr.find(".edit_logo").val();
                const newCarImage = tr.find(".edit_car_image").val();
                const newColor = tr.find(".edit_color").val();

                if (!newName || !newLogo || !newCarImage || !newColor) {
                    alert('All fields are required');
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "teams.php",
                    data: {
                        what: "edit",
                        id: id,
                        name: newName,
                        logo: newLogo,
                        car_image: newCarImage,
                        color: newColor
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('Error saving changes: ' + xhr.responseText);
                    }
                });
            });

            tr.find(".cancel-icon").off("click").on("click", function() {
                location.reload();
            });
        });

        // Delete team
        $(document).on("click", ".delete-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");
            const driverCount = tr.find(".driver-item").length;

            if (driverCount > 0) {
                alert('Cannot delete this team because it has ' + driverCount + ' assigned driver(s).');
                return;
            }

            if (!confirm("Are you sure you want to delete this team?")) return;

            $.ajax({
                type: "POST",
                url: "teams.php",
                data: {
                    id: id,
                    what: "delete"
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        tr.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error deleting team: ' + xhr.responseText);
                }
            });
        });
    </script>
</body>

</html>