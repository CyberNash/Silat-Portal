<?php
// Include database connection
include('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['Name']);
    $matrix = trim($_POST['Matrix']);
    $email = trim($_POST['Email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['Password'];
    $role = trim($_POST['Role']);
    $kk = trim($_POST['kk']); // Get the KK value from the dropdown
    $profile_picture_path = null;

    // Basic validation
    if (empty($name) || empty($matrix) || empty($email) || empty($password) || empty($role) || empty($phone_number) || empty($kk)) {
        echo "<script>alert('Please fill in all required fields.'); window.history.back();</script>";
        exit();
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_pictures/';
        $file_name = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $target_file = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $profile_picture_path = $target_file;
        } else {
            echo "<script>alert('Error uploading profile picture.'); window.history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('Profile picture is required.'); window.history.back();</script>";
        exit();
    }

    // Insert data into the database
    $query = "INSERT INTO users (Name, Matrix, Email, phone_number, Password, Role, KK, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ssssssss", $name, $matrix, $email, $phone_number, $password, $role, $kk, $profile_picture_path);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful!'); window.location.href = 'Enrollment.php';</script>";
            exit();
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
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up Page</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="https://adminlte.io/themes/v3/dist/css/adminlte.min.css?v=3.2.0">
<script data-cfasync="false" nonce="2c570cfb-ea13-4939-b433-bae6eea37a2c">try{(function(w,d){!function(a,b,c,d){if(a.zaraz)console.error("zaraz is loaded twice");else{a[c]=a[c]||{};a[c].executed=[];a.zaraz={deferred:[],listeners:[]};a.zaraz._v="5848";a.zaraz._n="2c570cfb-ea13-4939-b433-bae6eea37a2c";a.zaraz.q=[];a.zaraz._f=function(e){return async function(){var f=Array.prototype.slice.call(arguments);a.zaraz.q.push({m:e,a:f})}};for(const g of["track","set","debug"])a.zaraz[g]=a.zaraz._f(g);a.zaraz.init=()=>{var h=b.getElementsByTagName(d)[0],i=b.createElement(d),j=b.getElementsByTagName("title")[0];j&&(a[c].t=b.getElementsByTagName("title")[0].text);a[c].x=Math.random();a[c].w=a.screen.width;a[c].h=a.screen.height;a[c].j=a.innerHeight;a[c].e=a.innerWidth;a[c].l=a.location.href;a[c].r=b.referrer;a[c].k=a.screen.colorDepth;a[c].n=b.characterSet;a[c].o=(new Date).getTimezoneOffset();if(a.dataLayer)for(const k of Object.entries(Object.entries(dataLayer).reduce(((l,m)=>({...l[1],...m[1]})),{})))zaraz.set(k[0],k[1],{scope:"page"});a[c].q=[];for(;a.zaraz.q.length;){const n=a.zaraz.q.shift();a[c].q.push(n)}i.defer=!0;for(const o of[localStorage,sessionStorage])Object.keys(o||{}).filter((q=>q.startsWith("_zaraz_"))).forEach((p=>{try{a[c]["z_"+p.slice(7)]=JSON.parse(o.getItem(p))}catch{a[c]["z_"+p.slice(7)]=o.getItem(p)}}));i.referrerPolicy="origin";i.src="/cdn-cgi/zaraz/s.js?z="+btoa(encodeURIComponent(JSON.stringify(a[c])));h.parentNode.insertBefore(i,h)};["complete","interactive"].includes(b.readyState)?zaraz.init():a.addEventListener("DOMContentLoaded",zaraz.init)}}(w,d,"zarazData","script");window.zaraz._p=async bs=>new Promise((bt=>{if(bs){bs.e&&bs.e.forEach((bu=>{try{const bv=d.querySelector("script[nonce]"),bw=bv?.nonce||bv?.getAttribute("nonce"),bx=d.createElement("script");bw&&(bx.nonce=bw);bx.innerHTML=bu;bx.onload=()=>{d.head.removeChild(bx)};d.head.appendChild(bx)}catch(by){console.error(`Error executing script: ${bu}\n`,by)}}));Promise.allSettled((bs.f||[]).map((bz=>fetch(bz[0],bz[1]))))}bt()}));zaraz._p({"e":["(function(w,d){})(window,document)"]});})(window,document)}catch(e){throw fetch("/cdn-cgi/zaraz/t"),e;};</script></head>
<body class="hold-transition register-page">
<div class="register-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="#" class="h1"><b>SignUp</b> </a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Register a new account</p>

      <form method="POST" action="SignUp.php" enctype="multipart/form-data">
      <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="Full name" name="Name" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-user"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="Matrix" name="Matrix" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-id-card"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <input type="email" class="form-control" placeholder="Email" name="Email" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-envelope"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="Phone Number" name="phone_number" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-phone"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <select class="form-control" name="kk" required>
          <option value="" disabled selected>Select Kolej Kediaman</option>
          <option value="PF1">PF1</option>
          <option value="PF2">PF2</option>
          <option value="Bumita">Bumita</option>
          <option value="Wang Ulu">Wang Ulu</option>
          <option value="Simpang Empat">Simpang Empat</option>
          <option value="Uniciti">Uniciti</option>
        </select>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-map-marker"></span>
          </div>
        </div>
      </div>


      <div class="input-group mb-3">
        <input type="password" class="form-control" placeholder="Password" name="Password" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <input type="password" class="form-control" placeholder="Retype password" name="RetypePassword" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <select class="form-control" name="Role" required>
          <option value="student">Student</option>
          <!-- Add more roles as needed -->
        </select>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-users"></span>
          </div>
        </div>
      </div>

      <div class="input-group mb-3">
        <input type="file" class="form-control" name="profile_picture" accept="image/*" required>
        <div class="input-group-append">
            <div class="input-group-text">
                <span class="fas fa-camera"></span>
            </div>
        </div>
      </div>

        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="agreeTerms" name="terms" value="agree">
              <label for="agreeTerms">
               I agree to the <a href="#">terms</a>
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block" onclick="window.location.href='Enrollment.php'">Register</button>
          </div>

          <!-- /.col -->
        </div>
      </form>

      <a href="Login.html" class="text-center">I already have a membership</a>
    </div>
    <!-- /.form-box -->
  </div><!-- /.card -->
</div>
<!-- /.register-box -->

<!-- jQuery -->
<script src="../../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../../dist/js/adminlte.min.js?v=3.2.0"></script>
</body>
</html>
