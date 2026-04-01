<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matrix = $_POST['Matrix'] ?? null;
    $amount = $_POST['Payment_Amount'] ?? 0;
    $method = $_POST['Payment_Method'] ?? 'Cash';

    if (!$matrix || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Payment (Matrix, Payment_Date, Payment_Method, Payment_Amount) VALUES (?, NOW(), ?, ?)");
    $stmt->bind_param("ssd", $matrix, $method, $amount);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add payment']);
    }

    $stmt->close();
    $conn->close();
}
?>
