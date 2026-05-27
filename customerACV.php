<?php
session_start();
require_once "dbconnACV.php";

if (!isset($_SESSION['id'])) {
    header("Location: loginACV.php");
    exit;
}

$role = strtolower($_SESSION['acc_type']);
if ($role != 'customer') {
    if ($role == 'admin') {
        header("Location: adminACV.php");
        exit;
    } else if ($role == 'employee') {
        header("Location: employeeACV.php");
        exit;
    } else {
        header("Location: loginACV.php");
        exit;
    }
}

$user_id = $_SESSION['id'];
$fullname = $_SESSION['fullname'];
$imgpth = $_SESSION['imgpth'];

$res_sql = "SELECT r.reservation_id, a.accommodation_name, r.check_in_date, r.check_out_date, r.total_price, r.reservation_status 
            FROM tbl_reservations r 
            JOIN tbl_accommodations a ON r.accommodation_id = a.accommodation_id 
            WHERE r.user_id = ? 
            ORDER BY r.check_in_date DESC";

$stmt = $conn->prepare($res_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservations_result = $stmt->get_result();

$rooms_sql = "SELECT * FROM tbl_accommodations WHERE availability_status = 'available'";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Customer Dashboard - Serke's Cove</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="frontendstyle.css" />
  <style>
    body {
      background-color: #faf9f7;
      font-family: 'Lato', sans-serif;
      color: #333333;
    }
    h1, h2, h3, h4, h5, .font-serif {
      font-family: 'Playfair Display', Georgia, serif;
    }
    .navbar {
      background-color: #2c3e50;
      border-bottom: 2px solid #c8a96e;
    }
    .navbar-brand {
      letter-spacing: 2px;
      font-weight: 700;
      font-family: 'Playfair Display', serif;
    }
    .dashboard-header {
      background-color: #ffffff;
      border-bottom: 1px solid #eeeeee;
      padding: 40px 0;
    }
    .profile-card {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .profile-card img {
      border-radius: 50%;
      width: 80px;
      height: 80px;
      object-fit: cover;
      border: 2px solid #c8a96e;
    }
    .btn-reserve, .btn-submit, .btn-add {
      background-color: #c8a96e;
      color: #ffffff;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      border-radius: 0px !important;
      border: none;
      transition: background-color 0.2s ease;
      text-decoration: none;
      display: inline-block;
    }
    .btn-reserve:hover, .btn-submit:hover, .btn-add:hover {
      background-color: #b8945a;
      color: #ffffff;
    }
    .table-container {
      background-color: #ffffff;
      border: 1px solid #eeeeee;
      padding: 30px;
      margin-bottom: 40px;
    }
    .table {
      border-collapse: collapse;
      width: 100%;
    }
    .table th {
      background-color: #2c3e50;
      color: #ffffff;
      font-family: 'Lato', sans-serif;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 1px;
      padding: 16px;
      border: none;
      cursor: pointer;
      user-select: none;
    }
    .table td {
      padding: 16px;
      vertical-align: middle;
      border-bottom: 1px solid #eeeeee;
      font-size: 0.9rem;
    }
    .table tbody tr:hover {
      background-color: #fcfbf9;
    }
    .search-input {
      border-radius: 0px;
      border: 1px solid #cccccc;
      padding: 10px 16px;
      font-size: 0.9rem;
      width: 300px;
    }
    .search-input:focus {
      border-color: #c8a96e;
      outline: none;
    }
    .status-badge {
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 6px 12px;
    }
    .status-pending {
      background-color: #fff8e1;
      color: #f57f17;
    }
    .status-approved {
      background-color: #e8f5e9;
      color: #2e7d32;
    }
    .status-rejected {
      background-color: #ffebee;
      color: #c62828;
    }
    .status-cancelled {
      background-color: #eceff1;
      color: #37474f;
    }
    .room-card {
      border: 1px solid #eeeeee;
      background-color: #ffffff;
      border-radius: 0px;
      box-shadow: none !important;
      height: 100%;
    }
    .room-card img {
      border-radius: 0px;
      height: 200px;
      object-fit: cover;
    }
    .section-label {
      font-size: 1.2rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #c8a96e;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">SERKE'S COVE</a>
      <div class="ms-auto d-flex align-items-center gap-3">
        <a href="logoutACV.php" class="btn btn-outline-light btn-sm rounded-0 uppercase">Log Out</a>
      </div>
    </div>
  </nav>

  <header class="dashboard-header">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <div class="profile-card">
            <?php 
            if (!empty($imgpth)) {
                ?>
                <img src="<?php echo $imgpth; ?>" alt="Profile">
                <?php
            } else {
                ?>
                <i class="bi bi-person-circle fs-1 text-secondary"></i>
                <?php
            }
            ?>
            <div>
              <p class="section-label mb-0">Welcome Back</p>
              <h2 class="mb-0 text-dark"><?php echo htmlspecialchars($fullname); ?></h2>
              <span class="badge bg-secondary rounded-0 uppercase">Guest Account</span>
            </div>
          </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
          <a href="Reservation.php" class="btn-add px-4 py-3">Book New Reservation</a>
        </div>
      </div>
    </div>
  </header>

  <main class="container py-5">

    <div class="table-container">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="mb-0 font-serif text-dark">My Reservations</h3>
        <div>
          <input type="text" id="tblSearch" class="search-input" placeholder="Search reservations..." />
        </div>
      </div>

      <div class="table-responsive">
        <table class="table" id="reservationsTable">
          <thead>
            <tr>
              <th onclick="sortTable(0);">ID <i class="bi bi-arrow-down-up ms-1 text-white-50"></i></th>
              <th onclick="sortTable(1);">Accommodation <i class="bi bi-arrow-down-up ms-1 text-white-50"></i></th>
              <th onclick="sortTable(2);">Check-In Date <i class="bi bi-arrow-down-up ms-1 text-white-50"></i></th>
              <th onclick="sortTable(3);">Check-Out Date <i class="bi bi-arrow-down-up ms-1 text-white-50"></i></th>
              <th onclick="sortTable(4);">Total Price <i class="bi bi-arrow-down-up ms-1 text-white-50"></i></th>
              <th onclick="sortTable(5);">Status <i class="bi bi-arrow-down-up ms-1 text-white-50"></i></th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($reservations_result && $reservations_result->num_rows > 0) {
                while ($res = $reservations_result->fetch_assoc()) {
                    $status_class = "";
                    if ($res['reservation_status'] == 'pending') {
                        $status_class = 'status-pending';
                    } else if ($res['reservation_status'] == 'approved') {
                        $status_class = 'status-approved';
                    } else if ($res['reservation_status'] == 'rejected') {
                        $status_class = 'status-rejected';
                    } else {
                        $status_class = 'status-cancelled';
                    }
                    ?>
                    <tr>
                      <td>#<?php echo $res['reservation_id']; ?></td>
                      <td class="fw-bold text-dark"><?php echo htmlspecialchars($res['accommodation_name']); ?></td>
                      <td><?php echo htmlspecialchars($res['check_in_date']); ?></td>
                      <td><?php echo htmlspecialchars($res['check_out_date']); ?></td>
                      <td class="fw-bold">₱<?php echo number_format($res['total_price'], 2); ?></td>
                      <td>
                        <span class="status-badge <?php echo $status_class; ?>">
                          <?php echo htmlspecialchars($res['reservation_status']); ?>
                        </span>
                      </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr class="no-records">
                  <td colspan="6" class="text-center text-muted py-4">You do not have any reservations yet.</td>
                </tr>
                <?php
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="row g-4 mt-2">
      
      <div class="col-lg-6">
        <div class="p-4 bg-white border border-light">
          <h3 class="font-serif mb-4 text-dark">Available Rooms</h3>
          <div class="row g-3">
            <?php 
            if ($rooms_result && $rooms_result->num_rows > 0) {
                while ($room = $rooms_result->fetch_assoc()) {
                    ?>
                    <div class="col-12">
                      <div class="d-flex align-items-center gap-3 p-3 border">
                        <?php 
                        $img = $room['image_url'];
                        if (empty($img)) {
                            $img = 'images/bedroom1.jpg';
                        }
                        ?>
                        <img src="<?php echo $img; ?>" alt="Room" width="80" height="80" style="object-fit: cover;">
                        <div class="flex-grow-1">
                          <h5 class="mb-1"><?php echo htmlspecialchars($room['accommodation_name']); ?></h5>
                          <p class="mb-0 text-muted small">Max <?php echo $room['capacity']; ?> guests • ₱<?php echo number_format($room['price_per_night'], 2); ?>/night</p>
                        </div>
                        <div>
                          <a href="Reservation.php?room_id=<?php echo $room['accommodation_id']; ?>" class="btn-reserve btn-sm px-3 py-2 fs-6">Book</a>
                        </div>
                      </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col text-center">
                  <p class="text-muted small">No rooms available currently.</p>
                </div>
                <?php
            }
            ?>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="p-4 bg-white border border-light">
          <h3 class="font-serif mb-4 text-dark">Special Bundles</h3>
          <div class="row g-3">
            <?php 
            if ($packages_result && $packages_result->num_rows > 0) {
                while ($pkg = $packages_result->fetch_assoc()) {
                    ?>
                    <div class="col-12">
                      <div class="p-3 border">
                        <h5 class="mb-1 text-dark"><?php echo htmlspecialchars($pkg['package_name']); ?></h5>
                        <?php
                          $acc_name = isset($package_accommodations[$pkg['package_id']]) ? $package_accommodations[$pkg['package_id']] : 'Not set';
                          $amenities = isset($package_amenities[$pkg['package_id']]) ? implode(', ', $package_amenities[$pkg['package_id']]) : 'None';
                        ?>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($pkg['description']); ?></p>
                        <p class="text-muted small mb-2">Includes: Accommodation: <?php echo htmlspecialchars($acc_name); ?>; Amenities: <?php echo htmlspecialchars($amenities); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                          <strong class="text-dark">₱<?php echo number_format($pkg['price'], 2); ?></strong>
                          <a href="Reservation.php?package_id=<?php echo $pkg['package_id']; ?>" class="btn-reserve btn-sm px-3 py-2 fs-6">Book Bundle</a>
                        </div>
                      </div>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="col text-center">
                  <p class="text-muted small">No packages found.</p>
                </div>
                <?php
            }
            ?>
          </div>
        </div>
      </div>

    </div>

  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
  
  <script>
    const searchInput = document.getElementById("tblSearch");
    searchInput.addEventListener("keyup", function() {
        const query = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll("#reservationsTable tbody tr");
        
        let j = 0;
        while (j < rows.length) {
            const row = rows[j];
            if (row.classList.contains("no-records")) {
                j = j + 1;
                continue;
            }
            const cells = row.getElementsByTagName("td");
            let rowMatches = false;
            
            let k = 0;
            while (k < cells.length) {
                const cellText = cells[k].innerText.toLowerCase();
                if (cellText.indexOf(query) > -1) {
                    rowMatches = true;
                    break;
                }
                k = k + 1;
            }
            
            if (rowMatches) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
            j = j + 1;
        }
    });

    let sortDirections = [true, true, true, true, true, true];

    function sortTable(columnIndex) {
        const table = document.getElementById("reservationsTable");
        const tbody = table.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        
        if (rows.length === 1 && rows[0].classList.contains("no-records")) {
            return;
        }

        const ascending = sortDirections[columnIndex];
        sortDirections[columnIndex] = !ascending;

        rows.sort(function(rowA, rowB) {
            const cellA = rowA.getElementsByTagName("td")[columnIndex].innerText;
            const cellB = rowB.getElementsByTagName("td")[columnIndex].innerText;

            let valA = cellA;
            let valB = cellB;

            if (columnIndex === 0) {
                valA = parseInt(cellA.replace("#", ""));
                valB = parseInt(cellB.replace("#", ""));
            } else if (columnIndex === 4) {
                valA = parseFloat(cellA.replace("₱", "").replace(/,/g, ""));
                valB = parseFloat(cellB.replace("₱", "").replace(/,/g, ""));
            }

            if (typeof valA === 'number' && typeof valB === 'number') {
                if (ascending) {
                    return valA - valB;
                } else {
                    return valB - valA;
                }
            } else {
                const strA = valA.toString().toLowerCase();
                const strB = valB.toString().toLowerCase();
                if (ascending) {
                    return strA.localeCompare(strB);
                } else {
                    return strB.localeCompare(strA);
                }
            }
        });

        let l = 0;
        while (l < rows.length) {
            tbody.appendChild(rows[l]);
            l = l + 1;
        }
    }
  </script>
</body>
</html>
<?php
$stmt->close();
?>