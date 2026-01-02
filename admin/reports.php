<?php
declare(strict_types=1);

require_once __DIR__ . "/check_admin.php";
require_once __DIR__ . "/../connection.php";

function fetch_all(mysqli $conn, string $sql): array {
    $rows = [];
    $res = $conn->query($sql);
    if ($res instanceof mysqli_result) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $res->free();
    }
    return $rows;
}

function fetch_one(mysqli $conn, string $sql): array {
    $res = $conn->query($sql);
    if ($res instanceof mysqli_result) {
        $row = $res->fetch_assoc() ?: [];
        $res->free();
        return $row;
    }
    return [];
}

$modes = [
    "week" => [
        "selector" => "Theo tuần",
        "rangeTitle" => "8 tuần gần nhất",
        "where" => "created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)",
        "label" => "DATE_FORMAT(created_at, '%x-W%v')",
        "limit" => 8,
        "topWhere" => "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)",
    ],
    "month" => [
        "selector" => "Theo tháng",
        "rangeTitle" => "12 tháng gần nhất",
        "where" => "created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)",
        "label" => "DATE_FORMAT(created_at, '%Y-%m')",
        "limit" => 12,
        "topWhere" => "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)",
    ],
    "year" => [
        "selector" => "Theo năm",
        "rangeTitle" => "5 năm gần nhất",
        "where" => "1=1",
        "label" => "DATE_FORMAT(created_at, '%Y')",
        "limit" => 5,
        "topWhere" => "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)",
    ],
];

$revenue = [];
$topFoods = [];
$kpis = [];

foreach ($modes as $mode => $cfg) {
    // Revenue buckets
    $revenue[$mode] = fetch_all($conn, "
        SELECT {$cfg["label"]} AS label, COALESCE(SUM(total_amount),0) AS total
        FROM orders
        WHERE {$cfg["where"]}
        GROUP BY label
        ORDER BY label DESC
        LIMIT {$cfg["limit"]}
    ");

    // KPIs
    $kpis[$mode] = fetch_one($conn, "
        SELECT
            COUNT(*) AS orders_count,
            COALESCE(SUM(total_amount),0) AS revenue_total,
            COALESCE(AVG(total_amount),0) AS aov,
            COALESCE(COUNT(DISTINCT user_id),0) AS customers
        FROM orders
        WHERE {$cfg["where"]}
    ");

    // Top foods
    $topFoods[$mode] = fetch_all($conn, "
        SELECT f.name, COALESCE(SUM(oi.quantity),0) AS qty
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        JOIN food f ON f.id = oi.food_id
        WHERE {$cfg["topWhere"]}
        GROUP BY f.id, f.name
        ORDER BY qty DESC
        LIMIT 8
    ");
}

function max_value(array $rows, string $key): float {
    $m = 0.0;
    foreach ($rows as $r) $m = max($m, (float)($r[$key] ?? 0));
    return $m;
}

function sum_value(array $rows, string $key): float {
    $s = 0.0;
    foreach ($rows as $r) $s += (float)($r[$key] ?? 0);
    return $s;
}

function top_item(array $rows, string $nameKey, string $valKey): array {
    if (empty($rows)) return ["name" => "", "val" => 0];
    return ["name" => (string)($rows[0][$nameKey] ?? ""), "val" => (int)($rows[0][$valKey] ?? 0)];
}
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
                <p class="dash-sub">Tổng quan nhanh, doanh thu theo kỳ và top món bán chạy.</p>
            </div>
            <div class="dash-header-actions">
                <select id="reportSelector" class="dash-input">
                    <?php foreach ($modes as $key => $cfg): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($cfg["selector"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </header>

        <section class="report-panels">
            <?php foreach (array_keys($modes) as $mode):
                $cfg = $modes[$mode];

                $rev = $revenue[$mode];
                $top = $topFoods[$mode];
                $k   = $kpis[$mode];

                $maxRev = max_value($rev, "total");
                $maxQty = max_value($top, "qty");

                $revTotal = (float)($k["revenue_total"] ?? 0);
                $ordersCount = (int)($k["orders_count"] ?? 0);
                $aov = (float)($k["aov"] ?? 0);
                $customers = (int)($k["customers"] ?? 0);

                $revTopLabel = "";
                $revTopVal = 0.0;
                foreach ($rev as $r) {
                    $t = (float)($r["total"] ?? 0);
                    if ($t >= $revTopVal) {
                        $revTopVal = $t;
                        $revTopLabel = (string)($r["label"] ?? "");
                    }
                }

                $topFood = top_item($top, "name", "qty");
            ?>
            <div class="report-panel" data-mode="<?= $mode ?>" <?= $mode === "week" ? "" : "hidden" ?>>

                <div class="report-grid">
                    <div class="report-card">
                        <div class="dash-table-head">
                            <div>
                                <p class="dash-kicker">Tổng quan</p>
                                <h3><?= htmlspecialchars($cfg["rangeTitle"]) ?></h3>
                            </div>
                        </div>

                        <div class="report-bars">
                            <div class="report-bar-row">
                                <div class="report-bar-label">Tổng doanh thu</div>
                                <div class="report-bar-value"><?= number_format($revTotal) ?>đ</div>
                            </div>
                            <div class="report-bar-row">
                                <div class="report-bar-label">Số đơn</div>
                                <div class="report-bar-value"><?= number_format($ordersCount) ?></div>
                            </div>
                            <div class="report-bar-row">
                                <div class="report-bar-label">Giá trị TB/đơn</div>
                                <div class="report-bar-value"><?= number_format($aov) ?>đ</div>
                            </div>
                            <div class="report-bar-row">
                                <div class="report-bar-label">Khách hàng</div>
                                <div class="report-bar-value"><?= number_format($customers) ?></div>
                            </div>
                            <div class="report-bar-row">
                                <div class="report-bar-label">Kỳ doanh thu cao nhất</div>
                                <div class="report-bar-value">
                                    <?= $revTopLabel !== "" ? htmlspecialchars($revTopLabel) . " (" . number_format($revTopVal) . "đ)" : "Chưa có" ?>
                                </div>
                            </div>
                            <div class="report-bar-row">
                                <div class="report-bar-label">Top món</div>
                                <div class="report-bar-value">
                                    <?= $topFood["name"] !== "" ? htmlspecialchars($topFood["name"]) . " (" . (int)$topFood["val"] . " lượt)" : "Chưa có" ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="dash-table-head">
                            <div>
                                <p class="dash-kicker">Doanh thu theo kỳ</p>
                                <h3><?= htmlspecialchars($cfg["rangeTitle"]) ?></h3>
                            </div>
                        </div>

                        <div class="report-bars">
                            <?php if (empty($rev)): ?>
                                <div class="dash-empty">Chưa có dữ liệu.</div>
                            <?php else: ?>
                                <?php foreach ($rev as $r):
                                    $total = (float)($r["total"] ?? 0);
                                    $percent = $maxRev > 0 ? (int)round(($total / $maxRev) * 100) : 0;
                                ?>
                                    <div class="report-bar-row">
                                        <div class="report-bar-label"><?= htmlspecialchars((string)($r["label"] ?? "")) ?></div>
                                        <div class="report-bar-track">
                                            <div class="report-bar-fill" style="width: <?= $percent ?>%;"></div>
                                        </div>
                                        <div class="report-bar-value"><?= number_format($total) ?>đ</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="report-card">
                        <div class="dash-table-head">
                            <div>
                                <p class="dash-kicker">Top món bán chạy</p>
                                <h3><?= $mode === "week" ? "Trong 8 tuần" : ($mode === "month" ? "Trong 12 tháng" : "Trong 5 năm") ?></h3>
                            </div>
                        </div>

                        <div class="report-bars">
                            <?php if (empty($top)): ?>
                                <div class="dash-empty">Chưa có dữ liệu.</div>
                            <?php else: ?>
                                <?php foreach ($top as $t):
                                    $qty = (int)($t["qty"] ?? 0);
                                    $percent = $maxQty > 0 ? (int)round(($qty / $maxQty) * 100) : 0;
                                ?>
                                    <div class="report-bar-row">
                                        <div class="report-bar-label"><?= htmlspecialchars((string)($t["name"] ?? "")) ?></div>
                                        <div class="report-bar-track">
                                            <div class="report-bar-fill info" style="width: <?= $percent ?>%;"></div>
                                        </div>
                                        <div class="report-bar-value"><?= $qty ?> lượt</div>
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
        if (p.dataset.mode === mode) p.removeAttribute('hidden');
        else p.setAttribute('hidden', 'hidden');
    });
});
</script>
</body>
</html>
