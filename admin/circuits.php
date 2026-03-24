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

    // NEW: Handle position updates
    if ($_POST['what'] === 'updatePositions') {
        $positions = json_decode($_POST['positions'], true);
        foreach ($positions as $item) {
            $id = (int)$item['id'];
            $position = (int)$item['position'];
            mysqli_query($conn, "UPDATE circuits SET position = $position WHERE id = $id");
        }
        echo json_encode(["success" => true]);
        exit;
    }
}

// CHANGED: Fetch all circuits ordered by position
$result = mysqli_query($conn, "SELECT * FROM circuits ORDER BY position ASC, id ASC");
$totalCircuits = mysqli_num_rows($result);
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
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <title>F1 Dashboard | Circuits Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <style>
        .circuit-map-preview {
            max-width: 100px;
            max-height: 60px;
            border-radius: 4px;
            object-fit: cover;
        }

        .edit-map-preview {
            max-width: 100%;
            height: auto;
            margin-top: 5px;
            border-radius: 4px;
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
                        <!-- NEW: Add drag handle column -->
                        <th></th>
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
                            <!-- NEW: Add drag handle cell -->
                            <td class="drag-handle"><i class="fas fa-grip-vertical"></i></td>
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

    <!-- Modal za dodavanje novog kruga -->
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

        // Modal za dodavanje kruga
        const addCircuitBtn = document.getElementById('addCircuitBtn');
        const addCircuitModal = document.getElementById('addCircuitModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const confirmAddBtn = document.getElementById('confirmAddBtn');
        const addCircuitForm = document.getElementById('addCircuitForm');

        addCircuitBtn.addEventListener('click', () => {
            addCircuitModal.style.display = 'flex';
            addCircuitForm.reset();
            $(".modal").scrollTop = 0;
        });

        cancelAddBtn.addEventListener('click', () => {
            addCircuitModal.style.display = 'none';
        });

        addCircuitModal.addEventListener('click', (e) => {
            if (e.target === addCircuitModal) {
                addCircuitModal.style.display = 'none';
            }
        });

        // Dodavanje nove 
        confirmAddBtn.addEventListener('click', () => {
            const name = $('#newCircuitName').val();
            const lengthKM = $('#newCircuitLength').val();
            const firstGP = $('#newCircuitFirstGP').val();
            const numberOfLaps = $('#newCircuitLaps').val();
            const raceDistance = $('#newCircuitRaceDistance').val();
            const circuitMapUrl = $('#newCircuitMapUrl').val();
            const country = $('#newCircuitCountry').val();

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
                            // CHANGED: Added drag handle cell
                            const newRow = `
                                <tr data-id="${data.id}">
                                    <td class="drag-handle"><i class="fas fa-grip-vertical"></i></td>
                                    <td class="name">${name}</td>
                                    <td class="lengthKM">${lengthKM}</td>
                                    <td class="firstGP">${firstGP}</td>
                                    <td class="numberOfLaps">${numberOfLaps}</td>
                                    <td class="raceDistance">${raceDistance}</td>
                                    <td class="circuitMapUrl">
                                        <img src="${circuitMapUrl}" alt="Circuit Map" class="circuit-map-preview">
                                    </td>
                                    <td class="country">${country}</td>
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
                            `;
                            $('#circuitsTableBody').prepend(newRow);
                            addCircuitModal.style.display = 'none';
                            alert('Circuit added successfully!');
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

        // Enter u formi za dodavanje
        addCircuitForm.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddBtn.click();
            }
        });

        // Edit circuit
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");

            // Check if already in edit mode
            if (tr.find("input").length) return;

            const fields = ['name', 'lengthKM', 'firstGP', 'numberOfLaps', 'raceDistance', 'circuitMapUrl', 'country'];
            const originalValues = {};

            // Store original values and replace with inputs
            fields.forEach(f => {
                const td = tr.find(`.${f}`);
                let val = '';

                if (f === 'circuitMapUrl') {
                    const img = td.find("img");
                    val = img.attr("src");
                    td.html(`<textarea class="edit_${f}" style="width:100%;">${val}</textarea>`);
                } else {
                    val = td.text().trim();
                    td.html(`<input type="text" class="edit_${f}" value="${val}" ${f === 'name' || f === 'country' ? '' : 'type="number" step="0.001"'}>`);
                }
                originalValues[f] = val;
            });

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
                fields.forEach(f => {
                    const td = tr.find(`.${f}`);
                    if (f === 'circuitMapUrl') {
                        td.html(`<img src="${originalValues[f]}" alt="Circuit Map" class="circuit-map-preview">`);
                    } else {
                        td.text(originalValues[f]);
                    }
                });
                tr.find(".action-icons").html(originalIcons);
            });

            // Enter for saving
            tr.find("input").on("keydown", function(e) {
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
                            tr.find(".name").text(name);
                            tr.find(".lengthKM").text(lengthKM);
                            tr.find(".firstGP").text(firstGP);
                            tr.find(".numberOfLaps").text(numberOfLaps);
                            tr.find(".raceDistance").text(raceDistance);
                            tr.find(".circuitMapUrl").html(`<img src="${circuitMapUrl}" alt="Circuit Map" class="circuit-map-preview">`);
                            tr.find(".country").text(country);

                            tr.find(".action-icons").html(`
                                <div class="edit-icon" title="Edit circuit">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="delete-icon" title="Delete circuit">
                                    <i class="fas fa-trash-alt"></i>
                                </div>
                            `);
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

        // NEW: Drag and drop functionality
        $(function() {
            $("#circuitsTableBody").sortable({
                handle: ".drag-handle",
                helper: function(e, tr) {
                    var $helper = tr.clone();
                    $helper.children().each(function(i) {
                        $(this).width(tr.children().eq(i).width());
                    });
                    return $helper;
                },
                update: function() {
                    const positions = [];
                    $('#circuitsTableBody tr').each(function(index) {
                        positions.push({
                            id: $(this).data('id'),
                            position: index + 1
                        });
                    });
                    
                    if ($('.save-order-btn').length === 0) {
                        $('.add-user').append('<button  class="action-btn" style="margin-top:20px;" id="saveOrderBtn"><i class="fas fa-save"></i> Save Order</button>');
                    }
                    
                    $('#saveOrderBtn').off('click').click(function() {
                        $.ajax({
                            type: "POST",
                            url: "circuits.php",
                            data: {
                                what: "updatePositions",
                                positions: JSON.stringify(positions)
                            },
                            success: function(response) {
                                $('#saveOrderBtn').remove();
                                alert('Circuit order saved!');
                            }
                        });
                    });
                }
            });
        });
    </script>
</body>

</html>