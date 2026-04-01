<?php
// Prevent Clickjacking
header("X-Frame-Options: SAMEORIGIN");
// Force browsers to block detected XSS attacks
header("X-XSS-Protection: 1; mode=block");
// Prevent MIME-sniffing
header("X-Content-Type-Options: nosniff");
$servername = "localhost"; // Your server address
$username = "root";        // Your database username
$password = "";            // Your database password (leave empty for XAMPP)
$dbname = "silat_club_db"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
