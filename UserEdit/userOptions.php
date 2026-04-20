<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";

// Get user data
$email = $_SESSION['email'];
$query = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Theme preference
$theme = $user['theme_preference'] ?? 'light';
$bodyClass = ($theme === 'dark') ? 'dark-theme' : '';

// Handle profile update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $bio = mysqli_real_escape_string($conn, $_POST['bio'] ?? '');
    $avatar_url = mysqli_real_escape_string($conn, $_POST['avatar_url'] ?? '');

    $updateQuery = "UPDATE users SET name = ?, gender = ?, bio = ?, avatar_url = ? WHERE email = ?";
    $updateStmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($updateStmt, "sssss", $name, $gender, $bio, $avatar_url, $email);

    if (mysqli_stmt_execute($updateStmt)) {
        $_SESSION['name'] = $name;
        $message = "Profile updated successfully!";
        $messageType = "success";

        // Refresh user data
        $user['name'] = $name;
        $user['gender'] = $gender;
        $user['bio'] = $bio;
        $user['avatar_url'] = $avatar_url;
    } else {
        $message = "Error updating profile: " . mysqli_error($conn);
        $messageType = "error";
    }
    mysqli_stmt_close($updateStmt);
}
?>
<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <title>F1 Dashboard | User Options</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="userEdit.css">
    <link rel="shortcut icon" type="image/x-icon" href="../pictures/flagIcon.png" />
</head>

<body class="<?= $bodyClass ?>">
    <div class="header">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-flag-checkered"></i>
            </div>
            <div class="logo-text">F1 Dashboard</div>
        </div>
        <button onclick="history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Go Back
        </button>

        <div class="header-right">
            <div class="theme-toggle" id="themeToggle">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <div class="toggle-circle"></div>
            </div>
            <div class="user-badge" onclick="window.location.href='userOptions.php'">
                <i class="fas fa-user-shield"></i>
                <span><?= htmlspecialchars($_SESSION["name"]) ?></span>
            </div>
            <button class="action-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </div>
    </div>

    <div class="container">
        <div class="page-title">
            <i class="fas fa-user-edit"></i>
            <h1>My <span>Profile</span></h1>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?= $messageType ?>">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <?php if ($user['avatar_url']): ?>
                        <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar" class="avatar-large" id="avatarPreview">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="user-info-card">
                    <h3><i class="fas fa-info-circle"></i> Account Info</h3>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Role:</span>
                        <span class="info-value role-badge <?= $user['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                            <i class="fas <?= $user['role'] === 'admin' ? 'fa-crown' : 'fa-user' ?>"></i>
                            <?= htmlspecialchars($user['role']) ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member since:</span>
                        <span class="info-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="profile-form-container">
                <form method="POST" action="" id="profileForm">
                    <div class="form-section">
                        <h2><i class="fas fa-user-circle"></i> Personal Information</h2>

                        <div class="form-group">
                            <label for="name">
                                <i class="fas fa-user"></i>
                                Full Name <span class="required">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                Email Address
                            </label>
                            <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small class="form-hint">Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="gender">
                                <i class="fas fa-venus-mars"></i>
                                Gender
                            </label>
                            <select id="gender" name="gender">
                                <option value="">Prefer not to say</option>
                                <option value="Male" <?= $user['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $user['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="avatar_url">
                                <i class="fas fa-image"></i>
                                Avatar URL
                            </label>
                            <input type="url" id="avatar_url" name="avatar_url" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" placeholder="https://example.com/avatar.jpg">
                            <small class="form-hint">Enter a URL for your profile picture</small>
                        </div>

                        <div class="form-group">
                            <label for="bio">
                                <i class="fas fa-comment"></i>
                                Bio
                            </label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small class="form-hint">Share your F1 passion, favorite driver, or anything else!</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../contact.php" class="btn">
                            <i class="fas fa-headset"></i> Something went wrong? Contact the admin for help!
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;

        const dbTheme = <?= json_encode($theme); ?>;

        function applyTheme(isDark) {
            if (isDark) {
                body.classList.add('dark-theme');
                themeToggle.classList.add('dark');
            } else {
                body.classList.remove('dark-theme');
                themeToggle.classList.remove('dark');
            }
        }

        applyTheme(dbTheme === 'dark');

        themeToggle.addEventListener('click', () => {
            const isNowDark = !body.classList.contains('dark-theme');
            applyTheme(isNowDark);
            $.ajax({
                type: "POST",
                url: "userUpdating.php",
                data: {
                    theme_preference: isNowDark ? 'dark' : 'light'
                },
                success: function(response) {
                    console.log("Theme saved to DB:", response);
                },
                error: function() {
                    console.error("Failed to save theme.");
                }
            });
        });

        // Live avatar preview
        const avatarUrlInput = document.getElementById('avatar_url');
        const avatarPreview = document.getElementById('avatarPreview');

        if (avatarUrlInput && avatarPreview) {
            avatarUrlInput.addEventListener('input', function() {
                const url = this.value;
                if (url) {
                    avatarPreview.src = url;
                    avatarPreview.onerror = function() {
                        this.src = '';
                        this.alt = 'Invalid image URL';
                    };
                }
            });
        }

        function resetForm() {
            document.getElementById('profileForm').reset();
        }

        // Auto-hide message after 5 seconds
        const messageDiv = document.querySelector('.message');
        if (messageDiv) {
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 300);
            }, 5000);
        }
    </script>
</body>

</html>