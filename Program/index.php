<?php
session_start();
require_once 'db.php';

// เช็กสถานะล็อกอิน
$is_logged_in = isset($_SESSION['role']) && $_SESSION['role'] === 'customer';

// ดึงเฉพาะร้านค้าที่ได้รับการ "อนุมัติ" จากแอดมินแล้วเท่านั้น
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM banquetshop WHERE shop_status = 'อนุมัติ'";

if (!empty($search)) {
    $search_clean = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (shop_name LIKE '%$search_clean%' OR service_area LIKE '%$search_clean%' OR address LIKE '%$search_clean%')";
}

$sql .= " ORDER BY shop_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โต๊ะจีนออนไลน์</title>
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

        /* Navbar ด้านบน */
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
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 20px;
            padding: 3px 12px;
            width: 380px;
        }
        .search-box input {
            border: none;
            outline: none;
            padding: 6px;
            width: 100%;
            border-radius: 20px;
            font-size: 14px;
        }
        .search-box button {
            background: none;
            border: none;
            cursor: pointer;
            color: #8b0000;
            font-size: 16px;
        }
        
        /* เมนูฝั่งขวาสำหรับคนยังไม่เข้าสู่ระบบ */
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
        .guest-nav a.active, .guest-nav a:hover {
            color: #ffffff;
        }

        /* Dropdown Profile สำหรับคนเข้าสู่ระบบแล้ว */
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

        /* ส่วนแสดงผลหลัก */
        .main-container {
            padding: 30px 60px;
        }
        .page-title {
            font-size: 20px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            margin-bottom: 25px;
            color: #222;
        }
        .shop-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }
        .shop-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-decoration: none;
            color: #333;
            transition: transform 0.2s;
        }
        .shop-card:hover {
            transform: translateY(-4px);
        }
        .shop-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background-color: #e2e8f0;
        }
        .shop-info {
            padding: 15px;
            text-align: center;
        }
        .shop-name {
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 6px;
            color: #111;
        }
        .shop-desc {
            font-size: 12px;
            color: #d90429;
            margin: 0;
        }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820-3">
</head>
<body class="page-home customer-ui">

    <!-- แถบ Header -->
    <div class="navbar">
        <a href="index.php" class="logo">โต๊ะจีนออนไลน์</a>

        <?php if (!$is_logged_in): ?>
            <ul class="guest-nav">
                <li><a href="index.php" class="active">หน้าหลัก</a></li>
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

    <section class="home-hero">
        <div class="hero-inner">
            <div class="hero-eyebrow">แพลตฟอร์มรวมผู้ประกอบการโต๊ะจีน</div>
            <h1 class="hero-title">ค้นหาโต๊ะจีนที่ใช่ สำหรับทุกงานสำคัญ</h1>
            <p class="hero-subtitle">ค้นหาร้านจากชื่อหรือพื้นที่บริการ แล้วเปรียบเทียบแพ็กเกจก่อนตัดสินใจจองได้ในที่เดียว</p>
            <form action="index.php" method="GET" class="hero-search">
                <input type="text" name="search" aria-label="ค้นหาร้านโต๊ะจีน" placeholder="ค้นหาร้านโต๊ะจีน หรือพื้นที่บริการ..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">ค้นหาร้าน</button>
            </form>
        </div>
    </section>

    <!-- รายการร้านค้า -->
    <div class="main-container">
        <div class="page-title-row">
            <div>
                <p class="page-kicker">ร้านที่ผ่านการอนุมัติ</p>
                <div class="page-title">ร้านโต๊ะจีนที่เปิดให้บริการ</div>
            </div>
            <div class="result-note"><?php echo isset($result) ? number_format($result->num_rows) : 0; ?> ร้าน</div>
        </div>

        <div class="shop-grid">
            <?php if (isset($result) && $result->num_rows > 0): ?>
                <?php while ($shop = $result->fetch_assoc()): ?>
                    <a href="shop_detail.php?shop_id=<?php echo $shop['shop_id']; ?>" class="shop-card">
                        
                        <!-- ปรับมาใช้ shop_logo ตามโครงสร้างตาราง -->
                        <?php if (!empty($shop['shop_logo'])): ?>
                            <img src="uploads/<?php echo $shop['shop_logo']; ?>" class="shop-img" alt="<?php echo htmlspecialchars($shop['shop_name']); ?>">
                        <?php else: ?>
                            <div class="shop-img" style="display:flex; align-items:center; justify-content:center; color:#888;">ไม่มีรูปภาพ</div>
                        <?php endif; ?>
                        
                        <div class="shop-info">
                            <div class="shop-name"><?php echo htmlspecialchars($shop['shop_name']); ?></div>
                            <!-- ปรับมาใช้ service_area แทน province -->
                            <p class="shop-desc">📍 <?php echo htmlspecialchars(!empty($shop['service_area']) ? $shop['service_area'] : 'ไม่ระบุพื้นที่'); ?></p>
                            <span class="shop-card-action">ดูรายละเอียดร้าน →</span>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    ไม่พบร้านที่ตรงกับคำค้นหา ลองค้นด้วยชื่อจังหวัดหรือพื้นที่บริการอื่น
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="dropdown.js?v=20260820-1"></script>
</body>
</html>