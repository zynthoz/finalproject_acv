<?php
session_start();
require_once "dbconnACV.php";

if (isset($_SESSION['id'])) {
    $id = $_SESSION['id'];
    $logssql = "INSERT INTO tbl_logs (user_id, action, datetime) VALUES (?, 'Logged Out', NOW())";
    $logstmt = $conn->prepare($logssql);
    $logstmt->bind_param("i", $id);
    $logstmt->execute();
    $logstmt->close();
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: index.php");
exit;
?>
