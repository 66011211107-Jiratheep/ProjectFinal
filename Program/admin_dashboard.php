<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('สำหรับผู้ดูแลระบบเท่านั้น'); window.location.href='login.html';</script>";
    exit();
}
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
        .sidebar { width: 250px; height: 100vh; background: #2c3e50; position: fixed; color: #fff; padding-top: 20px; }
        .sidebar h3 { text-align: center; color: #ecf0f1; margin-bottom: 30px; }
        .sidebar a { display: block; color: #bdc3c7; padding: 15px 25px; text-decoration: none; font-size: 15px; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: #fff; border-left: 4px solid #e74c3c; }
        .main-content { margin-left: 250px; padding: 30px; }
        .header { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #e74c3c; text-align: center; }
        .card h4 { margin: 0 0 10px 0; color: #333; }
        .card p { color: #777; font-size: 14px; }
        .btn { display: inline-block; padding: 8px 16px; background: #e74c3c; color: #fff; text-decoration: none; border-radius: 4px; margin-top: 10px; font-size: 14px; }
        .btn:hover { background: #c0392b; }
    </style>
</head>
<body>

    <!-- แถบ เมนูด้านข้าง -->
    <div class="sidebar">
        <h3>Admin Control</h3>
        <a href="admin_dashboard.php" class="active">📌 หน้าแรกแอดมิน</a>
        <a href="admin_approve.php">🏪 อนุมัติ/ระงับร้านค้า (1.3.3.3)</a>
        <a href="admin_manage_users.php">👤 จัดการผู้ใช้งาน (1.3.3.2)</a>
        <a href="admin_manage_categories.php">🍲 จัดการประเภทโต๊ะจีน (1.3.3.6)</a>
        <a href="admin_manage_reviews.php">💬 จัดการรีวิว (1.3.3.4)</a>
        <a href="admin_transactions.php">💳 ตรวจสอบการชำระเงิน (1.3.3.5)</a>
        <a href="admin_profile.php">⚙️ ข้อมูลส่วนตัว (1.3.3.1)</a>
        <a href="logout.php" style="color: #e74c3c; margin-top: 30px;">🚪 ออกจากระบบ</a>
    </div>

    <!-- เนื้อหาหลัก -->
    <div class="main-content">
        <div class="header">
            <h2>ยินดีต้อนรับ ผู้ดูแลระบบ: <?php echo htmlspecialchars($_SESSION['name']); ?></h2>
            <p>เลือกเมนูการจัดการตามขอบเขตงานได้จากรายการด้านซ้ายมือ</p>
        </div>

        <div class="card-grid">
            <div class="card">
                <h4>ร้านโต๊ะจีน / ผู้ประกอบการ</h4>
                <p>อนุมัติร้านค้าใหม่ หรือระงับบัญชีร้านค้า</p>
                <a href="admin_approve.php" class="btn">ไปที่หน้าจัดการ</a>
            </div>
            <div class="card">
                <h4>บัญชีผู้ใช้งาน (ลูกค้า)</h4>
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