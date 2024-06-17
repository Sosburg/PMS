<?php
require 'database_connection.php';
require 'email_functions.php'; // Include the email functions

if (isset($_GET['token'], $_GET['mysafa_id'])) {
    $approval_token = htmlspecialchars($_GET['token']);
    $mysafa_id = htmlspecialchars($_GET['mysafa_id']);

    // Update the approval status to 'rejected'
    $sql = "UPDATE player_approvals SET approval_status = 'rejected' WHERE mysafa_id = ? AND approval_token = ?";
    $stmt = $local_conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed: " . $local_conn->error);
        echo "An error occurred. Please try again.";
        exit();
    }
    $stmt->bind_param("ss", $mysafa_id, $approval_token);
    if ($stmt->execute()) {
        error_log("Approval status updated to 'rejected' for mysafa_id: $mysafa_id");

        // Start the session
        session_start();

        // Check if the username is set in the session
        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];

            // Fetch the email of the logged-in user from the users table
            $user_sql = "SELECT email FROM users WHERE username = ?";
            $user_stmt = $local_conn->prepare($user_sql);
            if (!$user_stmt) {
                error_log("Prepare failed: " . $local_conn->error);
                echo "An error occurred. Please try again.";
                exit();
            }
            $user_stmt->bind_param("s", $username);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result->num_rows > 0) {
                $user = $user_result->fetch_assoc();
                $email = $user['email'];

                // Send notification email to the user
                $subject = "Player Rejection Notification";
                $body = "The player has rejected the access to their details. Click <a href='http://localhost/MySafa/project/dashboard.php?status=rejected&mysafa_id=$mysafa_id'>here</a> to view the details.";
                sendEmail($subject, $body, $email);
            } else {
                error_log("Failed to fetch user email for username: $username");
            }
        } else {
            error_log("Username not set in session");
        }

        // End the session for the player
        session_destroy();
        echo "You have rejected the access to your details.";
    } else {
        error_log("Failed to update approval status for mysafa_id: $mysafa_id, error: " . $stmt->error);
        echo "An error occurred. Please try again.";
    }
} else {
    error_log("Invalid request: token or mysafa_id not set");
    echo "Invalid request.";
}

exit();
?>
