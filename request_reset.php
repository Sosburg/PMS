<?php
require 'vendor/autoload.php';
require 'database_connection.php';
require 'email_functions.php';

use Ramsey\Uuid\Uuid;

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Debugging statement
    error_log("Requesting password reset for email: $email");

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $local_conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = Uuid::uuid4()->toString();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $stmt = $local_conn->prepare($sql);
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // Debugging statement
        error_log("Token generated: $token for email: $email");

        sendPasswordResetEmail($email, $token);
        $success_message = "Password reset email has been sent!";
        header("Location: signin.php?success=" . urlencode($success_message));
        exit();
    } else {
        $error_message = "No user found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-4 text-center">Request Password Reset</h1>
        <form action="request_reset.php" method="POST" class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md space-y-4">
            <!-- Display error message -->
            <?php if (!empty($error_message)): ?>
                <div class="text-red-500"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <!-- Display success message -->
            <?php if (!empty($success_message)): ?>
                <div class="text-green-500"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <div>
                <label for="email" class="block mb-1">Email:</label>
                <input type="email" id="email" name="email" placeholder="e.g., john@example.com" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full hover:bg-blue-600">Request Reset</button>
        </form>
    </div>
</body>
</html>
