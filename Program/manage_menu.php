<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider' || !isset($_SESSION['provider_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.html';</script>";
    exit();
}

$provider_id = $_SESSION['provider_id'];
$sql_shop = "SELECT * FROM banquetshop WHERE provider_id = '$provider_id' AND shop_status = 'อนุมัติ' LIMIT 1";
$res_shop = $conn->query($sql_shop);
$shop = $res_shop->fetch_assoc();

if (!$shop) {
    echo "<script>alert('ร้านของคุณยังไม่ได้รับการอนุมัติ'); window.location.href='provider_dashboard.php';</script>";
    exit();
}

$shop_id = $shop['shop_id'];

// เพิ่มรายการอาหาร (Menu)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_menu'])) {
    $menu_name = mysqli_real_escape_string($conn, $_POST['menu_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    $menu_image = '';
    if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
        $ext = pathinfo($_FILES['menu_image']['name'], PATHINFO_EXTENSION);
        $menu_image = 'menu_' . time() . '_' . rand(100, 999) . '.' . $ext;
        move_uploaded_file($_FILES['menu_image']['tmp_name'], 'uploads/' . $menu_image);
    }

    $conn->query("INSERT INTO menu (shop_id, menu_name, category, menu_image) VALUES ('$shop_id', '$menu_name', '$category', " . ($menu_image ? "'$menu_image'" : "NULL") . ")");
    echo "<script>alert('เพิ่มรายการอาหารเรียบร้อย'); window.location.href='manage_menu.php';</script>";
    exit();
}

// เพิ่มบริการเสริม (Additional Service)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $price = floatval($_POST['price']);
    
    $service_image = '';
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
        $ext = pathinfo($_FILES['service_image']['name'], PATHINFO_EXTENSION);
        $service_image = 'service_' . time() . '_' . rand(100, 999) . '.' . $ext;
        move_uploaded_file($_FILES['service_image']['tmp_name'], 'uploads/' . $service_image);
    }

    $conn->query("INSERT INTO additional_service (shop_id, service_name, price, service_image) VALUES ('$shop_id', '$service_name', '$price', " . ($service_image ? "'$service_image'" : "NULL") . ")");
    echo "<script>alert('เพิ่มบริการเสริมเรียบร้อย'); window.location.href='manage_menu.php';</script>";
    exit();
}

// ลบข้อมูล
if (isset($_GET['delete_menu'])) {
    $id = intval($_GET['delete_menu']);
    $conn->query("DELETE FROM menu WHERE menu_id = '$id' AND shop_id = '$shop_id'");
    echo "<script>window.location.href='manage_menu.php';</script>";
}
if (isset($_GET['delete_service'])) {
    $id = intval($_GET['delete_service']);
    $conn->query("DELETE FROM additional_service WHERE service_id = '$id' AND shop_id = '$shop_id'");
    echo "<script>window.location.href='manage_menu.php';</script>";
}

$res_menu = $conn->query("SELECT * FROM menu WHERE shop_id = '$shop_id' ORDER BY category ASC");
$res_service = $conn->query("SELECT * FROM additional_service WHERE shop_id = '$shop_id' ORDER BY service_id DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการรายการอาหารและบริการเสริม - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 1000px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; }
        h2, h3 { color: #8b0000; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; float: right; font-size: 14px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 15px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; width: 100%; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #8b0000; color: white; }
        .btn-del { color: #dc3545; text-decoration: none; font-weight: bold; }
        .img-thumb { width: 45px; height: 35px; object-fit: cover; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <a href="provider_dashboard.php" class="btn-back">⬅ กลับ Dashboard</a>
    <h2>จัดการรายการอาหาร และ บริการเสริม</h2>
    <p><strong>ร้าน:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>

    <div class="grid">
        <!-- เมนูอาหาร -->
        <div class="card">
            <h3>🍲 รายการอาหาร (Menu)</h3>
            <form action="manage_menu.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>ชื่ออาหาร:</label>
                    <input type="text" name="menu_name" placeholder="เช่น ปลากะพงนึ่งมะนาว" required>
                </div>
                <div class="form-group">
                    <label>หมวดหมู่:</label>
                    <select name="category" required>
                        <option value="อาหารทานเล่น">อาหารทานเล่น</option>
                        <option value="อาหารจานหลัก">อาหารจานหลัก</option>
                        <option value="ซุป/ต้มยำ">ซุป/ต้มยำ</option>
                        <option value="ของหวาน/เครื่องดื่ม">ของหวาน/เครื่องดื่ม</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>รูปภาพ:</label>
                    <input type="file" name="menu_image" accept="image/*">
                </div>
                <button type="submit" name="add_menu" class="btn-submit">เพิ่มรายการอาหาร</button>
            </form>

            <h4 style="margin-top:15px;">รายการที่มี</h4>
            <table>
                <tr><th>รูป</th><th>ชื่อ</th><th>หมวดหมู่</th><th>ลบ</th></tr>
                <?php if ($res_menu && $res_menu->num_rows > 0): ?>
                    <?php while ($m = $res_menu->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $m['menu_image'] ? "<img src='uploads/".htmlspecialchars($m['menu_image'])."' class='img-thumb'>" : "-"; ?></td>
                        <td><?php echo htmlspecialchars($m['menu_name']); ?></td>
                        <td><?php echo htmlspecialchars($m['category']); ?></td>
                        <td><a href="manage_menu.php?delete_menu=<?php echo $m['menu_id']; ?>" class="btn-del" onclick="return confirm('ลบ?');">ลบ</a></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>

        <!-- บริการเสริม -->
        <div class="card">
            <h3>🎪 บริการเสริม (Additional Service)</h3>
            <form action="manage_menu.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>ชื่อบริการ:</label>
                    <input type="text" name="service_name" placeholder="เช่น ดนตรีสดโฟล์คซอง" required>
                </div>
                <div class="form-group">
                    <label>ราคา (บาท):</label>
                    <input type="number" name="price" step="0.01" placeholder="เช่น 3500" required>
                </div>
                <div class="form-group">
                    <label>รูปภาพ:</label>
                    <input type="file" name="service_image" accept="image/*">
                </div>
                <button type="submit" name="add_service" class="btn-submit">เพิ่มบริการเสริม</button>
            </form>

            <h4 style="margin-top:15px;">บริการที่มี</h4>
            <table>
                <tr><th>รูป</th><th>ชื่อบริการ</th><th>ราคา</th><th>ลบ</th></tr>
                <?php if ($res_service && $res_service->num_rows > 0): ?>
                    <?php while ($s = $res_service->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $s['service_image'] ? "<img src='uploads/".htmlspecialchars($s['service_image'])."' class='img-thumb'>" : "-"; ?></td>
                        <td><?php echo htmlspecialchars($s['service_name']); ?></td>
                        <td><?php echo number_format($s['price'], 2); ?></td>
                        <td><a href="manage_menu.php?delete_service=<?php echo $s['service_id']; ?>" class="btn-del" onclick="return confirm('ลบ?');">ลบ</a></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>