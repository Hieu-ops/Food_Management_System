<?php
include("check_auth.php");
include("connection.php");

$orders = [];
$stmt = $conn->prepare("
    SELECT 
        o.id,
        o.order_code,
        o.total_amount,
        o.status,
        o.order_type,
        o.address,
        o.created_at,
        GROUP_CONCAT(CONCAT(f.name, ' x', oi.quantity) SEPARATOR ', ') AS items
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    LEFT JOIN food f ON f.id = oi.food_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

if ($stmt) {
    $stmt->bind_param("i", $current_user["id"]);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
    .orders-shell { max-width:1100px; margin:32px auto 48px; padding:0 16px; }
    .orders-card {
        background:#fff;
        border-radius:18px;
        box-shadow:0 16px 45px rgba(15,23,42,0.08);
        padding:16px;
    }
    .orders-table { width:100%; border-collapse:collapse; }
    .orders-table th {
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.4px;
        color: #6b7280;
        font-weight: 700;
        border-top: none;
        border-bottom: 1px solid #eef2f7;
    }
    .orders-table td {
        border-top: 1px solid #eef2f7;
        border-bottom: none;
        vertical-align: middle;
        height: 56px;
    }
    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }
    .badge-pending { background: #fef3c7; color: #92400e; }
    .badge-completed { background: #dcfce7; color: #166534; }
    </style>
</head>
<body class="page-index">
    <div class="orders-shell">
        <div class="index-header">
            <div class="index-title">
                <span class="index-title-badge">FM</span>
                <div>
                    <p class="index-kicker">Food Management</p>
                    <h1 style="margin:4px 0 0; font-size:32px; font-weight:800; color:#111827;">My Orders</h1>
                </div>
            </div>
            <div class="index-actions" style="gap:10px; flex-wrap:wrap;">
                <a href="index.php" class="index-btn index-btn-ghost">Food List</a>
                <a href="users/order.php" class="index-btn index-btn-primary">Add Cart</a>
                <a href="logout.php" class="index-btn index-btn-ghost">Logout</a>
            </div>
        </div>

        <div class="orders-card">
            <div class="table-responsive">
                <table class="table orders-table align-middle mb-0" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Món</th>
                            <th>Tổng</th>
                            <th>Trạng thái</th>
                            <th>Loại/Địa chỉ</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Chưa có đơn nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><?= htmlspecialchars($o["order_code"] ?? ("OD-" . $o["id"])) ?></td>
                                    <td><?= htmlspecialchars($o["items"] ?? "") ?></td>
                                    <td><?= number_format($o["total_amount"] ?? 0) ?>đ</td>
                                    <td>
                                        <?php
                                            $st = strtolower($o["status"] ?? "");
                                            $cls = $st === "completed" ? "badge-completed" : "badge-pending";
                                        ?>
                                        <span class="badge-status <?= $cls ?>"><?= htmlspecialchars($o["status"] ?? "") ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($o["order_type"] ?? "") ?><br>
                                        <small><?= htmlspecialchars($o["address"] ?? "") ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($o["created_at"] ?? "") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
