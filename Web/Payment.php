<?php
session_start();
include('config.php');
// 1. Kick out anyone who isn't logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

// 2. Kick out anyone who has been idle for 30 minutes
$timeout_duration = 1800; 
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    echo "<script>alert('Session expired due to inactivity. Please log in again.'); window.location.href='../index.html';</script>";
    exit();
}

// 3. Update their activity timer since they just loaded this page
$_SESSION['LAST_ACTIVITY'] = time(); 

// Fetch user ID (Matrix) from session
$user_id = $_SESSION['user_id'];

// Fetch user's name and profile picture for the sidebar
$userQuery = "SELECT Name, profile_picture FROM Users WHERE Matrix = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$user_name = $user['Name']; // Store the name
$profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : "uploads/profile_pictures/default.png";

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if both fields are set and valid
    if (isset($_POST['Payment_Method']) && isset($_POST['Payment_Amount'])) {
        $payment_method = $_POST['Payment_Method'];
        $payment_amount = $_POST['Payment_Amount'];

        // Validate the payment amount (should be positive and non-zero)
        if ($payment_amount <= 0) {
            $error_message = "Please enter a valid payment amount.";
        } else {
            // Prepare SQL statement to insert payment into the database
            $insertPaymentQuery = "INSERT INTO payment (Matrix, Payment_Method, Payment_Amount, Payment_Date) 
                                   VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertPaymentQuery);
            $stmt->bind_param("ssd", $user_id, $payment_method, $payment_amount);

            if ($stmt->execute()) {
                // Redirect to the same page after successful submission
                header('Location: Payment.php');
                exit(); // Prevent further code execution after redirect
            } else {
                $error_message = "Error recording payment. Please try again.";
            }
        }
    } else {
        $error_message = "Please fill out all the required fields.";
    }
}

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

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tempusdominus-bootstrap-4@5.39.0/build/css/tempusdominus-bootstrap-4.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/icheck-bootstrap@3.0.1/icheck-bootstrap.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@1.13.3/css/OverlayScrollbars.min.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css">
 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<script data-cfasync="false" nonce="596cb9ab-5689-4950-bc2a-99360dfb9c3f">try{(function(w,d){!function(a,b,c,d){if(a.zaraz)console.error("zaraz is loaded twice");else{a[c]=a[c]||{};a[c].executed=[];a.zaraz={deferred:[],listeners:[]};a.zaraz._v="5848";a.zaraz._n="596cb9ab-5689-4950-bc2a-99360dfb9c3f";a.zaraz.q=[];a.zaraz._f=function(e){return async function(){var f=Array.prototype.slice.call(arguments);a.zaraz.q.push({m:e,a:f})}};for(const g of["track","set","debug"])a.zaraz[g]=a.zaraz._f(g);a.zaraz.init=()=>{var h=b.getElementsByTagName(d)[0],i=b.createElement(d),j=b.getElementsByTagName("title")[0];j&&(a[c].t=b.getElementsByTagName("title")[0].text);a[c].x=Math.random();a[c].w=a.screen.width;a[c].h=a.screen.height;a[c].j=a.innerHeight;a[c].e=a.innerWidth;a[c].l=a.location.href;a[c].r=b.referrer;a[c].k=a.screen.colorDepth;a[c].n=b.characterSet;a[c].o=(new Date).getTimezoneOffset();if(a.dataLayer)for(const k of Object.entries(Object.entries(dataLayer).reduce(((l,m)=>({...l[1],...m[1]})),{})))zaraz.set(k[0],k[1],{scope:"page"});a[c].q=[];for(;a.zaraz.q.length;){const n=a.zaraz.q.shift();a[c].q.push(n)}i.defer=!0;for(const o of[localStorage,sessionStorage])Object.keys(o||{}).filter((q=>q.startsWith("_zaraz_"))).forEach((p=>{try{a[c]["z_"+p.slice(7)]=JSON.parse(o.getItem(p))}catch{a[c]["z_"+p.slice(7)]=o.getItem(p)}}));i.referrerPolicy="origin";i.src="/cdn-cgi/zaraz/s.js?z="+btoa(encodeURIComponent(JSON.stringify(a[c])));h.parentNode.insertBefore(i,h)};["complete","interactive"].includes(b.readyState)?zaraz.init():a.addEventListener("DOMContentLoaded",zaraz.init)}}(w,d,"zarazData","script");window.zaraz._p=async bs=>new Promise((bt=>{if(bs){bs.e&&bs.e.forEach((bu=>{try{const bv=d.querySelector("script[nonce]"),bw=bv?.nonce||bv?.getAttribute("nonce"),bx=d.createElement("script");bw&&(bx.nonce=bw);bx.innerHTML=bu;bx.onload=()=>{d.head.removeChild(bx)};d.head.appendChild(bx)}catch(by){console.error(`Error executing script: ${bu}\n`,by)}}));Promise.allSettled((bs.f||[]).map((bz=>fetch(bz[0],bz[1]))))}bt()}));zaraz._p({"e":["(function(w,d){})(window,document)"]});})(window,document)}catch(e){throw fetch("/cdn-cgi/zaraz/t"),e;};</script></head>
<body class="hold-transition sidebar-mini layout-fixed">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="StudentDashboard.php" class="nav-link">Home</a>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Navbar Search -->
      <li class="nav-item">
        <a class="nav-link" data-widget="navbar-search" href="#" role="button">
          <i class="fas fa-search"></i>
        </a>
        <div class="navbar-search-block">
          <form class="form-inline">
            <div class="input-group input-group-sm">
              <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
              <div class="input-group-append">
                <button class="btn btn-navbar" type="submit">
                  <i class="fas fa-search"></i>
                </button>
                <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </li>
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">7</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">7 Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> 4 new messages
            <span class="float-right text-muted text-sm">3 mins</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> 3 new reports
            <span class="float-right text-muted text-sm">2 days</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>
       <?php include 'dark-mode.php'; ?>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="StudentDashboard.php" class="brand-link">
      <img src="pics/logoSilatCekak.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">PSSCM UniMAP</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-picture" class="profile-picture" 
          style="width: 40px; height:40px; border-radius: 50%; border: 0.5px solid #ddd;">
        </div>
        <div class="info">
          <a href="StudentDashboard.php" class="d-block"><?= htmlspecialchars($user_name); ?></a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="StudentDashboard.php" class="nav-link">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                Student Dashboard
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="Payment.php" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Payment Gateway
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="../index.html" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Log Out
              </p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Payment Layout</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="StudentDashboard.php">Home</a></li>
              <li class="breadcrumb-item active">Payment</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- INVOICE & HISTORY -->
    <section class="content">
      <div class="row">
        <div class="col-md-6">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Payment History</h3>

              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
            </div>
            
            <div class="card-body">
                <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Transferred Amount</th>
                        <th>Payment Method</th>
                        <th>Balance Left</th>
                        <th>Payment Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    $total_paid_so_far = 0; // Initialize total paid so far
                    foreach ($payments as $payment):
                        // Add the current payment amount to the total paid so far
                        $total_paid_so_far += $payment['Payment_Amount'];
                        
                        // Calculate the remaining balance after this payment
                        $remaining_balance = $total_amount_due - $total_paid_so_far;
                    ?>
                        <tr>
                            <td>RM <?= number_format($payment['Payment_Amount'], 2); ?></td>
                            <td><?= htmlspecialchars($payment['Payment_Method']); ?></td>
                            <td>RM <?= number_format($remaining_balance, 2); ?></td>
                            <td><?= htmlspecialchars(date('d-m-Y', strtotime($payment['Payment_Date']))); ?></td>
                            <td><a href="invoice.php" target="_blank" class="btn btn-primary">Print Invoice</a></td>
                        </tr>
                        <?php ?>
                        <?php endforeach; ?>
        
                  </tbody>
              </table>
           </div>
          </div>
          <!-- /.card -->
        </div>
        <div class="col-md-6">
    <div class="card card-secondary">
        <div class="card-header">
            <h3 class="card-title">Payment Methods</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                <!-- QR Code Image -->
                <div class="qr-code" style="text-align: center;">
                    <img src="pics/QR_RR.png" alt="QR Code" class="img-fluid" style="max-width: 250px; height: auto;">
                </div>

                <!-- JOMPAY ID Image -->
                <div class="jompay-id" style="text-align: center;">
                    <img src="pics/FakeJomPay.drawio.png" alt="JOMPAY ID" class="img-fluid" style="max-width: 250px; height: auto;">
                </div>
            </div>
                
                <!-- Reference 1: Matrix -->
                <div class="reference">
                    <p><strong>Reference 1: Matrix</strong>: 1234567890</p>
                </div>
                
                <!-- Reference 2: IC Number -->
                <div class="reference">
                    <p><strong>Reference 2: IC Number</strong>: 901234567890</p>
                </div>
            </div>
        <!-- /.card-body -->
        
        </div>
    <!-- /.Amount Payment -->
    <!-- Payment Form -->
                <form method="POST" action="Payment.php">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Amount Paid</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Choose a Payment Method:</h5>
                                    <select class="form-select mb-3" name="Payment_Method" required>
                                        <option value="QR Code">QR Code</option>
                                        <option value="JomPAY">JomPAY</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <h5>Enter Payment Amount (RM):</h5>
                                    <input type="number" class="form-control" name="Payment_Amount" placeholder="Enter amount" required>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary">Submit Payment</button>
                            </div>
                        </div>
                    </div>
                </form>
    </div>



    </section>


  <!-- /.content-wrapper -->
  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://adminlte.io/themes/v3/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://adminlte.io/themes/v3/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="https://adminlte.io/themes/v3/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="https://adminlte.io/themes/v3/dist/js/adminlte.min.js?v=3.2.0"></script>
</body>
</html>
