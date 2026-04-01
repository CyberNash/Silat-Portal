<?php
session_start();

// 1. Kick out anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// 2. Kick out anyone who has been idle for 30 minutes
$timeout_duration = 1800; 
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    echo "<script>alert('Session expired due to inactivity. Please log in again.'); window.location.href='login.html';</script>";
    exit();
}

// 3. Update their activity timer since they just loaded this page
$_SESSION['LAST_ACTIVITY'] = time();
// Include database connection
include('config.php');

// Fetch available classes from the database
$classesQuery = "SELECT * FROM Classes";
$classesResult = $conn->query($classesQuery);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $matrix = $_POST['Matrix'] ?? '';
    $class_id = $_POST['class_ID'] ?? 0;

    // Validate form data
    if (empty($matrix) || empty($class_id)) {
        echo "<script>alert('Matrix number and class selection are required.');</script>";
        exit();
    }

    // Prepare SQL query to insert enrollment data
    $query = "INSERT INTO Enrollments (Matrix, Class_ID) VALUES (?, ?)";

    // Prepare statement
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("si", $matrix, $class_id);

        // Execute the query
        if ($stmt->execute()) {
            echo "<script>alert('Enrollment successful!'); window.location.href = 'Login.html';</script>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f9;
        }
        .container {
            width: 400px;
            background: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"], select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #007bff;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Enroll in a Class</h2>
        <form id="enrollmentForm" action="Enrollment.php" method="POST">
            <label for="matrix">Matrix Number</label>
            <input type="text" id="Matrix" name="Matrix" placeholder="Enter your matrix number" required>

            <label for="class">Select Class</label>
            <select id="class" name="class_ID" required>
                <?php while ($row = $classesResult->fetch_assoc()): ?>
                    <option value="<?= $row['Class_ID']; ?>"><?= $row['Class_Name']; ?> - RM <?= number_format($row['Class_Fee'], 2); ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Submit Enrollment</button>
        </form>
    </div>
</body>

</html>
