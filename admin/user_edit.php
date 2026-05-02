<?php
session_start();
require_once "../config.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$what = $_POST['what'] ?? '';

switch ($what) {
    case 'add':
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
        
        $query = "INSERT INTO users (name, email, password, role, created_at) VALUES ('$name', '$email', '$password', '$role', NOW())";
        if (mysqli_query($conn, $query)) {
            $id = mysqli_insert_id($conn);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;
        
    case 'edit':
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        
        $query = "UPDATE users SET name='$name', email='$email', role='$role' WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;
        
    case 'edit_full':
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $gender = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
        $bio = mysqli_real_escape_string($conn, $_POST['bio'] ?? '');
        $role = mysqli_real_escape_string($conn, $_POST['role']);
        $theme = mysqli_real_escape_string($conn, $_POST['theme_preference'] ?? 'light');
        $avatar = mysqli_real_escape_string($conn, $_POST['avatar_url'] ?? '');
        
        $query = "UPDATE users SET name='$name', email='$email', gender='$gender', bio='$bio', role='$role', theme_preference='$theme', avatar_url='$avatar' WHERE id=$id";
        
        // Update password only if provided
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET name='$name', email='$email', password='$password', gender='$gender', bio='$bio', role='$role', theme_preference='$theme', avatar_url='$avatar' WHERE id=$id";
        }
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;
        
    case 'get':
        $id = (int)$_POST['id'];
        $result = mysqli_query($conn, "SELECT id, name, email, gender, bio, role, theme_preference, avatar_url FROM users WHERE id = $id");
        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        break;
        
    case 'delete':
        $id = (int)$_POST['id'];
        // Prevent admin from deleting themselves
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            exit();
        }
        
        $query = "DELETE FROM users WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
?>