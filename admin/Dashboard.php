
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
