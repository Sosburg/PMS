<?php
require 'vendor/autoload.php';
require 'database_connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Debugging statement
        error_log("Resetting password with token: $token");

        $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
        $stmt = $local_conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
            $stmt = $local_conn->prepare($sql);
            $stmt->bind_param("ss", $hashed_password, $token);
            $stmt->execute();

            // Debugging statement
            error_log("Password reset successful for token: $token");

            $success_message = "Your password has been reset successfully!";
        } else {
            // Debugging statement
            error_log("Invalid or expired token: $token");

            $error_message = "Invalid or expired token.";
        }
    }
} else if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Debugging statement
    error_log("Received token from URL: $token");
} else {
    $error_message = "No token provided.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-4 text-center">Reset Password</h1>
        <form action="reset_password_form.php" method="POST" class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md space-y-4">
            <!-- Display error message -->
            <?php if (!empty($error_message)): ?>
                <div class="text-red-500"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <!-- Display success message -->
            <?php if (!empty($success_message)): ?>
                <div class="text-green-500"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div>
                <label for="new_password" class="block mb-1">New Password:</label>
                <input type="password" id="new_password" name="new_password" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label for="confirm_password" class="block mb-1">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full hover:bg-blue-600">Reset Password</button>
        </form>
    </div>
</body>
</html>
