<?php
session_start();
require_once "dbconnACV.php";

if (!isset($_SESSION['id'])) {
    header("Location: loginACV.php");
    exit;
}

$role = strtolower($_SESSION['acc_type']);
if ($role != 'admin' && $role != 'employee') {
    header("Location: loginACV.php");
    exit;
}

$dashboard_page = ($role == 'employee') ? 'employeeACV.php' : 'adminACV.php';

$page = isset($_GET['page']) ? htmlspecialchars(trim($_GET['page'])) : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($page) || $id <= 0) {
  header("Location: " . $dashboard_page);
    exit;
}

if ($role == 'employee' && ($page == 'users' || $page == 'logs')) {
    header("Location: employeeACV.php");
    exit;
}

$success_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_record'])) {
    if ($page == 'accommodations') {
        $name = $_POST['accommodation_name'];
        $desc = $_POST['description'];
        $cap = $_POST['capacity'];
        $price = $_POST['price_per_night'];
        $avail = $_POST['availability_status'];
        
        $img = $record['image_url'];
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "images/";
            $filename = time() . "_" . basename($_FILES['image_file']['name']);
            $target_file = $target_dir . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                $img = $target_file;
            }
        }

        $sql = "UPDATE tbl_accommodations SET accommodation_name='$name', description='$desc', capacity='$cap', price_per_night='$price', availability_status='$avail', image_url='$img' WHERE accommodation_id='$id'";
        $conn->query($sql);

        $log_action = "Updated Accommodation ID $id: $name";
        $conn->query("INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('{$_SESSION['id']}', '$log_action', NOW())");

        header("Location: " . $dashboard_page . "?page=accommodations");
        exit;
    }

    if ($page == 'amenities') {
        $name = $_POST['amenity_name'];
        $desc = $_POST['description'];
        $price = $_POST['price_per_use'];

        $sql = "UPDATE tbl_amenities SET amenity_name='$name', description='$desc', price_per_use='$price' WHERE amenity_id='$id'";
        $conn->query($sql);

        $log_action = "Updated Amenity ID $id: $name";
        $conn->query("INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('{$_SESSION['id']}', '$log_action', NOW())");

        header("Location: " . $dashboard_page . "?page=amenities");
        exit;
    }

    if ($page == 'packages') {
        $name = $_POST['package_name'];
        $desc = $_POST['description'];
        $price = $_POST['price'];
      $acc_id = isset($_POST['package_accommodation_id']) ? intval($_POST['package_accommodation_id']) : 0;
      $amenity_ids = array();
      if (isset($_POST['package_amenity_ids']) && is_array($_POST['package_amenity_ids'])) {
        foreach ($_POST['package_amenity_ids'] as $amenity_id) {
          $amenity_ids[] = intval($amenity_id);
        }
        $amenity_ids = array_values(array_unique(array_filter($amenity_ids)));
      }

      $sql = "UPDATE tbl_packages SET package_name='$name', description='$desc', price='$price', inclusion_details='' WHERE package_id='$id'";
      $conn->query($sql);

      $conn->query("DELETE FROM tbl_package_accommodations WHERE package_id = '$id'");
      $conn->query("DELETE FROM tbl_package_amenities WHERE package_id = '$id'");

      if ($acc_id > 0) {
        $conn->query("INSERT INTO tbl_package_accommodations (package_id, accommodation_id) VALUES ('$id', '$acc_id')");
      }

      if (!empty($amenity_ids)) {
        $values = array();
        foreach ($amenity_ids as $amenity_id) {
          $values[] = "('$id', '$amenity_id')";
        }
        $conn->query("INSERT INTO tbl_package_amenities (package_id, amenity_id) VALUES " . implode(',', $values));
      }

        $log_action = "Updated Package ID $id: $name";
        $conn->query("INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('{$_SESSION['id']}', '$log_action', NOW())");

        header("Location: " . $dashboard_page . "?page=packages");
        exit;
    }

    if ($page == 'reservations') {
        $st = $_POST['reservation_status'];

        $sql = "UPDATE tbl_reservations SET reservation_status='$st' WHERE reservation_id='$id'";
        $conn->query($sql);

        $log_action = "Updated Reservation ID $id Status to $st";
        $conn->query("INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('{$_SESSION['id']}', '$log_action', NOW())");

        header("Location: " . $dashboard_page . "?page=reservations");
        exit;
    }

    if ($page == 'users') {
        $fn = $_POST['full_name'];
        $rl = $_POST['role'];
        $em = $_POST['email'];
        $status = $_POST['status'];

        $sql = "UPDATE tbl_users SET full_name='$fn', role='$rl', email='$em', status='$status' WHERE user_id='$id'";
        $conn->query($sql);

        $log_action = "Updated User ID $id: $fn";
        $conn->query("INSERT INTO tbl_logs (user_id, action, datetime) VALUES ('{$_SESSION['id']}', '$log_action', NOW())");

        header("Location: " . $dashboard_page . "?page=users");
        exit;
    }
}

$record = null;
if ($page == 'accommodations') {
    $result = $conn->query("SELECT * FROM tbl_accommodations WHERE accommodation_id = '$id'");
    $record = $result->fetch_assoc();
} elseif ($page == 'amenities') {
    $result = $conn->query("SELECT * FROM tbl_amenities WHERE amenity_id = '$id'");
    $record = $result->fetch_assoc();
} elseif ($page == 'packages') {
    $result = $conn->query("SELECT * FROM tbl_packages WHERE package_id = '$id'");
    $record = $result->fetch_assoc();
} elseif ($page == 'reservations') {
    $result = $conn->query("SELECT r.*, u.full_name, a.accommodation_name FROM tbl_reservations r JOIN tbl_users u ON r.user_id = u.user_id JOIN tbl_accommodations a ON r.accommodation_id = a.accommodation_id WHERE r.reservation_id = '$id'");
    $record = $result->fetch_assoc();
} elseif ($page == 'users') {
    $result = $conn->query("SELECT * FROM tbl_users WHERE user_id = '$id'");
    $record = $result->fetch_assoc();
}

$package_accommodation_id = 0;
$package_amenity_ids = array();
$package_accommodations_list = array();
$package_amenities_list = array();
if ($page == 'packages') {
  $acc_list_result = $conn->query("SELECT accommodation_id, accommodation_name FROM tbl_accommodations ORDER BY accommodation_name");
  if ($acc_list_result && $acc_list_result->num_rows > 0) {
    while ($row = $acc_list_result->fetch_assoc()) {
      $package_accommodations_list[] = $row;
    }
  }

  $amen_list_result = $conn->query("SELECT amenity_id, amenity_name FROM tbl_amenities ORDER BY amenity_name");
  if ($amen_list_result && $amen_list_result->num_rows > 0) {
    while ($row = $amen_list_result->fetch_assoc()) {
      $package_amenities_list[] = $row;
    }
  }

  $acc_map_result = $conn->query("SELECT accommodation_id FROM tbl_package_accommodations WHERE package_id = '$id'");
  if ($acc_map_result && $acc_map_result->num_rows > 0) {
    $acc_row = $acc_map_result->fetch_assoc();
    $package_accommodation_id = intval($acc_row['accommodation_id']);
  }

  $amen_map_result = $conn->query("SELECT amenity_id FROM tbl_package_amenities WHERE package_id = '$id'");
  if ($amen_map_result && $amen_map_result->num_rows > 0) {
    while ($row = $amen_map_result->fetch_assoc()) {
      $package_amenity_ids[] = intval($row['amenity_id']);
    }
  }
}

if (!$record) {
  header("Location: " . $dashboard_page);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit <?php echo ucfirst($page); ?> - Serke's Cove</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Lato', sans-serif;
      background: #faf9f7;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }
    .edit-card {
      background: #ffffff;
      border: 1px solid #eae6e0;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      max-width: 600px;
      width: 100%;
      padding: 40px;
    }
    .edit-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .edit-header h2 {
      font-family: 'Playfair Display', serif;
      color: #2c3e50;
      font-weight: 700;
      margin-bottom: 5px;
    }
    .edit-header p {
      color: #888;
      font-size: 0.9rem;
    }
    .form-label {
      font-weight: 700;
      color: #2c3e50;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .form-control, .form-select {
      border: 1px solid #ccc;
      padding: 12px;
      border-radius: 6px;
      font-size: 0.95rem;
      background-color: #fcfcfc;
    }
    .form-control:focus, .form-select:focus {
      border-color: #c8a96e;
      box-shadow: none;
      background-color: #fff;
    }
    .btn-gold {
      background: #c8a96e;
      color: #fff;
      border: none;
      padding: 12px 30px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 1px;
      border-radius: 6px;
      cursor: pointer;
      width: 100%;
      transition: background 0.2s ease-in-out;
    }
    .btn-gold:hover {
      background: #b8945a;
      color: #fff;
    }
    .btn-cancel {
      background: #f0ede8;
      color: #555;
      border: none;
      padding: 12px 30px;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 1px;
      border-radius: 6px;
      cursor: pointer;
      width: 100%;
      text-align: center;
      text-decoration: none;
      display: inline-block;
      margin-top: 10px;
      transition: background 0.2s ease-in-out;
    }
    .btn-cancel:hover {
      background: #e2ded7;
      color: #333;
    }
  </style>
</head>
<body>

  <div class="edit-card">
    <div class="edit-header">
      <h2>Edit <?php echo ucfirst(rtrim($page, 's')); ?></h2>
      <p>Modify record details below. Action will be logged securely.</p>
    </div>

    <form method="post" enctype="multipart/form-data">
      
      <?php if ($page == 'accommodations') { ?>
        <div class="mb-3">
          <label class="form-label">Accommodation Name</label>
          <input type="text" class="form-control" name="accommodation_name" value="<?php echo htmlspecialchars($record['accommodation_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($record['description']); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Capacity</label>
          <input type="number" class="form-control" name="capacity" value="<?php echo $record['capacity']; ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Price per Night (₱)</label>
          <input type="number" step="0.01" class="form-control" name="price_per_night" value="<?php echo $record['price_per_night']; ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Availability Status</label>
          <select class="form-select" name="availability_status">
            <option value="available" <?php if($record['availability_status']=='available'){echo 'selected';} ?>>Available</option>
            <option value="unavailable" <?php if($record['availability_status']=='unavailable'){echo 'selected';} ?>>Unavailable</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label" style="display: block;">Current Image</label>
          <?php if (!empty($record['image_url'])) { ?>
            <img src="<?php echo htmlspecialchars($record['image_url']); ?>" alt="Room Image" class="img-thumbnail mb-2" style="max-width: 150px; display: block;">
          <?php } else { ?>
            <span class="text-muted d-block mb-2">No image uploaded</span>
          <?php } ?>
          <label class="form-label">Upload New Image</label>
          <input type="file" class="form-control" name="image_file">
        </div>

      <?php } elseif ($page == 'amenities') { ?>
        <div class="mb-3">
          <label class="form-label">Amenity Name</label>
          <input type="text" class="form-control" name="amenity_name" value="<?php echo htmlspecialchars($record['amenity_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($record['description']); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Price per Use (₱)</label>
          <input type="number" step="0.01" class="form-control" name="price_per_use" value="<?php echo $record['price_per_use']; ?>" required>
        </div>

      <?php } elseif ($page == 'packages') { ?>
        <div class="mb-3">
          <label class="form-label">Package Name</label>
          <input type="text" class="form-control" name="package_name" value="<?php echo htmlspecialchars($record['package_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($record['description']); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Price (₱)</label>
          <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $record['price']; ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Included Accommodation</label>
          <select class="form-select" name="package_accommodation_id" required>
            <option value="" disabled>Select Accommodation</option>
            <?php foreach ($package_accommodations_list as $acc) { ?>
              <option value="<?php echo $acc['accommodation_id']; ?>" <?php echo ($acc['accommodation_id'] == $package_accommodation_id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($acc['accommodation_name']); ?>
              </option>
            <?php } ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Included Amenities</label>
          <div class="border p-2" style="max-height: 220px; overflow-y: auto;">
            <?php foreach ($package_amenities_list as $amen) { ?>
              <label style="display:block; font-size:0.85rem;">
                <input type="checkbox" name="package_amenity_ids[]" value="<?php echo $amen['amenity_id']; ?>" <?php echo in_array($amen['amenity_id'], $package_amenity_ids, true) ? 'checked' : ''; ?>>
                <?php echo htmlspecialchars($amen['amenity_name']); ?>
              </label>
            <?php } ?>
          </div>
        </div>

      <?php } elseif ($page == 'reservations') { ?>
        <div class="mb-3">
          <label class="form-label">Guest Name</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($record['full_name']); ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Accommodation</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($record['accommodation_name']); ?>" disabled>
        </div>
        <div class="mb-2 row">
          <div class="col">
            <label class="form-label">Check-In</label>
            <input type="text" class="form-control" value="<?php echo $record['check_in_date']; ?>" disabled>
          </div>
          <div class="col">
            <label class="form-label">Check-Out</label>
            <input type="text" class="form-control" value="<?php echo $record['check_out_date']; ?>" disabled>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Total Price</label>
          <input type="text" class="form-control" value="₱<?php echo number_format($record['total_price'], 2); ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Reservation Status</label>
          <select class="form-select" name="reservation_status">
            <option value="pending" <?php if($record['reservation_status']=='pending'){echo 'selected';} ?>>Pending</option>
            <option value="approved" <?php if($record['reservation_status']=='approved'){echo 'selected';} ?>>Approved</option>
            <option value="rejected" <?php if($record['reservation_status']=='rejected'){echo 'selected';} ?>>Rejected</option>
            <option value="cancelled" <?php if($record['reservation_status']=='cancelled'){echo 'selected';} ?>>Cancelled</option>
          </select>
        </div>

      <?php } elseif ($page == 'users') { ?>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($record['full_name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($record['username']); ?>" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($record['email']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select class="form-select" name="role">
            <option value="admin" <?php if($record['role']=='admin'){echo 'selected';} ?>>Admin</option>
            <option value="employee" <?php if($record['role']=='employee'){echo 'selected';} ?>>Employee</option>
            <option value="customer" <?php if($record['role']=='customer'){echo 'selected';} ?>>Customer</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="Active" <?php if($record['status']=='Active'){echo 'selected';} ?>>Active</option>
            <option value="Pending" <?php if($record['status']=='Pending'){echo 'selected';} ?>>Pending</option>
            <option value="Inactive" <?php if($record['status']=='Inactive'){echo 'selected';} ?>>Inactive</option>
          </select>
        </div>
      <?php } ?>

      <button type="submit" name="update_record" class="btn-gold mt-2">Save Changes</button>
      <a href="<?php echo $dashboard_page; ?>?page=<?php echo $page; ?>" class="btn-cancel">Cancel</a>
    </form>
  </div>

</body>
</html>
