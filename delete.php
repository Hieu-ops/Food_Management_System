<?php
include("validation.php");
include("connection.php");

$id = $_GET['id'] ?? null;
if ($id) {
  $conn->query("DELETE FROM food WHERE id=$id");
}
header("Location: index.php");
exit();
