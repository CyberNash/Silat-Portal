<?php
session_start();
include 'config.php';  // Ensure you have a connection to your database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL query to fetch the user
    $sql = "SELECT * FROM Users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists and password matches
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Compare plain text password with stored password (since it's not hashed)
        if ($password === $user['Password']) {
            // Password is correct, create session for the user
            $_SESSION['user_id'] = $user['Matrix'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_role'] = $user['Role'];

            // Check user role and redirect accordingly
            if ($_SESSION['user_role'] === 'admin') {
                // Redirect to admin dashboard
                header('Location: admin.php');
            } else {
                // Redirect to student dashboard
                header('Location: StudentDashboard.php');
            }
            exit();
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No user found with that email address.";
    }
}
?>
