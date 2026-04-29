<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate fields
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "All fields are required.";
        header("Location: index.php");
        exit();
    }

    // Fetch user by email
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            //  Successful login
            $_SESSION['user']    = $row['name'];
            $_SESSION['user_id'] = $row['id'];
            unset($_SESSION['login_error']);
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Incorrect password. Please try again.";
        }
    } else {
        $_SESSION['login_error'] = "No account found with that email.";
    }

    header("Location: index.php");
    exit();
}

// If accessed directly without POST, redirect home
header("Location: index.php");
exit();