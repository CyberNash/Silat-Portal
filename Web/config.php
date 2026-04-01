<?php
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
