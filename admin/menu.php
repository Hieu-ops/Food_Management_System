<?php
declare(strict_types=1);

require_once __DIR__ . "/check_admin.php";
require_once __DIR__ . "/../connection.php";

$message = null;
$message_type = "success";

function set_message(string $text, string $type = "success"): void {
    global $message, $message_type;
    $message = $text;
    $message_type = $type;
}

function redirect(string $to): void {
    header("Location: {$to}");
    exit;
}

function post_int(string $key): ?int {
    $v = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    return ($v === false || $v === null) ? null : (int)$v;
}

function get_int(string $key): ?int {
    $v = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
    return ($v === false || $v === null) ? null : (int)$v;
}

function post_str(string $key): string {
    $v = $_POST[$key] ?? "";
    return is_string($v) ? trim($v) : "";
}

function safe_upload_image(array $file): ?string {
    if (empty($file["name"]) || empty($file["tmp_name"])) return null;
    if (!is_uploaded_file($file["tmp_name"])) return null;
    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return null;

    $uploadDir = __DIR__ . "/../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo((string)$file["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png", "webp", "gif"];
    if (!in_array($ext, $allowed, true)) {
        $ext = "jpg";
    }

    $fileName = uniqid("food_", true) . "." . $ext;
    $dest = $uploadDir . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $dest)) return null;
    return "uploads/" . $fileName;
}

/* =========================
   ADD FOOD
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["insert"])) {
    $name        = post_str("name");
    $price       = post_int("price");
    $category    = post_str("category");
    $description = post_str("description");
    $isAvailable = isset($_POST["is_available"]) ? 1 : 0;

    if ($name === "") {
        set_message("Tên món không được để trống.", "error");
    } elseif ($price === null || $price < 0) {
        set_message("Giá không hợp lệ.", "error");
    } else {
        $imagePath = safe_upload_image($_FILES["image"] ?? []);

        $stmt = $conn->prepare(
            "INSERT INTO food (name, description, category, price, image_path, is_available)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            set_message("Lỗi hệ thống khi thêm món.", "error");
        } else {
            $stmt->bind_param("sssisi", $name, $description, $category, $price, $imagePath, $isAvailable);
            if ($stmt->execute()) {
                set_message("Đã thêm món mới.", "success");
            } else {
                set_message("Thêm món thất bại.", "error");
            }
            $stmt->close();
        }
    }
}

/* =========================
   DELETE FOOD
========================= */
if (isset($_GET["delete"])) {
    $id = get_int("delete");

    if (!$id || $id <= 0) {
        redirect("menu.php?deleted=0");
    }

    $conn->begin_transaction();
    $imageToDelete = null;

    try {
        $stmt = $conn->prepare("SELECT image_path FROM food WHERE id = ? LIMIT 1");
        if (!$stmt) throw new Exception("prepare");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $conn->rollback();
            redirect("menu.php?deleted=0");
        }

        $imageToDelete = $row["image_path"] ?? null;

        $stmt = $conn->prepare("DELETE FROM order_items WHERE food_id = ?");
        if (!$stmt) throw new Exception("prepare");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM food WHERE id = ?");
        if (!$stmt) throw new Exception("prepare");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
    }

    if (!empty($imageToDelete)) {
        $file = __DIR__ . "/../" . $imageToDelete;
        if (is_file($file)) {
            unlink($file);
        }
    }

    redirect("menu.php?deleted=1");
}

/* =========================
   TOGGLE AVAILABILITY
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_id"])) {
    $tid = post_int("toggle_id");
    $val = post_int("val");

    if (!$tid || ($val !== 0 && $val !== 1)) {
        redirect("menu.php");
    }

    $stmt = $conn->prepare("UPDATE food SET is_available = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $val, $tid);
        $stmt->execute();
        $stmt->close();
    }

    redirect("menu.php");
}

/* =========================
   LOAD LIST
========================= */
$res = $conn->query("SELECT * FROM food ORDER BY created_at DESC");
?>
