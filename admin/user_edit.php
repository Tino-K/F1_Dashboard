<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== "admin") {
    header("Location: ../index.php");
    exit();
}

require_once "../config.php";
if (!empty($_POST['what'])) {
    $what = $_POST['what'];
    // DELETE
    if ($what == "delete" && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM users WHERE id = $id");
        echo "OK";
        exit;
    }
    //edit
    if ($what == "edit" &&!empty($_POST['id']) &&!empty($_POST['name']) &&!empty($_POST['email']) &&!empty($_POST['role'])
    ) {
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $role = $conn->real_escape_string($_POST['role']);
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email', role='$role' WHERE id=$id");
        echo "OK";
        exit;
    }
    //add
    if (
        $what == "add" && !empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['role'])
    ) {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $role = $conn->real_escape_string($_POST['role']);
        mysqli_query($conn, "INSERT INTO users (name, email, role) VALUES ('$name','$email','$role')");
        echo "OK";
        exit;
    }
}
