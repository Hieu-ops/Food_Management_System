<?php
include("check_admin.php");
include("../connection.php");

$statusOptions = ["Pending", "Processing", "Completed", "Cancelled"];
$message = null;
$ordersError = null;
$paymentsHint = null;

function table_exists(mysqli $conn, string $table): bool {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows === 1;
    $stmt->close();
    return $exists;
}

function status_class($status) {
    $map = [
        "Pending" => "dash-badge warn",
        "Processing" => "dash-badge info",
        "Completed" => "dash-badge success",
        "Cancelled" => "dash-badge danger",
    ];
    return $map[$status] ?? "dash-badge";
}

// Update order status and log
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_id"])) {
    if (!table_exists($conn, "orders")) {
        $ordersError = "B?ng orders kh„ng t?n t?i. Vui l•ng t?o c…u trúc database tr“c khi c?p nh?t.";
    }

    $orderId = intval($_POST["order_id"]);
    $newStatus = in_array($_POST["status"] ?? "", $statusOptions) ? $_POST["status"] : null;

    if ($newStatus && !$ordersError) {
        // Get old status
        $oldStmt = $conn->prepare("SELECT status FROM orders WHERE id=?");
        $oldStmt->bind_param("i", $orderId);
        $oldStmt->execute();
        $oldRes = $oldStmt->get_result();
        $oldStatus = $oldRes->fetch_assoc()["status"] ?? "";
        $oldStmt->close();

        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();
        $stmt->close();

        // log status change
        $log = $conn->prepare("INSERT INTO order_status_logs (order_id, old_status, new_status, changed_at) VALUES (?, ?, ?, NOW())");
        $log->bind_param("iss", $orderId, $oldStatus, $newStatus);
        $log->execute();
        $log->close();

        // admin action
        $act = $conn->prepare("INSERT INTO admin_actions (admin_id, order_id, action, action_detail, created_at) VALUES (?, ?, 'update_status', ?, NOW())");
        $detail = "Set status to $newStatus";
        $act->bind_param("iis", $current_admin["id"], $orderId, $detail);
        $act->execute();
        $act->close();

        $message = "Đã cập nhật trạng thái đơn #$orderId.";
    }
}

// Fetch orders with user & payment summary
$statusMapping = [
    "Pending" => "pending",
    "Processing" => "processing",
    "Completed" => "completed",
    "Cancelled" => "cancelled",
];
$orders = [];
$paymentsExists = table_exists($conn, "payments");
if (!$paymentsExists) {
    $paymentsHint = "Payments table has not been created yet; update the schema before showing payment details.";
}
$columns = "
    o.id, o.order_code, o.total_amount, o.status, o.order_type, o.address, o.created_at,
    u.name AS customer_name, u.phone, u.email,
" . ($paymentsExists
        ? "p.status AS payment_status, p.method, p.amount AS paid_amount"
        : "NULL AS payment_status, NULL AS method, NULL AS paid_amount"
    );
$joins = "FROM orders o
    LEFT JOIN users u ON u.id = o.user_id" .
    ($paymentsExists ? " LEFT JOIN payments p ON p.order_id = o.id" : "");

$sql = "
    SELECT $columns
    $joins
    ORDER BY o.created_at DESC
    LIMIT 100
";

if (!table_exists($conn, "orders")) {
    $ordersError = "B?ng orders kh,ng t?n t?i. Vui l?ng t?o database tr?c khi xem dashboard.";
} else {
    try {
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) $orders[] = $row;
        }
    } catch (mysqli_sql_exception $e) {
        $ordersError = "Kh?ng th? t?i don h?ng: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="page-admin page-dashboard">
    <aside class="dash-nav">
        <div class="dash-brand">
            <span class="dash-logo">FM</span>
            <div>
                <p class="dash-kicker">Admin</p>
                <strong>Food Manager</strong>
            </div>
        </div>
        <div class="dash-links">
            <a class="active" href="#">Orders</a>
            <a href="menu.php">Menu</a>
            <a href="customers.php">Customers</a>
            <a href="reports.php">Reports</a>
        </div>
        <a class="dash-logout" href="../logout.php">Logout</a>
    </aside>

    <main class="dash-main">
        <header class="dash-header">
            <div>
                <p class="dash-kicker">Dashboard</p>
                <h1>Quản lý đơn hàng</h1>
                <p class="dash-sub">Cập nhật trạng thái, xem thông tin khách và thanh toán.</p>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="dash-alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($ordersError): ?>
            <div class="dash-alert error"><?= htmlspecialchars($ordersError) ?></div>
        <?php endif; ?>
        <?php if ($paymentsHint): ?>
            <div class="dash-alert info"><?= htmlspecialchars($paymentsHint) ?></div>
        <?php endif; ?>

        <section class="dash-table-card">
            <div class="dash-table-head">
                <div>
                    <p class="dash-kicker">Orders</p>
                    <h3>Đơn hàng mới nhất</h3>
                </div>
            </div>
            <div class="dash-table-wrapper">
                <table class="dash-table">
                    <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách</th>
                                <th>Liên hệ</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Loại/Địa chỉ</th>
                                <th>Trạng thái</th>
                                <th>Thời gian</th>
                                <th>Thao tác</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="9" class="dash-empty">Chưa có đơn hàng.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order["order_code"] ?? ("OD-" . $order["id"])) ?></td>
                                    <td><?= htmlspecialchars($order["customer_name"] ?? "Khách lẻ") ?></td>
                                    <td>
                                        <?= htmlspecialchars($order["phone"] ?? "") ?><br>
                                        <?= htmlspecialchars($order["email"] ?? "") ?>
                                    </td>
                                    <td><?= number_format($order["total_amount"] ?? 0) ?>đ</td>
                                    <td>
                                        <?= htmlspecialchars($order["payment_status"] ?? "N/A") ?><br>
                                        <small><?= htmlspecialchars($order["method"] ?? "") ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($order["order_type"] ?? "") ?><br>
                                        <small><?= htmlspecialchars($order["address"] ?? "") ?></small>
                                    </td>
                                    <td><span class="<?= status_class($order["status"] ?? "") ?>"><?= htmlspecialchars($order["status"] ?? "") ?></span></td>
                                    <td><?= htmlspecialchars($order["created_at"] ?? "") ?></td>
                                    <td class="dash-actions">
                                        <form method="post" class="dash-inline-form">
                                            <input type="hidden" name="order_id" value="<?= (int)$order["id"] ?>">
                                            <?php $statusKey = $statusMapping[$order["status"] ?? "Pending"] ?? "pending"; ?>
                                            <select name="status" class="dash-chip outline status-select <?= $statusKey ?>">
                                                <?php foreach ($statusOptions as $opt): ?>
                                                    <option value="<?= $opt ?>" <?= ($order["status"] === $opt ? "selected" : "") ?>>
                                                        <?= $opt ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="dash-chip success" type="submit">Cập nhật</button>
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
