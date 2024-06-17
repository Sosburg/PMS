<?php
require 'vendor/autoload.php';
require 'auth.php';
require 'database_connection.php';
require 'email_functions.php'; // Include the email_functions.php file

use Ramsey\Uuid\Uuid;

$error_message = null;
$success_message = null;
$pending_approvals = array();
$approval_status = null;
$mysafa_id = null;

// Check for status updates
if (isset($_GET['status'], $_GET['mysafa_id'])) {
    $status = htmlspecialchars($_GET['status'] ?? '');
    $mysafa_id = htmlspecialchars($_GET['mysafa_id'] ?? '');
    if ($status == 'approved') {
        $success_message = "The player has approved the access to their details.";
        $approval_status = 'approved';
    } elseif ($status == 'rejected') {
        $success_message = "The player has rejected the access to their details.";
        $approval_status = 'rejected';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $player_id = htmlspecialchars($_POST['player_id'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');

    // Reset the $player variable
    $player = null;

    // Check if the player exists in the local database
    $sql = "SELECT * FROM players WHERE player_id = ?";
    $stmt = $local_conn->prepare($sql);
    if (!$stmt) {
        $error_message = "Database error: " . $local_conn->error;
    } else {
        $stmt->bind_param("s", $player_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $player = $result->fetch_assoc();
            error_log("Player found in local database: " . print_r($player, true));
        } else {
            // Fetch player information from home affairs database
            $sql = "SELECT player_id, name AS first_name, surname AS last_name, date_of_birth, image FROM home_affairs WHERE player_id = ?";
            $stmt = $home_conn->prepare($sql);
            if (!$stmt) {
                $error_message = "Database error: " . $home_conn->error;
            } else {
                $stmt->bind_param("s", $player_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $mysafa_id = Uuid::uuid4()->toString();

                    // Create an approval request
                    $sql = "INSERT INTO player_approvals (player_id, mysafa_id, approval_status) VALUES (?, ?, 'pending')";
                    $stmt = $local_conn->prepare($sql);
                    if (!$stmt) {
                        $error_message = "Database error: " . $local_conn->error;
                    } else {
                        $stmt->bind_param("ss", $player_id, $mysafa_id);
                        $stmt->execute();

                        // Send approval email
                        $approval_token = sendApprovalEmail($email, $mysafa_id, $player_id);

                        if ($approval_token) {
                            error_log("Approval email sent successfully to: $email");
                            error_log("Approval token: $approval_token");
                            error_log("Mysafa ID: $mysafa_id");
                            error_log("Player ID: $player_id");

                            // Update the approval request with the token
                            $sql = "UPDATE player_approvals SET approval_token = ? WHERE mysafa_id = ?";
                            $stmt = $local_conn->prepare($sql);
                            if (!$stmt) {
                                $error_message = "Database error: " . $local_conn->error;
                            } else {
                                $stmt->bind_param("ss", $approval_token, $mysafa_id);
                                $stmt->execute();

                                // Fetch player information and create a new player record
                                $player = $result->fetch_assoc();
                                error_log("Player fetched from home affairs database: " . print_r($player, true));
                                $sql = "INSERT INTO players (mysafa_id, player_id, first_name, last_name, date_of_birth, image, email) VALUES (?, ?, ?, ?, ?, ?, ?)";
                                $stmt = $local_conn->prepare($sql);
                                if (!$stmt) {
                                    $error_message = "Database error: " . $local_conn->error;
                                } else {
                                    $stmt->bind_param("sssssss", $mysafa_id, $player['player_id'], $player['first_name'], $player['last_name'], $player['date_of_birth'], $player['image'], $email);
                                    $stmt->execute();

                                    $success_message = "Approval request created for the player. An email has been sent to the player for approval.";
                                }
                            }
                        } else {
                            error_log("Failed to send approval email to: $email");
                            $error_message = "Failed to send approval email.";
                        }
                    }
                } else {
                    $error_message = "Player not found in either database.";
                }
            }
        }
    }
}

// Fetch pending approvals
$sql = "SELECT pa.*, p.first_name, p.last_name
        FROM player_approvals pa
        JOIN players p ON pa.player_id = p.player_id
        WHERE pa.approval_status = 'pending'";
$stmt = $local_conn->prepare($sql);

if (!$stmt) {
    $error_message = "Database error: " . $local_conn->error;
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    $pending_approvals = $result->fetch_all(MYSQLI_ASSOC);
    error_log("Pending approvals: " . print_r($pending_approvals, true));
}

// Fetch all players
$sql = "SELECT * FROM players";
$stmt = $local_conn->prepare($sql);

if (!$stmt) {
    $error_message = "Database error: " . $local_conn->error;
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    $players = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: #333;
            display: block;
        }
        .sidebar a:hover {
            background-color: #ddd;
        }
        .content {
            margin-left: 260px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2 class="text-center">Dashboard</h2>
        <a href="#" onclick="showSection('status')">Status</a>
        <a href="#" onclick="showSection('player-reports')">Player Reports</a>
        <a href="#" onclick="showSection('search-player')">Search Player</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <div id="status" class="section">
            <h2>Status</h2>
            <?php if (isset($error_message)): ?>
                <p class="text-danger"><?php echo htmlspecialchars($error_message ?? ''); ?></p>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <p class="text-success"><?php echo htmlspecialchars($success_message ?? ''); ?></p>
            <?php endif; ?>

            <!-- Display approval status and options -->
            <?php if ($approval_status == 'approved'): ?>
                <div class="mt-5">
                    <h2 class="mb-4">Player Information</h2>
                    <div class="card">
                        <div class="card-body">
                            <p>Player has approved access to their details. You can now retrieve the information.</p>
                            <button onclick="retrievePlayerInfo('<?php echo $mysafa_id; ?>')" class="btn btn-primary mt-3">Retrieve Information</button>
                        </div>
                    </div>
                </div>
            <?php elseif ($approval_status == 'rejected'): ?>
                <div class="mt-5">
                    <h2 class="mb-4">Player Information</h2>
                    <div class="card">
                        <div class="card-body">
                            <p>Player has rejected access to their details. Information cannot be retrieved.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pending Approvals -->
            <?php if (!empty($pending_approvals)): ?>
                <div class="mt-5">
                    <h2 class="mb-4">Pending Approvals</h2>
                    <div class="card">
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Player Name</th>
                                        <th>Date Requested</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="pending-approvals-body">
                                    <?php foreach ($pending_approvals as $approval): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($approval['first_name'] . ' ' . $approval['last_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($approval['created_at'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="player-reports" class="section d-none">
            <h2>Player Reports</h2>
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Player ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Date of Birth</th>
                                <th>Email</th>
                                <th>Cellphone</th>
                                <th>Address</th>
                                <th>Team</th>
                                <th>Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($player['player_id'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['first_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['last_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['date_of_birth'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['cellphone'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['address'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($player['team'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($player['image']): ?>
                                            <img src="data:image/jpeg;base64,<?php echo base64_encode($player['image']); ?>" class="img-fluid rounded" style="max-width: 100px;">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="search-player" class="section d-none">
            <h2>Search Player</h2>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="player_id" class="form-label">Player ID:</label>
                            <input type="text" id="player_id" name="player_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Player Information Form -->
        <div id="player-info-form" class="mt-5 d-none">
            <h2 class="mb-4">Player Profile</h2>
            <div class="card">
                <div class="card-body">
                    <form id="player-profile-form">
                        <div class="mb-3">
                            <label for="profile_player_id" class="form-label">Player ID:</label>
                            <input type="text" id="profile_player_id" name="profile_player_id" readonly class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="profile_first_name" class="form-label">First Name:</label>
                            <input type="text" id="profile_first_name" name="profile_first_name" readonly class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="profile_last_name" class="form-label">Last Name:</label>
                            <input type="text" id="profile_last_name" name="profile_last_name" readonly class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="profile_date_of_birth" class="form-label">Date of Birth:</label>
                            <input type="text" id="profile_date_of_birth" name="profile_date_of_birth" readonly class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="profile_email" class="form-label">Email:</label>
                            <input type="email" id="profile_email" name="profile_email" readonly class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">Image:</label>
                            <img id="profile_image" class="img-fluid rounded">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showSection(sectionId) {
        document.querySelectorAll('.section').forEach(section => {
            section.classList.add('d-none');
        });
        document.getElementById(sectionId).classList.remove('d-none');
    }

    function retrievePlayerInfo(mysafa_id) {
        fetch('getPlayerInfo.php?mysafa_id=' + mysafa_id)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    // Populate the form with the player information
                    document.getElementById('profile_player_id').value = data.player_id;
                    document.getElementById('profile_first_name').value = data.first_name;
                    document.getElementById('profile_last_name').value = data.last_name;
                    document.getElementById('profile_date_of_birth').value = data.date_of_birth;
                    document.getElementById('profile_email').value = data.email;
                    document.getElementById('profile_image').src = 'data:image/jpeg;base64,' + data.image;

                    // Show the player information form
                    document.getElementById('player-info-form').classList.remove('d-none');
                }
            })
            .catch(error => console.error('Error:', error));
    }
    </script>
</body>
</html>
