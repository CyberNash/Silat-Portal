<?php
session_start();
// Include your database connection file
include 'config.php';

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

// Fetch aggregated data in one go where possible
$sql_aggregates = "
    SELECT
        (SELECT COUNT(*) FROM Users WHERE Role != 'admin') AS total_users,
        (SELECT COUNT(DISTINCT Matrix) FROM Enrollments WHERE Matrix IN (SELECT Matrix FROM Users WHERE Role != 'admin')) AS total_enrollments,
        (SELECT SUM(Payment_Amount) FROM Payment WHERE Matrix IN (SELECT Matrix FROM Users WHERE Role != 'admin')) AS total_payment,
        (SELECT SUM(c.Class_Fee) 
         FROM Enrollments e 
         JOIN Classes c ON e.Class_ID = c.Class_ID 
         WHERE e.Matrix IN (SELECT Matrix FROM Users WHERE Role != 'admin')) AS total_fees
";
$result_aggregates = $conn->query($sql_aggregates);
$aggregates = $result_aggregates->fetch_assoc();

$totalUsers = $aggregates['total_users'];
$totalEnrolledUsers = $aggregates['total_enrollments'];
$totalPayment = $aggregates['total_payment'];
$totalFees = $aggregates['total_fees'];

// Calculate the payment rate
$paymentRate = ($totalFees > 0) ? ($totalPayment / $totalFees) * 100 : 0;

// Fetch user payments and calculate dues
$sql_users_payments = "
    SELECT 
        u.Name,
        IFNULL(u.profile_picture, 'uploads/profile_pictures/default.png') AS profile_picture,
        c.Class_Fee AS Fee,
        SUM(p.Payment_Amount) AS AmountPaid,
        (c.Class_Fee - IFNULL(SUM(p.Payment_Amount), 0)) AS Due
    FROM Users u
    LEFT JOIN Enrollments e ON u.Matrix = e.Matrix
    LEFT JOIN Classes c ON e.Class_ID = c.Class_ID
    LEFT JOIN Payment p ON u.Matrix = p.Matrix
    WHERE u.Role != 'admin'
    GROUP BY u.Matrix, c.Class_Fee
    ORDER BY u.Name ASC";
$result_users_payments = $conn->query($sql_users_payments);

// Fetch payment percentage per class
$sql_class_payment = "
    SELECT 
        c.Class_ID,
        c.Class_Name,
        c.Class_Fee,
        COUNT(DISTINCT e.Matrix) AS Total_Students,
        SUM(p.Payment_Amount) AS Total_Paid,
        (COUNT(DISTINCT e.Matrix) * c.Class_Fee) AS Expected_Payment,
        (IFNULL(SUM(p.Payment_Amount), 0) / (COUNT(DISTINCT e.Matrix) * c.Class_Fee)) * 100 AS Payment_Percentage
    FROM Classes c
    LEFT JOIN Enrollments e ON c.Class_ID = e.Class_ID
    LEFT JOIN Payment p ON e.Matrix = p.Matrix
    GROUP BY c.Class_ID, c.Class_Name, c.Class_Fee";
$result_class_payment = $conn->query($sql_class_payment);

// Fetch user registrations per day excluding admins
$sql_registration_data = "
    SELECT 
        DATE(registration_date) AS registration_date, 
        COUNT(*) AS user_count 
    FROM Users 
    WHERE Role != 'admin'
    GROUP BY registration_date 
    ORDER BY registration_date";
$result_registration_data = $conn->query($sql_registration_data);

$dates = [];
$user_counts = [];
while ($row = $result_registration_data->fetch_assoc()) {
    $dates[] = $row['registration_date'];
    $user_counts[] = $row['user_count'];
}

// Fetch total payments per class
$sql_payment_data = "
    SELECT 
        c.Class_Name,
        SUM(p.Payment_Amount) AS TotalPayment
    FROM Payment p
    JOIN Enrollments e ON p.Matrix = e.Matrix
    JOIN Classes c ON e.Class_ID = c.Class_ID
    WHERE p.Matrix IN (SELECT Matrix FROM Users WHERE Role != 'admin')
    GROUP BY c.Class_Name
    ORDER BY c.Class_Name ASC";
$result_payment_data = $conn->query($sql_payment_data);

$class_names = [];
$total_payments = [];
while ($row = $result_payment_data->fetch_assoc()) {
    $class_names[] = $row['Class_Name'];
    $total_payments[] = $row['TotalPayment'];
}
?>

<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin CASHFLOW</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/dist/css/adminlte.min.css?v=3.2.0">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/summernote/summernote-bs4.min.css">
<script data-cfasync="false" nonce="281cf799-1598-4f04-a582-ad73f4626210">try{(function(w,d){!function(a,b,c,d){if(a.zaraz)console.error("zaraz is loaded twice");else{a[c]=a[c]||{};a[c].executed=[];a.zaraz={deferred:[],listeners:[]};a.zaraz._v="5848";a.zaraz._n="281cf799-1598-4f04-a582-ad73f4626210";a.zaraz.q=[];a.zaraz._f=function(e){return async function(){var f=Array.prototype.slice.call(arguments);a.zaraz.q.push({m:e,a:f})}};for(const g of["track","set","debug"])a.zaraz[g]=a.zaraz._f(g);a.zaraz.init=()=>{var h=b.getElementsByTagName(d)[0],i=b.createElement(d),j=b.getElementsByTagName("title")[0];j&&(a[c].t=b.getElementsByTagName("title")[0].text);a[c].x=Math.random();a[c].w=a.screen.width;a[c].h=a.screen.height;a[c].j=a.innerHeight;a[c].e=a.innerWidth;a[c].l=a.location.href;a[c].r=b.referrer;a[c].k=a.screen.colorDepth;a[c].n=b.characterSet;a[c].o=(new Date).getTimezoneOffset();if(a.dataLayer)for(const k of Object.entries(Object.entries(dataLayer).reduce(((l,m)=>({...l[1],...m[1]})),{})))zaraz.set(k[0],k[1],{scope:"page"});a[c].q=[];for(;a.zaraz.q.length;){const n=a.zaraz.q.shift();a[c].q.push(n)}i.defer=!0;for(const o of[localStorage,sessionStorage])Object.keys(o||{}).filter((q=>q.startsWith("_zaraz_"))).forEach((p=>{try{a[c]["z_"+p.slice(7)]=JSON.parse(o.getItem(p))}catch{a[c]["z_"+p.slice(7)]=o.getItem(p)}}));i.referrerPolicy="origin";i.src="/cdn-cgi/zaraz/s.js?z="+btoa(encodeURIComponent(JSON.stringify(a[c])));h.parentNode.insertBefore(i,h)};["complete","interactive"].includes(b.readyState)?zaraz.init():a.addEventListener("DOMContentLoaded",zaraz.init)}}(w,d,"zarazData","script");window.zaraz._p=async bs=>new Promise((bt=>{if(bs){bs.e&&bs.e.forEach((bu=>{try{const bv=d.querySelector("script[nonce]"),bw=bv?.nonce||bv?.getAttribute("nonce"),bx=d.createElement("script");bw&&(bx.nonce=bw);bx.innerHTML=bu;bx.onload=()=>{d.head.removeChild(bx)};d.head.appendChild(bx)}catch(by){console.error(`Error executing script: ${bu}\n`,by)}}));Promise.allSettled((bs.f||[]).map((bz=>fetch(bz[0],bz[1]))))}bt()}));zaraz._p({"e":["(function(w,d){})(window,document)"]});})(window,document)}catch(e){throw fetch("/cdn-cgi/zaraz/t"),e;};</script></head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
      <a href="#" class="navbar-brand">
        <img src="../logoSilatCekak.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
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
            <a href="roster.php" class="nav-link">Details</a>
          </li>
          <li class="nav-item dropdown">
            <a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Dropdown</a>
            <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow">
              <li><a href="#" class="dropdown-item">Setting</a></li>
              <li><a href="Login.html" class="dropdown-item">Logout</a></li>
            </ul>
          </li>
        </ul>

        <!-- SEARCH FORM -->
        <form class="form-inline ml-0 ml-md-3">
          <div class="input-group input-group-sm">
            <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
            <div class="input-group-append">
              <button class="btn btn-navbar" type="submit">
                <i class="fas fa-search"></i>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- Right navbar links -->
      <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
              <i class="far fa-bell"></i>
              <span class="badge badge-warning navbar-badge">15</span>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
              <span class="dropdown-header">15 Notifications</span>
              
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                  <i class="fas fa-envelope mr-2"></i> 4 new messages
                  <span class="float-right text-muted text-sm">3 mins</span>
              </a>
              
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                  <i class="fas fa-users mr-2"></i> 8 friend requests
                  <span class="float-right text-muted text-sm">12 hours</span>
              </a>
              
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item">
                  <i class="fas fa-file mr-2"></i> 3 new reports
                  <span class="float-right text-muted text-sm">2 days</span>
              </a>
              
              <div class="dropdown-divider"></div>
              <?php include 'dark-mode.php'; ?>
              
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
          </div> </li>
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
            <h1 class="m-0">PSSCM ADMIN <small>Cashflow</small></h1>
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

    <!-- Main content -->
    <div class="content">
      <div class="container">
      <div class="row">
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?php echo $totalUsers; ?></h3>
                <p>User Registrations</p>
              </div>
              <div class="icon">
                <i class="ion ion-person-add"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <div class="col-lg-3 col-6">

            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?php echo $totalEnrolledUsers; ?></h3>

                <p>Class Registration</p>
              </div>
              <div class="icon">
                <i class="ion ion-bag"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?php echo number_format($paymentRate, 2); ?><sup style="font-size: 20px">%</sup></h3>

                <p>Payment Rate</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>RM <?php echo number_format($totalFees); ?></h3>

                <p>Expected Revenue</p>
              </div>
              <div class="icon">
                <i class="ion ion-pie-graph"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-6">
            <!-- HTML for the chart -->
            <div class="card">
              <div class="card-header border-0">
                <div class="d-flex justify-content-between">
                  <h3 class="card-title">Users Registration</h3>
                  <a href="javascript:void(0);">View Report</a>
                </div>
              </div>
              <div class="card-body">
                <div class="d-flex">
                  <p class="d-flex flex-column">
                    <span class="text-bold text-lg"><?php echo array_sum($user_counts); ?></span>
                    <span>Users Over Time</span>
                  </p>
                  <p class="ml-auto d-flex flex-column text-right">
                    <!-- Calculate percentage based on a goal of 300 users -->
                    <?php
                    $total_users = array_sum($user_counts);
                    $goal = 300;
                    $percentage = ($total_users / $goal) * 100;
                    ?>
                    <span class="text-success">
                      <i class="fas fa-arrow-up"></i> <?php echo number_format($percentage, 2); ?>% of Goal
                    </span>
                    <span class="text-muted">Ever Since</span>
                  </p>
                </div>
                
                <div class="position-relative mb-4">
                  <canvas id="users-chart" height="150"></canvas> <!-- Shortened height to 150 -->
                </div>

                <div class="d-flex flex-row justify-content-end">
                  <span class="mr-2">
                    <i class="fas fa-square text-primary"></i> Ever Since
                  </span>
                </div>
              </div>
            </div>

            <!-- Script for Chart.js -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
              // Convert PHP arrays to JavaScript arrays
              var dates = <?php echo json_encode($dates); ?>;
              var user_counts = <?php echo json_encode($user_counts); ?>;

              // Now we can use these variables in the chart
              var ctx = document.getElementById('users-chart').getContext('2d');
              var chart = new Chart(ctx, {
                  type: 'line', // Area chart (line chart with fill)
                  data: {
                      labels: dates, // Dates for the x-axis
                      datasets: [{
                          label: 'Registered User',
                          data: user_counts, // User counts for the y-axis
                          backgroundColor: 'rgba(75, 192, 192, 0.2)', // Fill color under the line
                          borderColor: 'rgba(75, 192, 192, 1)', // Line color
                          borderWidth: 1,
                          fill: true // Makes it an area chart (fills under the line)
                      }]
                  },
                  options: {
                      responsive: true,
                      scales: {
                          y: {
                              beginAtZero: true
                          }
                      }
                  }
              });
            </script>

            <div class="card">
              <div class="card-header border-0">
                  <h3 class="card-title">Users Payment</h3>
                  <div class="card-tools">
                      <a href="#" class="btn btn-tool btn-sm">
                          <i class="fas fa-download"></i>
                      </a>
                      <a href="#" class="btn btn-tool btn-sm">
                          <i class="fas fa-bars"></i>
                      </a>
                  </div>
              </div>
              <div class="card-body table-responsive p-0" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                  <table class="table table-striped table-valign-middle" style="margin-bottom: 0;">
                      <thead>
                      <tr>
                          <th>Name</th>
                          <th>Fee</th>
                          <th>Amount Paid</th>
                          <th>Due</th>
                      </tr>
                      </thead>
                      <tbody>
                      <?php if ($result_users_payments && $result_users_payments->num_rows > 0): ?>
                          <?php while ($row = $result_users_payments->fetch_assoc()): ?>
                              <tr>
                                  <td>
                                  <img src="<?php echo htmlspecialchars($row['profile_picture']); ?>" alt="Profile Picture" class="img-circle img-size-32 mr-2">
                                  <?php echo htmlspecialchars($row['Name']); ?>
                                  </td>
                                  <td>
                                      RM <?php echo number_format($row['Fee'], 2); ?>
                                  </td>
                                  <td>
                                      <small class="text-success mr-1">
                                          <i class="fas fa-arrow-up"></i>
                                          <?php echo number_format(($row['AmountPaid'] / $row['Fee']) * 100, 2); ?>%
                                      </small>
                                       <?php echo number_format($row['AmountPaid'], 2); ?>
                                  </td>
                                  <td class="<?php echo $row['Due'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                      RM <?php echo number_format($row['Due'], 2); ?>
                                  </td>
                              </tr>
                          <?php endwhile; ?>
                      <?php else: ?>
                          <tr>
                              <td colspan="4" class="text-center">No payment data available</td>
                          </tr>
                      <?php endif; ?>
                      </tbody>
                  </table>
                </div>
              </div>
              </div>
                    <!-- /.col-md-6 -->
              <div class="col-lg-6">
                <!-- HTML for the Payment Chart -->
                <div class="card">
                  <div class="card-header border-0">
                    <div class="d-flex justify-content-between">
                      <h3 class="card-title">Payments</h3>
                      <a href="javascript:void(0);">View Report</a>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <p class="d-flex flex-column">
                        <span class="text-bold text-lg">RM<?php echo number_format($totalPayment, 2); ?></span>
                        <span>Payments Over Time</span>
                      </p>
                      <p class="text-right">
                        <span class="text-success">
                          <i class="fas fa-arrow-up"></i> <?php echo number_format($paymentRate, 2); ?><sup style="font-size: 12px">%</sup>
                        </span>
                      </p>
                    </div>
                    <!-- /.d-flex -->
                    <div style="width: 300px; height: 300px; margin: 0 auto;">
                      <canvas id="payment-piechart"></canvas> <!-- Adjusted container size -->
                    </div>
                  </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <script>
                  // Pass PHP arrays to JavaScript
                  var classNames = <?php echo json_encode($class_names); ?>;
                  var totalPayments = <?php echo json_encode($total_payments); ?>;

                  // Create the pie chart
                  var ctx = document.getElementById('payment-piechart').getContext('2d');
                  var pieChart = new Chart(ctx, {
                    type: 'pie',  // Pie chart type
                    data: {
                      labels: classNames,  // Labels: Class Names
                      datasets: [{
                        label: 'Payments per Class',
                        data: totalPayments,  // Data: Total payments for each class
                        backgroundColor: ['#4CAF50', '#FFC107', '#2196F3', '#9C27B0', '#FF5722'],  // Professional color palette
                        hoverOffset: 4  // Slight offset when hovering
                      }]
                    },
                    options: {
                      responsive: true,
                      plugins: {
                        legend: {
                          position: 'bottom',  // Moved legend to the bottom
                          labels: {
                            usePointStyle: true  // Using point style for the legend for a cleaner look
                          }
                        },
                        tooltip: {
                          callbacks: {
                            label: function(tooltipItem) {
                              return tooltipItem.label + ': RM ' + tooltipItem.raw.toFixed(2);  // Show RM value
                            }
                          }
                        }
                      }
                    }
                  });
                </script>


                <div class="card">
                  <div class="card-header border-0">
                      <h3 class="card-title">Class Registration Overview</h3>
                      <div class="card-tools">
                          <a href="#" class="btn btn-sm btn-tool">
                              <i class="fas fa-download"></i>
                          </a>
                          <a href="#" class="btn btn-sm btn-tool">
                              <i class="fas fa-bars"></i>
                          </a>
                      </div>
                  </div>
                  <div class="card-body">
                      <?php while ($row = $result_class_payment->fetch_assoc()): ?>
                      <div class="d-flex justify-content-between align-items-center border-bottom mb-3">
                          <p class="text-<?= $row['Payment_Percentage'] >= 100 ? 'success' : ($row['Payment_Percentage'] > 50 ? 'warning' : 'warning') ?> text-xl">
                              <i class="ion ion-ios-checkmark-outline"></i>
                          </p>
                          <p class="d-flex flex-column text-right">
                              <span class="font-weight-bold">
                                  <i class="ion ion-android-arrow-up text-<?= $row['Payment_Percentage'] >= 100 ? 'success' : ($row['Payment_Percentage'] > 50 ? 'warning' : 'danger') ?>"></i> 
                                  <?= round($row['Payment_Percentage'], 2) ?>%
                              </span>
                              <span class="text-muted"><?= htmlspecialchars($row['Class_Name']) ?></span>
                          </p>
                      </div>
                      <?php endwhile; ?>
                    </div>
              </div>

              </div>
                    <!-- /.col-md-6 -->
              </div><!-- /.container-fluid -->
                
              </div>
              <!-- /.content -->
            </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="https://adminlte.io/themes/v3/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://adminlte.io/themes/v3/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://adminlte.io/themes/v3/dist/js/adminlte.min.js?v=3.2.0"></script>

</body>
</html>
