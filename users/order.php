<?php
include("check_user.php");
include("../connection.php");

$message = null;
$message_type = "success";

// Load available food
$foods = [];
$foodStmt = $conn->query("SELECT id, name, price, category, description, image_path FROM food WHERE is_available = 1 ORDER BY created_at DESC");
if ($foodStmt) {
    while ($row = $foodStmt->fetch_assoc()) $foods[] = $row;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $qty = $_POST["qty"] ?? [];
    $address = trim($_POST["address"] ?? "");
    $orderType = $_POST["order_type"] ?? "Pickup";

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
            $unit = (float)$row["price"];
            $line = $unit * $q;
            $total += $line;
            $items[] = [
                "food_id" => $fid,
                "name" => $row["name"],
                "qty" => $q,
                "unit_price" => $unit,
                "line_total" => $line
            ];
        }
        $stmt->close();

        if ($total <= 0 || empty($items)) {
            $message = "Không hợp lệ, vui lòng chọn món khác.";
            $message_type = "error";
        } else {
            $orderCode = "OD-" . date("YmdHis") . "-" . random_int(100, 999);
            $insert = $conn->prepare("
                INSERT INTO orders (user_id, order_code, total_amount, status, order_type, address, created_at)
                VALUES (?, ?, ?, 'Pending', ?, ?, NOW())
            ");
            $insert->bind_param("isiss", $current_user["id"], $orderCode, $total, $orderType, $address);
            $insert->execute();
            $orderId = $insert->insert_id;
            $insert->close();

            $itemStmt = $conn->prepare("
                INSERT INTO order_items (order_id, food_id, quantity, unit_price, line_total)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items as $it) {
                $itemStmt->bind_param("iiidd", $orderId, $it["food_id"], $it["qty"], $it["unit_price"], $it["line_total"]);
                $itemStmt->execute();
            }
            $itemStmt->close();

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
                <p class="order-sub">Chọn số lượng món, chọn loại đơn và địa chỉ giao.</p>
            </div>
            <div class="order-actions">
                <a href="../index.php" class="order-btn ghost">Trang chủ</a>
                <a href="payment.php" class="order-btn ghost">Payment</a>
                <a href="../logout.php" class="order-btn danger">Đăng xuất</a>
            </div>
        </header>

        <?php if ($message): ?>
            <div class="order-alert <?= $message_type === 'error' ? 'error' : 'success' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="order-form">
            <div class="order-grid">
                <?php foreach ($foods as $food): ?>
                    <div class="order-card order-card-simple">
                        <div class="order-card-body">
                            <div class="order-card-head">
                                <h3><?= htmlspecialchars($food["name"]) ?></h3>
                            </div>
                            <label class="order-qty-label">
                                Số lượng
                                <input type="number" name="qty[<?= (int)$food["id"] ?>]" min="0" max="99" value="0" class="order-qty-input" data-price="<?= htmlspecialchars($food["price"]) ?>">
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-extra">
                <label class="order-qty-label">
                    Loại đơn
                    <select name="order_type" class="order-qty-input" style="width:140px;">
                        <option value="Pickup">Pickup</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Dine-in">Dine-in</option>
                    </select>
                </label>
                <label class="order-qty-label" style="width:100%;">
                    Địa chỉ (nếu giao)
                    <input type="text" name="address" class="order-qty-input" style="width:100%;" placeholder="Số nhà, đường, quận...">
                </label>
            </div>

            <div class="order-submit-bar">
                <div class="order-total">Tổng: <span id="orderTotal">0</span>đ</div>
                <button type="submit" class="order-btn primary">Đặt đơn</button>
            </div>
        </form>
    </div>
<script>
const qtyInputs = document.querySelectorAll('.order-qty-input');
const totalEl = document.getElementById('orderTotal');
const formatNumber = (n) => n.toLocaleString('vi-VN');

function calcTotal() {
    let total = 0;
    qtyInputs.forEach(inp => {
        const price = parseFloat(inp.dataset.price || "0");
        const qty = parseInt(inp.value || "0", 10);
        if (!isNaN(price) && qty > 0) {
            total += price * qty;
        }
    });
    if (totalEl) totalEl.textContent = formatNumber(total);
}

qtyInputs.forEach(inp => inp.addEventListener('input', calcTotal));
calcTotal();
</script>
</body>
</html>
