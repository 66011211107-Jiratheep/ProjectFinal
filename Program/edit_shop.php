<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ผู้ใช้งาน
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

// รายการภูมิภาคสำหรับ Dropdown
$regions = [
    'ภาคเหนือ',
    'ภาคกลาง',
    'ภาคตะวันออกเฉียงเหนือ (อีสาน)',
    'ภาคใต้',
    'ทั่วประเทศ'
];

// บันทึกแก้ไขข้อมูล (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shop_name    = mysqli_real_escape_string($conn, $_POST['shop_name']);
    $tel          = mysqli_real_escape_string($conn, $_POST['phone']);
    $address      = mysqli_real_escape_string($conn, $_POST['address']);
    $service_area = mysqli_real_escape_string($conn, $_POST['service_area']);
    $shop_detail  = mysqli_real_escape_string($conn, $_POST['description']);
    $latitude     = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : "NULL";
    $longitude    = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : "NULL";

    $shop_id = $shop['shop_id'];
    $current_status = $shop['shop_status'];

    // ตรวจสอบสถานะ: หากโดนปฏิเสธให้เปลี่ยนเป็น 'รออนุมัติ' แต่ถ้า 'อนุมัติ' แล้ว ให้คงสถานะเดิมไว้
    if ($current_status == 'ไม่อนุมัติ' || $current_status == 'rejected') {
        $new_status = 'รออนุมัติ';
        $alert_msg  = 'บันทึกข้อมูลและยื่นขออนุมัติใหม่อีกครั้งเรียบร้อยแล้ว';
    } else {
        $new_status = $current_status;
        $alert_msg  = 'แก้ไขข้อมูลร้านค้าเรียบร้อยแล้ว';
    }

    // จัดการอัปโหลดโลโก้ร้าน (shop_logo)
    $shop_logo = $shop['shop_logo'];
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
        $ext = pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION);
        $shop_logo = 'logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
        move_uploaded_file($_FILES['shop_logo']['tmp_name'], 'uploads/' . $shop_logo);
    }

    // จัดการอัปโหลดผลงานร้าน (shop_portfolio)
    $shop_portfolio = $shop['shop_portfolio'];
    if (isset($_FILES['shop_portfolio']) && $_FILES['shop_portfolio']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
        $ext = pathinfo($_FILES['shop_portfolio']['name'], PATHINFO_EXTENSION);
        $shop_portfolio = 'port_' . time() . '_' . rand(100, 999) . '.' . $ext;
        move_uploaded_file($_FILES['shop_portfolio']['tmp_name'], 'uploads/' . $shop_portfolio);
    }

    // 1. อัปเดตข้อมูลร้านค้าในตาราง banquetshop
    $sql_update_shop = "UPDATE banquetshop SET 
                        shop_name = '$shop_name', 
                        address = '$address', 
                        service_area = '$service_area',
                        shop_detail = '$shop_detail',
                        latitude = $latitude,
                        longitude = $longitude,
                        shop_logo = " . ($shop_logo ? "'$shop_logo'" : "NULL") . ",
                        shop_portfolio = " . ($shop_portfolio ? "'$shop_portfolio'" : "NULL") . ",
                        shop_status = '$new_status',
                        reject_reason = NULL 
                        WHERE shop_id = '$shop_id' AND provider_id = '$provider_id'";

    // 2. อัปเดตเบอร์โทรศัพท์ในตาราง serviceprovider
    $sql_update_provider = "UPDATE serviceprovider SET 
                            tel = '$tel' 
                            WHERE provider_id = '$provider_id'";

    if ($conn->query($sql_update_shop) && $conn->query($sql_update_provider)) {
        echo "<script>
            alert('$alert_msg'); 
            window.location.href='provider_dashboard.php';
        </script>";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลร้านค้า</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 650px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #8b0000; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; }
        input[type="text"], textarea, select, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn-save { background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 15px; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-save:hover { background: #218838; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; float: right; font-size: 14px; }
        .reject-box { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .img-preview { max-width: 120px; max-height: 120px; margin-top: 8px; border-radius: 4px; border: 1px solid #ddd; display: block; }
        .row { display: flex; gap: 15px; }
        .col { flex: 1; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-edit-shop provider-ui">
<div class="container">
    <a href="provider_dashboard.php" class="btn-back">⬅ กลับ Dashboard</a>
    <h2>⚙️ แก้ไขข้อมูลร้านค้า</h2>

    <!-- แสดงเหตุผลการปฏิเสธ (ถ้ามี) -->
    <?php 
    $status = $shop['shop_status'] ?? '';
    if (($status == 'ไม่อนุมัติ' || $status == 'rejected') && !empty($shop['reject_reason'])): 
    ?>
        <div class="reject-box">
            <strong>⚠️ เหตุผลที่ไม่ผ่านการอนุมัติครั้งก่อน:</strong><br>
            <?php echo htmlspecialchars($shop['reject_reason']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="edit_shop.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>ชื่อร้านค้า:</label>
            <input type="text" name="shop_name" value="<?php echo htmlspecialchars($shop['shop_name']); ?>" required>
        </div>

        <div class="form-group">
            <label>เบอร์โทรศัพท์ผู้ประกอบการ:</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($shop['tel'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>ที่อยู่ร้านค้า:</label>
            <textarea name="address" rows="3" required><?php echo htmlspecialchars($shop['address'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label>พื้นที่ให้บริการ :</label>
            <select name="service_area" required>
                <option value="">-- เลือกพื้นที่ให้บริการ --</option>
                <?php foreach ($regions as $region): ?>
                    <option value="<?php echo $region; ?>" <?php echo (isset($shop['service_area']) && $shop['service_area'] == $region) ? 'selected' : ''; ?>>
                        <?php echo $region; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>รายละเอียดร้านเพิ่มเติม:</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($shop['shop_detail'] ?? ''); ?></textarea>
        </div>

        <div class="row">
            <div class="col form-group">
                <label>ละติจูด (Latitude):</label>
                <input type="text" name="latitude" value="<?php echo htmlspecialchars($shop['latitude'] ?? ''); ?>" placeholder="เช่น 13.756331">
            </div>
            <div class="col form-group">
                <label>ลองจิจูด (Longitude):</label>
                <input type="text" name="longitude" value="<?php echo htmlspecialchars($shop['longitude'] ?? ''); ?>" placeholder="เช่น 100.501762">
            </div>
        </div>

        <div class="form-group">
            <label>โลโก้ร้านค้า (Shop Logo):</label>
            <input type="file" name="shop_logo" accept="image/*">
            <?php if (!empty($shop['shop_logo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($shop['shop_logo']); ?>" class="img-preview">
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>ภาพผลงานร้านค้า (Shop Portfolio):</label>
            <input type="file" name="shop_portfolio" accept="image/*">
            <?php if (!empty($shop['shop_portfolio'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($shop['shop_portfolio']); ?>" class="img-preview">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-save">💾 บันทึกการเปลี่ยนแปลง</button>
    </form>
</div>
</body>
</html>