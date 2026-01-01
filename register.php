<?php
include("connection.php");
$message = "";

function ensure_users_phone_column(mysqli $conn): void {
    try {
        $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'phone' LIMIT 1";
        $res = $conn->query($sql);
        $exists = $res && $res->num_rows > 0;
        if (!$exists) {
            $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(32) NULL");
        }
    } catch (Throwable $e) {
        // best effort; do not block registration
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $rawPassword = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if ($username === '' || $rawPassword === '') {
        $message = "Vui long nhap du thong tin.";
    } else {
        $password = password_hash($rawPassword, PASSWORD_DEFAULT);
        ensure_users_phone_column($conn);

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $checkRes = $check->get_result();

        if ($checkRes && $checkRes->num_rows > 0) {
            $message = "Tai khoan da ton tai!";
        } else {
            $hasPhone = false;
            try {
                $colCheck = $conn->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'phone' LIMIT 1");
                $hasPhone = $colCheck && $colCheck->num_rows > 0;
            } catch (Throwable $e) {
                $hasPhone = false;
            }

            if ($hasPhone) {
                $stmt = $conn->prepare("INSERT INTO users (username, password, phone) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $password, $phone);
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $username, $password);
            }

            $stmt->execute();
            $stmt->close();
            $message = "Dang ky thanh cong. <a href='login.php'>Dang nhap</a>";
        }
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
      <input type="text" name="phone" placeholder="Phone number" class="register-input">
      <input type="password" name="password" placeholder="Password" class="register-input" required>
      <button type="submit" class="register-btn">REGISTER</button>
    </form>
  </div>

</div>

</body>
</html>
