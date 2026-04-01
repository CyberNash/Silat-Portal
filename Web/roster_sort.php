<?php
// Include the database configuration file
include 'config.php';

// Determine the column to sort by and filter options
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'Matrix';
$filter = isset($_GET['filter']) ? $_GET['filter'] : null;
$sort_direction = isset($_GET['sort_direction']) && ($_GET['sort_direction'] === 'DESC' || $_GET['sort_direction'] === 'ASC') ? $_GET['sort_direction'] : 'ASC';

// Map user-friendly sort options to actual column names
$valid_columns = [
    'KK' => 'u.KK',
    'Payment_Status' => 'Payment_Status',
    'Class_Name' => 'c.Class_Name',
];

// Validate the column to sort by
$order_column = isset($valid_columns[$sort_by]) ? $valid_columns[$sort_by] : 'u.Matrix';

// Start building the SQL query
$sql_roster = "
    SELECT 
        u.Matrix,
        u.Name,
        u.KK,
        IFNULL(c.Class_Name, 'N/A') AS Class_Name,
        IFNULL(SUM(p.Payment_Amount), 0) AS Payment_Amount,
        IFNULL(c.Class_Fee, 0) AS Class_Fee,
        CASE 
            WHEN IFNULL(SUM(p.Payment_Amount), 0) >= IFNULL(c.Class_Fee, 0) THEN 'Fully Paid'
            WHEN IFNULL(SUM(p.Payment_Amount), 0) > 0 THEN 'Pending Payment'
            ELSE 'No Payment'
        END AS Payment_Status
    FROM Users u
    LEFT JOIN Enrollments e ON u.Matrix = e.Matrix
    LEFT JOIN Classes c ON e.Class_ID = c.Class_ID
    LEFT JOIN Payment p ON u.Matrix = p.Matrix
    WHERE u.Role != 'admin'
";

// Add a filter condition if provided
if ($filter) {
    if ($sort_by === 'KK') {
        $sql_roster .= " AND u.KK = '$filter'";
    } elseif ($sort_by === 'Class_Name') {
        $sql_roster .= " AND c.Class_Name = '$filter'";
    }
}

// Group by necessary fields
$sql_roster .= "
    GROUP BY u.Matrix, u.Name, u.KK, c.Class_Name, c.Class_Fee
";

// Add a filter condition for Payment_Status after grouping
if ($filter && $sort_by === 'Payment_Status') {
    $sql_roster .= " HAVING Payment_Status = '$filter'";
}

// Apply sorting
$sql_roster .= "
    ORDER BY $order_column $sort_direction
";

// Execute the query
$result_roster = $conn->query($sql_roster);

// Output the table rows
if ($result_roster->num_rows > 0) {
    $id = 1;
    while ($row = $result_roster->fetch_assoc()) {
        $paymentAmount = number_format($row['Payment_Amount'], 2);
        $statusClass = ($row['Payment_Status'] === 'Fully Paid') ? 'badge-success' : (($row['Payment_Status'] === 'Pending Payment') ? 'badge-warning' : 'badge-danger');

        echo "
            <tr>
                <td>" . $id++ . "</td>
                <td>" . htmlspecialchars($row['Matrix']) . "</td>
                <td>" . htmlspecialchars($row['Name']) . "</td>
                <td>" . htmlspecialchars($row['KK']) . "</td>
                <td>" . htmlspecialchars($row['Class_Name']) . "</td>
                <td>RM{$paymentAmount}</td>
                <td>
                    <span class='badge {$statusClass}'>" . htmlspecialchars($row['Payment_Status']) . "</span>
                </td>
                <td class='project-actions text-right'>
                    <!-- Action buttons (e.g., Edit, Delete) -->
                </td>
            </tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center'>No data available</td></tr>";
}
?>
