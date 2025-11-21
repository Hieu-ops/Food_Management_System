<?php
include("connection.php");

if (!isset($_COOKIE["auth_token"])) {
    header("Location: login.php");
    exit();
}

$token = $_COOKIE["auth_token"];

$stmt = $conn->prepare("SELECT * FROM user WHERE auth_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: login.php");
    exit();
}

$current_user = $result->fetch_assoc();
?>
