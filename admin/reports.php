<?php
include("check_admin.php");
include("../connection.php");

function fetch_group($conn, $sql) {
    $rows = [];
    $res = $conn->query($sql);
    if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

$revenue = [
    "week" => fetch_group($conn, "
        SELECT DATE_FORMAT(created_at, '%x-W%v') AS label, SUM(total_amount) total
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY label
        ORDER BY label DESC
        LIMIT 8
    "),
    "month" => fetch_group($conn, "
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS label, SUM(total_amount) total
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY label
        ORDER BY label DESC
        LIMIT 12
    "),
    "year" => fetch_group($conn, "
        SELECT DATE_FORMAT(created_at, '%Y') AS label, SUM(total_amount) total
        FROM orders
        GROUP BY label
        ORDER BY label DESC
        LIMIT 5
    "),
];

$topFoods = [
    "week" => fetch_group($conn, "
        SELECT f.name, SUM(oi.quantity) qty
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN food f ON f.id = oi.food_id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY f.id, f.name
        ORDER BY qty DESC
        LIMIT 8
    "),
    "month" => fetch_group($conn, "
        SELECT f.name, SUM(oi.quantity) qty
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN food f ON f.id = oi.food_id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY f.id, f.name
        ORDER BY qty DESC
        LIMIT 8
    "),
    "year" => fetch_group($conn, "
        SELECT f.name, SUM(oi.quantity) qty
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN food f ON f.id = oi.food_id
        WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
        GROUP BY f.id, f.name
        ORDER BY qty DESC
        LIMIT 8
    "),
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
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
            <a href="customers.php">Customers</a>
            <a class="active" href="#">Reports</a>
        </div>
        <a class="dash-logout" href="../logout.php">Logout</a>
    </aside>

    <main class="dash-main">
        <header class="dash-header">
            <div>
                <p class="dash-kicker">Dashboard</p>
                <h1>Báo cáo</h1>
                <p class="dash-sub">Doanh thu theo tuần/tháng/năm và top món bán chạy.</p>
            </div>
            <div class="dash-header-actions">
                <select id="reportSelector" class="dash-input">
                    <option value="week">Theo tuần</option>
                    <option value="month">Theo tháng</option>
                    <option value="year">Theo năm</option>
                </select>
            </div>
        </header>

        <section class="report-panels">
            <?php foreach (["week","month","year"] as $mode): 
                $rev = $revenue[$mode];
                $top = $topFoods[$mode];
                $maxRev = 0;
                foreach ($rev as $r) {
                    $maxRev = max($maxRev, (float)$r["total"]);
                }
                $maxQty = 0;
                foreach ($top as $t) {
                    $maxQty = max($maxQty, (int)$t["qty"]);
                }
            ?>
            <div class="report-panel" data-mode="<?= $mode ?>" <?= $mode === "week" ? "" : "hidden" ?>>
                <div class="report-grid">
                    <div class="report-card">
                        <div class="dash-table-head">
                            <div>
                                <p class="dash-kicker">Doanh thu</p>
                                <h3><?= $mode === "week" ? "8 tuần gần nhất" : ($mode === "month" ? "12 tháng gần nhất" : "5 năm gần nhất") ?></h3>
                            </div>
                        </div>
                        <div class="report-bars">
                            <?php if (empty($rev)): ?>
                                <div class="dash-empty">Chưa có dữ liệu.</div>
                            <?php else: ?>
                                <?php foreach ($rev as $r): 
                                    $percent = $maxRev > 0 ? round(($r["total"] / $maxRev) * 100) : 0;
                                ?>
                                    <div class="report-bar-row">
                                        <div class="report-bar-label"><?= htmlspecialchars($r["label"]) ?></div>
                                        <div class="report-bar-track">
                                            <div class="report-bar-fill" style="width: <?= $percent ?>%;"></div>
                                        </div>
                                        <div class="report-bar-value"><?= number_format($r["total"]) ?>đ</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="dash-table-head">
                            <div>
                                <p class="dash-kicker">Top món</p>
                                <h3>Bán chạy <?= $mode === "week" ? "8 tuần" : ($mode === "month" ? "12 tháng" : "5 năm") ?></h3>
                            </div>
                        </div>
                        <div class="report-bars">
                            <?php if (empty($top)): ?>
                                <div class="dash-empty">Chưa có dữ liệu.</div>
                            <?php else: ?>
                                <?php foreach ($top as $t): 
                                    $percent = $maxQty > 0 ? round(($t["qty"] / $maxQty) * 100) : 0;
                                ?>
                                    <div class="report-bar-row">
                                        <div class="report-bar-label"><?= htmlspecialchars($t["name"]) ?></div>
                                        <div class="report-bar-track">
                                            <div class="report-bar-fill info" style="width: <?= $percent ?>%;"></div>
                                        </div>
                                        <div class="report-bar-value"><?= (int)$t["qty"] ?> lượt</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </section>
    </main>

<script>
const selector = document.getElementById('reportSelector');
const panels = document.querySelectorAll('.report-panel');
selector?.addEventListener('change', () => {
    const mode = selector.value;
    panels.forEach(p => {
        if (p.dataset.mode === mode) {
            p.removeAttribute('hidden');
        } else {
            p.setAttribute('hidden', 'hidden');
        }
    });
});
</script>
</body>
</html>
