<?php
include("../connection.php");

// Require auth token
if (!isset($_COOKIE["auth_token"])) {
    header("Location: ../login.php");
    exit();
}

$token = $_COOKIE["auth_token"];

$stmt = $conn->prepare("SELECT * FROM users WHERE auth_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: ../login.php");
    exit();
}

$current_user = $result->fetch_assoc();

// Only allow admin username
if (strtolower($current_user["username"]) !== "admin") {
    header("Location: ../index.php");
    exit();
}
?>
