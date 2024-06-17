<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = parse_ini_file('config.ini', true);
$mail_config = $config['mail'];

function sendEmail($subject, $body, $email) {
    global $mail_config;

    $mail = new PHPMailer(true);
    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $mail_config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mail_config['smtp_username'];
        $mail->Password = $mail_config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mail_config['smtp_port'];

        // Email details
        $mail->setFrom($mail_config['smtp_from_email'], $mail_config['smtp_from_name']);
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send the email
        $mail->send();

        // Debugging statement
        error_log("Email sent successfully to: $email");

        return true;
    } catch (Exception $e) {
        // Log the error
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");

        // Display a user-friendly error message
        return false;
    }
}

function sendVerificationEmail($email, $verification_token) {
    $subject = 'Verify your email address';
    $body = 'Please click the following link to verify your email address: <a href="http://localhost/MySafa/project/verify.php?token=' . $verification_token . '">Verify Email</a>';
    if (sendEmail($subject, $body, $email)) {
        echo "Verification email sent successfully.";
    } else {
        echo "There was an error sending the verification email. Please try again later.";
    }
}

function sendApprovalEmail($email, $mysafa_id, $player_id) {
    global $config;

    $approval_token = bin2hex(random_bytes(16));
    $subject = 'Approval Request';
    $base_url = $config['app']['base_url'];
    $body = 'Please approve the access to your details: <a href="' . $base_url . '/approve.php?token=' . $approval_token . '&mysafa_id=' . $mysafa_id . '">Approve Access</a> or <a href="' . $base_url . '/reject.php?token=' . $approval_token . '&mysafa_id=' . $mysafa_id . '">Reject Access</a>';
    if (sendEmail($subject, $body, $email)) {
        error_log("Approval email sent successfully to: $email");
        echo "Approval email sent successfully.";
        return $approval_token;
    } else {
        error_log("There was an error sending the approval email to: $email");
        echo "There was an error sending the approval email. Please try again later.";
        return false;
    }
}

function sendPasswordResetEmail($email, $reset_token) {
    $subject = 'Password Reset';
    $body = 'Please click the following link to reset your password: <a href="http://localhost/MySafa/project/reset_password_form.php?token=' . $reset_token . '">Reset Password</a>';
    if (sendEmail($subject, $body, $email)) {
        echo "Password reset email sent successfully.";
    } else {
        echo "There was an error sending the password reset email. Please try again later.";
    }
}
?>
