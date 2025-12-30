<?php
include("connection.php");
$message = "";
$role = "user";
$selectedRole = $_POST['role'] ?? 'user';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$role = $selectedRole === 'admin' ? 'admin' : 'user';

  if ($role === 'admin') {
    $username = trim($_POST['username'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';

    if ($username === '' || $passwordRaw === '') {
      $message = "Vui lòng nhập username và mật khẩu cho admin.";
      goto render;
    }

    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
      $message = "Username admin đã tồn tại.";
    } else {
      $hash = password_hash($passwordRaw, PASSWORD_DEFAULT);
      $ins = $conn->prepare("INSERT INTO admin (username, password, role, created_at) VALUES (?, ?, 'admin', NOW())");
      $ins->bind_param("ss", $username, $hash);
      $ins->execute();
      $ins->close();
      $message = "Tạo tài khoản admin thành công. <a href='login.php'>Đăng nhập</a>";
    }
    $stmt->close();

  } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $passwordRaw === '') {
      $message = "Vui lòng nhập đầy đủ họ tên, email và mật khẩu.";
    } else {
      // Check duplicate email
      $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows > 0) {
        $message = "Email đã tồn tại.";
      } else {
        // Nếu phone rỗng thì lưu NULL để tránh trùng key rỗng
        if ($phone === '') {
          $phone = null;
        } else {
          // Check duplicate phone nếu có nhập
          $pstmt = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
          $pstmt->bind_param("s", $phone);
          $pstmt->execute();
          $pstmt->store_result();
          if ($pstmt->num_rows > 0) {
            $message = "Số điện thoại đã tồn tại.";
            $pstmt->close();
            $stmt->close();
            goto render;
          }
          $pstmt->close();
        }

        $hash = password_hash($passwordRaw, PASSWORD_DEFAULT);
        $ins = $conn->prepare("INSERT INTO users (name, phone, email, password, created_at) VALUES (?, ?, ?, ?, NOW())");
        $ins->bind_param("ssss", $name, $phone, $email, $hash);
        $ins->execute();
        $message = "Đăng ký thành công. <a href='login.php'>Đăng nhập</a>";
        $ins->close();
      }
      $stmt->close();
    }
  }
}
render:
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Register</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
      <div class="mb-2 d-flex gap-2">
        <label><input type="radio" name="role" value="user" <?= $role === 'user' ? 'checked' : '' ?>> User</label>
        <label><input type="radio" name="role" value="admin" <?= $role === 'admin' ? 'checked' : '' ?>> Admin</label>
      </div>

      <div id="user-fields" style="<?= $role === 'user' ? '' : 'display:none;' ?>">
        <input type="text" name="name" placeholder="Full name" class="register-input" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email" class="register-input" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <input type="text" name="phone" placeholder="Phone (optional)" class="register-input" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>

      <div id="admin-fields" style="<?= $role === 'admin' ? '' : 'display:none;' ?>">
        <input type="text" name="username" placeholder="Admin username" class="register-input" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>

      <input type="password" name="password" placeholder="Password" class="register-input" required>
      <button type="submit" class="register-btn">REGISTER</button>
    </form>
  </div>

</div>

<script>
const roleRadios = document.querySelectorAll('input[name="role"]');
const userFields = document.getElementById('user-fields');
const adminFields = document.getElementById('admin-fields');
roleRadios.forEach(r => r.addEventListener('change', () => {
  if (r.value === 'admin' && r.checked) {
    userFields.style.display = 'none';
    adminFields.style.display = '';
  } else if (r.value === 'user' && r.checked) {
    userFields.style.display = '';
    adminFields.style.display = 'none';
  }
}));
</script>
</body>
</html>
