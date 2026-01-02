<?php
include("connection.php");

$redirect = function () {
    header("Location: login.php");
    exit();
};

if (!isset($_COOKIE["auth_token"])) {
    $redirect();
}

$token = $_COOKIE["auth_token"];

$stmt = $conn->prepare("SELECT * FROM users WHERE auth_token = ? LIMIT 1");
if (!$stmt) {
    $redirect();
}

$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $redirect();
}

$current_user = $result->fetch_assoc();
$stmt->close();
?>
