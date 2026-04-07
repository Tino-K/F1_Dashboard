<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

// Handle AJAX actions: delete, update, or add
if (!empty($_POST['what'])) {
    if ($_POST['what'] === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM drivers WHERE id = $id");
        echo json_encode(["success" => true]);
        exit;
    }

    if ($_POST['what'] === 'edit' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $surname = mysqli_real_escape_string($conn, $_POST['surname']);
        $shortName = mysqli_real_escape_string($conn, $_POST['shortName']);
        $number = $_POST['number'] !== '' ? (int)$_POST['number'] : 'NULL';
        $image = mysqli_real_escape_string($conn, $_POST['image']);
        $team_id = $_POST['team_id'] !== '' ? (int)$_POST['team_id'] : 'NULL';

        $update = mysqli_query($conn, "UPDATE drivers 
            SET name='$name', surname='$surname', shortName='$shortName', number=$number, image='$image', team_id=$team_id
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
        $surname = mysqli_real_escape_string($conn, $_POST['surname']);
        $shortName = mysqli_real_escape_string($conn, $_POST['shortName']);
        $number = $_POST['number'] !== '' ? (int)$_POST['number'] : 'NULL';
        $image = mysqli_real_escape_string($conn, $_POST['image']);
        $team_id = $_POST['team_id'] !== '' ? (int)$_POST['team_id'] : 'NULL';

        $insert = mysqli_query($conn, "INSERT INTO drivers (name, surname, shortName, number, image, team_id) 
            VALUES ('$name', '$surname', '$shortName', $number, '$image', $team_id)");

        if ($insert) {
            $newId = mysqli_insert_id($conn);
            echo json_encode(["success" => true, "id" => $newId]);
        } else {
            echo json_encode(["success" => false, "message" => "Insert failed"]);
        }
        exit;
    }
}

// Fetch all drivers ordered by surname then name alphabetically, with team information
$result = mysqli_query($conn, "SELECT drivers.*, teams.name AS team_name 
                               FROM drivers 
                               LEFT JOIN teams ON drivers.team_id = teams.id 
                               ORDER BY drivers.surname ASC, drivers.name ASC");
$totalDrivers = mysqli_num_rows($result);

// Fetch all teams for dropdown
$teamsResult = mysqli_query($conn, "SELECT id, teams.name FROM teams ORDER BY teams.name ASC");
$teams = [];
while ($team = mysqli_fetch_assoc($teamsResult)) {
    $teams[] = $team;
}
?>
<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <script>
        // SPRJEČAVA FLASHBANG
        (function() {
            const savedTheme = localStorage.getItem('f1-theme');
            if (savedTheme === 'dark-theme') {
                document.documentElement.classList.add('dark-theme');
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | Drivers Admin</title>
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

        <div class="page-title">
            <i class="fas fa-id-badge"></i>
            <h1>Driver <span>Management</span></h1>
        </div>

        <!-- Drivers Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Number</th>
                        <th>Short Name</th>
                        <th>Image</th>
                        <th>Team</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="driversTableBody">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td class="driver-name"><?= htmlspecialchars($row['surname'] . ' ' . $row['name']) ?></td>
                            <td class="driver-number">
                                <span class="driver-number">#<?= $row['number'] ?></span>
                            </td>
                            <td class="shortName"><?= htmlspecialchars($row['shortName']) ?></td>
                            <td class="image">
                                <?php if ($row['image']): ?>
                                    <img src="<?= htmlspecialchars($row['image']) ?>" alt="Driver" class="driver-image-preview">
                                <?php else: ?>
                                    <i class="fas fa-user-circle" style="font-size: 40px; color: var(--border-color);"></i>
                                <?php endif; ?>
                            </td>
                            <td class="team" data-team-id="<?= $row['team_id'] ?>">
                                <?php if ($row['team_id']): ?>
                                    <div class="team-badge">
                                        <span><?= htmlspecialchars($row['team_name']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--border-color);">No team</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <div class="action-icons">
                                    <div class="edit-icon" title="Edit driver">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="delete-icon" title="Delete driver">
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
                <span>Total drivers: <?= $totalDrivers; ?></span>
            </div>
            <div class="add-user">
                <button class="action-btn add-btn" id="addDriverBtn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Driver</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal za dodavanje novog vozača -->
    <div class="modal-overlay" id="addDriverModal">
        <div class="modal" style="max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Driver</h3>
            </div>
            <div class="modal-body" style="width: 100%;">
                <form id="addDriverForm">
                    <div class="form-group">
                        <label for="newDriverName">First Name</label>
                        <input type="text" id="newDriverName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="newDriverSurname">Last Name</label>
                        <input type="text" id="newDriverSurname" name="surname" required>
                    </div>
                    <div class="form-group">
                        <label for="newDriverShortName">Short Name</label>
                        <input type="text" id="newDriverShortName" name="shortName" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label for="newDriverNumber">Number</label>
                        <input type="number" id="newDriverNumber" name="number" min="1" max="99" required>
                    </div>
                    <div class="form-group">
                        <label for="newDriverImage">Driver Image URL</label>
                        <textarea id="newDriverImage" name="image" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="newDriverTeam">Team</label>
                        <select id="newDriverTeam" name="team_id">
                            <option value="">-- Select Team --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <span>Add Driver</span>
                </button>
            </div>
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

        // Modal za dodavanje vozača
        const addDriverBtn = document.getElementById('addDriverBtn');
        const addDriverModal = document.getElementById('addDriverModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const confirmAddBtn = document.getElementById('confirmAddBtn');
        const addDriverForm = document.getElementById('addDriverForm');

        addDriverBtn.addEventListener('click', () => {
            addDriverModal.style.display = 'flex';
            addDriverForm.reset();
            $(".modal").scrollTop(0);
        });

        cancelAddBtn.addEventListener('click', () => {
            addDriverModal.style.display = 'none';
        });

        addDriverModal.addEventListener('click', (e) => {
            if (e.target === addDriverModal) {
                addDriverModal.style.display = 'none';
            }
        });

        // Dodavanje novog vozača
        confirmAddBtn.addEventListener('click', () => {
            const name = $('#newDriverName').val().trim();
            const surname = $('#newDriverSurname').val().trim();
            const shortName = $('#newDriverShortName').val().trim();
            const number = $('#newDriverNumber').val();
            const image = $('#newDriverImage').val().trim();
            const team_id = $('#newDriverTeam').val();

            if (!name || !surname || !shortName || !number || !image) {
                alert('Please fill in all required fields');
                return;
            }

            $.ajax({
                type: "POST",
                url: "drivers.php",
                data: {
                    what: "add",
                    name: name,
                    surname: surname,
                    shortName: shortName,
                    number: number,
                    image: image,
                    team_id: team_id || ''
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            alert('Driver added successfully!');
                            location.reload(); // Reload to show in correct alphabetical order
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        alert('Driver added successfully!');
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error adding driver');
                }
            });
        });

        // Enter u formi za dodavanje
        addDriverForm.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddBtn.click();
            }
        });

        // Edit driver
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");

            if (tr.find("input").length || tr.find("select").length) return;

            // Store original values - now surname first
            const nameParts = tr.find(".driver-name").text().trim().split(' ');
            const surname = nameParts[0];
            const firstName = nameParts.slice(1).join(' ');
            const number = tr.find("td .driver-number").text().replace('#', '');
            const shortName = tr.find(".shortName").text().trim();
            const image = tr.find(".image img").attr("src") || '';
            const teamId = tr.find(".team").data("team-id") || '';

            // Replace cells with inputs
            tr.find(".driver-name").html(`
                <input type="text" class="edit_surname" value="${surname}" placeholder="Last name" style="width:100%;">
                <input type="text" class="edit_name" value="${firstName}" placeholder="First name" style="width:100%; margin-top:5px;">
            `);

            tr.find(".driver-number").html(`<input type="number" class="edit_number" value="${number}" min="1" max="99" style="width:100%;">`);

            tr.find(".shortName").html(`<input type="text" class="edit_shortName" value="${shortName}" maxlength="10" style="width:100%;">`);

            tr.find(".image").html(`
                <textarea class="edit_image" style="width:100%;" placeholder="Image URL">${image}</textarea>
            `);

            // Create team dropdown
            let teamOptions = '<option value="">-- No Team --</option>';
            <?php foreach ($teams as $team): ?>
                teamOptions += `<option value="<?= $team['id'] ?>" ${teamId == <?= $team['id'] ?> ? 'selected' : ''}>${'<?= htmlspecialchars($team['name']) ?>'}</option>`;
            <?php endforeach; ?>

            tr.find(".team").html(`<select class="edit_team_id" style="width:100%;">${teamOptions}</select>`);

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
                saveDriverChanges(tr, id);
            });

            // Cancel function
            tr.find(".cancel-icon").on("click", function() {
                location.reload(); // Simple reload to restore original view
            });

            // Enter for saving
            tr.find("input, select").on("keydown", function(e) {
                if (e.key === "Enter") {
                    saveDriverChanges(tr, id);
                }
            });
        });

        function saveDriverChanges(tr, id) {
            const name = tr.find(".edit_name").val();
            const surname = tr.find(".edit_surname").val();
            const shortName = tr.find(".edit_shortName").val();
            const number = tr.find(".edit_number").val();
            const image = tr.find(".edit_image").val();
            const team_id = tr.find(".edit_team_id").val();

            if (!name || !surname || !shortName || !number || !image) {
                alert('All fields are required');
                return;
            }

            $.ajax({
                type: "POST",
                url: "drivers.php",
                data: {
                    what: "edit",
                    id: id,
                    name: name,
                    surname: surname,
                    shortName: shortName,
                    number: number,
                    image: image,
                    team_id: team_id || ''
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

        // Delete driver
        $(document).on("click", ".delete-icon", function() {
            if (!confirm("Are you sure you want to delete this driver?")) return;

            const tr = $(this).closest("tr");
            const id = tr.data("id");

            $.ajax({
                type: "POST",
                url: "drivers.php",
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
                    alert('Error deleting driver');
                }
            });
        });
    </script>
</body>

</html>