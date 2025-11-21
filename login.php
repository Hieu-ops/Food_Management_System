<?php
// Bật báo lỗi để không còn màn trắng khi có lỗi PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("connection.php");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = "Vui lòng nhập đầy đủ tài khoản và mật khẩu.";
    } else {
        // Lấy user theo username
        $stmt = $conn->prepare("SELECT id, username, password FROM user WHERE username = ?");
        if (!$stmt) {
            die("Lỗi prepare: " . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Kiểm tra mật khẩu
            if (password_verify($password, $user['password'])) {

                // Tạo token ngẫu nhiên
                if (function_exists('random_bytes')) {
                    $token = bin2hex(random_bytes(32));
                } elseif (function_exists('openssl_random_pseudo_bytes')) {
                    $token = bin2hex(openssl_random_pseudo_bytes(32));
                } else {
                    // Fallback trong môi trường rất cũ
                    $token = bin2hex(md5(uniqid(mt_rand(), true)));
                }

                // Lưu token vào DB
                $upd = $conn->prepare("UPDATE user SET auth_token = ? WHERE id = ?");
                if (!$upd) {
                    die("Lỗi prepare (update): " . $conn->error);
                }
                $upd->bind_param("si", $token, $user['id']);
                $upd->execute();

                // Lưu token vào cookie (7 ngày)
                setcookie(
                    "auth_token",
                    $token,
                    time() + (7 * 24 * 60 * 60),
                    "/",
                    "",
                    false,
                    true // HttpOnly
                );

                // (tuỳ chọn) vẫn giữ session nếu muốn dùng
                $_SESSION['username'] = $user['username'];

                // Chuyển sang trang chính
                header("Location: index.php");
                exit();
            } else {
                $message = "Sai mật khẩu.";
            }
        } else {
            $message = "Không tìm thấy tài khoản.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- CSS chung -->
  <link rel="stylesheet" href="style.css">
</head>

<body class="page-login">

<div class="login-container">

  <div class="login-left">
    <h2>Welcome!</h2>
    <p>Don't have an account?</p>
    <a href="register.php" class="login-register-btn">REGISTER</a>
  </div>

  <div class="login-right">
    <h2 class="login-title">Login</h2>

    <?php if (!empty($message)) : ?>
      <div class="login-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" class="login-form">
      <input type="text" name="username" placeholder="Username" class="login-input" required>
      <input type="password" name="password" placeholder="Password" class="login-input" required>
      <button type="submit" class="login-btn">LOGIN</button>
    </form>
  </div>

</div>

</body>
</html>
