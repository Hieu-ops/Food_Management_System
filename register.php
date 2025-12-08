<?php
include("connection.php");
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $username = $_POST['username'];
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

  $check = $conn->query("SELECT * FROM user WHERE username='$username'");

  if ($check->num_rows > 0) {
    $message = "Tài khoản đã tồn tại!";
  } else {
    $conn->query("INSERT INTO user (username, password) VALUES ('$username', '$password')");
    $message = "Đăng ký thành công. <a href='login.php'>Đăng nhập</a>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Style chung -->
  <link rel="stylesheet" href="style.css">
</head>

<body class="page-register">

<div class="register-container">

  <div class="register-left">
    <h2>Welcome!</h2>
    <p>Already have an account?</p>
    <a href="login.php" class="register-login-btn">LOGIN</a>
  </div>

  <div class="register-right">
    <h2 class="register-title">Register</h2>

    <?php if (!empty($message)) : ?>
      <div class="register-message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" class="register-form">
      <input type="text" name="username" placeholder="Username" class="register-input" required>
      <input type="password" name="password" placeholder="Password" class="register-input" required>
      <button type="submit" class="register-btn">REGISTER</button>
    </form>
  </div>

</div>

</body>
</html>
