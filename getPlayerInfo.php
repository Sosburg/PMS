<?php
header('Content-Type: application/json');
require 'vendor/autoload.php';
require 'database_connection.php';
require 'email_functions.php'; // Include the email_functions.php file

use Ramsey\Uuid\Uuid;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = parse_ini_file('config.ini', true);
$mail_config = $config['email'];

function generateMysafaID() {
    return 'mysafa_' . Uuid::uuid4()->toString();
}

function sendApprovalNotification($player_id, $email, $config, $mysafa_id) {
    global $local_conn;

    // Generate a new approval token
    $approval_token = bin2hex(random_bytes(16));

    $subject = 'Approval Request';
    $base_url = $config['app']['base_url'];
    $body = 'Please approve the access to your details: <a href="' . $base_url . '/approve.php?token=' . $approval_token . '&mysafa_id=' . $mysafa_id . '">Approve Access</a> or <a href="' . $base_url . '/reject.php?token=' . $approval_token . '&mysafa_id=' . $mysafa_id . '">Reject Access</a>';

    // Insert the approval request into the database
    $approval_sql = "INSERT INTO player_approvals (mysafa_id, player_id, approval_token) VALUES (?, ?, ?)";
    $approval_stmt = $local_conn->prepare($approval_sql);
    $approval_stmt->bind_param("sss", $mysafa_id, $player_id, $approval_token);
    $approval_stmt->execute();

    if (sendEmail($subject, $body, $email)) {
        error_log("Approval notification sent successfully to: $email");
    } else {
        error_log("Failed to send approval notification to: $email");
    }
}

function getPlayerInfo($player_id, $email, $config) {
    global $local_conn, $home_conn;

    // Check if player exists in the local database
    $sql = "SELECT * FROM players WHERE player_id = ?";
    $stmt = $local_conn->prepare($sql);
    $stmt->bind_param("s", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Player found in local database
        return $result->fetch_assoc();
    } else {
        // Check approval status
        $approval_sql = "SELECT approval_status FROM player_approvals WHERE player_id = ?";
        $approval_stmt = $local_conn->prepare($approval_sql);
        $approval_stmt->bind_param("s", $player_id);
        $approval_stmt->execute();
        $approval_result = $approval_stmt->get_result();

        if ($approval_result->num_rows > 0) {
            $approval_status = $approval_result->fetch_assoc()['approval_status'];

            if ($approval_status == 'approved') {
                // Fetch from home affairs database
                $homeAffairsInfo = getHomeAffairsInfo($player_id);
                if ($homeAffairsInfo) {
                    // Insert into local database and generate Mysafa ID
                    $mysafa_id = generateMysafaID();
                    $sql = "INSERT INTO players (mysafa_id, player_id, first_name, last_name, date_of_birth, image, email) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $local_conn->prepare($sql);
                    $stmt->bind_param("sssssss", $mysafa_id, $player_id, $homeAffairsInfo['name'], $homeAffairsInfo['surname'], $homeAffairsInfo['date_of_birth'], $homeAffairsInfo['image'], $email);
                    $stmt->execute();

                    // Return the newly inserted player info
                    return [
                        'mysafa_id' => $mysafa_id,
                        'player_id' => $player_id,
                        'first_name' => $homeAffairsInfo['name'],
                        'last_name' => $homeAffairsInfo['surname'],
                        'date_of_birth' => $homeAffairsInfo['date_of_birth'],
                        'image' => base64_encode($homeAffairsInfo['image']),
                        'approval_status' => 'approved'
                    ];
                } else {
                    return null;
                }
            } else {
                return ['status' => 'waiting_for_approval', 'approval_status' => $approval_status];
            }
        } else {
            // Insert into approval table and send notification
            $mysafa_id = generateMysafaID();
            $approval_sql = "INSERT INTO player_approvals (mysafa_id, player_id) VALUES (?, ?)";
            $approval_stmt = $local_conn->prepare($approval_sql);
            $approval_stmt->bind_param("ss", $mysafa_id, $player_id);
            $approval_stmt->execute();

            // Send notification to player
            sendApprovalNotification($player_id, $email, $config, $mysafa_id);

            return ['status' => 'waiting_for_approval', 'approval_status' => 'pending'];
        }
    }
}

function getHomeAffairsInfo($player_id) {
    global $home_conn;

    $sql = "SELECT * FROM home_affairs WHERE player_id = ?";
    $stmt = $home_conn->prepare($sql);
    $stmt->bind_param("s", $player_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

if (isset($_GET['player_id']) && isset($_GET['email'])) {
    $player_id = htmlspecialchars($_GET['player_id']);
    $email = htmlspecialchars($_GET['email']);
    $playerInfo = getPlayerInfo($player_id, $email, $config);
    echo json_encode($playerInfo);
} else {
    echo json_encode(['error' => 'Player ID or email not provided']);
}
?>
