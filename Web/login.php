<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize the email format before it even hits the database
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    // Prepared SQL query prevents SQL Injection
    $sql = "SELECT * FROM Users WHERE Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Compare plain text password (Update to password_verify() in version 2.0!)
        if ($password === $user['Password']) {
            
            // SECURITY ADDITION: Prevent session fixation attacks
            session_regenerate_id(true); 
            
            $_SESSION['user_id'] = $user['Matrix'];
            $_SESSION['user_name'] = $user['Name'];
            $_SESSION['user_role'] = $user['Role'];

            if ($_SESSION['user_role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: StudentDashboard.php');
            }
            exit();
        } else {
            // UI ADDITION: Clean pop-up error instead of a blank white page
            echo "<script>alert('Invalid password!'); window.history.back();</script>";
        }
    } else {
        // UI ADDITION: Clean pop-up error
        echo "<script>alert('No user found with that email address.'); window.history.back();</script>";
    }
    $stmt->close();
}
$conn->close();
?>