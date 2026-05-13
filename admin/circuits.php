<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

//Theme
$email = $_SESSION['email'];
$theme = mysqli_fetch_assoc(mysqli_query($conn, "SELECT users.theme_preference FROM users WHERE users.email = '$email'"));

// Handle AJAX actions: delete, update, or add
if (!empty($_POST['what'])) {
    if ($_POST['what'] === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM circuits WHERE id = $id");
        echo json_encode(["success" => true]);
        exit;
    }

    if ($_POST['what'] === 'edit' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $lengthKM = (float)$_POST['lengthKM'];
        $firstGP = (int)$_POST['firstGP'];
        $numberOfLaps = (int)$_POST['numberOfLaps'];
        $raceDistance = (float)$_POST['raceDistance'];
        $circuitMapUrl = mysqli_real_escape_string($conn, $_POST['circuitMapUrl']);
        $country = mysqli_real_escape_string($conn, $_POST['country']);

        $update = mysqli_query($conn, "UPDATE circuits 
            SET name='$name', lengthKM=$lengthKM, firstGP=$firstGP, numberOfLaps=$numberOfLaps, raceDistance=$raceDistance, circuitMapUrl='$circuitMapUrl', country='$country'
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
        $lengthKM = (float)$_POST['lengthKM'];
        $firstGP = (int)$_POST['firstGP'];
        $numberOfLaps = (int)$_POST['numberOfLaps'];
        $raceDistance = (float)$_POST['raceDistance'];
        $circuitMapUrl = mysqli_real_escape_string($conn, $_POST['circuitMapUrl']);
        $country = mysqli_real_escape_string($conn, $_POST['country']);

        $insert = mysqli_query($conn, "INSERT INTO circuits (name, lengthKM, firstGP, numberOfLaps, raceDistance, circuitMapUrl, country) 
            VALUES ('$name', $lengthKM, $firstGP, $numberOfLaps, $raceDistance, '$circuitMapUrl', '$country')");

        if ($insert) {
            $newId = mysqli_insert_id($conn);
            echo json_encode(["success" => true, "id" => $newId]);
        } else {
            echo json_encode(["success" => false, "message" => "Insert failed"]);
        }
        exit;
    }
}

$result = mysqli_query($conn, "SELECT * FROM circuits ORDER BY name ASC");
$totalCircuits = mysqli_num_rows($result);
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
    <title>F1 Dashboard | Circuits</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <style>
        .circuit-map-preview {
            max-width: 100px;
            max-height: 60px;
            border-radius: 4px;
            object-fit: cover;
            cursor: pointer;
        }

        .edit-map-preview {
            max-width: 100%;
            height: auto;
            margin-top: 5px;
            border-radius: 4px;
        }

        .imageModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            cursor: pointer;
            justify-content: center;
            align-items: center;
        }

        .imageModal.active {
            display: flex;
        }

        .modalImage-container {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            text-align: center;
        }

        .modalImage {
            max-width: 100%;
            max-height: 85vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.5);
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
            <a href="admin_requests.php" class="nav-link">
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Requests</span>
                </button>
            </a>
            <a href="circuits.php" class="nav-link">
                <button class="nav-btn nav-btn-dashboard">
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
            <i class="fas fa-medal"></i>
            <h1>Circuit <span>Management</span></h1>
        </div>

        <!-- Circuits Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Circuit Name</th>
                        <th>Length (KM)</th>
                        <th>First GP</th>
                        <th>Laps</th>
                        <th>Race Distance</th>
                        <th>Map</th>
                        <th>Country</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="circuitsTableBody">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td class="name"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="lengthKM"><?= $row['lengthKM'] ?></td>
                            <td class="firstGP"><?= $row['firstGP'] ?></td>
                            <td class="numberOfLaps"><?= $row['numberOfLaps'] ?></td>
                            <td class="raceDistance"><?= $row['raceDistance'] ?></td>
                            <td class="circuitMapUrl">
                                <img src="<?= htmlspecialchars($row['circuitMapUrl']) ?>" alt="Circuit Map" class="circuit-map-preview">
                            </td>
                            <td class="country"><?= htmlspecialchars($row['country']) ?></td>
                            <td class="actions">
                                <div class="action-icons">
                                    <div class="edit-icon" title="Edit circuit">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="delete-icon" title="Delete circuit">
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
                <span>Total circuits: <?= $totalCircuits; ?></span>
            </div>
            <div class="add-user">
                <button class="action-btn add-btn" id="addCircuitBtn">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Circuit</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="imageModal">
        <img class="modalImage" src="" alt="Circuit Map">
    </div>

    <div class="modal-overlay" id="addCircuitModal">
        <div class="modal" style="max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Add New Circuit</h3>
            </div>
            <div class="modal-body" style="width: 100%;">
                <form id="addCircuitForm">
                    <div class="form-group">
                        <label for="newCircuitName">Circuit Name</label>
                        <input type="text" id="newCircuitName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="newCircuitLength">Length (KM)</label>
                        <input type="text" id="newCircuitLength" name="lengthKM" required>
                    </div>
                    <div class="form-group">
                        <label for="newCircuitFirstGP">First GP Year</label>
                        <input type="text" id="newCircuitFirstGP" name="firstGP" required>
                    </div>
                    <div class="form-group">
                        <label for="newCircuitLaps">Number of Laps</label>
                        <input type="text" id="newCircuitLaps" name="numberOfLaps" required>
                    </div>
                    <div class="form-group">
                        <label for="newCircuitRaceDistance">Race Distance (KM)</label>
                        <input type="text" id="newCircuitRaceDistance" name="raceDistance" required>
                    </div>
                    <div class="form-group">
                        <label for="newCircuitMapUrl">Circuit Map URL</label>
                        <textarea id="newCircuitMapUrl" name="circuitMapUrl" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="newCircuitCountry">Country</label>
                        <input type="text" id="newCircuitCountry" name="country" required>
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
                    <span>Add Circuit</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal to view images
        $(document).on("click", ".circuit-map-preview", function(e) {
            e.stopPropagation();
            const imageSrc = $(this).attr("data-full-image") || $(this).attr("src");
            $(".modalImage").attr("src", imageSrc);
            $(".imageModal").addClass("active");
        });

        $(".imageModal").on("click", function() {
            $(this).removeClass("active");
        });

        // Modal to add circuit
        const addCircuitBtn = document.getElementById('addCircuitBtn');
        const addCircuitModal = document.getElementById('addCircuitModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const confirmAddBtn = document.getElementById('confirmAddBtn');
        const addCircuitForm = document.getElementById('addCircuitForm');

        if (addCircuitBtn) {
            addCircuitBtn.addEventListener('click', () => {
                addCircuitModal.style.display = 'flex';
                addCircuitForm.reset();
                const modalElement = document.querySelector('.modal');
                if (modalElement) modalElement.scrollTop = 0;
            });
        }

        if (cancelAddBtn) {
            cancelAddBtn.addEventListener('click', () => {
                addCircuitModal.style.display = 'none';
            });
        }

        if (addCircuitModal) {
            addCircuitModal.addEventListener('click', (e) => {
                if (e.target === addCircuitModal) {
                    addCircuitModal.style.display = 'none';
                }
            });
        }

        // Add circuit using jQuery AJAX
        if (confirmAddBtn) {
            confirmAddBtn.addEventListener('click', () => {
                const name = $('#newCircuitName').val().trim();
                const lengthKM = $('#newCircuitLength').val().trim();
                const firstGP = $('#newCircuitFirstGP').val().trim();
                const numberOfLaps = $('#newCircuitLaps').val().trim();
                const raceDistance = $('#newCircuitRaceDistance').val().trim();
                const circuitMapUrl = $('#newCircuitMapUrl').val().trim();
                const country = $('#newCircuitCountry').val().trim();

                if (!name || !lengthKM || !firstGP || !numberOfLaps || !raceDistance || !circuitMapUrl || !country) {
                    alert('Please fill in all fields');
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "circuits.php",
                    data: {
                        what: "add",
                        name: name,
                        lengthKM: lengthKM,
                        firstGP: firstGP,
                        numberOfLaps: numberOfLaps,
                        raceDistance: raceDistance,
                        circuitMapUrl: circuitMapUrl,
                        country: country
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                alert('Circuit added successfully!');
                                location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        } catch (e) {
                            alert('Circuit added successfully!');
                            location.reload();
                        }
                    },
                    error: function() {
                        alert('Error adding circuit');
                    }
                });
            });
        }

        if (addCircuitForm) {
            addCircuitForm.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (confirmAddBtn) confirmAddBtn.click();
                }
            });
        }

        // Edit circuit
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");

            if (tr.find("input").length || tr.find("textarea").length) return;

            // Store original values
            const name = tr.find(".name").text().trim();
            const lengthKM = tr.find(".lengthKM").text().trim();
            const firstGP = tr.find(".firstGP").text().trim();
            const numberOfLaps = tr.find(".numberOfLaps").text().trim();
            const raceDistance = tr.find(".raceDistance").text().trim();
            const circuitMapUrl = tr.find(".circuitMapUrl img").attr("src") || '';
            const country = tr.find(".country").text().trim();

            // Replace cells with inputs
            tr.find(".name").html(`<input type="text" class="edit_name" value="${escapeHtml(name)}" style="width:100%;">`);
            tr.find(".lengthKM").html(`<input type="number" step="0.001" class="edit_lengthKM" value="${lengthKM}" style="width:100%;">`);
            tr.find(".firstGP").html(`<input type="number" class="edit_firstGP" value="${firstGP}" style="width:100%;">`);
            tr.find(".numberOfLaps").html(`<input type="number" class="edit_numberOfLaps" value="${numberOfLaps}" style="width:100%;">`);
            tr.find(".raceDistance").html(`<input type="number" step="0.001" class="edit_raceDistance" value="${raceDistance}" style="width:100%;">`);
            tr.find(".circuitMapUrl").html(`<textarea class="edit_circuitMapUrl" style="width:100%;" placeholder="Map URL">${escapeHtml(circuitMapUrl)}</textarea>`);
            tr.find(".country").html(`<input type="text" class="edit_country" value="${escapeHtml(country)}" style="width:100%;">`);

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
                saveCircuitChanges(tr, id);
            });

            // Cancel function
            tr.find(".cancel-icon").on("click", function() {
                location.reload();
            });

            // Enter for saving
            tr.find("input, textarea").on("keydown", function(e) {
                if (e.key === "Enter") {
                    saveCircuitChanges(tr, id);
                }
            });
        });

        function saveCircuitChanges(tr, id) {
            const name = tr.find(".edit_name").val();
            const lengthKM = tr.find(".edit_lengthKM").val();
            const firstGP = tr.find(".edit_firstGP").val();
            const numberOfLaps = tr.find(".edit_numberOfLaps").val();
            const raceDistance = tr.find(".edit_raceDistance").val();
            const circuitMapUrl = tr.find(".edit_circuitMapUrl").val();
            const country = tr.find(".edit_country").val();

            if (!name || !lengthKM || !firstGP || !numberOfLaps || !raceDistance || !circuitMapUrl || !country) {
                alert('All fields are required');
                return;
            }

            $.ajax({
                type: "POST",
                url: "circuits.php",
                data: {
                    what: "edit",
                    id: id,
                    name: name,
                    lengthKM: lengthKM,
                    firstGP: firstGP,
                    numberOfLaps: numberOfLaps,
                    raceDistance: raceDistance,
                    circuitMapUrl: circuitMapUrl,
                    country: country
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            location.reload();
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

        // Delete circuit
        $(document).on("click", ".delete-icon", function() {
            if (!confirm("Are you sure you want to delete this circuit?")) return;

            const tr = $(this).closest("tr");
            const id = tr.data("id");

            $.ajax({
                type: "POST",
                url: "circuits.php",
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
                    alert('Error deleting circuit');
                }
            });
        });

        // Helper function to escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>