<?php
session_start();
require_once "dbconnACV.php";

$rooms_sql = "SELECT * FROM tbl_accommodations";
$rooms_result = $conn->query($rooms_sql);

$packages_sql = "SELECT * FROM tbl_packages";
$packages_result = $conn->query($packages_sql);

$package_accommodations = array();
$acc_map_result = $conn->query("SELECT pa.package_id, a.accommodation_name FROM tbl_package_accommodations pa JOIN tbl_accommodations a ON pa.accommodation_id = a.accommodation_id");
if ($acc_map_result && $acc_map_result->num_rows > 0) {
  while ($row = $acc_map_result->fetch_assoc()) {
    $package_accommodations[$row['package_id']] = $row['accommodation_name'];
  }
}

$package_amenities = array();
$amen_map_result = $conn->query("SELECT pam.package_id, am.amenity_name FROM tbl_package_amenities pam JOIN tbl_amenities am ON pam.amenity_id = am.amenity_id ORDER BY am.amenity_name");
if ($amen_map_result && $amen_map_result->num_rows > 0) {
  while ($row = $amen_map_result->fetch_assoc()) {
    if (!isset($package_amenities[$row['package_id']])) {
      $package_amenities[$row['package_id']] = array();
    }
    $package_amenities[$row['package_id']][] = $row['amenity_name'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Serke's Cove Resort</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="frontendstyle.css" />
</head>
<body>

  <nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-0">
      <a class="navbar-brand" href="index.php">SERKE'S COVE</a>
      <button class="navbar-toggler border-0 me-3" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="bi bi-list text-white fs-2"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link active" href="#front">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#rooms">Rooms</a></li>
          <li class="nav-item"><a class="nav-link" href="#packages">Packages</a></li>
          <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        </ul>
        <?php 
        if (isset($_SESSION['id'])) {
            $role = strtolower($_SESSION['acc_type']);
            if ($role == 'admin') {
                $dashboard_link = 'adminACV.php';
            } else if ($role == 'employee') {
                $dashboard_link = 'employeeACV.php';
            } else {
                $dashboard_link = 'customerACV.php';
            }
            ?>
            <a href="<?php echo $dashboard_link; ?>" class="btn-reserve">Dashboard</a>
            <a href="logoutACV.php" class="nav-link text-white px-3">Log Out</a>
            <?php
        } else {
            ?>
            <a href="loginACV.php" class="nav-link text-white px-3">Log In</a>
            <a href="RegistrationACV.php" class="btn-reserve">Register</a>
            <?php
        }
        ?>
      </div>
    </div>
  </nav>

  <section class="frontsection" id="front">
    <div class="frontOverlay"></div>
    <div class="frontText">
      <h3 class="tagline">Bansud, Lubao, Navotas</h3>
      <h1>Welcome to Serke's Cove</h1>
      <a href="#rooms" class="btnbook">Book Now</a>
    </div>
  </section>

  <section class="rooms-section" id="rooms">
    <div class="container">
      <div class="text-center mb-5">
        <p class="section-label">Accommodation</p>
        <h2>Rooms & Suites</h2>
      </div>
      <div class="row g-4">
        <?php 
        if ($rooms_result && $rooms_result->num_rows > 0) {
            while ($room = $rooms_result->fetch_assoc()) {
                $img = $room['image_url'];
                if (empty($img)) {
                    $img = 'images/bedroom1.jpg';
                }
                ?>
                <div class="col-md-4">
                  <div class="room-card">
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($room['accommodation_name']); ?>" />
                    <div class="room-card-body">
                      <h4><?php echo htmlspecialchars($room['accommodation_name']); ?></h4>
                      <p><?php echo htmlspecialchars($room['description']); ?></p>
                      <div class="room-card-footer">
                        <span class="room-price">₱<?php echo number_format($room['price_per_night'], 2); ?> / night</span>
                        <?php 
                        if ($room['availability_status'] == 'available') {
                            ?>
                            <a class="section-link" href="Reservation.php?room_id=<?php echo $room['accommodation_id']; ?>">Book</a>
                            <?php
                        } else {
                            ?>
                            <span class="text-muted">Unavailable</span>
                            <?php
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <?php
            }
        } else {
            ?>
            <p class="text-center text-muted">No rooms found.</p>
            <?php
        }
        ?>
      </div>
    </div>
  </section>

  <section style="padding: 90px 0; background-color: #f0ede8;" id="packages">
    <div class="container">
      <div class="text-center mb-5">
        <p class="section-label">Packages</p>
        <h2>Special Bundles</h2>
      </div>
      <div class="row g-4">
        <?php 
        if ($packages_result && $packages_result->num_rows > 0) {
            while ($pkg = $packages_result->fetch_assoc()) {
                ?>
                <div class="col-md-6">
                  <div style="background:#fff; padding:30px; height:100%;">
                    <h4 style="font-family:'Playfair Display',serif; color:#2c3e50;"><?php echo htmlspecialchars($pkg['package_name']); ?></h4>
                    <p style="color:#777; font-size:0.9rem;"><?php echo htmlspecialchars($pkg['description']); ?></p>
                    <?php
                      $acc_name = isset($package_accommodations[$pkg['package_id']]) ? $package_accommodations[$pkg['package_id']] : 'Not set';
                      $amenities = isset($package_amenities[$pkg['package_id']]) ? implode(', ', $package_amenities[$pkg['package_id']]) : 'None';
                    ?>
                    <p style="font-size:0.85rem; color:#555;"><strong>Includes:</strong> Accommodation: <?php echo htmlspecialchars($acc_name); ?>; Amenities: <?php echo htmlspecialchars($amenities); ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span style="font-family:'Playfair Display',serif; font-size:1.2rem; color:#2c3e50; font-weight:700;">₱<?php echo number_format($pkg['price'], 2); ?></span>
                      <a href="Reservation.php?package_id=<?php echo $pkg['package_id']; ?>" class="section-link">Book</a>
                    </div>
                  </div>
                </div>
                <?php
            }
        } else {
            ?>
            <p class="text-center text-muted">No packages found.</p>
            <?php
        }
        ?>
      </div>
    </div>
  </section>

  <footer class="site-footer" id="contact">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <p class="footer-brand">SERKE'S COVE</p>
          <p class="footer-tagline">Bansud, Lubao, Navotas</p>
          <p class="footer-tagline mt-2">reservations@serkes.com</p>
        </div>
        <div class="col-lg-2 col-6">
          <p class="footer-heading">Explore</p>
          <ul class="footer-links">
            <li><a href="#rooms">Rooms</a></li>
            <li><a href="#packages">Packages</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-6">
          <p class="footer-heading">Information</p>
          <ul class="footer-links">
            <li><a href="#">Privacy Policy</a></li>
            <li><a href="#">Terms</a></li>
          </ul>
        </div>
        <div class="col-lg-4">
          <p class="footer-heading">Stay Connected</p>
          <div class="icons mt-3">
            <a href="#"><i class="bi bi-instagram"></i></a>
            <a href="#"><i class="bi bi-facebook"></i></a>
            <a href="#"><i class="bi bi-twitter-x"></i></a>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 Serke's Cove. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
