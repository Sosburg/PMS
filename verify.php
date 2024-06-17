<?php
require 'database_connection.php';

if (isset($_GET['token'])) {
    $token = htmlspecialchars($_GET['token']);
    echo "Token from URL: " . $token . "<br>";

    $sql = "SELECT * FROM users WHERE verification_token = ? AND reset_token_expiry > NOW()";
    $stmt = $local_conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Token from database: " . $user['verification_token'] . "<br>";
        echo "Token expiry: " . $user['reset_token_expiry'] . "<br>";

        if ($user) {
            $sql = "UPDATE users SET is_verified = 1, verification_token = NULL, reset_token_expiry = NULL WHERE id = ?";
            $stmt = $local_conn->prepare($sql);
            $stmt->bind_param("i", $user['id']);
            if ($stmt->execute()) {
                echo "Your email has been verified successfully.";
            } else {
                echo "An error occurred. Please try again.";
            }
        } else {
            echo "Invalid or expired token.";
        }
    } else {
        echo "Invalid or expired token.";
    }
} else {
    echo "No verification token provided.";
}

sleep(3); // Wait for 3 seconds (you can adjust the delay)

// Redirect to signin.php
header("Location: signin.php");
exit();
?>
