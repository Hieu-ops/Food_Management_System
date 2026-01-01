<?php
declare(strict_types=1);

require_once __DIR__ . "/check_admin.php";
require_once __DIR__ . "/../connection.php";

/* =========================
   ADD FOOD
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["insert"])) {
    $name        = trim($_POST["name"] ?? "");
    $price       = filter_input(INPUT_POST, "price", FILTER_VALIDATE_INT);
    $category    = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $isAvailable = isset($_POST["is_available"]) ? 1 : 0;
    $imagePath   = null;

    if ($name !== "" && $price !== false) {
        // Handle image upload
        if (!empty($_FILES["image"]["name"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
            $targetDir = __DIR__ . "/../uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $fileName = uniqid("food_", true) . "." . strtolower($ext);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imagePath = "uploads/" . $fileName;
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO food (name, description, category, price, image_path, is_available)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssisi",
            $name,
            $description,
            $category,
            $price,
            $imagePath,
            $isAvailable
        );
        $stmt->execute();
        $stmt->close();
    }
}

/* =========================
   DELETE FOOD
========================= */
if (isset($_GET["delete"])) {
    $id = filter_input(INPUT_GET, "delete", FILTER_VALIDATE_INT);

    if ($id) {
        $conn->begin_transaction();
        try {
            // Get image path
            $stmt = $conn->prepare("SELECT image_path FROM food WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $img = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!empty($img["image_path"])) {
                $file = __DIR__ . "/../" . $img["image_path"];
                if (is_file($file)) {
                    unlink($file);
                }
            }

            // Delete related order items (FK safe)
            $stmt = $conn->prepare("DELETE FROM order_items WHERE food_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            // Delete food
            $stmt = $conn->prepare("DELETE FROM food WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
        }
    }

    header("Location: menu.php?deleted=1");
    exit;
}

/* =========================
   TOGGLE AVAILABILITY
========================= */
if (isset($_POST["toggle_id"])) {
    $tid = filter_input(INPUT_POST, "toggle_id", FILTER_VALIDATE_INT);
    $val = filter_input(INPUT_POST, "val", FILTER_VALIDATE_INT);

    if ($tid !== false && ($val === 0 || $val === 1)) {
        $stmt = $conn->prepare("UPDATE food SET is_available = ? WHERE id = ?");
        $stmt->bind_param("ii", $val, $tid);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: menu.php");
    exit;
}

/* =========================
   LOAD LIST
========================= */
$res = $conn->query("SELECT * FROM food ORDER BY created_at DESC");
