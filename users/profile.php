<?php
include("check_auth.php");
include("connection.php");

function ensure_users_phone_column(mysqli $conn): void {
    try {
        $sql = "
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'users'
              AND column_name = 'phone'
            LIMIT 1
        ";
        $res = $conn->query($sql);
        $exists = $res && $res->num_rows > 0;
        if (!$exists) {
            $conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(32) NULL");
        }
    } catch (Throwable $e) {
        // best effort; ignore if cannot alter schema
    }
}

ensure_users_phone_column($conn);

$message = null;
$message_type = "info";
$phone = $current_user["phone"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST["phone"] ?? "");

    if (strlen($phone) > 32) {
        $message = "So dien thoai qua dai (toi da 32 ky tu).";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $phone, $current_user["id"]);
            $stmt->execute();
            $stmt->close();

            $message = "Cap nhat so dien thoai thanh cong.";
            $message_type = "success";
        } else {
            $message = "Khong the cap nhat luc nay. Thu lai sau.";
            $message_type = "error";
        }

        // refresh user info
        $refresh = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        if ($refresh) {
            $refresh->bind_param("i", $current_user["id"]);
            $refresh->execute();
            $res = $refresh->get_result();
            if ($res && $res->num_rows === 1) {
                $current_user = $res->fetch_assoc();
                $phone = $current_user["phone"] ?? "";
            }
            $refresh->close();
        }
    }
}

$username = $current_user["username"] ?? "User";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Há»“ sÆ¡ cÃ¡ nhÃ¢n</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        margin: 0;
        min-height: 100vh;
        background: radial-gradient(circle at 15% 20%, rgba(16, 185, 129, 0.08), transparent 26%),
                    radial-gradient(circle at 80% 0%, rgba(59, 130, 246, 0.08), transparent 30%),
                    #f5f7fb;
        font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
    }
    .user-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }
    .user-brand { display: flex; align-items: center; gap: 10px; }
    .user-logo {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        background: #10b981;
        color: #fff;
        font-weight: 800;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .user-title { margin: 0; font-size: 28px; font-weight: 800; color: #111827; }
    .user-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .user-btn {
        border: none;
        border-radius: 12px;
        padding: 10px 16px;
        font-weight: 700;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        cursor: pointer;
    }
    .user-btn.primary { background: linear-gradient(135deg, #0ea5e9, #16a34a); color: #fff; }
    .user-btn.ghost { background: #fff; color: #0f172a; border: 1px solid #d1d5db; }
    .profile-shell {
        max-width: 720px;
        margin: 0 auto;
        padding: 32px 20px 48px;
    }
    .user-header { margin-bottom: 18px; }
    .profile-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 18px 50px rgba(15,23,42,0.08);
        padding: 20px;
    }
    .profile-title {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
    }
    .profile-sub {
        color: #6b7280;
        margin-bottom: 16px;
    }
    .profile-label {
        font-weight: 700;
        margin-bottom: 6px;
        color: #374151;
    }
    .profile-input {
        width: 100%;
        border-radius: 12px;
        border: 1px solid #d1d5db;
        padding: 10px 12px;
    }
    .profile-btn {
        border: none;
        border-radius: 12px;
        padding: 12px 16px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #0ea5e9, #16a34a);
        box-shadow: 0 12px 26px rgba(15,23,42,0.14);
    }
    .profile-alert.success { background: #ecfdf3; border: 1px solid #c8f2df; color: #166534; }
    .profile-alert.error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .profile-alert {
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 14px;
        font-weight: 600;
    }
    .user-logo { width: 48px; height: 48px; }
    .user-title { font-size: 28px; }
    </style>
</head>
<body>
<div class="profile-shell">
    <div class="user-header">
        <div class="user-brand">
            <span class="user-logo">FM</span>
            <div>
                <p class="mb-0 text-uppercase text-muted" style="font-size:12px;letter-spacing:0.5px;">Food Management</p>
                <h2 class="user-title">Profile</h2>
            </div>
        </div>
        <div class="user-actions">
            <a href="index.php" class="user-btn ghost text-decoration-none">Food List</a>
            <a href="my_orders.php" class="user-btn ghost text-decoration-none">My Orders</a>
            <a href="users/order.php" class="user-btn primary text-decoration-none text-white">Add Cart</a>
            <a href="logout.php" class="user-btn ghost text-decoration-none">Logout</a>
        </div>
    </div>

    <div class="profile-card">
        <h3 class="profile-title mb-1">Xin chao, <?= htmlspecialchars($username) ?></h3>
        <p class="profile-sub">Xem thong tin tai khoan va cap nhat so dien thoai cua ban.</p>

        <?php if ($message): ?>
            <div class="profile-alert <?= $message_type === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="d-flex flex-column gap-3">
            <div>
                <label class="profile-label" for="username">Ten dang nhap</label>
                <input type="text" id="username" class="profile-input bg-light" value="<?= htmlspecialchars($username) ?>" readonly>
            </div>
            <div>
                <label class="profile-label" for="phone">So dien thoai</label>
                <input type="text" id="phone" name="phone" class="profile-input" maxlength="32" value="<?= htmlspecialchars($phone) ?>" placeholder="Nhap so dien thoai">
            </div>
            <div>
                <button type="submit" class="profile-btn">Luu thay doi</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
