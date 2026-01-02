<?php
declare(strict_types=1);

require_once __DIR__ . "/check_admin.php";
require_once __DIR__ . "/../connection.php";

$message = null;
$message_type = "success";

function fail(string $msg): void {
    global $message, $message_type;
    $message = $msg;
    $message_type = "error";
}

/**
 * Delete user + related records (optimized):
 * - Use transaction for consistency
 * - Use set-based DELETE with JOIN/IN instead of loop queries
 * - Use prepared statements
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {
    $userId = filter_input(INPUT_POST, "delete_user", FILTER_VALIDATE_INT);

    if (!$userId || $userId <= 0) {
        fail("User ID không hợp lệ.");
    } else {
        $conn->begin_transaction();
        try {
            // order_status_logs
            $stmt = $conn->prepare("
                DELETE osl
                FROM order_status_logs osl
                INNER JOIN orders o ON o.id = osl.order_id
                WHERE o.user_id = ?
            ");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            // order_items
            $stmt = $conn->prepare("
                DELETE oi
                FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
                WHERE o.user_id = ?
            ");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            // payments
            $stmt = $conn->prepare("
                DELETE p
                FROM payments p
                INNER JOIN orders o ON o.id = p.order_id
                WHERE o.user_id = ?
            ");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            // admin_actions
            $stmt = $conn->prepare("
                DELETE aa
                FROM admin_actions aa
                INNER JOIN orders o ON o.id = aa.order_id
                WHERE o.user_id = ?
            ");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            // orders
            $stmt = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();

            // users
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if (!$stmt) throw new Exception("Prepare failed");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            $conn->commit();

            if ($affected > 0) {
                $message = "Đã xóa người dùng #{$userId} và dữ liệu liên quan.";
            } else {
                $message = "Không tìm thấy người dùng #{$userId}.";
            }
        } catch (Throwable $e) {
            $conn->rollback();
            fail("Xóa thất bại, vui lòng thử lại.");
        }
    }
}

$users = [];
$stmt = $conn->prepare("SELECT id, username, created_at FROM users ORDER BY created_at DESC");
if ($stmt && $stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $users[] = [
            "id" => (int)($row["id"] ?? 0),
            "name" => (string)($row["username"] ?? ""),
            "phone" => "",
            "email" => "",
            "created_at" => (string)($row["created_at"] ?? ""),
        ];
    }
    $stmt->close();
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
