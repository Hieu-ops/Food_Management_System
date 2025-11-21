<?php
include("connection.php");

if (isset($_COOKIE["auth_token"])) {
    $token = $_COOKIE["auth_token"];

    $stmt = $conn->prepare("UPDATE user SET auth_token = NULL WHERE auth_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();

    setcookie("auth_token", "", time() - 3600, "/");
}

header("Location: login.php");
exit();
?>
