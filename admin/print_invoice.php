<?php
include("check_admin.php");
include("../connection.php");

$orderId = intval($_GET["order_id"] ?? 0);
$order = null;
$items = [];
$payment = null;

if ($orderId > 0) {
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS customer_name, u.phone, u.email
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        WHERE o.id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $order = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

if ($order) {
    $itemStmt = $conn->prepare("
        SELECT oi.quantity, oi.unit_price, oi.line_total, f.name
        FROM order_items oi
        LEFT JOIN food f ON f.id = oi.food_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    if ($itemStmt) {
        $itemStmt->bind_param("i", $orderId);
        $itemStmt->execute();
        $res = $itemStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
        $itemStmt->close();
    }
    $payStmt = $conn->prepare("SELECT method, amount, status, paid_at FROM payments WHERE order_id=? LIMIT 1");
    if ($payStmt) {
        $payStmt->bind_param("i", $orderId);
        $payStmt->execute();
        $payRes = $payStmt->get_result();
        if ($payRes && $payRes->num_rows === 1) {
            $payment = $payRes->fetch_assoc();
        }
        $payStmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoá đơn <?= htmlspecialchars($order["order_code"] ?? "N/A") ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="page-admin page-dashboard invoice-page">
    <main class="invoice-shell">
        <?php if (!$order): ?>
            <div class="invoice-card">
                <p class="invoice-empty">Đơn hàng không tìm thấy.</p>
            </div>
        <?php else: ?>
            <div class="invoice-card invoice-summary">
                <div>
                    <p class="invoice-kicker">Hoá đơn</p>
                    <h1><?= htmlspecialchars($order["order_code"]) ?></h1>
                    <p class="invoice-meta"><?= htmlspecialchars($order["customer_name"] ?? "Khách lạ") ?></p>
                    <p class="invoice-meta-sub">
                        <?= htmlspecialchars($order["phone"] ?? "") ?>
                        <?php if (!empty($order["email"])): ?>
                            • <?= htmlspecialchars($order["email"]) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <button type="button" class="invoice-print-btn" onclick="window.print();">In hoá đơn</button>
            </div>

            <section class="invoice-info-grid">
                <div>
                    <span class="invoice-label">Trạng thái đơn</span>
                    <strong><?= htmlspecialchars($order["status"] ?? "") ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Thanh toán</span>
                    <strong><?= htmlspecialchars($payment["status"] ?? (empty($order["status"]) ? "Pending" : $order["status"])) ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Loại đơn</span>
                    <strong><?= htmlspecialchars($order["order_type"] ?? "Pickup") ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Phương thức</span>
                    <strong><?= htmlspecialchars($payment["method"] ?? "N/A") ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Địa chỉ</span>
                    <strong><?= htmlspecialchars($order["address"] ?? "-") ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Ngày tạo</span>
                    <strong><?= htmlspecialchars($order["created_at"] ?? "") ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Thanh toán lúc</span>
                    <strong><?= htmlspecialchars(!empty($payment["paid_at"]) ? date("H:i d/m/Y", strtotime($payment["paid_at"])) : "Chưa có") ?></strong>
                </div>
                <div>
                    <span class="invoice-label">Số tiền</span>
                    <strong><?= number_format($payment["amount"] ?? ($order["total_amount"] ?? 0)) ?>đ</strong>
                </div>
            </section>

            <div class="index-card invoice-items">
                <div class="index-table-wrapper">
                    <table class="index-table">
                        <thead>
                            <tr>
                                <th>Món ăn</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="4" class="dash-empty">Không có món nào trong đơn.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($it["name"] ?? "") ?></td>
                                        <td><?= number_format($it["unit_price"] ?? 0) ?>đ</td>
                                        <td><?= (int)($it["quantity"] ?? 0) ?></td>
                                        <td><?= number_format($it["line_total"] ?? 0) ?>đ</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="invoice-total">
                    <span>Tổng đơn</span>
                    <strong><?= number_format($order["total_amount"] ?? 0) ?>đ</strong>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
