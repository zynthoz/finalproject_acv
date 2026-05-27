<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register – Serke's Cove</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />

  <style>
  body {
    font-family: 'Lato', sans-serif;
    margin: 0;
    padding: 0;
    background-image: url('images/infinity1.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    min-height: 100vh;
    }

    .navbar {
      background-color: #2c3e50;
      position: absolute;
      top: 0;
      width: 100%;
      z-index: 1000;
    }

    .navbar-brand {
      font-family: 'Playfair Display', serif;
      color: white;
      letter-spacing: 2px;
      font-size: 1.4rem;
      padding: 14px 20px;
    }
  </style>
</head>

<body style="font-family: 'Lato', sans-serif;">

    <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
        SERKE'S COVE 
        </a>
    </div>
    </nav>    

<div class="container w-50 mt-5 p-5 rounded" style="background-color: #2c3e50;">
  <form action="RegistrationACV.php" method="post" enctype="multipart/form-data">

    <h2 class="text-center p-2 rounded" style="color: #fff; background-color: #c8a96e;">SERKE'S COVE</h2>
    <p class="text-center text-decoration-underline" style="color: rgba(255,255,255,0.6);">
      Guest Account Registration
    </p>

    

    <div class="mb-3">
                <div class="col text-center">   
                    <img src="" alt="" id="preview" width=200 height=200 class="img-thumbnail">
                    <input type="file" name="upload_img" id=""class="form-control" onchange="ACVpreviewimg(event);">
                </div>
    </div>
    
    <div class="mb-3">
      <label style="color: #c8a96e;">First Name:</label>
      <input type="text" name="fnameACV" class="form-control" placeholder="First name" required />
    </div>

    <div class="mb-3">
      <label style="color: #c8a96e;">Last Name:</label>
      <input type="text" name="lnameACV" class="form-control" placeholder="Last name" required />
    </div>

    <div class="mb-3">
      <label style="color: #c8a96e;">Age:</label>
      <input type="number" name="ageACV" class="form-control" placeholder="Age" required />
    </div>

    <div class="mb-3">
      <label style="color: #c8a96e;">Contact Number:</label>
      <input type="tel" name="contactACV" class="form-control" placeholder="09XXXXXXXXX" required />
    </div>

    <div class="mb-3">
      <label style="color: #c8a96e;">Email Address:</label>
      <input type="email" name="emailACV" class="form-control" placeholder="your@email.com" required />
    </div>

   <div class="mb-3 text-white">
            <label style="color: #c8a96e;">Gender:</label><br>
            <input type="radio" name="genderACV" value="Female"> Female
            <input type="radio" name="genderACV" value="Male" class="ms-3"> Male
            <input type="radio" name="genderACV" value="Other" class="ms-3"> Other
    </div>

    <div class="mb-3">
      <label style="color: #c8a96e;">Username:</label>
      <input type="text" name="usernameACV" class="form-control" placeholder="Choose a username" required />
    </div>

    <div class="mb-3">
      <label style="color: #c8a96e;">Password:</label>
      <input type="password" name="passwordACV" class="form-control" placeholder="Create a password" required />
    </div>

    <div class="text-center pt-3">
      <input type="submit" name="subACV" value="Create Account" class="btn w-50" style="background-color: #c8a96e; color: #fff;" />
    </div>


  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <script>
            function ACVpreviewimg(event) {
                var ACVdisplayimg = document.getElementById("preview");
                ACVdisplayimg.src = URL.createObjectURL(event.target.files[0]);
            }
     </script>

</body>
</html>

<?php
require_once "dbconnACV.php";
require_once "verifyotpemailACV.php";

if(isset($_POST['subACV'])) {
    $fnameACV = $_POST['fnameACV'];
    $lnameACV = $_POST['lnameACV'];
    $ageACV = $_POST['ageACV'];
    $contactACV = $_POST['contactACV'];
    $emailACV = $_POST['emailACV'];
    $genderACV = $_POST['genderACV'];
    $usernameACV = $_POST['usernameACV'];
    $passwordACV = md5($_POST['passwordACV']); 

    $fullnameACV = $fnameACV." ".$lnameACV;

    $imagepathACV = "accPFPimg/".basename($_FILES['upload_img']['name']);
    copy($_FILES['upload_img']['tmp_name'],$imagepathACV);

    $otpACV = rand(100000, 999999);

    $insertsql = "INSERT INTO tbl_users (full_name, role, username, password, email, age, contact_num, gender, imgpath, otp, status) 
                  VALUES ('$fullnameACV', 'customer', '$usernameACV', '$passwordACV', '$emailACV', '$ageACV', '$contactACV', '$genderACV', '$imagepathACV', '$otpACV', 'Pending')";
    $result = $conn->query($insertsql);

    if ($result == true) {
        send_verification($fullnameACV, $emailACV, $otpACV);
        ?> 
        <script>
        Swal.fire({
          title: "Registration complete!",
          position: "center",
          icon: "success",
          draggable: true,
          timer: 3000
        }).then(() =>{
            window.location.href = "OTPVerificationACV.php";
        });
        </script>

    <?php
    } else { ?>
        <script>
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "Registration not complete!"
        });
        </script>
        <?php
    }
}
?>