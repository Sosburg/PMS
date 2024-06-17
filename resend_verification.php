<?php
require 'database_connection.php';
require 'email_functions.php';

use Ramsey\Uuid\Uuid;

session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT email, verification_token, token_expiry FROM users WHERE id = ?";
    $stmt = $local_conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($user['verification_token'] && $user['token_expiry'] > date('Y-m-d H:i:s')) {
            // Resend the existing verification email
            sendVerificationEmail($user['email'], $user['verification_token']);
            header("Location: verification_sent.php");
            exit();
        } else {
            // Generate a new verification token and resend the email
            $token = Uuid::uuid4()->toString();
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 day')); // Token expiry time (1 day from now)

            $sql = "UPDATE users SET verification_token = ?, token_expiry = ? WHERE id = ?";
            $stmt = $local_conn->prepare($sql);
            $stmt->bind_param("ssi", $token, $expiry_time, $user_id);
            $stmt->execute();

            sendVerificationEmail($user['email'], $token);
            header("Location: verification_sent.php");
            exit();
        }
    } else {
        $error_message = "User not found.";
    }
} else {
    $error_message = "You need to be logged in to resend the verification email.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resend Verification Email</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-4 text-center">Resend Verification Email</h1>
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md space-y-4">
            <?php if (isset($error_message)): ?>
                <div class="text-red-500"><?php echo $error_message; ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
