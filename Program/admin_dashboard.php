<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('สำหรับผู้ดูแลระบบเท่านั้น'); window.location.href='login.html';</script>";
    exit();
}

$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 1;

// 1. ดึงข้อมูลผู้ดูแลระบบจากตาราง admin (หรือปรับชื่อตารางให้ตรงกับ DB ของคุณ)
$sql_admin = "SELECT * FROM admin WHERE admin_id = '$admin_id'";
$result_admin = $conn->query($sql_admin);
$admin = ($result_admin && $result_admin->num_rows > 0) ? $result_admin->fetch_assoc() : null;

// กำหนดค่าเริ่มต้นถ้าหากไม่พบข้อมูลในตาราง
$admin_name  = $admin['admin_name'] ?? $_SESSION['name'] ?? $_SESSION['username'] ?? 'ผู้ดูแลระบบ';
$admin_email = $admin['email'] ?? 'admin@example.com';
$admin_tel   = $admin['tel'] ?? '-';
$admin_img   = !empty($admin['profile_img']) ? 'uploads/' . $admin['profile_img'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';

// 2. ดึงจำนวนร้านค้าที่รออนุมัติแบบเรียลไทม์
$sql_pending = "SELECT COUNT(*) AS pending_count FROM banquetshop WHERE shop_status IN ('รออนุมัติ', 'รอนุมัติ', 'pending')";
$result_pending = $conn->query($sql_pending);
$pending_count = $result_pending ? $result_pending->fetch_assoc()['pending_count'] : 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการผู้ดูแลระบบ (Admin Dashboard)</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; background-color: #f4f6f9; }
        
        /* Sidebar */
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; position: fixed; color: #fff; padding-top: 20px; }
        .sidebar h3 { text-align: center; color: #ecf0f1; margin-bottom: 30px; }
        .sidebar a { display: block; color: #bdc3c7; padding: 15px 25px; text-decoration: none; font-size: 15px; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: #fff; border-left: 4px solid #e74c3c; }
        
        /* Main Content */
        .main-content { margin-left: 250px; padding: 30px; }
        
        /* Header Profile Box */
        .header-profile { background: #fff; padding: 20px 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; }
        .profile-detail { display: flex; align-items: center; gap: 20px; }
        .profile-avatar { width: 75px; height: 75px; border-radius: 50%; object-fit: cover; border: 3px solid #e74c3c; }
        .profile-info h2 { margin: 0 0 5px 0; color: #2c3e50; font-size: 22px; }
        .profile-info p { margin: 2px 0; color: #666; font-size: 14px; }
        .badge-admin { background: #e74c3c; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        
        /* Card Grid */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #e74c3c; text-align: center; position: relative; }
        .card h4 { margin: 0 0 10px 0; color: #333; }
        .card p { color: #777; font-size: 14px; }
        .btn { display: inline-block; padding: 8px 16px; background: #e74c3c; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 10px; font-size: 14px; }
        .btn:hover { background: #c0392b; }
        
        /* Badge Count สำหรับร้านค้ารออนุมัติ */
        .badge-pending { background: #e74c3c; color: white; border-radius: 12px; padding: 2px 8px; font-size: 12px; margin-left: 5px; font-weight: bold; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-admin-dashboard admin-ui">

    <!-- แถบ เมนูด้านข้าง -->
    <div class="sidebar">
        <h3>Admin Control</h3>
        <a href="admin_dashboard.php" class="active">📌 หน้าแรกแอดมิน</a>
        <a href="admin_approve.php">
            🏪 อนุมัติ/ระงับร้านค้า 
            <?php if ($pending_count > 0): ?>
                <span class="badge-pending"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_manage_users.php">👤 จัดการผู้ใช้งาน</a>
        <a href="admin_manage_categories.php">🍲 จัดการประเภทโต๊ะจีน (1.3.3.6)</a>
        <a href="admin_manage_reviews.php">💬 จัดการรีวิว (1.3.3.4)</a>
        <a href="admin_transactions.php">💳 ตรวจสอบการชำระเงิน (1.3.3.5)</a>
        <a href="admin_profile.php">⚙️ ข้อมูลส่วนตัว (1.3.3.1)</a>
        <a href="logout.php" style="color: #e74c3c; margin-top: 30px;" onclick="return confirm('ยืนยันออกจากระบบ?');">🚪 ออกจากระบบ</a>
    </div>

    <!-- เนื้อหาหลัก -->
    <div class="main-content">
        <!-- แสดง Profile Admin และคำต้อนรับ -->
        <div class="header-profile">
            <div class="profile-detail">
                <img src="<?php echo htmlspecialchars($admin_img); ?>" alt="Admin Profile" class="profile-avatar">
                <div class="profile-info">
                    <h2>ยินดีต้อนรับ: <?php echo htmlspecialchars($admin_name); ?> <span class="badge-admin">Admin</span></h2>
                    
                    <p style="color: #888; font-size: 13px;">จัดการข้อมูลและตรวจสอบสิทธิ์ต่างๆ ของระบบได้จากเมนูด้านซ้าย</p>
                </div>
            </div>
            <div>
                <a href="admin_profile.php" class="btn" style="background: #34495e;">⚙️ แก้ไขโปรไฟล์</a>
            </div>
        </div>

        <!-- การ์ด เมนูหลัก -->
        <div class="card-grid">
            <div class="card">
                <h4>
                    ร้านโต๊ะจีน / ผู้ประกอบการ
                    <?php if ($pending_count > 0): ?>
                        <span class="badge-pending"><?php echo $pending_count; ?> รออนุมัติ</span>
                    <?php endif; ?>
                </h4>
                <p>อนุมัติร้านค้าใหม่ ตรวจสอบเอกสาร หรือระงับบัญชีร้านค้า</p>
                <a href="admin_approve.php" class="btn">ไปที่หน้าจัดการ</a>
            </div>
            <div class="card">
                <h4>บัญชีผู้ใช้งาน </h4>
                <p>ตรวจสอบและระงับสิทธิ์การใช้งานบัญชีลูกค้า</p>
                <a href="admin_manage_users.php" class="btn">ไปที่หน้าจัดการ</a>
            </div>
            <div class="card">
                <h4>ประเภทโต๊ะจีน</h4>
                <p>เพิ่ม แก้ไข หรือลบหมวดหมู่/ประเภทโต๊ะจีน</p>
                <a href="admin_manage_categories.php" class="btn">ไปที่หน้าจัดการ</a>
            </div>
            <div class="card">
                <h4>ธุรกรรมการชำระเงิน</h4>
                <p>ตรวจสอบประวัติและสลิปการชำระเงินในระบบ</p>
                <a href="admin_transactions.php" class="btn">ไปที่หน้าจัดการ</a>
            </div>
        </div>
    </div>

</body>
</html>