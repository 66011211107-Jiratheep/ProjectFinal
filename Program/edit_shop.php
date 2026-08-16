<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider' || !isset($_SESSION['provider_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.html';</script>";
    exit();
}

$provider_id = $_SESSION['provider_id'];

// ดึงข้อมูลร้านปัจจุบัน และ ดึงเบอร์โทรจากตาราง serviceprovider
$sql = "SELECT b.*, p.tel 
        FROM banquetshop b 
        LEFT JOIN serviceprovider p ON b.provider_id = p.provider_id 
        WHERE b.provider_id = '$provider_id' 
        ORDER BY b.shop_id DESC LIMIT 1";
$result = $conn->query($sql);
$shop = $result ? $result->fetch_assoc() : null;

if (!$shop) {
    echo "<script>alert('ไม่พบข้อมูลร้านค้า'); window.location.href='provider_dashboard.php';</script>";
    exit();
}

// บันทึกแก้ไข
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name   = mysqli_real_escape_string($conn, $_POST['shop_name']);
    $tel         = mysqli_real_escape_string($conn, $_POST['phone']);
    $address     = mysqli_real_escape_string($conn, $_POST['address']);
    $shop_detail = mysqli_real_escape_string($conn, $_POST['description']);

    $shop_id = $shop['shop_id'];

    // 1. อัปเดตข้อมูลร้านค้าในตาราง banquetshop (ใช้ shop_detail แทน description)
    $sql_update_shop = "UPDATE banquetshop SET 
                        shop_name = '$shop_name', 
                        address = '$address', 
                        shop_detail = '$shop_detail' 
                        WHERE shop_id = '$shop_id' AND provider_id = '$provider_id'";

    // 2. อัปเดตเบอร์โทรศัพท์ในตาราง serviceprovider (ใช้ tel)
    $sql_update_provider = "UPDATE serviceprovider SET 
                            tel = '$tel' 
                            WHERE provider_id = '$provider_id'";

    if ($conn->query($sql_update_shop) && $conn->query($sql_update_provider)) {
        echo "<script>alert('แก้ไขข้อมูลร้านค้าสำเร็จ'); window.location.href='provider_dashboard.php';</script>";
        exit();
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลร้านค้า</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #8b0000; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-save { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 15px; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; float: right; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <a href="provider_dashboard.php" class="btn-back">⬅ กลับ Dashboard</a>
    <h2>⚙️ แก้ไขข้อมูลร้านค้า</h2>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="edit_shop.php" method="POST">
        <div class="form-group">
            <label>ชื่อร้านค้า:</label>
            <input type="text" name="shop_name" value="<?php echo htmlspecialchars($shop['shop_name']); ?>" required>
        </div>
        <div class="form-group">
            <label>เบอร์โทรศัพท์:</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($shop['tel'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>ที่อยู่ร้านค้า:</label>
            <textarea name="address" rows="3" required><?php echo htmlspecialchars($shop['address'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>รายละเอียดร้านเพิ่มเติม:</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($shop['shop_detail'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn-save">บันทึกการเปลี่ยนแปลง</button>
    </form>
</div>
</body>
</html>