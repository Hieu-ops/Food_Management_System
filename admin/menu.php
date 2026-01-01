<?php
declare(strict_types=1);

require_once __DIR__ . "/check_admin.php";
require_once __DIR__ . "/../connection.php";

/* ADD FOOD */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["insert"])) {
    $name        = trim($_POST["name"] ?? "");
    $price       = filter_input(INPUT_POST, "price", FILTER_VALIDATE_INT);
    $category    = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $isAvailable = isset($_POST["is_available"]) ? 1 : 0;
    $imagePath   = null;

    if ($name !== "" && $price !== false && $price >= 0) {
        if (!empty($_FILES["image"]["name"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
            $uploadDir = __DIR__ . "/../uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $safeExt = in_array($ext, ["jpg", "jpeg", "png", "webp", "gif"], true) ? $ext : "jpg";
            $fileName = uniqid("food_", true) . "." . $safeExt;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir . $fileName)) {
                $imagePath = "uploads/" . $fileName;
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO food (name, description, category, price, image_path, is_available)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssisi", $name, $description, $category, $price, $imagePath, $isAvailable);
        $stmt->execute();
        $stmt->close();
    }
}

/* DELETE FOOD */
if (isset($_GET["delete"])) {
    $id = filter_input(INPUT_GET, "delete", FILTER_VALIDATE_INT);

    if ($id && $id > 0) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("SELECT image_path FROM food WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM order_items WHERE food_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM food WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            if (!empty($row["image_path"])) {
                $file = __DIR__ . "/../" . $row["image_path"];
                if (is_file($file)) {
                    unlink($file);
                }
            }
        } catch (Throwable $e) {
            $conn->rollback();
        }
    }

    header("Location: menu.php?deleted=1");
    exit;
}

/* TOGGLE AVAILABILITY */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_id"])) {
    $tid = filter_input(INPUT_POST, "toggle_id", FILTER_VALIDATE_INT);
    $val = filter_input(INPUT_POST, "val", FILTER_VALIDATE_INT);

    if ($tid && ($val === 0 || $val === 1)) {
        $stmt = $conn->prepare("UPDATE food SET is_available = ? WHERE id = ?");
        $stmt->bind_param("ii", $val, $tid);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: menu.php");
    exit;
}

/* LOAD LIST */
$res = $conn->query("SELECT * FROM food ORDER BY created_at DESC");
?>
