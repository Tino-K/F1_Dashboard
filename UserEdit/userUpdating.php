<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = $_SESSION['email'];

// Handle different types of updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle theme preference update
    if (isset($_POST['theme_preference'])) {
        $theme = $_POST['theme_preference'];
        
        // Validate theme value
        if ($theme !== 'dark' && $theme !== 'light') {
            $theme = 'light';
        }
        
        // Use prepared statement to prevent SQL injection
        $query = "UPDATE users SET theme_preference = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $theme, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'theme' => $theme, 'type' => 'theme']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error', 'type' => 'theme']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // Handle profile update (name, gender, bio, avatar_url)
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $gender = $_POST['gender'] ?? null;
        $bio = trim($_POST['bio'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        
        // Validate required fields
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required', 'type' => 'profile']);
            exit();
        }
        
        // Validate avatar URL if provided
        if (!empty($avatar_url) && !filter_var($avatar_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid avatar URL format', 'type' => 'profile']);
            exit();
        }
        
        // Handle empty values as NULL for optional fields
        $gender = empty($gender) ? null : $gender;
        $bio = empty($bio) ? null : $bio;
        $avatar_url = empty($avatar_url) ? null : $avatar_url;
        
        // Update user profile
        $query = "UPDATE users SET name = ?, gender = ?, bio = ?, avatar_url = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $gender, $bio, $avatar_url, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update session name
            $_SESSION['name'] = $name;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile updated successfully',
                'type' => 'profile',
                'data' => [
                    'name' => $name,
                    'gender' => $gender,
                    'bio' => $bio,
                    'avatar_url' => $avatar_url
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn), 'type' => 'profile']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // Handle single field updates (AJAX from inline editing)
    if (isset($_POST['field']) && isset($_POST['value'])) {
        $field = $_POST['field'];
        $value = trim($_POST['value']);
        
        // Allowed fields for update
        $allowed_fields = ['name', 'gender', 'bio', 'avatar_url'];
        
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Invalid field', 'type' => 'single']);
            exit();
        }
        
        // Validate based on field
        if ($field === 'name' && empty($value)) {
            echo json_encode(['success' => false, 'message' => 'Name cannot be empty', 'type' => 'single']);
            exit();
        }
        
        if ($field === 'avatar_url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid URL format', 'type' => 'single']);
            exit();
        }
        
        // Handle empty values as NULL
        if (empty($value)) {
            $value = null;
        }
        
        $query = "UPDATE users SET $field = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $value, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            if ($field === 'name') {
                $_SESSION['name'] = $value;
            }
            echo json_encode(['success' => true, 'message' => ucfirst($field) . ' updated successfully', 'type' => 'single']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error', 'type' => 'single']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
}

// If no valid action was specified
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?><?php
session_start();
require_once "./config.php";

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$email = $_SESSION['email'];

// Handle different types of updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle theme preference update
    if (isset($_POST['theme_preference'])) {
        $theme = $_POST['theme_preference'];
        
        // Validate theme value
        if ($theme !== 'dark' && $theme !== 'light') {
            $theme = 'light';
        }
        
        // Use prepared statement to prevent SQL injection
        $query = "UPDATE users SET theme_preference = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $theme, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'theme' => $theme, 'type' => 'theme']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error', 'type' => 'theme']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // Handle profile update (name, gender, bio, avatar_url)
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name'] ?? '');
        $gender = $_POST['gender'] ?? null;
        $bio = trim($_POST['bio'] ?? '');
        $avatar_url = trim($_POST['avatar_url'] ?? '');
        
        // Validate required fields
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required', 'type' => 'profile']);
            exit();
        }
        
        // Validate avatar URL if provided
        if (!empty($avatar_url) && !filter_var($avatar_url, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid avatar URL format', 'type' => 'profile']);
            exit();
        }
        
        // Handle empty values as NULL for optional fields
        $gender = empty($gender) ? null : $gender;
        $bio = empty($bio) ? null : $bio;
        $avatar_url = empty($avatar_url) ? null : $avatar_url;
        
        // Update user profile
        $query = "UPDATE users SET name = ?, gender = ?, bio = ?, avatar_url = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $gender, $bio, $avatar_url, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update session name
            $_SESSION['name'] = $name;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Profile updated successfully',
                'type' => 'profile',
                'data' => [
                    'name' => $name,
                    'gender' => $gender,
                    'bio' => $bio,
                    'avatar_url' => $avatar_url
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn), 'type' => 'profile']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required', 'type' => 'password']);
            exit();
        }
        
        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match', 'type' => 'password']);
            exit();
        }
        
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long', 'type' => 'password']);
            exit();
        }
        
        // Get current password from database
        $query = "SELECT password FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Verify current password (assuming passwords are stored as plain text - you should use password_hash!)
        if ($current_password !== $user['password']) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect', 'type' => 'password']);
            exit();
        }
        
        // Update password
        $query = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $new_password, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully', 'type' => 'password']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error', 'type' => 'password']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
    
    // Handle single field updates (AJAX from inline editing)
    if (isset($_POST['field']) && isset($_POST['value'])) {
        $field = $_POST['field'];
        $value = trim($_POST['value']);
        
        // Allowed fields for update
        $allowed_fields = ['name', 'gender', 'bio', 'avatar_url'];
        
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Invalid field', 'type' => 'single']);
            exit();
        }
        
        // Validate based on field
        if ($field === 'name' && empty($value)) {
            echo json_encode(['success' => false, 'message' => 'Name cannot be empty', 'type' => 'single']);
            exit();
        }
        
        if ($field === 'avatar_url' && !empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid URL format', 'type' => 'single']);
            exit();
        }
        
        // Handle empty values as NULL
        if (empty($value)) {
            $value = null;
        }
        
        $query = "UPDATE users SET $field = ? WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $value, $email);
        
        if (mysqli_stmt_execute($stmt)) {
            if ($field === 'name') {
                $_SESSION['name'] = $value;
            }
            echo json_encode(['success' => true, 'message' => ucfirst($field) . ' updated successfully', 'type' => 'single']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error', 'type' => 'single']);
        }
        
        mysqli_stmt_close($stmt);
        exit();
    }
}

// If no valid action was specified
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>