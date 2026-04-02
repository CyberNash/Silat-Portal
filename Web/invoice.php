<?php
session_start();
include('config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}
$timeout_duration = 1800; 
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    echo "<script>alert('Session expired due to inactivity. Please log in again.'); window.location.href='../index.html';</script>";
    exit();
}
// Fetch user ID (Matrix) from session
$user_id = $_SESSION['user_id'];

// Fetch user details (Name, Email, etc.)
$userQuery = "SELECT Matrix, Name, Email, phone_number FROM Users WHERE Matrix = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $user_id); // Bind Matrix (user_id)
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
// Fetch all payments for the logged-in user
$sql = "
    SELECT Payment_Amount, Payment_Method, Payment_Date
    FROM payment 
    WHERE Matrix = ?
    ORDER BY Payment_Date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id); // Bind Matrix (user_id)
$stmt->execute();
$result = $stmt->get_result();

// Fetch all rows into an array
$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
}

// Assume total amount due is RM 150 (or fetch dynamically)
$total_amount_due = 150;

// Calculate total paid amount
$total_paid = array_sum(array_column($payments, 'Payment_Amount'));

// Calculate balance left
$remaining_balance = $total_amount_due - $total_paid;

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }
        .invoice-header {
            background-color: #343a40;
            color: #ffffff;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .invoice-header img {
            max-height: 50px;
        }
        .invoice-body {
            padding: 20px;
            background: #ffffff;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .invoice-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .premium-title {
            font-size: 1.5rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .table th {
            background-color: #6c757d;
            color: #ffffff;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: #ffffff;
            }
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <!-- Invoice Header -->
                <div class="invoice-header text-center">
                    <div class="row">
                        <div class="col-md-6 text-start">
                            <img src="pics/logoSilatCekak.png" alt="Silat Club Logo"> <!-- Add your logo path -->
                        </div>
                        <div class="col-md-6 text-end">
                            <p class="mb-0">Persatuan Silat Cekak Malaysia UniMAP</p>
                            <p class="mb-0">UniMAP, Kampus Alam Pauh, Perlis, Malaysia</p>
                            <p>Email: silatnimep@example.com | Phone: +60 123-456-789</p>
                        </div>
                    </div>
                </div>

                <!-- Invoice Body -->
                <div class="invoice-body">
                    <div class="row">
                        <div class="col-6">
                            <h5>Invoice To:</h5>
                        
                            <p>Name: <?php echo $user['Name']; ?></p>
                            <p>Email: <?php echo $user['Email']; ?></p>
                            <p>Phone: <?php echo $user['phone_number']; ?></p>
                            <p>Matrix: <?php echo $user['Matrix']; ?></p>
                        </div>
                        <div class="col-6 text-end">
                            <h5>Invoice Details:</h5>
                            <p>Total Class Fee : RM <?php echo number_format($total_amount_due, 2); ?></p>
                            <p>Total Amount Paid: RM <?php echo number_format($total_paid, 2); ?></p>
                            <p>Remaining Balance: RM <?php echo number_format($remaining_balance, 2); ?></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <table class="table table-bordered mt-4">
                                <thead>
                                    <tr>
                                        <th>Transferred Amount</th>
                                        <th>Payment Method</th>
                                        <th>Balance Left</th>
                                        <th>Payment Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                // Initialize the remaining balance as the total amount due
                                $current_balance = $total_amount_due;
                                $payments = array_reverse($payments);
                                foreach ($payments as $payment) {
                                    // Subtract the current payment amount from the remaining balance
                                    
                                    $current_balance -= $payment['Payment_Amount'];
                                    
                                ?>
                                    <tr>
                                        <td>RM <?php echo number_format($payment['Payment_Amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['Payment_Method']); ?></td>
                                        <td>RM <?php echo number_format($current_balance, 2); ?></td>
                                        <td><?php echo date("d-m-Y", strtotime($payment['Payment_Date'])); ?></td>
                                    </tr>
                                <?php
                                }
                                ?>
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Invoice Footer -->
                <div class="invoice-footer">
                    <p>Thank you for being a part of Silat Club!</p>
                    <p>Please contact us for any inquiries regarding your payment.</p>
                </div>

                <!-- Actions -->
                <div class="text-center mt-4 no-print">
                    <button onclick="window.print()" class="btn btn-success">Print Invoice</button>
                    <a href="payment.php" class="btn btn-secondary">Back to Payment</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
