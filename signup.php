<?php
require 'vendor/autoload.php';
require 'database_connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;

$config = parse_ini_file('config.ini', true);
$mail_config = $config['mail'];

function sendVerificationEmail($email, $token) {
    global $mail_config;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $mail_config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mail_config['smtp_username'];
        $mail->Password = $mail_config['smtp_password'];
        $mail->SMTPSecure = $mail_config['smtp_secure'];
        $mail->Port = $mail_config['smtp_port'];

        // Recipients
        $mail->setFrom($mail_config['smtp_from_email'], $mail_config['smtp_from_name']);
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification';
        $mail->Body = "Please click the link below to verify your email address:<br><br>
        <a href='http://localhost/MySafa/project/verify.php?token={$token}'>Verify Email</a>";

        $mail->send();
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $username = htmlspecialchars($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (!$email) {
        $error_message = "Please enter a valid email address";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if the username or email is already taken
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $local_conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existing_user = $result->fetch_assoc();
            if ($existing_user['username'] === $username) {
                $error_message = "Username already taken";
            } elseif ($existing_user['email'] === $email) {
                $error_message = "Email already taken";
            }
        } else {
            // Generate a unique token for email verification
            $token = Uuid::uuid4()->toString();
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 day')); // Token expiry time (1 day from now)

            // Insert the user into the database
            $sql = "INSERT INTO users (first_name, last_name, username, email, password, verification_token, reset_token_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $local_conn->prepare($sql);
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssssss", $first_name, $last_name, $username, $email, $hashed_password, $token, $expiry_time);

            if ($stmt->execute()) {
                sendVerificationEmail($email, $token);
                $success_message = "Registration successful! Please check your email for verification.";
                // Redirect to signin.php after successful registration
                header("Location: signin.php?success=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-3xl font-bold mb-4 text-center">Sign Up</h1>
        <form action="signup.php" method="POST" id="signup-form" class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md space-y-4" novalidate>
            <!-- Display error message -->
            <?php if (!empty($error_message)): ?>
                <div class="text-red-500"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <!-- Display success message -->
            <?php if (!empty($success_message)): ?>
                <div class="text-green-500"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <div>
                <label for="first-name" class="block mb-1">First Name</label>
                <input type="text" id="first-name" name="first_name" placeholder="e.g., John" required class="w-full px-3 py-2 border border-gray-300 rounded">
                <span class="text-red-500" id="first-name-error"></span>
            </div>
            <div>
                <label for="last-name" class="block mb-1">Last Name</label>
                <input type="text" id="last-name" name="last_name" placeholder="e.g., Doe" required class="w-full px-3 py-2 border border-gray-300 rounded">
                <span class="text-red-500" id="last-name-error"></span>
            </div>
            <div>
                <label for="username" class="block mb-1">Username</label>
                <input type="text" id="username" name="username" placeholder="e.g., johndoe" required class="w-full px-3 py-2 border border-gray-300 rounded">
                <span class="text-red-500" id="username-error"></span>
            </div>
            <div>
                <label for="email" class="block mb-1">Email</label>
                <input type="email" id="email" name="email" placeholder="e.g., john@example.com" required class="w-full px-3 py-2 border border-gray-300 rounded">
                <span class="text-red-500" id="email-error"></span>
            </div>
            <div>
                <label for="password" class="block mb-1">Password</label>
                <input type="password" id="password" name="password" placeholder="Password" required class="w-full px-3 py-2 border border-gray-300 rounded">
                <span class="text-red-500" id="password-error"></span>
            </div>
            <div>
                <label for="confirm-password" class="block mb-1">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required class="w-full px-3 py-2 border border-gray-300 rounded">
                <span class="text-red-500" id="confirm-password-error"></span>
            </div>
            <div>
                <span class="text-red-500" id="error-message"></span>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded w-full hover:bg-blue-600">Sign Up</button>
        </form>
        <div class="text-center mt-4">
            <a href="signin.php" class="text-blue-500">Already have an account? Sign In</a>
        </div>
    </div>
    <script src="js/validation.js"></script>
</body>
</html>
