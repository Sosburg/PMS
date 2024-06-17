<?php
require 'vendor/autoload.php'; // If using Composer
require 'database_connection.php'; // Your database connection file

use FPDF\FPDF;

class PDF extends FPDF
{

    // Page header
    function Header()
    {
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        // Move to the right
        $this->Cell(80);
        // Title
        $this->Cell(30, 10, 'Players Information', 0, 0, 'C');
        // Line break
        $this->Ln(20);
    }

    // Player details table
    function PlayerTable($header, $data)
    {
        // Header
        foreach ($header as $col)
            $this->Cell(30, 7, $col, 1);
        $this->Ln();
        // Data
        foreach ($data as $row) {
            foreach ($row as $cell)
                $this->Cell(30, 6, $cell, 1);
            $this->Ln();
        }
    }
}

// Fetch all players
$sql = "SELECT * FROM players";
$stmt = $local_conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $local_conn->error);
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    $players = $result->fetch_all(MYSQLI_ASSOC);
}

// Column headings
$header = array('Player ID', 'First Name', 'Last Name', 'DOB', 'Email', 'Cellphone', 'Address', 'Team');

// Create PDF
$pdf = new PDF();
$pdf->SetFont('Arial', '', 12);
$pdf->AddPage();
$pdf->PlayerTable($header, $players);
$pdf->Output('D', 'players_information.pdf');
?>
