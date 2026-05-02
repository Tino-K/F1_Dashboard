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

$rezultat = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
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
    <title>F1 Dashboard | User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
    <style>
        /* Modal Styles */
        .edit-user-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .edit-user-modal .modal-content {
            background: var(--card-bg, #fff);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .dark-theme .edit-user-modal .modal-content {
            background: #1e1e2f;
            color: #f0f0f0;
        }

        .edit-user-modal .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color, #ddd);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .edit-user-modal .modal-header h3 {
            margin: 0;
        }

        .edit-user-modal .close-modal {
            cursor: pointer;
            font-size: 24px;
        }

        .edit-user-modal .modal-body {
            padding: 20px;
        }

        .edit-user-modal .form-group {
            margin-bottom: 15px;
        }

        .edit-user-modal .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .edit-user-modal .form-group input,
        .edit-user-modal .form-group select,
        .edit-user-modal .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color, #ddd);
            border-radius: 6px;
            background: var(--input-bg, #fff);
            color: var(--text-color, #333);
        }

        .dark-theme .edit-user-modal .form-group input,
        .dark-theme .edit-user-modal .form-group select,
        .dark-theme .edit-user-modal .form-group textarea {
            background: #2d2d3a;
            border-color: #444;
            color: #f0f0f0;
        }

        .edit-user-modal .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color, #ddd);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .edit-user-modal .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .edit-user-modal .btn-primary {
            background: #dc3545;
            color: white;
        }

        .edit-user-modal .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 10px;
        }

        .password-hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
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
        <div class="nav-container">
            <a href="admin_page.php" class="nav-link">
                <button class="nav-btn nav-btn-dashboard">
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
                        <tr data-id="<?= $row['id'] ?>" data-user='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>'>
                            <td class="name"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="email"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="role">
                                <span class="role-badge role-<?= $row['role'] ?>">
                                    <?= htmlspecialchars($row['role']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <div class="action-icons">
                                    <div class="edit-icon" title="View/Edit user details" data-id="<?= $row['id'] ?>">
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

    <!-- Modal za pregled i uređivanje svih podataka korisnika -->
    <div id="editUserModal" class="edit-user-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> User Details</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="id">
                    <div class="form-group">
                        <label for="editUserName">Username</label>
                        <input type="text" id="editUserName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editUserEmail">Email</label>
                        <input type="email" id="editUserEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="editUserPassword">Password (leave blank to keep current)</label>
                        <input type="password" id="editUserPassword" name="password" placeholder="Enter new password to change">
                        <div class="password-hint">Password will be hashed automatically</div>
                    </div>
                    <div class="form-group">
                        <label for="editUserGender">Gender</label>
                        <select id="editUserGender" name="gender">
                            <option value="">Prefer not to say</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editUserBio">Bio</label>
                        <textarea id="editUserBio" name="bio" rows="3" placeholder="User biography..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editUserRole">Role</label>
                        <select id="editUserRole" name="role" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editUserTheme">Theme Preference</label>
                        <select id="editUserTheme" name="theme_preference">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Avatar URL</label>
                        <input type="text" id="editUserAvatar" name="avatar_url" placeholder="https://example.com/avatar.jpg">
                    </div>
                    <div class="form-group">
                        <label>Current Avatar Preview</label>
                        <div>
                            <img id="avatarPreview" class="avatar-preview" src="" alt="Avatar preview">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
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

        addUserModal.addEventListener('click', (e) => {
            if (e.target === addUserModal) {
                addUserModal.style.display = 'none';
            }
        });

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
                                <tr data-id="${data.id}" data-user='${JSON.stringify({
                                    id: data.id,
                                    name: name,
                                    email: email,
                                    role: role,
                                    gender: '',
                                    bio: '',
                                    avatar_url: '',
                                    theme_preference: 'light'
                                })}'>
                                    <td class="name">${name.replace(/[<>]/g, '')}</td>
                                    <td class="email">${email.replace(/[<>]/g, '')}</td>
                                    <td class="role">
                                        <span class="role-badge role-${role}">${role}</span>
                                    </td>
                                    <td class="actions">
                                        <div class="action-icons">
                                            <div class="edit-icon" title="Quick edit" title="View/Edit user details" data-id="${data.id}">
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

        addUserForm.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmAddBtn.click();
            }
        });

        // Open detailed user modal when clicking view icon
        $(document).on("click", ".edit-icon", function() {
            const tr = $(this).closest("tr");
            const userId = tr.data("id");
            
            // Fetch full user data via AJAX
            $.ajax({
                type: "POST",
                url: "user_edit.php",
                data: {
                    what: "get",
                    id: userId
                },
                dataType: "json",
                success: function(user) {
                    if (user.success && user.data) {
                        populateEditModal(user.data);
                        $('#editUserModal').css('display', 'flex');
                    } else {
                        alert('Error loading user data');
                    }
                },
                error: function() {
                    // Fallback: try to get data from data-user attribute
                    const userDataStr = tr.attr('data-user');
                    if (userDataStr) {
                        try {
                            const userData = JSON.parse(userDataStr);
                            populateEditModal(userData);
                            $('#editUserModal').css('display', 'flex');
                        } catch(e) {
                            alert('Error loading user data');
                        }
                    } else {
                        alert('Error loading user data');
                    }
                }
            });
        });

        function populateEditModal(user) {
            $('#editUserId').val(user.id);
            $('#editUserName').val(user.name);
            $('#editUserEmail').val(user.email);
            $('#editUserPassword').val(''); // Clear password field
            $('#editUserGender').val(user.gender || '');
            $('#editUserBio').val(user.bio || '');
            $('#editUserRole').val(user.role);
            $('#editUserTheme').val(user.theme_preference || 'light');
            $('#editUserAvatar').val(user.avatar_url || '');
            
            if (user.avatar_url) {
                $('#avatarPreview').attr('src', user.avatar_url);
            } else {
                $('#avatarPreview').attr('src', 'https://via.placeholder.com/80?text=No+Avatar');
            }
        }

        // Close modal
        $('.close-modal, #cancelEditBtn').on('click', function() {
            $('#editUserModal').css('display', 'none');
        });

        $(window).on('click', function(e) {
            if ($(e.target).is('#editUserModal')) {
                $('#editUserModal').css('display', 'none');
            }
        });

        // Save user changes from detailed modal
        $('#saveEditBtn').on('click', function() {
            const userId = $('#editUserId').val();
            const formData = {
                what: "edit_full",
                id: userId,
                name: $('#editUserName').val(),
                email: $('#editUserEmail').val(),
                password: $('#editUserPassword').val(),
                gender: $('#editUserGender').val(),
                bio: $('#editUserBio').val(),
                role: $('#editUserRole').val(),
                theme_preference: $('#editUserTheme').val(),
                avatar_url: $('#editUserAvatar').val()
            };

            $.ajax({
                type: "POST",
                url: "user_edit.php",
                data: formData,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Update the table row with new data
                        const tr = $(`tr[data-id="${userId}"]`);
                        tr.find('.name').text(formData.name);
                        tr.find('.email').text(formData.email);
                        tr.find('.role .role-badge').text(formData.role);
                        tr.find('.role .role-badge').attr('class', `role-badge role-${formData.role}`);
                        
                        // Update stored user data
                        const updatedUserData = {
                            id: userId,
                            name: formData.name,
                            email: formData.email,
                            role: formData.role,
                            gender: formData.gender,
                            bio: formData.bio,
                            avatar_url: formData.avatar_url,
                            theme_preference: formData.theme_preference
                        };
                        tr.attr('data-user', JSON.stringify(updatedUserData));
                        
                        $('#editUserModal').css('display', 'none');
                        alert('User updated successfully!');
                        
                        // If current user is updated, refresh session info
                        if (userId == <?= json_encode($_SESSION['user_id'] ?? 0) ?>) {
                            location.reload();
                        }
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error saving user: ' + error);
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
                success: function(response) {
                    tr.find(".name").text(newName);
                    tr.find(".email").text(newEmail);
                    tr.find(".role").html(`<span class="role-badge role-${newRole}">${newRole}</span>`);

                    tr.find(".action-icons").html(`
                        <div class="edit-icon" title="View/Edit user details" data-id="${id}">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div class="delete-icon" title="Delete user">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                    `);
                    
                    // Update stored data
                    const trElement = tr;
                    const userDataStr = trElement.attr('data-user');
                    if (userDataStr) {
                        try {
                            const userData = JSON.parse(userDataStr);
                            userData.name = newName;
                            userData.email = newEmail;
                            userData.role = newRole;
                            trElement.attr('data-user', JSON.stringify(userData));
                        } catch(e) {}
                    }
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