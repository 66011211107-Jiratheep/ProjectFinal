<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider' || !isset($_SESSION['provider_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.html';</script>";
    exit();
}

$provider_id = $_SESSION['provider_id'];

// ดึงข้อมูลร้านค้า
$sql_shop = "SELECT * FROM banquetshop WHERE provider_id = '$provider_id' AND shop_status = 'อนุมัติ' LIMIT 1";
$res_shop = $conn->query($sql_shop);
$shop = $res_shop ? $res_shop->fetch_assoc() : null;

if (!$shop) {
    echo "<script>alert('ร้านของคุณยังไม่ได้รับการอนุมัติ'); window.location.href='provider_dashboard.php';</script>";
    exit();
}

$shop_id = $shop['shop_id'];

// เพิ่มแพ็กเกจ (INSERT)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_package'])) {
    $type_id = intval($_POST['type_id']);
    $package_name = mysqli_real_escape_string($conn, $_POST['package_name']);
    $price_per_table = floatval($_POST['price_per_table']);

    $package_image = '';
    if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
        $ext = pathinfo($_FILES['package_image']['name'], PATHINFO_EXTENSION);
        $package_image = 'pkg_' . time() . '_' . rand(100, 999) . '.' . $ext;
        move_uploaded_file($_FILES['package_image']['tmp_name'], 'uploads/' . $package_image);
    }

    $sql_insert = "INSERT INTO package (shop_id, type_id, package_name, price_per_table, package_image) 
                   VALUES ('$shop_id', '$type_id', '$package_name', '$price_per_table', " . ($package_image ? "'$package_image'" : "NULL") . ")";
    
    if ($conn->query($sql_insert)) {
        echo "<script>alert('เพิ่มแพ็กเกจเรียบร้อยแล้ว'); window.location.href='manage_packages.php';</script>";
        exit();
    }
}

// ลบแพ็กเกจ (DELETE)
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM package WHERE package_id = '$delete_id' AND shop_id = '$shop_id'");
    echo "<script>alert('ลบแพ็กเกจเรียบร้อยแล้ว'); window.location.href='manage_packages.php';</script>";
    exit();
}

// ตรวจสอบตาราง banquet_type ว่ามีหรือไม่
$check_type_tbl = $conn->query("SHOW TABLES LIKE 'banquet_type'");
$has_type_tbl = ($check_type_tbl && $check_type_tbl->num_rows > 0);

$res_types = false;
if ($has_type_tbl) {
    $res_types = $conn->query("SELECT * FROM banquet_type ORDER BY type_name ASC");
    $sql_packages = "SELECT p.*, t.type_name 
                     FROM package p 
                     LEFT JOIN banquet_type t ON p.type_id = t.type_id
                     WHERE p.shop_id = '$shop_id' 
                     ORDER BY p.price_per_table ASC";
} else {
    $sql_packages = "SELECT p.*, NULL as type_name FROM package p WHERE p.shop_id = '$shop_id' ORDER BY p.price_per_table ASC";
}

$res_packages = $conn->query($sql_packages);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแพ็กเกจ - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2, h3 { color: #8b0000; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="number"], input[type="file"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-add { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 15px; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; float: right; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #8b0000; color: white; }
        .btn-delete { background: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; }
        .img-preview { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-manage-packages provider-ui">
<div class="container">
    <a href="provider_dashboard.php" class="btn-back">⬅ กลับ Dashboard</a>
    <h2>จัดการแพ็กเกจเมนูโต๊ะจีน</h2>
    <p><strong>ร้าน:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>

    <hr>
    <h3>➕ เพิ่มแพ็กเกจใหม่</h3>
    <form action="manage_packages.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>ประเภทโต๊ะจีน:</label>
            <?php if ($has_type_tbl && $res_types && $res_types->num_rows > 0): ?>
                <select name="type_id" required>
                    <option value="">-- เลือกประเภทโต๊ะจีน --</option>
                    <?php while ($type = $res_types->fetch_assoc()): ?>
                        <option value="<?php echo $type['type_id']; ?>"><?php echo htmlspecialchars($type['type_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            <?php else: ?>
                <input type="number" name="type_id" value="7001" placeholder="ระบุ type_id เช่น 7001" required>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label>ชื่อแพ็กเกจ:</label>
            <input type="text" name="package_name" placeholder="เช่น ชุดเศรษฐีมั่งมี" required>
        </div>
        <div class="form-group">
            <label>ราคาต่อโต๊ะ (บาท):</label>
            <input type="number" name="price_per_table" step="0.01" placeholder="เช่น 2500" required>
        </div>
        <div class="form-group">
            <label>รูปภาพแพ็กเกจ:</label>
            <input type="file" name="package_image" accept="image/*">
        </div>
        <button type="submit" name="add_package" class="btn-add">บันทึกแพ็กเกจ</button>
    </form>

    <hr>
    <h3>📋 รายการแพ็กเกจปัจจุบัน</h3>
    <table>
        <tr>
            <th>รูป</th>
            <th>ชื่อแพ็กเกจ</th>
            <th>ประเภท</th>
            <th>ราคา / โต๊ะ</th>
            <th>จัดการ</th>
        </tr>
        <?php if ($res_packages && $res_packages->num_rows > 0): ?>
            <?php while ($pkg = $res_packages->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php if (!empty($pkg['package_image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($pkg['package_image']); ?>" class="img-preview">
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td><strong><?php echo htmlspecialchars($pkg['package_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($pkg['type_name'] ?? $pkg['type_id']); ?></td>
                    <td><?php echo number_format($pkg['price_per_table'], 2); ?> บาท</td>
                    <td>
                        <a href="manage_packages.php?delete_id=<?php echo $pkg['package_id']; ?>" class="btn-delete" onclick="return confirm('ยืนยันการลบ?');">ลบ</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">ยังไม่มีแพ็กเกจในระบบ</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>