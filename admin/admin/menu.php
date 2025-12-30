<?php
include("check_admin.php");
include("../connection.php");

// ADD FOOD
if (isset($_POST["insert"])) {
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);
    $isAvailable = isset($_POST["is_available"]) ? 1 : 0;
    $imagePath = null;

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
        $imagePath = "uploads/" . $fileName;
    }

    $stmt = $conn->prepare(
        "INSERT INTO food (name, description, category, price, image_path, is_available) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssisi", $name, $description, $category, $price, $imagePath, $isAvailable);
    $stmt->execute();
    $stmt->close();
}

// DELETE FOOD
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);

    $res = $conn->query("SELECT image_path FROM food WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        if (!empty($row["image_path"]) && file_exists("../" . $row["image_path"])) {
            unlink("../" . $row["image_path"]);
        }
    }

    // Remove order items referencing this food to satisfy FK
    $conn->query("DELETE FROM order_items WHERE food_id = $id");

    $conn->query("DELETE FROM food WHERE id=$id");

    header("Location: menu.php?deleted=1");
    exit();
}

// Toggle availability
if (isset($_POST["toggle_id"])) {
    $tid = intval($_POST["toggle_id"]);
    $val = isset($_POST["val"]) && $_POST["val"] == "1" ? 1 : 0;
    $stmt = $conn->prepare("UPDATE food SET is_available=? WHERE id=?");
    $stmt->bind_param("ii", $val, $tid);
    $stmt->execute();
    $stmt->close();
    header("Location: menu.php");
    exit();
}

// LOAD LIST
$res = $conn->query("SELECT * FROM food ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Menu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../style.css">
</head>

<body class="page-index">

<div class="index-shell">

    <header class="index-header">
        <div class="index-title">
            <span class="index-title-badge">FM</span>
            <div>
                <p class="index-kicker">Admin</p>
                <h1>Quản lý món</h1>
            </div>
        </div>

        <div class="index-actions">
            <input id="tableSearch" class="index-search" placeholder="Tìm món">

            <button type="button" class="index-btn index-btn-primary" data-open-modal>
                Thêm món
            </button>

            <a href="Dashboard.php" class="index-btn index-btn-ghost">Dashboard</a>
            <a href="../logout.php" class="index-btn index-btn-ghost">Logout</a>
        </div>
    </header>

    <div class="index-card">
        <div class="index-table-wrapper">
            <table class="index-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price (VND)</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Available</th>
                        <th>Created</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody id="foodTbody">
                <?php $stt = 1; ?>
                <?php while($row = $res->fetch_assoc()): ?>
                    <tr>
                        <td><?= $stt++ ?></td>

                        <td><?= htmlspecialchars($row["name"]) ?></td>
                        <td><?= number_format($row["price"]) ?></td>
                        <td><?= htmlspecialchars($row["category"]) ?></td>
                        <td><?= htmlspecialchars($row["description"]) ?></td>
                        <td>
                            <form method="post" class="dash-inline-form">
                                <input type="hidden" name="toggle_id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="val" value="<?= $row["is_available"] ? 0 : 1 ?>">
                                <button class="dash-chip <?= $row['is_available'] ? 'success' : 'danger' ?>" type="submit">
                                    <?= $row["is_available"] ? "Đang bán" : "Ngừng" ?>
                                </button>
                            </form>
                        </td>
                        <td><?= $row["created_at"] ?></td>
                        <td>
                            <?php if ($row["image_path"]): ?>
                                <img src="../<?= $row["image_path"] ?>" class="index-food-img" alt="Food image">
                            <?php else: ?>
                                <span class="index-empty">No image</span>
                            <?php endif; ?>
                        </td>
                        <td class="index-actions-cell">
                            <a href="../edit.php?id=<?= $row['id'] ?>" class="index-chip index-chip-outline">
                                Edit
                            </a>

                            <a href="?delete=<?= $row['id'] ?>" 
                               class="index-chip index-chip-danger"
                               onclick="return confirm('Delete this food?')">
                               Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<div class="index-modal" id="addModal" hidden>
  <div class="index-modal-card">
    <div class="index-modal-head">
      <div>
        <p class="index-kicker">New item</p>
        <h3>Thêm món</h3>
      </div>
      <button type="button" class="index-modal-close" data-close-modal aria-label="Close add form">&times;</button>
    </div>

    <form method="post" enctype="multipart/form-data" class="index-form">
        <label class="index-label">Tên món<span class="index-required">*</span></label>
        <input type="text" name="name" class="index-input" required>

        <label class="index-label">Giá</label>
        <input type="number" name="price" class="index-input" required>

        <label class="index-label">Danh mục</label>
        <input type="text" name="category" class="index-input">

        <label class="index-label">Mô tả</label>
        <textarea name="description" class="index-textarea"></textarea>

        <label class="index-label">Ảnh</label>
        <input type="file" name="image" class="index-file" accept="image/*">

        <label class="index-label">
            <input type="checkbox" name="is_available" checked> Đang bán
        </label>

        <div class="index-modal-actions">
            <button type="button" class="index-btn index-btn-ghost" data-close-modal>Hủy</button>
            <button type="submit" name="insert" class="index-btn index-btn-primary">Lưu</button>
        </div>
    </form>

  </div>
</div>

<script>
const input = document.getElementById('tableSearch');
const tbody = document.getElementById('foodTbody');

input?.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    [...tbody.rows].forEach(row => {
        const cells = [...row.cells].map(c => c.textContent.toLowerCase());
        row.style.display = cells.some(c => c.includes(q)) ? "" : "none";
    });
});

const addModal = document.getElementById('addModal');
const openModalBtn = document.querySelector('[data-open-modal]');
const closeModalBtns = addModal?.querySelectorAll('[data-close-modal]');
const body = document.body;

const showModal = () => {
    if (!addModal) return;
    addModal.hidden = false;
    addModal.classList.add('show');
    body.classList.add('no-scroll');
    addModal.querySelector('input')?.focus();
};

const hideModal = () => {
    if (!addModal) return;
    addModal.classList.remove('show');
    addModal.hidden = true;
    body.classList.remove('no-scroll');
};

openModalBtn?.addEventListener('click', showModal);
closeModalBtns?.forEach(btn => btn.addEventListener('click', hideModal));

addModal?.addEventListener('click', (e) => {
    if (e.target === addModal) hideModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && addModal?.classList.contains('show')) hideModal();
});
</script>

</body>
</html>
