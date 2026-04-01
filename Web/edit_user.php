<?php
// Include database connection
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted data
    $matrixId = $_POST['Matrix'];
    $name = $_POST['Name'];
    $classId = $_POST['Class'];

    // Step 1: Update the user's information
    $stmt = $conn->prepare("UPDATE users SET Name = ?, Matrix = ? WHERE Matrix = ?");
    $stmt->bind_param('sss', $name, $matrixId, $matrixId);
    $stmt->execute();

    // Step 2: Update the enrollment's class
    $stmt = $conn->prepare("UPDATE enrollments SET Class_ID = ? WHERE Matrix = ?");
    $stmt->bind_param('is', $classId, $matrixId);
    $stmt->execute();

    // Return success response
    echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
}
?>
