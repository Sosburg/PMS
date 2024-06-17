<?php
session_start();
require 'database_connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $local_conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified']) {
                session_regenerate_id();
                $_SESSION['user_id'] = $user['id'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Please verify your email address.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-4 text-center">Sign In</h1>
        <form action="signin.php" method="POST" class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md space-y-4">
            <!-- Display error message -->
            <?php if (!empty($error_message)): ?>
                <div class="text-red-500"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <!-- Display success message -->
            <?php if (isset($_GET['success'])): ?>
                <div class="text-green-500"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            <div>
                <label for="username" class="block mb-1">Username</label>
                <input type="text" id="username" name="username" placeholder="Username" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <div>
                <label for="password" class="block mb-1">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required class="w-full px-3 py-2 border border-gray-300 rounded">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full hover:bg-blue-600">Sign In</button>
        </form>
        <div class="text-center mt-4">
            <a href="signup.php" class="text-blue-500">Don't have an account? Sign Up</a>
        </div>
        <div class="text-center mt-2">
            <a href="request_reset.php" class="text-blue-500">Forgot Password?</a>
        </div>
    </div>
</body>
</html>
