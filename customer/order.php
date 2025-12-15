<?php
include("../check_auth.php");
include("../connection.php");

$message = null;
$message_type = "success";

// Load menu
$foods = [];
$foodStmt = $conn->query("SELECT id, name, price, category, description, image_path FROM food ORDER BY created_at DESC");
if ($foodStmt) {
    while ($row = $foodStmt->fetch_assoc()) {
        $foods[] = $row;
    }
}

// Handle order submit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $qty = $_POST["qty"] ?? [];
    $quantities = [];
    foreach ($qty as $foodId => $q) {
        $q = intval($q);
        if ($q > 0) {
            $quantities[intval($foodId)] = $q;
        }
    }

    if (empty($quantities)) {
        $message = "Vui lòng chọn ít nhất một món.";
        $message_type = "error";
    } else {
        // Fetch selected foods
        $ids = array_keys($quantities);
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $types = str_repeat("i", count($ids));

        $stmt = $conn->prepare("SELECT id, name, price FROM food WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $total = 0;
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $fid = intval($row["id"]);
            $q = $quantities[$fid] ?? 0;
            $lineTotal = $q * (float)$row["price"];
            $total += $lineTotal;
            $items[] = $row["name"] . " x" . $q;
        }

        if ($total <= 0 || empty($items)) {
            $message = "Không hợp lệ, vui lòng chọn món khác.";
            $message_type = "error";
        } else {
            $orderCode = "OD-" . date("YmdHis") . "-" . random_int(100, 999);
            $customerName = $current_user["username"] ?? "Khách";
            $itemsText = implode(", ", $items);

            $insert = $conn->prepare("
                INSERT INTO orders (order_code, customer_name, items, total_amount, status)
                VALUES (?, ?, ?, ?, 'Pending')
            ");
            $insert->bind_param("sssd", $orderCode, $customerName, $itemsText, $total);
            $insert->execute();
            $insert->close();

            $message = "Đã đặt đơn $orderCode thành công!";
            $message_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt món</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="page-order">
    <div class="order-shell">
        <header class="order-header">
            <div>
                <p class="order-kicker">Khách hàng</p>
                <h1>Chọn món & đặt đơn</h1>
                <p class="order-sub">Chọn số lượng món ăn, hệ thống sẽ tự tính tổng và tạo đơn hàng.</p>
            </div>
            <div class="order-actions">
                <a href="../index.php" class="order-btn ghost">Về trang chủ</a>
                <a href="../logout.php" class="order-btn danger">Đăng xuất</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="order-alert <?= $message_type === 'error' ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="order-form" id="orderForm">
            <div class="order-grid">
                <?php foreach ($foods as $food): ?>
                    <div class="order-card">
                        <?php if (!empty($food["image_path"])): ?>
                            <img src="../<?= htmlspecialchars($food["image_path"]) ?>" class="order-img" alt="<?= htmlspecialchars($food["name"]) ?>">
                        <?php else: ?>
                            <div class="order-img placeholder">No image</div>
                        <?php endif; ?>
                        <div class="order-card-body">
                            <div class="order-card-head">
                                <h3><?= htmlspecialchars($food["name"]) ?></h3>
                                <span class="order-price"><?= number_format($food["price"]) ?>đ</span>
                            </div>
                            <p class="order-meta"><?= htmlspecialchars($food["category"] ?? "") ?></p>
                            <p class="order-desc"><?= htmlspecialchars($food["description"] ?? "") ?></p>
                            <label class="order-qty-label">
                                Số lượng
                                <input type="number" name="qty[<?= (int)$food["id"] ?>]" min="0" max="99" value="0" class="order-qty-input">
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-submit-bar">
                <button type="submit" class="order-btn primary">Đặt đơn</button>
            </div>
        </form>
    </div>

</body>
</html>
