<?php
session_start();

if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "./config.php";

$rezultat = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
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
    <title>F1 Dashboard | Admin Panel</title>
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
        <div class="nav-container">
            <a href="admin_page.php" class="nav-link">
                <button class="nav-btn nav-btn-dashboard">
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
                <button class="nav-btn nav-btn-default">
                    <i class="fas fa-trophy nav-icon-gold"></i>
                    <span>Championship</span>
                </button>
            </a>
        </div>

        <div class="page-title">
            <i class="fas fa-users-cog"></i>
            <h1>User <span>Management</span></h1>
        </div>
        <!-- Tablica korisnika -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php while ($row = mysqli_fetch_assoc($rezultat)): ?>
                        <tr data-id="<?= $row['id'] ?>">
                            <td class="name"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="role">
                                <span class="role-badge role-<?= $row['role'] ?>">
                                    <?= htmlspecialchars($row['role']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <div class="action-icons">
                                    <div class="edit-icon" title="Edit user">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="delete-icon" title="Delete user">
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
                <span>Total users: <?= mysqli_num_rows($rezultat); ?></span>
            </div>
            <div class="add-user">
                <button class="action-btn add-btn" id="addUserBtn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New User</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal za dodavanje novog korisnika -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="form-group">
                        <label for="newUserName">Username</label>
                        <input type="text" id="newUserName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="newUserEmail">Email</label>
                        <input type="email" id="newUserEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="newUserPassword">Password</label>
                        <input type="password" id="newUserPassword" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="newUserRole">Role</label>
                        <select id="newUserRole" name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
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
                    <span>Add User</span>
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

        // Modal za dodavanje korisnika
        const addUserBtn = document.getElementById('addUserBtn');
        const addUserModal = document.getElementById('addUserModal');
        const cancelAddBtn = document.getElementById('cancelAddBtn');
        const confirmAddBtn = document.getElementById('confirmAddBtn');
        const addUserForm = document.getElementById('addUserForm');

        addUserBtn.addEventListener('click', () => {
            addUserModal.style.display = 'flex';
            addUserForm.reset();
        });

        cancelAddBtn.addEventListener('click', () => {
            addUserModal.style.display = 'none';
        });

        // Klik izvan modal-a zatvara modal
        addUserModal.addEventListener('click', (e) => {
            if (e.target === addUserModal) {
                addUserModal.style.display = 'none';
            }
        });

        // Dodavanje novog korisnika
        confirmAddBtn.addEventListener('click', () => {
            const name = document.getElementById('newUserName').value;
            const email = document.getElementById('newUserEmail').value;
            const password = document.getElementById('newUserPassword').value;
            const role = document.getElementById('newUserRole').value;

            if (!name || !email || !password || !role) {
                alert('Please fill in all fields');
                return;
            }

            $.ajax({
                type: "POST",
                url: "user_edit.php",
                data: {
                    what: "add",
                    name: name,
                    email: email,
                    password: password,
                    role: role
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success) {
                            const newRow = `
                                <tr data-id="${data.id}">
                                    <td class="id">${data.id}</td>
                                    <td class="name">${name}</td>
                                    <td class="email">${email}</td>
                                    <td class="role">
                                        <span class="role-badge role-${role}">${role}</span>
                                    </td>
                                    <td class="actions">
                                        <div class="action-icons">
                                            <div class="edit-icon" title="Edit user">
                                                <i class="fas fa-edit"></i>
                                            </div>
                                            <div class="delete-icon" title="Delete user">
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;

                            $('#usersTableBody').prepend(newRow);
                            addUserModal.style.display = 'none';
                            alert('User added successfully!');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        alert('User added successfully!');
                        location.reload();
                    }
                },
                error: function() {
                    alert('Error adding user');
                }
            });
        });

        // Enter u formi za dodavanje
        addUserForm.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddBtn.click();
            }
        });

        // Edit user
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const id = tr.data("id");
            const tdName = tr.find(".name");
            const tdEmail = tr.find(".email");
            const tdRole = tr.find(".role");

            // Provjeri već li je u edit modeu
            if (tdName.find("input").length || tdEmail.find("input").length) return;

            const oldName = tdName.text().trim();
            const oldEmail = tdEmail.text().trim();
            const oldRole = tdRole.find(".role-badge").text().trim();

            // Spremi originalne ikone
            const originalIcons = tr.find(".action-icons").html();

            tdName.html(`<input type="text" class="editName" value="${oldName}">`);
            tdEmail.html(`<input type="email" class="editEmail" value="${oldEmail}">`);
            tdRole.html(`
                <select class="editRole">
                    <option value="user" ${oldRole === "user" ? "selected" : ""}>User</option>
                    <option value="admin" ${oldRole === "admin" ? "selected" : ""}>Admin</option>
                </select>
            `);

            // Zamijeni ikone sa Save/Cancel
            tr.find(".action-icons").html(`
                <div class="save-icon" title="Save changes">
                    <i class="fas fa-check"></i>
                </div>
                <div class="cancel-icon" title="Cancel">
                    <i class="fas fa-times"></i>
                </div>
            `);

            tdName.find("input").focus();

            // Save funkcija
            tr.find(".save-icon").on("click", function() {
                saveUserChanges(tr, id);
            });

            // Cancel funkcija
            tr.find(".cancel-icon").on("click", function() {
                tdName.text(oldName);
                tdEmail.text(oldEmail);
                tdRole.html(`<span class="role-badge role-${oldRole}">${oldRole}</span>`);
                tr.find(".action-icons").html(originalIcons);
            });

            // Enter za spremanje
            tr.find(".editName, .editEmail, .editRole").on("keydown", function(e) {
                if (e.key === "Enter") {
                    saveUserChanges(tr, id);
                }
            });
        });

        function saveUserChanges(tr, id) {
            const newName = tr.find(".editName").val();
            const newEmail = tr.find(".editEmail").val();
            const newRole = tr.find(".editRole").val();

            $.ajax({
                type: "POST",
                url: "user_edit.php",
                data: {
                    id: id,
                    what: "edit",
                    name: newName,
                    email: newEmail,
                    role: newRole
                },
                success: function() {
                    tr.find(".name").text(newName);
                    tr.find(".email").text(newEmail);
                    tr.find(".role").html(`<span class="role-badge role-${newRole}">${newRole}</span>`);

                    // Vrati originalne ikone
                    tr.find(".action-icons").html(`
                        <div class="edit-icon" title="Edit user">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="delete-icon" title="Delete user">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                    `);
                },
                error: function() {
                    alert('Error saving changes');
                }
            });
        }

        // Delete user
        $(document).on("click", ".delete-icon", function() {
            if (!confirm("Are you sure you want to delete this user?")) return;

            const tr = $(this).closest("tr");
            const id = tr.data("id");
            const userName = tr.find(".name").text().trim();

            $.ajax({
                type: "POST",
                url: "user_edit.php",
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
                        // Ako nije JSON, jednostavno ukloni
                        tr.fadeOut(300, function() {
                            $(this).remove();
                        });
                    }
                },
                error: function() {
                    alert('Error deleting user');
                }
            });
        });
    </script>
</body>

</html>