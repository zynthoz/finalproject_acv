<?php
session_start();
require_once "dbconnACV.php";

if (!isset($_SESSION['id'])) {
    header("Location: loginACV.php");
    exit;
}

$role = strtolower($_SESSION['acc_type']);
if ($role != 'employee') {
    if ($role == 'admin') {
        header("Location: adminACV.php");
        exit;
    }

    if ($role == 'customer') {
        header("Location: customerACV.php");
        exit;
    }

    header("Location: loginACV.php");
    exit;
}

$allowed_pages = array('accommodations', 'amenities', 'reservations', 'packages');
$page = 'accommodations';
if (isset($_GET['page']) && in_array($_GET['page'], $allowed_pages, true)) {
    $page = $_GET['page'];
}

if (isset($_POST['add_accommodation'])) {
    $name = $_POST['accommodation_name'];
    $desc = $_POST['description'];
    $cap = $_POST['capacity'];
    $price = $_POST['price_per_night'];
    $avail = $_POST['availability_status'];

    $img = "images/bedroom1.jpg";
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "images/";
        $filename = time() . "_" . basename($_FILES['image_file']['name']);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
            $img = $target_file;
        }
    }

    $sql = "INSERT INTO tbl_accommodations (accommodation_name, description, capacity, price_per_night, availability_status, image_url) VALUES ('$name', '$desc', '$cap', '$price', '$avail', '$img')";
    $conn->query($sql);
    header("Location: employeeACV.php?page=accommodations");
    exit;
}

if (isset($_POST['add_amenity'])) {
    $name = $_POST['amenity_name'];
    $desc = $_POST['description'];
    $price = $_POST['price_per_use'];
    $sql = "INSERT INTO tbl_amenities (amenity_name, description, price_per_use) VALUES ('$name', '$desc', '$price')";
    $conn->query($sql);
    header("Location: employeeACV.php?page=amenities");
    exit;
}

if (isset($_POST['add_package'])) {
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

  $sql = "INSERT INTO tbl_packages (package_name, description, price, inclusion_details) VALUES ('$name', '$desc', '$price', '')";
  $conn->query($sql);

  $new_package_id = $conn->insert_id;
  if ($acc_id > 0 && $new_package_id > 0) {
    $conn->query("INSERT INTO tbl_package_accommodations (package_id, accommodation_id) VALUES ('$new_package_id', '$acc_id')");
  }
  if (!empty($amenity_ids) && $new_package_id > 0) {
    $values = array();
    foreach ($amenity_ids as $amenity_id) {
      $values[] = "('$new_package_id', '$amenity_id')";
    }
    $conn->query("INSERT INTO tbl_package_amenities (package_id, amenity_id) VALUES " . implode(',', $values));
  }
    header("Location: employeeACV.php?page=packages");
    exit;
}

if (isset($_POST['add_reservation'])) {
    $uid = $_POST['user_id'];
    $aid = $_POST['accommodation_id'];
    $cin = $_POST['check_in_date'];
    $cout = $_POST['check_out_date'];
    $tp = $_POST['total_price'];
    $st = $_POST['reservation_status'];
    $sql = "INSERT INTO tbl_reservations (user_id, accommodation_id, check_in_date, check_out_date, total_price, reservation_status) VALUES ('$uid', '$aid', '$cin', '$cout', '$tp', '$st')";
    $conn->query($sql);
    header("Location: employeeACV.php?page=reservations");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Dashboard - Serke's Cove</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: 'Lato', sans-serif; margin: 0; background: #faf9f7; }
    .sidebar { width: 250px; background: #2c3e50; min-height: 100vh; position: fixed; top: 0; left: 0; padding-top: 20px; }
    .sidebar h4 { color: #c8a96e; font-family: 'Playfair Display', serif; text-align: center; padding: 20px; letter-spacing: 2px; font-size: 1.1rem; }
    .sidebar a { display: block; color: #ccc; padding: 14px 24px; text-decoration: none; font-size: 0.9rem; }
    .sidebar a:hover, .sidebar a.active { background: #1a252f; color: #fff; }
    .sidebar .logout { position: absolute; bottom: 20px; width: 100%; }
    .main-content { margin-left: 250px; padding: 30px; }
    .page-title { font-family: 'Playfair Display', serif; color: #2c3e50; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    table th { background: #2c3e50; color: #fff; padding: 12px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; }
    table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    table tr:hover { background: #fcfbf9; }
    .search-bar { border: 1px solid #ccc; padding: 10px; width: 300px; margin-bottom: 20px; }
    .search-bar:focus { border-color: #c8a96e; outline: none; }
    .btn-gold { background: #c8a96e; color: #fff; border: none; padding: 8px 20px; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; cursor: pointer; }
    .btn-gold:hover { background: #b8945a; }
    .btn-sm-edit { background: #2c3e50; color: #fff; border: none; padding: 5px 12px; font-size: 0.75rem; cursor: pointer; }
    .add-form { background: #fff; border: 1px solid #eee; padding: 20px; margin-bottom: 20px; }
    .add-form input, .add-form select, .add-form textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; font-size: 0.9rem; }
    .add-form textarea { height: 60px; }
  </style>
</head>
<body>

  <div class="sidebar">
    <h4>SERKE'S COVE</h4>
    <p style="color:#999; text-align:center; font-size:0.75rem; margin-top:-10px;">Employee Panel</p>
    <a href="employeeACV.php?page=accommodations" class="<?php if($page=='accommodations'){echo 'active';} ?>"><i class="bi bi-house-door me-2"></i> Accommodations</a>
    <a href="employeeACV.php?page=amenities" class="<?php if($page=='amenities'){echo 'active';} ?>"><i class="bi bi-stars me-2"></i> Amenities</a>
    <a href="employeeACV.php?page=reservations" class="<?php if($page=='reservations'){echo 'active';} ?>"><i class="bi bi-calendar-check me-2"></i> Reservations</a>
    <a href="employeeACV.php?page=packages" class="<?php if($page=='packages'){echo 'active';} ?>"><i class="bi bi-box me-2"></i> Packages</a>
    <div class="logout">
      <a href="logoutACV.php"><i class="bi bi-box-arrow-left me-2"></i> Log Out</a>
    </div>
  </div>

  <div class="main-content">
    <?php if ($page == 'accommodations') {
        $data = $conn->query("SELECT * FROM tbl_accommodations");
    ?>
        <h2 class="page-title">Accommodations</h2>
        <input type="text" class="search-bar" id="searchBar" placeholder="Search..." onkeyup="filterTable()">

        <div class="add-form" id="addForm" style="display:none;">
          <h5>Add New Accommodation</h5>
          <form method="post" enctype="multipart/form-data">
            <input type="text" name="accommodation_name" placeholder="Name" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="number" name="capacity" placeholder="Capacity" required>
            <input type="number" step="0.01" name="price_per_night" placeholder="Price per Night" required>
            <select name="availability_status">
              <option value="available">Available</option>
              <option value="unavailable">Unavailable</option>
            </select>
            <div class="mb-2" style="text-align: left;">
              <label class="form-label text-secondary" style="font-size: 0.75rem;">Upload Image</label>
              <input type="file" name="image_file" class="form-control" style="font-size: 0.8rem; padding: 6px;">
            </div>
            <button type="submit" name="add_accommodation" class="btn-gold">Save</button>
          </form>
        </div>
        <button class="btn-gold mb-3" onclick="document.getElementById('addForm').style.display = document.getElementById('addForm').style.display === 'none' ? 'block' : 'none';">+ Add</button>

        <table id="dataTable">
          <thead><tr>
            <th onclick="sortTable(0)">ID</th>
            <th onclick="sortTable(1)">Name</th>
            <th onclick="sortTable(2)">Description</th>
            <th onclick="sortTable(3)">Capacity</th>
            <th onclick="sortTable(4)">Price/Night</th>
            <th onclick="sortTable(5)">Status</th>
            <th>Action</th>
          </tr></thead>
          <tbody>
          <?php while($row = $data->fetch_assoc()) { ?>
            <tr>
              <td><?php echo $row['accommodation_id']; ?></td>
              <td><?php echo htmlspecialchars($row['accommodation_name']); ?></td>
              <td><?php echo htmlspecialchars($row['description']); ?></td>
              <td><?php echo $row['capacity']; ?></td>
              <td>₱<?php echo number_format($row['price_per_night'],2); ?></td>
              <td><?php echo htmlspecialchars($row['availability_status']); ?></td>
              <td>
                <a class="btn-sm-edit text-decoration-none" href="editACV.php?page=accommodations&id=<?php echo $row['accommodation_id']; ?>">Edit</a>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
    <?php } elseif ($page == 'amenities') {
        $data = $conn->query("SELECT * FROM tbl_amenities");
    ?>
        <h2 class="page-title">Amenities</h2>
        <input type="text" class="search-bar" id="searchBar" placeholder="Search..." onkeyup="filterTable()">

        <div class="add-form" id="addForm" style="display:none;">
          <h5>Add New Amenity</h5>
          <form method="post">
            <input type="text" name="amenity_name" placeholder="Name" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="number" step="0.01" name="price_per_use" placeholder="Price per Use" required>
            <button type="submit" name="add_amenity" class="btn-gold">Save</button>
          </form>
        </div>
        <button class="btn-gold mb-3" onclick="document.getElementById('addForm').style.display = document.getElementById('addForm').style.display === 'none' ? 'block' : 'none';">+ Add</button>

        <table id="dataTable">
          <thead><tr>
            <th onclick="sortTable(0)">ID</th>
            <th onclick="sortTable(1)">Name</th>
            <th onclick="sortTable(2)">Description</th>
            <th onclick="sortTable(3)">Price/Use</th>
            <th>Action</th>
          </tr></thead>
          <tbody>
          <?php while($row = $data->fetch_assoc()) { ?>
            <tr>
              <td><?php echo $row['amenity_id']; ?></td>
              <td><?php echo htmlspecialchars($row['amenity_name']); ?></td>
              <td><?php echo htmlspecialchars($row['description']); ?></td>
              <td>₱<?php echo number_format($row['price_per_use'],2); ?></td>
              <td>
                <a class="btn-sm-edit text-decoration-none" href="editACV.php?page=amenities&id=<?php echo $row['amenity_id']; ?>">Edit</a>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
    <?php } elseif ($page == 'reservations') {
        $data = $conn->query("SELECT r.reservation_id, u.full_name, a.accommodation_name, r.check_in_date, r.check_out_date, r.total_price, r.reservation_status FROM tbl_reservations r JOIN tbl_users u ON r.user_id = u.user_id JOIN tbl_accommodations a ON r.accommodation_id = a.accommodation_id ORDER BY r.check_in_date DESC");
    ?>
        <h2 class="page-title">Reservations</h2>
        <input type="text" class="search-bar" id="searchBar" placeholder="Search..." onkeyup="filterTable()">

        <div class="add-form" id="addForm" style="display:none;">
          <h5>Add New Reservation</h5>
          <form method="post">
            <input type="number" name="user_id" placeholder="User ID" required>
            <input type="number" name="accommodation_id" placeholder="Accommodation ID" required>
            <input type="date" name="check_in_date" required>
            <input type="date" name="check_out_date" required>
            <input type="number" step="0.01" name="total_price" placeholder="Total Price" required>
            <select name="reservation_status">
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <button type="submit" name="add_reservation" class="btn-gold">Save</button>
          </form>
        </div>
        <button class="btn-gold mb-3" onclick="document.getElementById('addForm').style.display = document.getElementById('addForm').style.display === 'none' ? 'block' : 'none';">+ Add</button>

        <table id="dataTable">
          <thead><tr>
            <th onclick="sortTable(0)">ID</th>
            <th onclick="sortTable(1)">Guest</th>
            <th onclick="sortTable(2)">Room</th>
            <th onclick="sortTable(3)">Check-In</th>
            <th onclick="sortTable(4)">Check-Out</th>
            <th onclick="sortTable(5)">Total</th>
            <th onclick="sortTable(6)">Status</th>
            <th>Action</th>
          </tr></thead>
          <tbody>
          <?php while($row = $data->fetch_assoc()) { ?>
            <tr>
              <td><?php echo $row['reservation_id']; ?></td>
              <td><?php echo htmlspecialchars($row['full_name']); ?></td>
              <td><?php echo htmlspecialchars($row['accommodation_name']); ?></td>
              <td><?php echo $row['check_in_date']; ?></td>
              <td><?php echo $row['check_out_date']; ?></td>
              <td>₱<?php echo number_format($row['total_price'],2); ?></td>
              <td><?php echo htmlspecialchars($row['reservation_status']); ?></td>
              <td>
                <a class="btn-sm-edit text-decoration-none" href="editACV.php?page=reservations&id=<?php echo $row['reservation_id']; ?>">Edit</a>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
    <?php } elseif ($page == 'packages') {
      $data = $conn->query("SELECT * FROM tbl_packages");
      $accommodations_list = $conn->query("SELECT accommodation_id, accommodation_name FROM tbl_accommodations ORDER BY accommodation_name");
      $amenities_list = $conn->query("SELECT amenity_id, amenity_name FROM tbl_amenities ORDER BY amenity_name");

      $package_accommodations = array();
      $acc_result = $conn->query("SELECT pa.package_id, a.accommodation_name FROM tbl_package_accommodations pa JOIN tbl_accommodations a ON pa.accommodation_id = a.accommodation_id");
      if ($acc_result && $acc_result->num_rows > 0) {
        while ($row = $acc_result->fetch_assoc()) {
          $package_accommodations[$row['package_id']] = $row['accommodation_name'];
        }
      }

      $package_amenities = array();
      $amen_result = $conn->query("SELECT pam.package_id, am.amenity_name FROM tbl_package_amenities pam JOIN tbl_amenities am ON pam.amenity_id = am.amenity_id ORDER BY am.amenity_name");
      if ($amen_result && $amen_result->num_rows > 0) {
        while ($row = $amen_result->fetch_assoc()) {
          if (!isset($package_amenities[$row['package_id']])) {
            $package_amenities[$row['package_id']] = array();
          }
          $package_amenities[$row['package_id']][] = $row['amenity_name'];
        }
      }
    ?>
        <h2 class="page-title">Packages</h2>
        <input type="text" class="search-bar" id="searchBar" placeholder="Search..." onkeyup="filterTable()">

        <div class="add-form" id="addForm" style="display:none;">
          <h5>Add New Package</h5>
          <form method="post">
            <input type="text" name="package_name" placeholder="Name" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <select name="package_accommodation_id" required>
              <option value="" disabled selected>Select Accommodation</option>
              <?php if ($accommodations_list && $accommodations_list->num_rows > 0) { ?>
                <?php while ($acc = $accommodations_list->fetch_assoc()) { ?>
                  <option value="<?php echo $acc['accommodation_id']; ?>"><?php echo htmlspecialchars($acc['accommodation_name']); ?></option>
                <?php } ?>
              <?php } ?>
            </select>
            <div class="border p-2" style="max-height: 200px; overflow-y: auto;">
              <?php if ($amenities_list && $amenities_list->num_rows > 0) { ?>
                <?php while ($amen = $amenities_list->fetch_assoc()) { ?>
                  <label style="display:block; font-size:0.85rem;">
                    <input type="checkbox" name="package_amenity_ids[]" value="<?php echo $amen['amenity_id']; ?>">
                    <?php echo htmlspecialchars($amen['amenity_name']); ?>
                  </label>
                <?php } ?>
              <?php } else { ?>
                <div class="text-muted">No amenities available.</div>
              <?php } ?>
            </div>
            <button type="submit" name="add_package" class="btn-gold">Save</button>
          </form>
        </div>
        <button class="btn-gold mb-3" onclick="document.getElementById('addForm').style.display = document.getElementById('addForm').style.display === 'none' ? 'block' : 'none';">+ Add</button>

        <table id="dataTable">
          <thead><tr>
            <th onclick="sortTable(0)">ID</th>
            <th onclick="sortTable(1)">Name</th>
            <th onclick="sortTable(2)">Description</th>
            <th onclick="sortTable(3)">Price</th>
            <th onclick="sortTable(4)">Inclusions</th>
            <th>Action</th>
          </tr></thead>
          <tbody>
          <?php while($row = $data->fetch_assoc()) { ?>
            <?php
              $acc_name = isset($package_accommodations[$row['package_id']]) ? $package_accommodations[$row['package_id']] : 'Not set';
              $amenities = isset($package_amenities[$row['package_id']]) ? implode(', ', $package_amenities[$row['package_id']]) : 'None';
            ?>
            <tr>
              <td><?php echo $row['package_id']; ?></td>
              <td><?php echo htmlspecialchars($row['package_name']); ?></td>
              <td><?php echo htmlspecialchars($row['description']); ?></td>
              <td>₱<?php echo number_format($row['price'],2); ?></td>
              <td>Accommodation: <?php echo htmlspecialchars($acc_name); ?><br>Amenities: <?php echo htmlspecialchars($amenities); ?></td>
              <td>
                <a class="btn-sm-edit text-decoration-none" href="editACV.php?page=packages&id=<?php echo $row['package_id']; ?>">Edit</a>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
    <?php } ?>
  </div>

  <script>
  function filterTable() {
      var input = document.getElementById("searchBar");
      var filter = input.value.toLowerCase();
      var table = document.getElementById("dataTable");
      if (!table) {
          return;
      }
      var rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");
      var i = 0;
      while (i < rows.length) {
          var cells = rows[i].getElementsByTagName("td");
          var match = false;
          var j = 0;
          while (j < cells.length) {
              if (cells[j].innerText.toLowerCase().indexOf(filter) > -1) {
                  match = true;
              }
              j = j + 1;
          }
          if (match) {
              rows[i].style.display = "";
          } else {
              rows[i].style.display = "none";
          }
          i = i + 1;
      }
  }

  var sortDir = {};
  function sortTable(col) {
      var table = document.getElementById("dataTable");
      if (!table) {
          return;
      }
      var tbody = table.getElementsByTagName("tbody")[0];
      var rows = Array.from(tbody.getElementsByTagName("tr"));

      if (!sortDir[col]) {
          sortDir[col] = true;
      } else {
          sortDir[col] = !sortDir[col];
      }
      var asc = sortDir[col];

      rows.sort(function(a, b) {
          var cellA = a.getElementsByTagName("td")[col].innerText;
          var cellB = b.getElementsByTagName("td")[col].innerText;
          var numA = parseFloat(cellA.replace(/[₱,#]/g, ''));
          var numB = parseFloat(cellB.replace(/[₱,#]/g, ''));
          if (!isNaN(numA) && !isNaN(numB)) {
              if (asc) { return numA - numB; }
              return numB - numA;
          }
          if (asc) { return cellA.localeCompare(cellB); }
          return cellB.localeCompare(cellA);
      });

      var k = 0;
      while (k < rows.length) {
          tbody.appendChild(rows[k]);
          k = k + 1;
      }
  }
  </script>

</body>
</html>