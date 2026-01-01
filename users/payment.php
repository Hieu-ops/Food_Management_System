<?php
include("check_user.php");
include("../connection.php");

$message = null;
$message_type = "success";
$message_hint = null;
$order = null;
$orderId = null;
$items = [];
$paymentDetails = null;

$paymentsReady = ensure_payments_table($conn);
if (!$paymentsReady) {
    $message_hint = "Unable to create the payments table automatically; please create it manually before confirming.";
}

$order = fetch_active_order($conn, $current_user["id"] ?? 0);
if ($order) {
    $orderId = (int)$order["id"];
    $items = fetch_order_items($conn, $orderId);
    if ($paymentsReady) {
        $paymentDetails = fetch_payment_summary($conn, $orderId);
    }
}

$serviceMethods = ["Cash", "Banking"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedId = intval($_POST["order_id"] ?? 0);
    if (!$orderId || ($postedId && $postedId !== $orderId)) {
        $message = "Đơn hàng không hợp lệ hoặc đã được xử lý.";
        $message_type = "error";
    } elseif (!$paymentsReady) {
        $message_hint = "Không thể hoàn tất vì bảng payments chưa được tạo.";
        $message_type = "error";
    } else {
        $method = trim($_POST["method"] ?? "Cash");
        if (!in_array($method, $serviceMethods, true)) {
            $method = "Cash";
        }
        $amount = (float)($order["total_amount"] ?? 0);
        $pCheck = $conn->prepare("SELECT id FROM payments WHERE order_id=? LIMIT 1");
        $pCheck->bind_param("i", $orderId);
        $pCheck->execute();
        $pCheck->store_result();
        if ($pCheck->num_rows > 0) {
            $pUpd = $conn->prepare("UPDATE payments SET amount=?, method=?, status='Paid', paid_at=NOW() WHERE order_id=?");
            $pUpd->bind_param("dsi", $amount, $method, $orderId);
            $pUpd->execute();
            $pUpd->close();
        } else {
            $pIns = $conn->prepare("INSERT INTO payments (order_id, amount, method, status, paid_at) VALUES (?, ?, ?, 'Paid', NOW())");
            $pIns->bind_param("ids", $orderId, $amount, $method);
            $pIns->execute();
            $pIns->close();
        }
        $pCheck->close();
        // Mark order completed after payment
        $updOrder = $conn->prepare("UPDATE orders SET status='Completed' WHERE id=?");
        if ($updOrder) {
            $updOrder->bind_param("i", $orderId);
            $updOrder->execute();
            $updOrder->close();
        }
        $paymentDetails = fetch_payment_summary($conn, $orderId);
        $message = "Thanh toán đơn hàng thành công.";
        $message_type = "success";
    }
}

$selectedMethod = $paymentDetails["method"] ?? ($_GET["method"] ?? "Cash");

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="page-order">
    <div class="order-shell">
        <header class="order-header">
            <div>
                <p class="order-kicker">Thanh toán</p>
                <h1>Thông tin đơn hàng</h1>
                <p class="order-sub">Kiểm tra món, số lượng và xác nhận thanh toán.</p>
            </div>
            <div class="order-actions">
                <a href="order.php" class="order-btn ghost">Đặt món</a>
                <a href="../logout.php" class="order-btn danger">Đăng xuất</a>
            </div>
        </header>

<?php if ($message): ?>
            <?php if ($paymentDetails): ?>
                <?php $paidAtLabel = !empty($paymentDetails["paid_at"]) ? date("H:i d/m/Y", strtotime($paymentDetails["paid_at"])) : ""; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($message_hint): ?>
            <div class="order-alert error"><?= htmlspecialchars($message_hint) ?></div>
        <?php endif; ?>

        <?php if (!$order): ?>
            <div class="order-alert error">Chưa có đơn hàng đang chờ thanh toán.</div>
        <?php else: ?>
            <div class="order-card" style="margin-bottom:12px;">
                <div class="order-card-body">
                    <div class="order-card-head">
                        <h3>Đơn: <?= htmlspecialchars($order["order_code"] ?? ("OD-" . $orderId)) ?></h3>
                        <span class="order-price"><?= number_format($order["total_amount"] ?? 0) ?>đ</span>
                    </div>
                    <p class="order-meta">Trạng thái: <?= htmlspecialchars($order["status"] ?? "") ?></p>
                </div>
            </div>

            <div class="index-card">
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
                                <tr><td colspan="4" class="dash-empty">Chưa có món nào trong đơn.</td></tr>
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
            </div>

            <form method="post" class="order-submit-bar" style="margin-top:12px;">
                <input type="hidden" name="order_id" value="<?= (int)$orderId ?>">
                <select name="method" class="order-qty-input" style="width:160px;">
                    <?php foreach ($serviceMethods as $methodOption): ?>
                        <option value="<?= $methodOption ?>" <?= $methodOption === $selectedMethod ? "selected" : "" ?>>
                            <?= $methodOption ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="order-btn primary">Confirm</button>
            </form>
        <?php endif; ?>

        <?php if ($message && $paymentDetails): ?>
            <?php $paidAtLabel = !empty($paymentDetails["paid_at"]) ? date("H:i d/m/Y", strtotime($paymentDetails["paid_at"])) : ""; ?>
            <div class="order-card" style="margin-top:12px; max-width:560px; margin-left:auto; margin-right:auto;">
                <div class="order-card-body">
                    <div class="order-card-head" style="margin-bottom:8px;">
                        <h3>Thanh toán hoàn tất</h3>
                    </div>
                    <p class="order-meta"><?= htmlspecialchars($message) ?></p>
                    <div class="payment-success-grid">
                        <div><span class="payment-success-label">Đơn hàng</span><br><strong><?= htmlspecialchars($order["order_code"] ?? ("OD-" . $orderId)) ?></strong></div>
                        <div><span class="payment-success-label">Tổng tiền</span><br><strong><?= number_format($order["total_amount"] ?? 0) ?>đ</strong></div>
                        <div><span class="payment-success-label">Phương thức</span><br><strong><?= htmlspecialchars($paymentDetails["method"] ?? "N/A") ?></strong></div>
                        <div><span class="payment-success-label">Trạng thái</span><br><strong><?= htmlspecialchars($paymentDetails["status"] ?? "Paid") ?></strong></div>
                        <div><span class="payment-success-label">Thời gian</span><br><strong><?= htmlspecialchars($paidAtLabel ?: "Vừa xong") ?></strong></div>
                    </div>
                    <div class="payment-success-actions" style="margin-top:10px;">
                        <a href="../index.php" class="order-btn primary">Tiếp tục đặt món</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
function table_exists(mysqli $conn, string $table): bool {
    $stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows === 1;
    $stmt->close();
    return $exists;
}

function ensure_payments_table(mysqli $conn): bool {
    if (table_exists($conn, "payments")) {
        return true;
    }

    $sql = "
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            method VARCHAR(64) NOT NULL,
            status VARCHAR(32) NOT NULL,
            paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_order (order_id)
        )
    ";

    return $conn->query($sql);
}

function fetch_active_order(mysqli $conn, int $userId): ?array {
    $stmt = $conn->prepare("
        SELECT *
        FROM orders
        WHERE user_id = ?
          AND status IN ('Pending', 'Processing')
        ORDER BY created_at DESC
        LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
    return $order;
}

function fetch_order_items(mysqli $conn, int $orderId): array {
    $items = [];
    $stmt = $conn->prepare("
        SELECT oi.quantity, oi.unit_price, oi.line_total, f.name
        FROM order_items oi
        LEFT JOIN food f ON f.id = oi.food_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    if (!$stmt) {
        return $items;
    }
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $stmt->close();
    return $items;
}

function fetch_payment_summary(mysqli $conn, int $orderId): ?array {
    $stmt = $conn->prepare("SELECT order_id, amount, method, status, paid_at FROM payments WHERE order_id=? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = ($res && $res->num_rows === 1) ? $res->fetch_assoc() : null;
    $stmt->close();
    return $data;
}
