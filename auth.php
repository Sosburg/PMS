<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
} else {
    require 'database_connection.php';

    $user_id = $_SESSION['user_id'];
    $sql = "SELECT is_verified, username FROM users WHERE id = ?";
    $stmt = $local_conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (!$user['is_verified']) {
            // User is not verified, redirect to a page informing them to check their email
            header("Location: unverified.php");
            exit();
        } else {
            // Store the username in the session
            $_SESSION['username'] = $user['username'];
        }
    } else {
        // User not found, handle the error
        echo "User not found.";
        exit();
    }
}
?>
