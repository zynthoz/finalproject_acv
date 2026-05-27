<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Serke's Cove</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
 <style>
    body {
      font-family: 'Lato', sans-serif;
      background-color: #f0ede8;
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
        <div class="row mb-5">
            <div class="col text-center fw-bold">
                <label class="display-2"  style="font-family: 'Playfair Display', serif; color: #c8a96e ;">Log In</label>
            </div>
        </div>

        <form action="loginACV.php" method="post">

            <div class="form-outline mb-4">
                <input type="text" name="unameACV" class="form-control" />
                <label class="form-label" style="font-family: 'Playfair Display', serif;  ">Username</label>
            </div>

            <div class="form-outline mb-4">
                <input type="password" name="passACV" class="form-control" />
                <label class="form-label" style="font-family: 'Playfair Display', serif; ">Password</label>
            </div>

            <?php $buttonColor = "#2c3e50"; ?>
            <?php $buttonText = "#c8a96e"; ?>

            <div class="text-center">
                <input 
                    type="submit" 
                    name="subACV"
                    value="Log In"
                    class="btn btn-primary px-5 mb-3"
                    style="font-family: 'Playfair Display', serif; border: 2px solid white; background-color: 
                        <?php echo $buttonColor; ?>; color: <?php echo $buttonText; ?>;"
                >
            </div>

            <div class="text-center">
                <a 
                    href="RegistrationACV.php"
                    class="btn btn-primary px-5"
                    style="font-family: 'Playfair Display', serif; border: 2px solid white; background-color: 
                        <?php echo $buttonColor; ?>; color: <?php echo $buttonText; ?>;"
                >Create Account</a>
            </div>

        </form>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>


<?php
require_once "dbconnACV.php";
session_start();

if (isset($_POST['subACV'])) {
    $unameACV = $_POST['unameACV'];
    $passACV = md5($_POST['passACV']);

    $loginsql = "SELECT * FROM tbl_users WHERE username = '$unameACV' AND password = '$passACV' AND status = 'Active'";
    $result = $conn->query($loginsql);

    if ($result->num_rows == 1) {
        $fieldname = $result->fetch_assoc();
        
        $role = $fieldname['role'];
        $acctype = ucfirst($role);
        $fullname = $fieldname['full_name'];
        $image_path = $fieldname['imgpath'];
        $id = $fieldname['user_id'];
        
        $_SESSION['acc_type'] = $acctype;
        $_SESSION['fullname'] = $fullname;
        $_SESSION['imgpth'] = $image_path;
        $_SESSION['id'] = $id;

        $logssql = "INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('$id', 'Logged In', NOW())";
        $conn->query($logssql);

        if ($role == "admin") { 
            ?>
            <script>
                window.location.href = "adminACV.php";
            </script>
            <?php
        } else if ($role == "employee") {
            ?>
            <script>
                window.location.href = "employeeACV.php";
            </script>
            <?php
        } else if ($role == "customer") {
            ?>
            <script>
                window.location.href = "customerACV.php";
            </script>
            <?php
        }
    } else {
        ?>
        <script>
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "Invalid Login!"
        });
        </script>
        <?php
    }
}
?>