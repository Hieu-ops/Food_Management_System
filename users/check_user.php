<?php
session_start();
include("../connection.php");
require_once("../auth_cookie.php");

// Require user auth; restore from cookie if the session expired.
$current_user = ensure_authenticated($conn, "user", "../login.php");
