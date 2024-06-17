<?php
require 'database_connection.php';

$sql = "SELECT pa.*, p.first_name, p.last_name
        FROM player_approvals pa
        JOIN players p ON pa.player_id = p.player_id
        WHERE pa.approval_status = 'pending'";
$stmt = $local_conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => "Database error: " . $local_conn->error]);
    exit();
}

$stmt->execute();
$result = $stmt->get_result();
$pending_approvals = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($pending_approvals);
?>
