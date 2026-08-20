<?php
session_start();
require_once 'db.php';

// เช็กสถานะล็อกอิน
$is_logged_in = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

// รับค่า shop_id จาก URL
$shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : 0;

if ($shop_id <= 0) {
    header("Location: index.php");
    exit();
}

// 1. ดึงข้อมูลรายละเอียดร้านค้า + JOIN เอาเบอร์โทร (tel) จากตาราง serviceprovider
$stmt = $conn->prepare("
    SELECT b.*, p.tel 
    FROM banquetshop b 
    LEFT JOIN serviceprovider p ON b.provider_id = p.provider_id 
    WHERE b.shop_id = ? AND b.shop_status = 'อนุมัติ'
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$res_shop = $stmt->get_result();

if ($res_shop->num_rows === 0) {
    echo "<script>alert('ไม่พบข้อมูลร้านค้า'); window.location.href='index.php';</script>";
    exit();
}

$shop = $res_shop->fetch_assoc();

// 2. ดึงรายการแพ็กเกจจากตาราง `package`
$stmt_pkg = $conn->prepare("SELECT * FROM package WHERE shop_id = ? ORDER BY price_per_table ASC");
$stmt_pkg->bind_param("i", $shop_id);
$stmt_pkg->execute();
$res_pkg = $stmt_pkg->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($shop['shop_name']); ?> - โต๊ะจีนออนไลน์</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f7f3e9;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', sans-serif;
        }

        .navbar {
            background-color: #8b0000;
            color: white;
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo {
            font-size: 18px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        .guest-nav {
            display: flex;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .guest-nav a {
            color: #ffcc00;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
        }
        .guest-nav a:hover {
            color: #ffffff;
        }

        .user-menu {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .user-name {
            font-weight: bold;
            color: white;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ffffff;
            min-width: 150px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            border-radius: 4px;
            overflow: hidden;
            z-index: 1000;
        }
        .dropdown-content a {
            color: #333;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 14px;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .user-menu:hover .dropdown-content {
            display: block;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* ตกแต่งปุ่มกดกลับ */
        .btn-back {
            display: inline-flex;
            align-items: center;
            color: #666;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 15px;
            transition: color 0.2s;
        }
        .btn-back:hover {
            color: #8b0000;
        }

        .shop-header-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .shop-detail-img {
            width: 40%;
            min-width: 300px;
            height: 280px;
            object-fit: cover;
            background-color: #e2e8f0;
        }
        .no-img {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-size: 16px;
        }
        .shop-detail-info {
            padding: 30px;
            flex: 1;
        }
        .shop-detail-title {
            font-size: 24px;
            font-weight: bold;
            color: #8b0000;
            margin-bottom: 10px;
        }
        .shop-meta {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .btn-booking {
            display: inline-block;
            background-color: #8b0000;
            color: white;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            border: none;
            cursor: pointer;
            margin-top: 15px;
            transition: background 0.2s;
        }
        .btn-booking:hover {
            background-color: #a00000;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #222;
            border-bottom: 2px solid #8b0000;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .package-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
        }
        .package-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #eee;
        }
        .package-body {
            padding: 20px;
        }
        .package-name {
            font-size: 18px;
            font-weight: bold;
            color: #111;
            margin-bottom: 8px;
        }
        .package-price {
            font-size: 20px;
            font-weight: bold;
            color: #d90429;
            margin-bottom: 12px;
        }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820-3">
</head>
<body class="page-shop-detail customer-ui">

    <div class="navbar">
        <a href="index.php" class="logo">โต๊ะจีนออนไลน์</a>

        <?php if (!$is_logged_in): ?>
            <ul class="guest-nav">
                <li><a href="index.php">หน้าหลัก</a></li>
                <li><a href="register.html">สมัครสมาชิก</a></li>
                <li><a href="login.html">เข้าสู่ระบบ</a></li>
            </ul>
        <?php else: ?>
            <div class="user-menu">
                <button type="button" class="user-name" aria-haspopup="true" aria-expanded="false">👤 <?php echo htmlspecialchars($_SESSION['name'] ?? 'ลูกค้า'); ?> ▾</button>
                <div class="dropdown-content">
                    <a href="profile.php">บัญชีของฉัน</a>
                    <a href="my_bookings.php">ประวัติการจอง</a>
                    <a href="logout.php" style="color: #dc3545;">ออกจากระบบ</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <!-- ปุ่มกดกลับหน้าหลัก -->
        <a href="index.php" class="btn-back">⬅ ย้อนกลับไปหน้าหลัก</a>

        <div class="shop-header-card">
            <?php if (!empty($shop['shop_logo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($shop['shop_logo']); ?>" class="shop-detail-img" alt="<?php echo htmlspecialchars($shop['shop_name']); ?>">
            <?php else: ?>
                <div class="shop-detail-img no-img">ไม่มีรูปภาพร้านค้า</div>
            <?php endif; ?>

            <div class="shop-detail-info">
                <span class="verified-badge">✓ ร้านผ่านการอนุมัติ</span>
                <div class="shop-detail-title"><?php echo htmlspecialchars($shop['shop_name']); ?></div>
                
                <div class="shop-meta">📍 <strong>ที่อยู่/พื้นที่บริการ:</strong> <?php echo htmlspecialchars(!empty($shop['address']) ? $shop['address'] : ($shop['service_area'] ?? 'ไม่ระบุ')); ?></div>
                <div class="shop-meta">📞 <strong>เบอร์โทรศัพท์:</strong> <?php echo htmlspecialchars(!empty($shop['tel']) ? $shop['tel'] : 'ไม่ระบุ'); ?></div>
                <div class="shop-meta">📝 <strong>รายละเอียดร้าน:</strong> <?php echo nl2br(htmlspecialchars(!empty($shop['shop_detail']) ? $shop['shop_detail'] : 'ไม่มีรายละเอียด')); ?></div>

                <?php if ($is_logged_in): ?>
                    <a href="booking.php?shop_id=<?php echo $shop['shop_id']; ?>" class="btn-booking">จองโต๊ะจีนร้านนี้</a>
                <?php else: ?>
                    <button onclick="alert('กรุณาเข้าสู่ระบบก่อนทำการจองโต๊ะจีน'); window.location.href='login.html';" class="btn-booking">
                        จองโต๊ะจีนร้านนี้
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-title">รายการชุดอาหาร / แพ็กเกจ</div>
        <div class="package-grid">
            <?php if ($res_pkg && $res_pkg->num_rows > 0): ?>
                <?php while ($pkg = $res_pkg->fetch_assoc()): ?>
                    <div class="package-card">
                        <?php if (!empty($pkg['package_image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($pkg['package_image']); ?>" class="package-img" alt="<?php echo htmlspecialchars($pkg['package_name']); ?>">
                        <?php endif; ?>
                        <div class="package-body">
                            <div class="package-name"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                            <div class="package-price">฿<?php echo number_format($pkg['price_per_table'], 2); ?> / โต๊ะ</div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    ร้านนี้ยังไม่มีข้อมูลแพ็กเกจอาหาร
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="dropdown.js?v=20260820-1"></script>
</body>
</html>