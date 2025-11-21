<?php
// KIỂM TRA COOKIE AUTH
include("check_auth.php");
include("validation.php");
include("connection.php");

$insert_ok = false;

// ADD FOOD
if (isset($_POST["insert"])) {
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);
    $imagePath = null;

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        move_uploaded_file($_FILES['image']['tmp_name'], $targetFile);
        $imagePath = $targetFile;
    }

    $stmt = $conn->prepare(
        "INSERT INTO food (name, price, category, description, image_path) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sdsss", $name, $price, $category, $description, $imagePath);
    $stmt->execute();
    $stmt->close();
}

// DELETE FOOD
if (isset($_GET["delete"])) {
    $id = intval($_GET["delete"]);

    $res = $conn->query("SELECT image_path FROM food WHERE id=$id");
    if ($row = $res->fetch_assoc()) {
        if (!empty($row["image_path"]) && file_exists($row["image_path"])) {
            unlink($row["image_path"]);
        }
    }

    $conn->query("DELETE FROM food WHERE id=$id");

    header("Location: index.php?deleted=1");
    exit();
}

// LOAD LIST
$res = $conn->query("SELECT * FROM food ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Food Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="style.css">
</head>

<body class="page-index">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-semibold text-success">
            <i class="bi bi-list-ul me-2"></i>Food List
        </h3>

        <div class="d-flex align-items-center gap-3">
            <input id="tableSearch" class="index-search" placeholder="Search...">

            <button class="index-btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i>Add Product
            </button>

            <!-- NÚT LOGOUT -->
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>

    <div class="index-box">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price (₫)</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>
                </thead>

               <tbody id="foodTbody">
    <?php $stt = 1; ?>
    <?php while($row = $res->fetch_assoc()): ?>
        <tr>
            <!-- STT chạy 1,2,3 thay cho ID DB -->
            <td><?= $stt++ ?></td>

                            <td><?= htmlspecialchars($row["name"]) ?></td>
                            <td><?= number_format($row["price"]) ?></td>
                            <td><?= htmlspecialchars($row["category"]) ?></td>
                            <td><?= htmlspecialchars($row["description"]) ?></td>
                            <td><?= $row["created_at"] ?></td>
                            <td>
                                <?php if ($row["image_path"]): ?>
                                    <img src="<?= $row["image_path"] ?>" class="index-food-img">
                                <?php else: ?>
                                    <span class="text-muted">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-outline-success btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <a href="?delete=<?= $row['id'] ?>" 
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Delete this food?')">
                                   <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content p-3">
      <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i>Add Food</h5>

      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Food Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" name="price" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>

        <div class="text-end">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="insert" class="btn btn-success">Save</button>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
</script>

</body>
</html>
