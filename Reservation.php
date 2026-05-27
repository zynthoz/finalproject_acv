<?php
session_start();
require_once "dbconnACV.php";

if (!isset($_SESSION['id'])) {
    header("Location: loginACV.php");
    exit;
}

$user_id = $_SESSION['id'];

$rooms_sql = "SELECT * FROM tbl_accommodations WHERE availability_status = 'available'";
$rooms_result = $conn->query($rooms_sql);

$packages_sql = "SELECT * FROM tbl_packages";
$packages_result = $conn->query($packages_sql);

$amenities_sql = "SELECT * FROM tbl_amenities ORDER BY amenity_name ASC";
$amenities_result = $conn->query($amenities_sql);

$rooms_data = array();
if ($rooms_result && $rooms_result->num_rows > 0) {
    while ($r = $rooms_result->fetch_assoc()) {
        $rooms_data[] = $r;
    }
}

$packages_data = array();
if ($packages_result && $packages_result->num_rows > 0) {
  while ($pkg = $packages_result->fetch_assoc()) {
    $packages_data[] = $pkg;
  }
}

$amenities_data = array();
if ($amenities_result && $amenities_result->num_rows > 0) {
  while ($amenity = $amenities_result->fetch_assoc()) {
    $amenities_data[] = $amenity;
  }
}

$package_accommodations = array();
$acc_map_result = $conn->query("SELECT pa.package_id, a.accommodation_id, a.accommodation_name FROM tbl_package_accommodations pa JOIN tbl_accommodations a ON pa.accommodation_id = a.accommodation_id");
if ($acc_map_result && $acc_map_result->num_rows > 0) {
  while ($row = $acc_map_result->fetch_assoc()) {
    $package_accommodations[$row['package_id']] = array(
      'id' => intval($row['accommodation_id']),
      'name' => $row['accommodation_name']
    );
  }
}

$package_amenities = array();
$package_amenity_ids = array();
$amen_map_result = $conn->query("SELECT pam.package_id, am.amenity_id, am.amenity_name FROM tbl_package_amenities pam JOIN tbl_amenities am ON pam.amenity_id = am.amenity_id ORDER BY am.amenity_name");
if ($amen_map_result && $amen_map_result->num_rows > 0) {
  while ($row = $amen_map_result->fetch_assoc()) {
    $pkg_id = intval($row['package_id']);
    if (!isset($package_amenities[$pkg_id])) {
      $package_amenities[$pkg_id] = array();
      $package_amenity_ids[$pkg_id] = array();
    }
    $package_amenities[$pkg_id][] = $row['amenity_name'];
    $package_amenity_ids[$pkg_id][] = intval($row['amenity_id']);
  }
}

for ($i = 0; $i < count($packages_data); $i++) {
  $pkg_id = intval($packages_data[$i]['package_id']);
  $acc_name = isset($package_accommodations[$pkg_id]) ? $package_accommodations[$pkg_id]['name'] : 'Not set';
  $amenities = isset($package_amenities[$pkg_id]) ? implode(', ', $package_amenities[$pkg_id]) : 'None';
  $packages_data[$i]['accommodation_id'] = isset($package_accommodations[$pkg_id]) ? $package_accommodations[$pkg_id]['id'] : 0;
  $packages_data[$i]['accommodation_name'] = $acc_name;
  $packages_data[$i]['amenity_ids'] = isset($package_amenity_ids[$pkg_id]) ? $package_amenity_ids[$pkg_id] : array();
  $packages_data[$i]['includes_display'] = 'Accommodation: ' . $acc_name . '; Amenities: ' . $amenities;
}

$preselected_room_id = 0;
if (isset($_GET['room_id'])) {
    $preselected_room_id = intval($_GET['room_id']);
}

$preselected_package_id = 0;
if (isset($_GET['package_id'])) {
  $preselected_package_id = intval($_GET['package_id']);
}

$selected_package_data = null;
if ($preselected_package_id > 0) {
  foreach ($packages_data as $package_item) {
    if (intval($package_item['package_id']) === $preselected_package_id) {
      $selected_package_data = $package_item;
      break;
    }
  }
}

$error_message = "";
$success_message = "";

if (isset($_POST['book_reservation'])) {
    $accommodation_id = intval($_POST['accommodation_id']);
    $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
    $check_in = htmlspecialchars(trim($_POST['check_in_date']));
    $check_out = htmlspecialchars(trim($_POST['check_out_date']));
    $amenity_ids = array();
    $package_name = "";
    $package_price = 0;
    if ($package_id > 0) {
      $package_sql = "SELECT package_name, price FROM tbl_packages WHERE package_id = ?";
      $package_stmt = $conn->prepare($package_sql);
      $package_stmt->bind_param("i", $package_id);
      $package_stmt->execute();
      $package_result = $package_stmt->get_result();

      if ($package_result->num_rows == 1) {
        $package_row = $package_result->fetch_assoc();
        $package_name = $package_row['package_name'];
        $package_price = floatval($package_row['price']);
      } else {
        $error_message = "Selected package is not available.";
      }
      $package_stmt->close();

      $acc_map_stmt = $conn->prepare("SELECT a.accommodation_id, a.accommodation_name FROM tbl_package_accommodations pa JOIN tbl_accommodations a ON pa.accommodation_id = a.accommodation_id WHERE pa.package_id = ?");
      $acc_map_stmt->bind_param("i", $package_id);
      $acc_map_stmt->execute();
      $acc_map_result = $acc_map_stmt->get_result();
      if ($acc_map_result->num_rows == 1) {
        $acc_map_row = $acc_map_result->fetch_assoc();
        $accommodation_id = intval($acc_map_row['accommodation_id']);
      } else {
        $error_message = "Bundle inclusions do not reference a valid accommodation.";
      }
      $acc_map_stmt->close();

      $amen_map_stmt = $conn->prepare("SELECT amenity_id FROM tbl_package_amenities WHERE package_id = ?");
      $amen_map_stmt->bind_param("i", $package_id);
      $amen_map_stmt->execute();
      $amen_map_result = $amen_map_stmt->get_result();
      if ($amen_map_result && $amen_map_result->num_rows > 0) {
        while ($amen_row = $amen_map_result->fetch_assoc()) {
          $amenity_ids[] = intval($amen_row['amenity_id']);
        }
      }
      $amen_map_stmt->close();
    } else if (isset($_POST['amenity_ids']) && is_array($_POST['amenity_ids'])) {
      foreach ($_POST['amenity_ids'] as $amenity_id) {
        $amenity_ids[] = intval($amenity_id);
      }
      $amenity_ids = array_values(array_unique(array_filter($amenity_ids)));
    }

    $room_price_sql = "SELECT price_per_night, accommodation_name FROM tbl_accommodations WHERE accommodation_id = ?";
    $room_stmt = $conn->prepare($room_price_sql);
    $room_stmt->bind_param("i", $accommodation_id);
    $room_stmt->execute();
    $room_result = $room_stmt->get_result();

    if ($room_result->num_rows == 1) {
        $room_row = $room_result->fetch_assoc();
        $price_per_night = $room_row['price_per_night'];
        $room_name = $room_row['accommodation_name'];

      $package_name = "";
      $package_price = 0;
      $package_inclusions = "";
      if ($package_id > 0) {
        $package_sql = "SELECT package_name, price, inclusion_details FROM tbl_packages WHERE package_id = ?";
        $package_stmt = $conn->prepare($package_sql);
        $package_stmt->bind_param("i", $package_id);
        $package_stmt->execute();
        $package_result = $package_stmt->get_result();

        if ($package_result->num_rows == 1) {
          $package_row = $package_result->fetch_assoc();
          $package_name = $package_row['package_name'];
          $package_price = floatval($package_row['price']);
          $package_inclusions = $package_row['inclusion_details'];

          $normalized_inclusions = normalize_text($package_inclusions);
          $matched_room_id = 0;
          $matched_len = 0;
          foreach ($rooms_data as $room_item) {
            $room_name = normalize_text($room_item['accommodation_name']);
            if ($room_name && strpos($normalized_inclusions, $room_name) !== false && strlen($room_name) > $matched_len) {
              $matched_room_id = intval($room_item['accommodation_id']);
              $matched_len = strlen($room_name);
            }
          }

          if ($matched_room_id > 0) {
            $accommodation_id = $matched_room_id;
          } else {
            $error_message = "Bundle inclusions do not reference a valid accommodation.";
          }

          $amenity_ids = array();
          foreach ($amenities_data as $amenity_item) {
            $amenity_name = normalize_text($amenity_item['amenity_name']);
            $short_name = normalize_text(preg_replace('/\b(rental|setup|tour)\b/i', '', $amenity_item['amenity_name']));

            if ($amenity_name && strpos($normalized_inclusions, $amenity_name) !== false) {
              $amenity_ids[] = intval($amenity_item['amenity_id']);
            } elseif ($short_name && strpos($normalized_inclusions, $short_name) !== false) {
              $amenity_ids[] = intval($amenity_item['amenity_id']);
            }
          }
          $amenity_ids = array_values(array_unique(array_filter($amenity_ids)));
        } else {
          $error_message = "Selected package is not available.";
        }
        $package_stmt->close();
      } else {
        $package_id = 0;
      }

    $amenity_total = 0;
    $amenity_names = array();
    if (!empty($amenity_ids)) {
      $amenity_id_list = implode(',', array_map('intval', $amenity_ids));
      $amenity_sql = "SELECT amenity_name, price_per_use FROM tbl_amenities WHERE amenity_id IN ($amenity_id_list)";
      $amenity_result = $conn->query($amenity_sql);

      if ($amenity_result && $amenity_result->num_rows > 0) {
        while ($amenity_row = $amenity_result->fetch_assoc()) {
          $amenity_total += floatval($amenity_row['price_per_use']);
          $amenity_names[] = $amenity_row['amenity_name'];
        }
      }
    }

        $check_in_time = strtotime($check_in);
        $check_out_time = strtotime($check_out);
        $diff_seconds = $check_out_time - $check_in_time;
        $nights = intval($diff_seconds / (60 * 60 * 24));
        if ($nights < 1) {
            $nights = 1;
        }

        $room_total = $price_per_night * $nights;
        $total_price = $room_total + $package_price + $amenity_total;
        $status = "pending";

        if (empty($error_message)) {
          $insert_res_sql = "INSERT INTO tbl_reservations (user_id, accommodation_id, check_in_date, check_out_date, total_price, reservation_status) VALUES (?, ?, ?, ?, ?, ?)";
          $insert_stmt = $conn->prepare($insert_res_sql);
          $insert_stmt->bind_param("iissds", $user_id, $accommodation_id, $check_in, $check_out, $total_price, $status);
          $insert_result = $insert_stmt->execute();

          if ($insert_result) {
            $new_res_id = $conn->insert_id;

            $log_action = "Created Reservation #" . $new_res_id . " for " . $room_name;
            if (!empty($package_name)) {
              $log_action .= " with package " . $package_name;
            }
            if (!empty($amenity_names)) {
              $log_action .= " and amenities: " . implode(", ", $amenity_names);
            }

            $log_sql = "INSERT INTO tbl_logs (user_id, action, datetime) VALUES (?, ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("is", $user_id, $log_action);
            $log_stmt->execute();
            $log_stmt->close();

            $success_message = "Your reservation request has been submitted successfully!";
          } else {
            $error_message = "System error: Failed to book your reservation.";
          }
          $insert_stmt->close();
        }
    } else {
        $error_message = "Selected accommodation is not available.";
    }
    $room_stmt->close();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book a Reservation - Serke's Cove</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reservation.css" />
  <style>
    body {
      background-color: #faf9f7;
      font-family: 'Lato', sans-serif;
      color: #333333;
    }
    .navbar {
      background-color: #2c3e50;
      border-bottom: 2px solid #c8a96e;
    }
    .navbar-brand {
      letter-spacing: 2px;
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      color: #ffffff !important;
    }
    .billing-card {
      border: 1px solid #eeeeee;
      background-color: #ffffff;
      border-radius: 0px;
      box-shadow: none;
      margin-top: 50px;
      margin-bottom: 50px;
    }
    .left-panel {
      background-color: #2c3e50;
      color: #ffffff;
      padding: 50px;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .left-panel h1 {
      font-family: 'Playfair Display', serif;
      font-size: 2.2rem;
      color: #c8a96e;
      margin-bottom: 20px;
    }
    .left-panel p {
      color: rgba(255, 255, 255, 0.7);
      line-height: 1.8;
      font-size: 0.95rem;
    }
    .form-section {
      padding: 50px;
    }
    .section-title {
      font-family: 'Playfair Display', serif;
      color: #2c3e50;
      margin-bottom: 30px;
      font-size: 1.8rem;
      border-bottom: 2px solid #faf9f7;
      padding-bottom: 10px;
    }
    .form-label {
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #777777;
    }
    .form-control, .form-select {
      border-radius: 0px;
      border: 1px solid #cccccc;
      padding: 12px;
    }
    .form-control:focus, .form-select:focus {
      border-color: #c8a96e;
      box-shadow: none;
    }
    .btn-submit {
      background-color: #c8a96e;
      color: #ffffff;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 14px 30px;
      border: none;
      border-radius: 0px;
      width: 100%;
      transition: background-color 0.2s ease;
    }
    .btn-submit:hover {
      background-color: #b8945a;
    }
    .pricing-summary {
      background-color: #faf9f7;
      border: 1px solid #eeeeee;
      padding: 24px;
      margin-top: 30px;
    }
    .pricing-row {
      display: flex;
      justify-content: justify;
      margin-bottom: 12px;
    }
    .pricing-row:last-child {
      margin-bottom: 0px;
      border-top: 1px solid #dddddd;
      padding-top: 12px;
      font-weight: 700;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      SERKE'S COVE
    </a>
    <div class="ms-auto">
      <a href="customerACV.php" class="btn btn-outline-light btn-sm rounded-0 uppercase px-3">Dashboard</a>
    </div>
  </div>
</nav>

<section class="main-section">
  <div class="container">
    <div class="billing-card">
      <div class="row g-0">

        <div class="col-lg-5">
          <div class="left-panel">
            <h1>Sanctuary Booking</h1>
            <p>
              Complete the form to reserve your accommodation at Serke's Cove. Once submitted, our staff will review your reservation request.
            </p>
            <p class="mt-3">
              We look forward to welcoming you to an experience of peaceful ocean waves and modern wellness.
            </p>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="form-section">
            <h2 class="section-title">Reservation Details</h2>

            <form action="Reservation.php" method="post" id="bookingForm">
              
              <div class="mb-4" id="accommodationSection">
                <label class="form-label" for="accommodation_id">Select Accommodation</label>
                <select name="accommodation_id" id="accommodation_id" class="form-select" required>
                  <option value="">Choose a room or suite...</option>
                  <?php if (!empty($rooms_data)) { ?>
                    <?php foreach ($rooms_data as $room) { ?>
                      <option value="<?php echo $room['accommodation_id']; ?>" data-price="<?php echo $room['price_per_night']; ?>" <?php echo ($room['accommodation_id'] == $preselected_room_id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($room['accommodation_name']); ?> (Capacity: <?php echo $room['capacity']; ?> guests) — ₱<?php echo number_format($room['price_per_night'], 2); ?>/night
                      </option>
                    <?php } ?>
                  <?php } else { ?>
                    <option value="" disabled>No accommodations available</option>
                  <?php } ?>
                </select>
              </div>

              <div class="mb-4">
                <label class="form-label" for="package_id">Select Special Bundle</label>
                <select name="package_id" id="package_id" class="form-select">
                  <option value="0">No bundle selected</option>
                  <?php foreach ($packages_data as $package) { ?>
                    <option value="<?php echo $package['package_id']; ?>" data-price="<?php echo $package['price']; ?>" <?php echo ($package['package_id'] == $preselected_package_id) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($package['package_name']); ?> — ₱<?php echo number_format($package['price'], 2); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="mb-4 p-4 border bg-white" id="bundleDetailsBox" style="display: <?php echo $selected_package_data ? 'block' : 'none'; ?>;">
                <div class="d-flex justify-content-between align-items-start gap-3">
                  <div>
                    <div class="text-uppercase small fw-bold text-muted mb-1">Selected Bundle</div>
                    <h5 class="mb-2 text-dark" id="bundleNameDisplay"><?php echo $selected_package_data ? htmlspecialchars($selected_package_data['package_name']) : ''; ?></h5>
                    <p class="mb-0 text-muted" id="bundleDescriptionDisplay"><?php echo $selected_package_data ? htmlspecialchars($selected_package_data['description']) : 'Choose a bundle to see what is included.'; ?></p>
                  </div>
                  <div class="text-end">
                    <div class="small text-muted">Bundle Price</div>
                    <div class="fw-bold fs-5 text-dark" id="bundlePriceDisplay"><?php echo $selected_package_data ? '₱' . number_format($selected_package_data['price'], 2) : '₱0.00'; ?></div>
                  </div>
                </div>
                <div class="mt-3">
                  <div class="small text-muted text-uppercase fw-bold mb-2">Includes</div>
                  <div class="border rounded p-3 bg-light">
                    <div class="mb-2">
                      <div class="text-muted small text-uppercase fw-bold">Accommodation</div>
                      <div class="text-dark" id="bundleAccommodationDisplay">
                        <?php echo $selected_package_data ? htmlspecialchars($selected_package_data['accommodation_name']) : ''; ?>
                      </div>
                    </div>
                    <div>
                      <div class="text-muted small text-uppercase fw-bold">Amenities</div>
                      <div class="d-flex flex-wrap gap-2 mt-2" id="bundleAmenitiesDisplay">
                        <?php
                          if ($selected_package_data && !empty($selected_package_data['amenity_ids'])) {
                              $amenity_labels = array();
                              foreach ($amenities_data as $amenity_item) {
                                  if (in_array($amenity_item['amenity_id'], $selected_package_data['amenity_ids'], true)) {
                                      $amenity_labels[] = $amenity_item['amenity_name'];
                                  }
                              }
                              foreach ($amenity_labels as $label) {
                                  echo '<span class="badge bg-white text-dark border">' . htmlspecialchars($label) . '</span>';
                              }
                          }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-4" id="amenitiesSection">
                <label class="form-label d-block">Select Amenities</label>
                <div class="row g-2">
                  <?php if (!empty($amenities_data)) { ?>
                    <?php foreach ($amenities_data as $amenity) { ?>
                      <div class="col-md-6">
                        <label class="d-flex align-items-start gap-2 border p-3 h-100" style="cursor:pointer;">
                          <input type="checkbox" class="form-check-input mt-1 amenity-check" name="amenity_ids[]" value="<?php echo $amenity['amenity_id']; ?>" data-price="<?php echo $amenity['price_per_use']; ?>">
                          <span>
                            <span class="d-block fw-bold text-dark"><?php echo htmlspecialchars($amenity['amenity_name']); ?></span>
                            <span class="d-block text-muted small"><?php echo htmlspecialchars($amenity['description']); ?></span>
                            <span class="d-block text-dark small fw-bold">₱<?php echo number_format($amenity['price_per_use'], 2); ?> per use</span>
                          </span>
                        </label>
                      </div>
                    <?php } ?>
                  <?php } else { ?>
                    <div class="col-12 text-muted small">No amenities available.</div>
                  <?php } ?>
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-md-6">
                  <label class="form-label" for="check_in_date">Check-In Date</label>
                  <input type="date" name="check_in_date" id="check_in_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="check_out_date">Check-Out Date</label>
                  <input type="date" name="check_out_date" id="check_out_date" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
              </div>

              <div class="pricing-summary">
                <h5 class="font-serif text-dark mb-3">Booking Summary</h5>
                <div class="row mb-2">
                  <div class="col-6 text-muted">Price per night:</div>
                  <div class="col-6 text-end fw-bold" id="lbl_price_per_night">₱0.00</div>
                </div>
                <div class="row mb-2">
                  <div class="col-6 text-muted">Number of nights:</div>
                  <div class="col-6 text-end fw-bold" id="lbl_nights">0 nights</div>
                </div>
                <div class="row mb-2">
                  <div class="col-6 text-muted">Bundle price:</div>
                  <div class="col-6 text-end fw-bold" id="lbl_package_price">₱0.00</div>
                </div>
                <div class="row mb-2">
                  <div class="col-6 text-muted">Amenities total:</div>
                  <div class="col-6 text-end fw-bold" id="lbl_amenity_price">₱0.00</div>
                </div>
                <div class="row pt-3 border-top">
                  <div class="col-6 font-serif text-dark fw-bold fs-5">Estimated Total:</div>
                  <div class="col-6 text-end font-serif text-dark fw-bold fs-5" id="lbl_total_price">₱0.00</div>
                </div>
              </div>

              <div class="mt-4">
                <button type="submit" name="book_reservation" class="btn-submit">
                  Book Reservation
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  const roomsData = <?php echo json_encode($rooms_data); ?>;
  const packagesData = <?php echo json_encode($packages_data); ?>;
  const amenitiesData = <?php echo json_encode($amenities_data); ?>;
  
  const selectRoom = document.getElementById("accommodation_id");
  const selectPackage = document.getElementById("package_id");
  const txtCheckIn = document.getElementById("check_in_date");
  const txtCheckOut = document.getElementById("check_out_date");
  const amenityChecks = document.querySelectorAll(".amenity-check");

  const lblPricePerNight = document.getElementById("lbl_price_per_night");
  const lblNights = document.getElementById("lbl_nights");
  const lblPackagePrice = document.getElementById("lbl_package_price");
  const lblAmenityPrice = document.getElementById("lbl_amenity_price");
  const lblTotalPrice = document.getElementById("lbl_total_price");
    const bundleDetailsBox = document.getElementById("bundleDetailsBox");
    const bundleNameDisplay = document.getElementById("bundleNameDisplay");
    const bundleDescriptionDisplay = document.getElementById("bundleDescriptionDisplay");
    const bundlePriceDisplay = document.getElementById("bundlePriceDisplay");
    const bundleAccommodationDisplay = document.getElementById("bundleAccommodationDisplay");
    const bundleAmenitiesDisplay = document.getElementById("bundleAmenitiesDisplay");
    const accommodationSection = document.getElementById("accommodationSection");
    const amenitiesSection = document.getElementById("amenitiesSection");

    let bundleAutofillLock = false;

    function getSelectedPackage() {
      const selectedValue = parseInt(selectPackage.value);
      let foundPackage = null;
      let index = 0;
      while (index < packagesData.length) {
        if (parseInt(packagesData[index].package_id) === selectedValue) {
          foundPackage = packagesData[index];
          break;
        }
        index = index + 1;
      }
      return foundPackage;
    }

    function setAmenityCheckedById(amenityId, checked) {
      let idx = 0;
      while (idx < amenityChecks.length) {
        if (parseInt(amenityChecks[idx].value) === parseInt(amenityId)) {
          amenityChecks[idx].checked = checked;
          break;
        }
        idx = idx + 1;
      }
    }

    function getAmenityLabel(amenityId) {
      let idx = 0;
      while (idx < amenitiesData.length) {
        if (parseInt(amenitiesData[idx].amenity_id) === parseInt(amenityId)) {
          return amenitiesData[idx].amenity_name;
        }
        idx = idx + 1;
      }
      return "";
    }

    function renderBundleAmenities(amenityIds) {
      bundleAmenitiesDisplay.innerHTML = "";
      if (!Array.isArray(amenityIds) || amenityIds.length === 0) {
        bundleAmenitiesDisplay.innerHTML = '<span class="text-muted small">No amenities included.</span>';
        return;
      }
      let idx = 0;
      while (idx < amenityIds.length) {
        const label = getAmenityLabel(amenityIds[idx]);
        if (label) {
          const badge = document.createElement("span");
          badge.className = "badge bg-white text-dark border";
          badge.innerText = label;
          bundleAmenitiesDisplay.appendChild(badge);
        }
        idx = idx + 1;
      }
    }


    function clearAmenitySelections() {
      let idx = 0;
      while (idx < amenityChecks.length) {
        amenityChecks[idx].checked = false;
        idx = idx + 1;
      }
    }

    function applyBundleAutofill() {
      const selectedPackage = getSelectedPackage();
      if (!selectedPackage) {
        bundleDetailsBox.style.display = "none";
        bundleNameDisplay.innerText = "";
        bundleDescriptionDisplay.innerText = "Choose a bundle to see what is included.";
        bundlePriceDisplay.innerText = "₱0.00";
        bundleAccommodationDisplay.innerText = "";
        bundleAmenitiesDisplay.innerHTML = "";
          accommodationSection.style.display = "block";
          amenitiesSection.style.display = "block";
          selectRoom.disabled = false;
          selectRoom.required = true;
          selectPackage.disabled = false;
          let idx = 0;
          while (idx < amenityChecks.length) {
            amenityChecks[idx].disabled = false;
            idx = idx + 1;
          }
          if (!selectRoom.value) {
            selectPackage.disabled = false;
          }
        return;
      }

      bundleDetailsBox.style.display = "block";
      bundleNameDisplay.innerText = selectedPackage.package_name;
      bundleDescriptionDisplay.innerText = selectedPackage.description || "";
      bundlePriceDisplay.innerText = "₱" + parseFloat(selectedPackage.price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      bundleAccommodationDisplay.innerText = selectedPackage.accommodation_name || "";
      renderBundleAmenities(selectedPackage.amenity_ids || []);
      accommodationSection.style.display = "none";
      amenitiesSection.style.display = "none";

      if (bundleAutofillLock) {
        return;
      }

      bundleAutofillLock = true;
      clearAmenitySelections();
        if (selectedPackage.accommodation_id) {
          selectRoom.value = String(selectedPackage.accommodation_id);
        }
        if (Array.isArray(selectedPackage.amenity_ids)) {
          let idx = 0;
          while (idx < selectedPackage.amenity_ids.length) {
            setAmenityCheckedById(selectedPackage.amenity_ids[idx], true);
            idx = idx + 1;
          }
        }
    selectRoom.disabled = true;
    selectRoom.required = false;
    selectPackage.disabled = false;
    let lockIdx = 0;
    while (lockIdx < amenityChecks.length) {
      amenityChecks[lockIdx].disabled = true;
      lockIdx = lockIdx + 1;
    }
      bundleAutofillLock = false;
    }

  function enforceAccommodationMode() {
      if (parseInt(selectPackage.value) > 0 || selectRoom.disabled) {
        return;
      }
    const hasRoom = !!selectRoom.value;
    if (hasRoom) {
      selectPackage.value = "0";
      selectPackage.disabled = true;
      bundleDetailsBox.style.display = "none";
      bundleNameDisplay.innerText = "";
      bundleDescriptionDisplay.innerText = "Choose a bundle to see what is included.";
      bundlePriceDisplay.innerText = "₱0.00";
      bundleAccommodationDisplay.innerText = "";
      bundleAmenitiesDisplay.innerHTML = "";
        accommodationSection.style.display = "block";
        amenitiesSection.style.display = "block";
      let idx = 0;
      while (idx < amenityChecks.length) {
        amenityChecks[idx].disabled = false;
        idx = idx + 1;
      }
    } else {
      selectPackage.disabled = false;
    }
  }

  function calculatePrice() {
      const selectedId = parseInt(selectRoom.value);
      const selectedPackageId = parseInt(selectPackage.value);
      const checkInVal = txtCheckIn.value;
      const checkOutVal = txtCheckOut.value;

      let pricePerNight = 0;
      let nights = 0;
      let total = 0;
      let packagePrice = 0;
      let amenityTotal = 0;

      let roomFound = false;
      let i = 0;
      while (i < roomsData.length) {
          if (parseInt(roomsData[i].accommodation_id) === selectedId) {
              pricePerNight = parseFloat(roomsData[i].price_per_night);
              roomFound = true;
              break;
          }
          i = i + 1;
      }

      if (roomFound) {
          lblPricePerNight.innerText = "₱" + pricePerNight.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      } else {
          lblPricePerNight.innerText = "₱0.00";
      }

        let packageFound = false;
        let p = 0;
        while (p < packagesData.length) {
          if (parseInt(packagesData[p].package_id) === selectedPackageId) {
            packagePrice = parseFloat(packagesData[p].price);
            packageFound = true;
            break;
          }
          p = p + 1;
        }

        if (packageFound) {
          lblPackagePrice.innerText = "₱" + packagePrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } else {
          lblPackagePrice.innerText = "₱0.00";
        }

        let a = 0;
        while (a < amenityChecks.length) {
          if (amenityChecks[a].checked) {
            amenityTotal = amenityTotal + parseFloat(amenityChecks[a].dataset.price);
          }
          a = a + 1;
        }
        lblAmenityPrice.innerText = "₱" + amenityTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

      if (checkInVal && checkOutVal) {
          const dateIn = new Date(checkInVal);
          const dateOut = new Date(checkOutVal);
          const diffTime = dateOut.getTime() - dateIn.getTime();
          
          if (diffTime > 0) {
              nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
          }
      }

      if (nights > 0) {
          lblNights.innerText = nights + " night" + (nights > 1 ? "s" : "");
      total = (pricePerNight * nights) + packagePrice + amenityTotal;
          lblTotalPrice.innerText = "₱" + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      } else {
          lblNights.innerText = "0 nights";
      total = packagePrice + amenityTotal;
      lblTotalPrice.innerText = "₱" + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }
  }

  selectRoom.addEventListener("change", function() {
      enforceAccommodationMode();
      calculatePrice();
  });
  selectPackage.addEventListener("change", function() {
      applyBundleAutofill();
      enforceAccommodationMode();
      calculatePrice();
  });
  let b = 0;
  while (b < amenityChecks.length) {
    amenityChecks[b].addEventListener("change", function() {
        if (!bundleAutofillLock) {
            calculatePrice();
        }
    });
    b = b + 1;
  }
  txtCheckIn.addEventListener("change", function() {
      if (txtCheckIn.value) {
          const checkInDate = new Date(txtCheckIn.value);
          checkInDate.setDate(checkInDate.getDate() + 1);
          
          const yyyy = checkInDate.getFullYear();
          let mm = checkInDate.getMonth() + 1;
          let dd = checkInDate.getDate();
          
          if (mm < 10) {
              mm = '0' + mm;
          }
          if (dd < 10) {
              dd = '0' + dd;
          }
          
          txtCheckOut.min = yyyy + '-' + mm + '-' + dd;
      }
      calculatePrice();
  });
  txtCheckOut.addEventListener("change", calculatePrice);

  applyBundleAutofill();
  enforceAccommodationMode();
  calculatePrice();

  <?php if (!empty($success_message)): ?>
      Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: <?php echo json_encode($success_message); ?>,
          confirmButtonColor: '#c8a96e',
          confirmButtonText: 'View Dashboard'
      }).then((result) => {
          window.location.href = "customerACV.php";
      });
  <?php endif; ?>

  <?php if (!empty($error_message)): ?>
      Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: <?php echo json_encode($error_message); ?>,
          confirmButtonColor: '#2c3e50',
          confirmButtonText: 'Try Again'
      });
  <?php endif; ?>
</script>

</body>
</html>
