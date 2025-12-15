<?php
include("validation.php");
include("connection.php");

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET["id"]);
$result = $conn->query("SELECT * FROM food WHERE id = $id");

if ($result->num_rows == 0) {
    die("Food not found.");
}

$food = $result->fetch_assoc();

if (isset($_POST["update"])) {
    $name = trim($_POST["name"]);
    $price = trim($_POST["price"]);
    $category = trim($_POST["category"]);
    $description = trim($_POST["description"]);
    $imagePath = $food["image_path"];

    // Upload ảnh mới
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {

            if (!empty($imagePath) && file_exists($imagePath)) {
                unlink($imagePath);
            }

            $imagePath = $targetFile;
        }
    }

    $stmt = $conn->prepare(
        "UPDATE food SET name=?, price=?, category=?, description=?, image_path=? WHERE id=?"
    );
    $stmt->bind_param("sdsssi", $name, $price, $category, $description, $imagePath, $id);
    $stmt->execute();

    header("Location: index.php?updated=1");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Food</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS chung -->
    <link rel="stylesheet" href="style.css">
</head>

<body class="page-edit">

<div class="container py-5 edit-container">

    <a href="index.php" class="btn btn-secondary mb-3">← Back</a>

    <div class="card p-4 mx-auto edit-card">

        <h4 class="mb-4 fw-semibold text-primary edit-title">
            <i class="bi bi-pencil-square"></i> Edit Food
        </h4>

        <form method="post" enctype="multipart/form-data">

            <label class="form-label">Food Name</label>
            <input type="text" name="name" class="form-control mb-3" 
                   value="<?= htmlspecialchars($food["name"]) ?>" required>

            <label class="form-label">Price</label>
            <input type="number" name="price" class="form-control mb-3" 
                   value="<?= $food["price"] ?>" required>

            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control mb-3" 
                   value="<?= htmlspecialchars($food["category"]) ?>">

            <label class="form-label">Description</label>
            <textarea name="description" class="form-control mb-3">
                <?= htmlspecialchars($food["description"]) ?>
            </textarea>

            <label class="form-label">Current Image</label><br>

            <?php if ($food["image_path"]): ?>
                <img src="<?= $food["image_path"] ?>" class="edit-image mb-3">
            <?php else: ?>
                <p class="text-muted mb-3">No image</p>
            <?php endif; ?>

            <label class="form-label">Change Image</label>
            <input type="file" name="image" class="form-control mb-4">

            <button type="submit" name="update" class="btn btn-primary w-100 edit-save-btn">
                Save Changes
            </button>

        </form>

    </div>

</div>

</body>
</html>
