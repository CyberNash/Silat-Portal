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
?>
include 'config.php';

// Handle deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
  $matrix = $_POST['delete_user'];

  // Start transaction
  $conn->begin_transaction();
  try {
      // Delete related records from `enrollments`
      $stmt = $conn->prepare("DELETE FROM enrollments WHERE Matrix = ?");
      $stmt->bind_param("s", $matrix);
      $stmt->execute();

      // Delete related records from `payment`
      $stmt = $conn->prepare("DELETE FROM payment WHERE Matrix = ?");
      $stmt->bind_param("s", $matrix);
      $stmt->execute();

      // Delete the user from `users`
      $stmt = $conn->prepare("DELETE FROM users WHERE Matrix = ?");
      $stmt->bind_param("s", $matrix);
      $stmt->execute();

      // Commit transaction
      $conn->commit();
      echo "<script>alert('User and related data deleted successfully.');</script>";
  } catch (Exception $e) {
      // Rollback transaction on error
      $conn->rollback();
      echo "<script>alert('Error deleting user: " . $e->getMessage() . "');</script>";
  }
}


// Determine sort_by parameter
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'Matrix';

// Map user-friendly sort options to actual column names
$valid_columns = [
    'KK' => 'u.KK', // Sorting by Address (KK)
    'Payment_Status' => 'Payment_Status',
    'Class_Name' => 'Class_Name',
];
$order_column = isset($valid_columns[$sort_by]) ? $valid_columns[$sort_by] : 'u.Matrix';

// Fetch data for roster
$sql_roster = "
    SELECT 
        u.Matrix,
        u.Name,
        IFNULL(c.Class_Name, 'N/A') AS Class_Name,
        IFNULL(SUM(p.Payment_Amount), 0) AS Payment_Amount,
        IFNULL(c.Class_Fee, 0) AS Class_Fee,
        IF(IFNULL(SUM(p.Payment_Amount), 0) >= IFNULL(c.Class_Fee, 0), 'Success', 'Pending') AS Payment_Status
    FROM Users u
    LEFT JOIN Enrollments e ON u.Matrix = e.Matrix
    LEFT JOIN Classes c ON e.Class_ID = c.Class_ID
    LEFT JOIN Payment p ON u.Matrix = p.Matrix
    WHERE u.Role != 'admin'
    GROUP BY u.Matrix, u.Name, c.Class_Name, c.Class_Fee
    ORDER BY u.Matrix ASC";

$result_roster = $conn->query($sql_roster);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Treasury Page</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tempusdominus-bootstrap-4@5.39.0/build/css/tempusdominus-bootstrap-4.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/icheck-bootstrap@3.0.1/icheck-bootstrap.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.3/css/OverlayScrollbars.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css">
  
<body class="hold-transition layout-top-nav">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
      <a href="#" class="navbar-brand">
        <img src="pics/logoSilatCekak.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">PSSCM UniMAP</span>
      </a>

      <button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse order-3" id="navbarCollapse">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
            <a href="admin.php" class="nav-link">Home</a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link">Details</a>
          </li>
           <?php include 'dark-mode.php'; ?>
          <li class="nav-item dropdown">
            <a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Dropdown</a>
            <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow">
              <li><a href="#" class="dropdown-item">Setting</a></li>
              <li><a href="Login.html" class="dropdown-item">Logout</a></li>
            </ul>
          </li>
        </ul>
    </div>
  </nav>
  <!-- /.navbar -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">PSSCM ADMIN <small>Student Listing</small></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item"><a href="#">Admin</a></li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <section class="content">
      <div class="container"> 
          <!-- Default box -->
          <div class="card">
              <div class="card-header">
                  <h3 class="card-title">List</h3>
                  <div class="card-tools">
                      <!-- Dropdowns for sorting -->
                      <div class="btn-group">
                          <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Sort By Address
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item sort-option" data-sort-by="KK" data-filter="PF1">PF1</a>
                                <a class="dropdown-item sort-option" data-sort-by="KK" data-filter="PF2">PF2</a>
                                <a class="dropdown-item sort-option" data-sort-by="KK" data-filter="Bumita">Bumita</a>
                                <a class="dropdown-item sort-option" data-sort-by="KK" data-filter="Uniciti">Uniciti</a>
                                <a class="dropdown-item sort-option" data-sort-by="KK" data-filter="Wang Ulu">Wang Ulu</a>
                                <a class="dropdown-item sort-option" data-sort-by="KK" data-filter="Simpang Empat">Simpang Empat</a>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Sort By Payment Status
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item sort-option" data-sort-by="Payment_Status" data-filter="Fully Paid">Fully Paid</a>
                                <a class="dropdown-item sort-option" data-sort-by="Payment_Status" data-filter="Pending Payment">Pending Payment</a>
                                <a class="dropdown-item sort-option" data-sort-by="Payment_Status" data-filter="No Payment">No Payment</a>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Sort By Class
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item sort-option" data-sort-by="Class_Name" data-filter="Persatuan">Persatuan</a>
                                <a class="dropdown-item sort-option" data-sort-by="Class_Name" data-filter="Ko-kurikulum">Ko-kurikulum</a>
                                <a class="dropdown-item sort-option" data-sort-by="Class_Name" data-filter="Ko-k dan Persatuan">Ko-k dan Persatuan</a>
                            </div>
                        </div>

                      </div>
                </div>
              </div>
              <div class="card-body p-0">
                  <table class="table table-striped projects">
                      <thead>
                          <tr>
                              <th style="width: 1%">ID</th>
                              <th style="width: 10%">Matrix</th>
                              <th style="width: 25%">Name</th>
                              <th>Class</th>
                              <th>Payment</th>
                              <th style="width: 8%" class="text-center">Status</th>
                              <th style="width: 25%"></th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php 
                          if ($result_roster->num_rows > 0) {
                              $id = 1;
                              while ($row = $result_roster->fetch_assoc()) {
                                  $paymentAmount = number_format($row['Payment_Amount'], 2);
                                  $classFee = number_format($row['Class_Fee'], 2);
                                  $statusClass = ($row['Payment_Status'] === 'Success') ? 'badge-success' : 'badge-warning';
                                  ?>
                                  <tr>
                                      <td><?php echo $id++; ?></td>
                                      <td><a><?php echo htmlspecialchars($row['Matrix']); ?></a></td>
                                      <td><a><?php echo htmlspecialchars($row['Name']); ?></a></td>
                                      <td><?php echo htmlspecialchars($row['Class_Name']); ?></td>
                                      <td><a>RM<?php echo $paymentAmount; ?></a></td>
                                      <td class="project-state">
                                          <span class="badge <?php echo $statusClass; ?>">
                                              <?php echo htmlspecialchars($row['Payment_Status']); ?>
                                          </span>
                                      </td>
                                      <td class="project-actions text-right">
                                        <a class="btn btn-primary btn-sm cash-button" data-matrix="<?php echo htmlspecialchars($row['Matrix']); ?>" href="#">
                                          <i class="fas fa-folder"></i> Cash
                                        </a>
                                        <a class="btn btn-info btn-sm" href="#">
                                            <i class="fas fa-pencil-alt"></i> Edit
                                        </a>
                                        <!-- Updated Delete Button -->
                                        <form method="POST" action="roster.php" style="display: inline;">
                                            <input type="hidden" name="delete_user" value="<?php echo htmlspecialchars($row['Matrix']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user and all related data?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                      </td>
                                  </tr>
                                  <?php
                              }
                          } else {
                              ?>
                              <tr>
                                  <td colspan="7" class="text-center">No data available</td>
                              </tr>
                              <?php
                          }
                          ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
    </section>
    <!-- Modal for Adding Cash Payment -->
      <div class="modal fade" id="cashPaymentModal" tabindex="-1" role="dialog" aria-labelledby="cashPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="cashPaymentModalLabel">Add Cash Payment</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form id="cashPaymentForm">
              <div class="modal-body">
                <input type="hidden" id="matrixId" name="Matrix">
                <div class="form-group">
                  <label for="paymentAmount">Payment Amount (RM)</label>
                  <input type="number" class="form-control" id="paymentAmount" name="Payment_Amount" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                  <label for="paymentMethod">Payment Method</label>
                  <select class="form-control" id="paymentMethod" name="Payment_Method" required>
                    <option value="Cash">Cash</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Payment</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <!-- Edit Modal -->
      <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="editUserForm">
                <input type="hidden" id="editMatrixId" name="Matrix">
                <div class="form-group">
                  <label for="editName">Name</label>
                  <input type="text" class="form-control" id="editName" name="Name" required>
                </div>
                <div class="form-group">
                  <label for="editMatrix">Matrix</label>
                  <input type="text" class="form-control" id="editMatrix" name="Matrix" required>
                </div>
                <div class="form-group">
                  <label for="editClass">Class</label>
                  <select class="form-control" id="editClass" name="Class" required>
                    <option value="">Select Class</option>
                    <!-- Dynamically populated options -->
                    <?php
                    $classQuery = "SELECT * FROM classes";
                    $classResult = $conn->query($classQuery);
                    while ($class = $classResult->fetch_assoc()) {
                        echo "<option value='{$class['Class_ID']}'>{$class['Class_Name']}</option>";
                    }
                    ?>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </form>
            </div>
          </div>
        </div>
      </div>
  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>

<!-- jQuery -->
<script src="https://adminlte.io/themes/v3/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://adminlte.io/themes/v3/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://adminlte.io/themes/v3/dist/js/adminlte.min.js?v=3.2.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // When "Cash" button is clicked
  document.querySelectorAll('.cash-button').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const matrixId = this.getAttribute('data-matrix');
      document.getElementById('matrixId').value = matrixId; // Set Matrix ID in the form
      $('#cashPaymentModal').modal('show'); // Show the modal
    });
  });

  // Handle form submission
  document.getElementById('cashPaymentForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('add_payment.php', {
      method: 'POST',
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Close the modal
          $('#cashPaymentModal').modal('hide');

          // Update the table dynamically
          const matrixId = document.getElementById('matrixId').value;
          const paymentAmount = parseFloat(document.getElementById('paymentAmount').value).toFixed(2);

          // Find the row corresponding to the matrix ID and update payment amount
          const row = document.querySelector(`a[data-matrix="${matrixId}"]`).closest('tr');
          const paymentCell = row.querySelector('td:nth-child(5) a');
          const statusBadge = row.querySelector('td:nth-child(6) .badge');

          // Update payment amount
          const currentAmount = parseFloat(paymentCell.textContent.replace('RM', '')) || 0;
          const newAmount = currentAmount + parseFloat(paymentAmount);
          paymentCell.textContent = `RM${newAmount.toFixed(2)}`;

          // Update payment status (Success/Pending)
          const classFee = parseFloat(row.querySelector('td:nth-child(4)').dataset.fee) || 0;
          if (newAmount >= classFee) {
            statusBadge.textContent = 'Success';
            statusBadge.classList.remove('badge-warning');
            statusBadge.classList.add('badge-success');
          }

          alert('Payment added successfully!');
        } else {
          alert('Error adding payment: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Cash error occurred.');
      });
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // When "Edit" button is clicked
  document.querySelectorAll('.btn-info').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      
      const matrixId = this.closest('tr').querySelector('td:nth-child(2) a').textContent;
      const name = this.closest('tr').querySelector('td:nth-child(3) a').textContent;
      const className = this.closest('tr').querySelector('td:nth-child(4)').textContent;

      // Populate the modal fields with the current user data
      document.getElementById('editMatrixId').value = matrixId;
      document.getElementById('editName').value = name;
      document.getElementById('editMatrix').value = matrixId;
      // Select the correct class based on current class
      const classSelect = document.getElementById('editClass');
      for (let i = 0; i < classSelect.options.length; i++) {
        if (classSelect.options[i].text === className) {
          classSelect.selectedIndex = i;
          break;
        }
      }

      $('#editUserModal').modal('show');
    });
  });

  // Handle form submission
  document.getElementById('editUserForm').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('edit_user.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update the table with the new values
        const matrixId = document.getElementById('editMatrixId').value;
        const row = document.querySelector(`a[data-matrix="${matrixId}"]`).closest('tr');
        row.querySelector('td:nth-child(3) a').textContent = document.getElementById('editName').value;
        row.querySelector('td:nth-child(4)').textContent = document.getElementById('editClass').options[document.getElementById('editClass').selectedIndex].text;

        // Close the modal
        $('#editUserModal').modal('hide');
        alert('User updated successfully!');
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Edit User error occurred.');
    });
  });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Attach event listeners to dropdown items
    document.querySelectorAll('.sort-option').forEach(function (item) {
        item.addEventListener('click', function () {
            const sortBy = this.getAttribute('data-sort-by');
            const filter = this.getAttribute('data-filter');

            // Use AJAX to reload the table rows based on sorting and filtering
            fetch(`roster_sort.php?sort_by=${sortBy}&filter=${filter}`)
                .then(response => response.text())
                .then(data => {
                    document.querySelector('table tbody').innerHTML = data;
                })
                .catch(error => console.error('Error:', error));
        });
    });
});
</script>

</body>
</html>