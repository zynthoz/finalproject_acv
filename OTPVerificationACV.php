<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify - Serke's Cove</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
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

<body>
<div class="login-background">
    <nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
        SERKE'S COVE 
        </a>
    </div>
    </nav>  

<div class="container w-25 border border-primary rounded p-5 position-absolute top-50 start-50 translate-middle" 
    style="background-color: rgba(74, 104, 135, 0.9);">
        <div class="row">
            <div class="col text-center fw-bold">
                <label class="display-5"  style="font-family: 'Playfair Display', serif; color:
                     #c8a96e ;">OTP Verification</label>
            </div>
        </div>
        <div class="row mb-1">
             <div class="col text-center">
                <label class="lead-5"  style="font-family: 'Playfair Display', serif; color:
                     #c8a96e ;">One time password (OTP) was sent to your email</label>
            </div>
        </div>
        <form action="OTPVerificationACV.php" method="post">

            <div class="form-outline mb-4">
                <label class="form-label" for="form2Example1" style="font-family: 'Playfair Display', serif; color:
                     #c8a96e ;">Enter the OTP Number to verify</label>
                <input type="text" name="otp" class="form-control" />
            </div>

            <?php $buttonColor = "#2c3e50"; ?>
            <?php $buttonText = "#c8a96e"; ?>

            <div class="text-center">
                <input 
                    type="submit" 
                    name="verACV"
                    value="Verify"
                    class="btn btn-primary px-5 mb-3"
                    style="font-family: 'Playfair Display', serif; border: 2px solid white; background-color: <?php echo $buttonColor; ?>; color: <?php echo $buttonText; ?>;"
                >
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>

<?php
require_once "dbconnACV.php";

if (isset($_POST['verACV'])) {
    $userotp = $_POST['otp'];

    $otpsql = "SELECT * FROM tbl_users WHERE otp = '$userotp'";
    $result = $conn->query($otpsql);

    if ($result->num_rows == 1) {
        $fieldname = $result->fetch_assoc();
        $useridACV = $fieldname['user_id'];

        $updatesql = "UPDATE tbl_users SET otp = NULL, status = 'Active' WHERE user_id = '$useridACV'";
        $conn->query($updatesql);

        $logssql = "INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('$useridACV', 'Created Account', NOW())";
        $conn->query($logssql);
        ?> 
        <script>
        Swal.fire({
          title: "Account Activated",
          position: "center",
          icon: "success",
          draggable: true,
          timer: 3000
        }).then(() =>{
            window.location.href = "loginACV.php";
        });
        </script>
        <?php
    } else {
        ?>
        <script>
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "INVALID OTP!"
        });
        </script>
        <?php
    }   
}
?>
