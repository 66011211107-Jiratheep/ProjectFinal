<?php
session_start();
require_once 'db.php';

// 1. ตรวจสอบสิทธิ์การเข้าใช้งานของผู้ประกอบการ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider' || !isset($_SESSION['provider_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบในฐานะผู้ประกอบการก่อน'); window.location.href='login.html';</script>";
    exit();
}

$provider_id = $_SESSION['provider_id'];

// 2. ดึงข้อมูลร้านค้าของผู้ประกอบการ
$sql = "SELECT * FROM banquetshop WHERE provider_id = '$provider_id' ORDER BY shop_id DESC LIMIT 1";
$result = $conn->query($sql);

if (!$result) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $conn->error);
}

$shop = $result->fetch_assoc();

// 3. ตรวจสอบว่ามีข้อมูลร้านค้าหรือไม่
if (!$shop) {
    echo "<script>
        alert('ไม่พบข้อมูลร้านค้าสำหรับ Provider ID: " . $provider_id . "\\nกรุณากรอกข้อมูลลงทะเบียนร้านค้าก่อนครับ'); 
        window.location.href='add_shop.php';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการร้านค้า - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 20px; }
        .container { max-width: 950px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 14px; }
        .approved { background-color: #d4edda; color: #155724; }
        .pending { background-color: #fff3cd; color: #856404; }
        .rejected { background-color: #f8d7da; color: #721c24; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 25px; }
        .menu-card { background: #fafafa; border: 1px solid #ddd; padding: 20px 15px; border-radius: 6px; text-align: center; text-decoration: none; color: #333; font-weight: bold; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .menu-card:hover { background: #8b0000; color: #fff; border-color: #8b0000; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>ร้าน: <?php echo htmlspecialchars($shop['shop_name']); ?></h2>
            <p>ยินดีต้อนรับ คุณ <?php echo htmlspecialchars($_SESSION['name'] ?? 'ผู้ประกอบการ'); ?></p>
        </div>
        <div>
            <?php if ($shop['shop_status'] == 'อนุมัติ'): ?>
                <span class="status-badge approved">✅ อนุมัติแล้ว</span>
            <?php elseif ($shop['shop_status'] == 'ไม่อนุมัติ'): ?>
                <span class="status-badge rejected">❌ ไม่อนุมัติ</span>
            <?php else: ?>
                <span class="status-badge pending">⏳ รออนุมัติ</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- แสดงเหตุผลกรณีไม่อนุมัติ -->
    <?php if ($shop['shop_status'] == 'ไม่อนุมัติ'): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; margin-top:15px; border-radius:5px;">
            <strong>เหตุผลที่ไม่ผ่านการอนุมัติ:</strong> 
            <?php echo htmlspecialchars($shop['reject_reason'] ?? 'ไม่ระบุเหตุผล'); ?>
        </div>
    <?php endif; ?>

    <!-- แสดงเมนูเมื่อได้รับการอนุมัติเรียบร้อย -->
    <?php if ($shop['shop_status'] == 'อนุมัติ'): ?>
        <h3 style="margin-top:25px;">เมนูจัดการร้านค้า</h3>
        <div class="menu-grid">
            <a href="manage_packages.php" class="menu-card">🍱 จัดการแพ็กเกจโต๊ะจีน</a>
            <a href="manage_menu.php" class="menu-card">🍲 จัดการอาหาร / บริการเสริม</a>
            <a href="provider_bookings.php" class="menu-card">📅 ตรวจสอบการจอง</a>
            <a href="provider_reviews.php" class="menu-card">💬 รีวิวจากลูกค้า</a>
            <a href="edit_shop.php" class="menu-card">⚙️ แก้ไขข้อมูลร้านค้า</a>
        </div>
    <?php else: ?>
        <div style="background:#e9ecef; padding:20px; border-radius:6px; margin-top:20px; text-align:center;">
            <p style="margin:0; color:#555; font-size:16px;">
                ขณะนี้ข้อมูลร้านค้าของคุณอยู่ในขั้นตอนการตรวจสอบโดยผู้ดูแลระบบ<br>
                ระบบจะเปิดใช้งานเมนูจัดการร้านค้าเมื่อได้รับการอนุมัติเรียบร้อยแล้ว
            </p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: right;">
        <a href="logout.php" class="btn-logout">ออกจากระบบ</a>
    </div>
</div>

</body>
</html>