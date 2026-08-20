<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('สำหรับผู้ดูแลระบบเท่านั้น'); window.location.href='login.html';</script>";
    exit();
}

$admin_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1; // ดึง ID Admin จาก Session

// บันทึกการแก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // เช็ครหัสผ่านตรงกันหรือไม่ (กรณีมีการกรอกเปลี่ยนรหัส)
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            echo "<script>alert('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');</script>";
        } else {
            // อัปเดตรวมรหัสผ่านใหม่ (แนะนำใช้วิธี Hash หรือเปลี่ยนเป็น password = '$new_password' หากระบบเดิมไม่ได้ hash)
            $hashed_pwd = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update = "UPDATE admin SET fullname = '$fullname', username = '$username', password = '$hashed_pwd' WHERE admin_id = '$admin_id'";
            
            if ($conn->query($sql_update)) {
                $_SESSION['fullname'] = $fullname;
                echo "<script>alert('แก้ไขข้อมูลและรหัสผ่านเรียบร้อยแล้ว'); window.location.href='admin_profile.php';</script>";
                exit();
            }
        }
    } else {
        // อัปเดตเฉพาะชื่อและ Username (ไม่เปลี่ยนรหัสผ่าน)
        $sql_update = "UPDATE admin SET fullname = '$fullname', username = '$username' WHERE admin_id = '$admin_id'";
        if ($conn->query($sql_update)) {
            $_SESSION['fullname'] = $fullname;
            echo "<script>alert('แก้ไขข้อมูลเรียบร้อยแล้ว'); window.location.href='admin_profile.php';</script>";
            exit();
        }
    }
}

// ดึงข้อมูล Admin ปัจจุบันมาแสดง
$sql = "SELECT * FROM admin WHERE admin_id = '$admin_id'";
$result = $conn->query($sql);
$admin_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลส่วนตัว - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header-bar h2 { margin: 0; color: #2c3e50; }
        .btn-back { background: #6c757d; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 14px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-radius: 4px; box-sizing: border-box; }
        
        .section-title { margin-top: 25px; font-size: 14px; color: #e67e22; border-bottom: 1px dashed #ccc; padding-bottom: 5px; }

        .btn-submit { width: 100%; background: #28a745; color: white; border: none; padding: 12px; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 15px; }
        .btn-submit:hover { background: #218838; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-admin-profile page-admin-table admin-ui">

<div class="container">
    <div class="header-bar">
        <h2>⚙️ แก้ไขข้อมูลส่วนตัว</h2>
        <a href="admin_dashboard.php" class="btn-back">⬅️ กลับหน้าหลัก</a>
    </div>

    <form action="admin_profile.php" method="POST">
        <div class="form-group">
            <label>ชื่อ-นามสกุล / ชื่อแสดงผล:</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($admin_data['fullname'] ?? 'ผู้ดูแลระบบ'); ?>" required>
        </div>

        <div class="form-group">
            <label>ชื่อผู้ใช้งาน (Username):</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($admin_data['username'] ?? 'admin'); ?>" required>
        </div>

        <div class="section-title">🔑 เปลี่ยนรหัสผ่าน (หากไม่ต้องการเปลี่ยน ให้ปล่อยว่างไว้)</div>

        <div class="form-group" style="margin-top: 10px;">
            <label>รหัสผ่านใหม่:</label>
            <input type="password" name="new_password" placeholder="ป้อนรหัสผ่านใหม่...">
        </div>

        <div class="form-group">
            <label>ยืนยันรหัสผ่านใหม่:</label>
            <input type="password" name="confirm_password" placeholder="ป้อนรหัสผ่านใหม่อีกครั้ง...">
        </div>

        <button type="submit" class="btn-submit">💾 บันทึกการแก้ไข</button>
    </form>
</div>

</body>
</html>