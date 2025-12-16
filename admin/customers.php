<?php
include("check_admin.php");
include("../connection.php");

$message = null;
$message_type = "success";

$users = [];
// Một số DB chỉ có cột username, không có name/phone/email
$userRes = $conn->query("SELECT id, username, created_at FROM users ORDER BY created_at DESC");
if ($userRes) {
    while ($row = $userRes->fetch_assoc()) {
        $users[] = [
            "id" => $row["id"],
            "name" => $row["username"] ?? "",
            "phone" => "",
            "email" => "",
            "created_at" => $row["created_at"] ?? "",
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="page-dashboard">
    <aside class="dash-nav">
        <div class="dash-brand">
            <span class="dash-logo">FM</span>
            <div>
                <p class="dash-kicker">Admin</p>
                <strong>Food Manager</strong>
            </div>
        </div>
        <div class="dash-links">
            <a href="Dashboard.php">Orders</a>
            <a href="menu.php">Menu</a>
            <a class="active" href="#">Customers</a>
            <a href="reports.php">Reports</a>
        </div>
        <a class="dash-logout" href="../logout.php">Logout</a>
    </aside>

    <main class="dash-main">
        <header class="dash-header">
            <div>
                <p class="dash-kicker">Dashboard</p>
                <h1>Người dùng</h1>
                <p class="dash-sub">Danh sách khách hàng, thông tin liên hệ và xóa tài khoản.</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="dash-alert <?= $message_type === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <section class="dash-table-card">
            <div class="dash-table-head">
                <div>
                    <p class="dash-kicker">Customers</p>
                    <h3>Danh sách tài khoản</h3>
                </div>
            </div>

            <div class="dash-table-wrapper">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên</th>
                            <th>Liên hệ</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="dash-empty">Chưa có người dùng.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= (int)$u["id"] ?></td>
                                    <td><?= htmlspecialchars($u["name"] ?? "") ?></td>
                                    <td>
                                        <?= htmlspecialchars($u["phone"] ?? "") ?><br>
                                        <?= htmlspecialchars($u["email"] ?? "") ?>
                                    </td>
                                    <td><?= htmlspecialchars($u["created_at"] ?? "") ?></td>
                                    <td class="dash-actions">
                                        <form method="post" class="dash-inline-form" onsubmit="return confirm('Xóa người dùng này và dữ liệu liên quan?');">
                                            <input type="hidden" name="delete_user" value="<?= (int)$u["id"] ?>">
                                            <button type="submit" class="dash-chip danger">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
