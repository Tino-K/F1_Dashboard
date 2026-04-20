<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

//Theme
$email=$_SESSION['email'];
$theme=mysqli_fetch_assoc(mysqli_query($conn,"SELECT users.theme_preference FROM users WHERE users.email = '$email'"));

// Handle AJAX actions: delete, update, or add
if (!empty($_POST['what'])) {
    if ($_POST['what'] === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // First check if team has any drivers
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

        $update = mysqli_query($conn, "UPDATE teams 
            SET name='$name', logo='$logo', car_image='$car_image'
            WHERE id=$id");

        if ($update) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed"]);
        }
        exit;
    }

    if ($_POST['what'] === 'add') {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $logo = mysqli_real_escape_string($conn, $_POST['logo']);
        $car_image = mysqli_real_escape_string($conn, $_POST['car_image']);

        $insert = mysqli_query($conn, "INSERT INTO teams (name, logo, car_image) 
            VALUES ('$name', '$logo', '$car_image')");

        if ($insert) {
            $newId = mysqli_insert_id($conn);
            echo json_encode(["success" => true, "id" => $newId]);
        } else {
            echo json_encode(["success" => false, "message" => "Insert failed"]);
        }
        exit;
    }
}

// Fetch all teams ordered by name alphabetically
$result = mysqli_query($conn, "SELECT * FROM teams ORDER BY name ASC");
$totalTeams = mysqli_num_rows($result);

// Get drivers for each team - fetch all drivers and organize by team
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
        // Theme
        (function() {
            const savedTheme = <?= json_encode($theme['theme_preference']); ?>;
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | Teams Admin</title>
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
        <div class="user-badge" onclick="window.location.href='../UserEdit/userOptions.php'">
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

        <!-- Teams Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Logo</th>
                        <th>Car Image</th>
                        <th>Drivers</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="teamsTableBody">
                    <?php while ($row = mysqli_fetch_assoc($result)): 
                        $teamDrivers = isset($driversByTeam[$row['id']]) ? $driversByTeam[$row['id']] : [];
                    ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td class="team-name">  
                                    <span><?= htmlspecialchars($row['name']) ?></span>
                                </div>
                            </td>
                            <td class="logo">
                                <?php if ($row['logo']): ?>
                                    <img src="<?= htmlspecialchars($row['logo']) ?>" alt="Team Logo" class="team-logo-preview">
                                <?php else: ?>
                                    <i class="fas fa-image" style="font-size: 30px; color: var(--border-color);"></i>
                                <?php endif; ?>
                            </td>
                            <td class="car-image">
                                <?php if ($row['car_image']): ?>
                                    <img src="<?= htmlspecialchars($row['car_image']) ?>" alt="Car Image" class="car-image-preview">
                                <?php else: ?>
                                    <i class="fas fa-car" style="font-size: 30px; color: var(--border-color);"></i>
                                <?php endif; ?>
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
                                                <img src="<?= htmlspecialchars($driver['image']) ?>" alt="Driver image" class="driver-image-preview">
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

        <!-- Footer Actions -->
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

    <!-- Modal za dodavanje novog tima -->
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
        // Modal za dodavanje tima
        const addTeamBtn = document.getElementById('addTeamBtn');
        const addTeamModal = document.getElementById('addTeamModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const confirmAddBtn = document.getElementById('confirmAddBtn');
        const addTeamForm = document.getElementById('addTeamForm');

        addTeamBtn.addEventListener('click', () => {
            addTeamModal.style.display = 'flex';
            addTeamForm.reset();
            $(".modal").scrollTop(0);
        });

        cancelAddBtn.addEventListener('click', () => {
            addTeamModal.style.display = 'none';
        });

        addTeamModal.addEventListener('click', (e) => {
            if (e.target === addTeamModal) {
                addTeamModal.style.display = 'none';
            }
        });

        // Dodavanje novog tima
        confirmAddBtn.addEventListener('click', () => {
            const name = $('#newTeamName').val().trim();
            const logo = $('#newTeamLogo').val().trim();
            const car_image = $('#newTeamCarImage').val().trim();

            if (!name || !logo || !car_image) {
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
                    car_image: car_image
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            alert('Team added successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        alert('Team added successfully!');
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error adding team');
                }
            });
        });

        // Enter u formi za dodavanje
        addTeamForm.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddBtn.click();
            }
        });

        // Edit team
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");

            // Check if already in edit mode
            if (tr.find("input").length) return;

            // Store original values
            const name = tr.find(".team-name span").text().trim();
            const logo = tr.find(".logo img").attr("src") || '';
            const car_image = tr.find(".car-image img").attr("src") || '';

            // Replace cells with inputs
            tr.find(".team-name").html(`
                <input type="text" class="edit_name" value="${name}" placeholder="Team name" style="width:100%;">
            `);
            
            tr.find(".logo").html(`
                <textarea class="edit_logo" style="width:100%;" placeholder="Logo URL">${logo}</textarea>
            `);
            
            tr.find(".car-image").html(`
                <textarea class="edit_car_image" style="width:100%;" placeholder="Car image URL">${car_image}</textarea>
            `);
            
            // Keep drivers list as is but make it readonly during edit
            // No changes needed to drivers cell during edit

            // Store original icons
            const originalIcons = tr.find(".action-icons").html();

            // Replace icons with Save/Cancel
            tr.find(".action-icons").html(`
                <div class="save-icon" title="Save changes">
                    <i class="fas fa-check"></i>
                </div>
                <div class="cancel-icon" title="Cancel">
                    <i class="fas fa-times"></i>
                </div>
            `);

            // Save function
            tr.find(".save-icon").on("click", function() {
                saveTeamChanges(tr, id);
            });

            // Cancel function
            tr.find(".cancel-icon").on("click", function() {
                location.reload(); // Simple reload to restore original view
            });

            // Enter for saving
            tr.find("input, textarea").on("keydown", function(e) {
                if (e.key === "Enter") {
                    saveTeamChanges(tr, id);
                }
            });
        });

        function saveTeamChanges(tr, id) {
            const name = tr.find(".edit_name").val();
            const logo = tr.find(".edit_logo").val();
            const car_image = tr.find(".edit_car_image").val();

            if (!name || !logo || !car_image) {
                alert('All fields are required');
                return;
            }

            $.ajax({
                type: "POST",
                url: "teams.php",
                data: {
                    what: "edit",
                    id: id,
                    name: name,
                    logo: logo,
                    car_image: car_image
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            location.reload(); // Reload to show in correct alphabetical order
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        alert('Changes saved successfully!');
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error saving changes');
                }
            });
        }

        // Delete team
        $(document).on("click", ".delete-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");
            const driverCount = tr.find(".driver-item").length;

            if (driverCount > 0) {
                alert('Cannot delete this team because it has ' + driverCount + ' assigned driver(s). Please reassign or delete the drivers first.');
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
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            tr.fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        tr.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                },
                error: function() {
                    alert('Error deleting team');
                }
            });
        });
    </script>
</body>

</html>