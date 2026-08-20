<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('สำหรับผู้ดูแลระบบเท่านั้น'); window.location.href='login.html';</script>";
    exit();
}

// ระบบจัดการสลับสถานะ (ระงับสิทธิ์ / เปิดใช้งาน)
if (isset($_GET['action']) && isset($_GET['type']) && isset($_GET['id'])) {
    $action = $_GET['action']; // 'ban' หรือ 'unban'
    $type   = $_GET['type'];   // 'customer', 'provider', 'shop'
    $id     = (int)$_GET['id'];

    if ($type === 'customer') {
        $status = ($action === 'ban') ? 'banned' : 'ปกติ';
        $sql_update = "UPDATE customer SET customer_status = '$status' WHERE customer_id = $id";
    } elseif ($type === 'provider') {
        // 🛠️ แก้ไขคอลัมน์เป็น provider_status และใช้ค่า 'banned' / 'ปกติ'
        $status = ($action === 'ban') ? 'banned' : 'ปกติ';
        $sql_update = "UPDATE serviceprovider SET provider_status = '$status' WHERE provider_id = $id";
    } elseif ($type === 'shop') {
        $status = ($action === 'ban') ? 'ระงับการใช้งาน' : 'อนุมัติแล้ว';
        $sql_update = "UPDATE banquetshop SET shop_status = '$status' WHERE shop_id = $id";
    }

    if (isset($sql_update) && $conn->query($sql_update)) {
        echo "<script>alert('อัปเดตสถานะเรียบร้อยแล้ว'); window.location.href='admin_manage_users.php?tab=$type';</script>";
        exit();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด: " . $conn->error . "'); window.location.href='admin_manage_users.php?tab=$type';</script>";
        exit();
    }
}

// กำหนด Tab ปัจจุบัน
$current_tab = $_GET['tab'] ?? 'customer';

// ดึงข้อมูลตาม Tab
$customers = $conn->query("SELECT * FROM customer ORDER BY customer_id DESC");
$providers = $conn->query("SELECT * FROM serviceprovider ORDER BY provider_id DESC");
$shops     = $conn->query("SELECT b.*, p.provider_name FROM banquetshop b LEFT JOIN serviceprovider p ON b.provider_id = p.provider_id ORDER BY b.shop_id DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งานและร้านค้า - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header-bar h2 { margin: 0; color: #2c3e50; }
        .btn-back { background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-back:hover { background: #5a6268; }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab-btn { padding: 10px 20px; text-decoration: none; color: #555; background: #e9ecef; border-radius: 5px 5px 0 0; font-weight: bold; }
        .tab-btn.active { background: #2c3e50; color: white; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        th { background: #2c3e50; color: white; }

        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-banned { background: #f8d7da; color: #721c24; }

        .btn-ban { background: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .btn-unban { background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; }
        .btn-ban:hover { background: #bd2130; }
        .btn-unban:hover { background: #218838; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-admin-users page-admin-table admin-ui">

<div class="container">
    <div class="header-bar">
        <h2>👤 จัดการผู้ใช้งานและร้านค้า</h2>
        <a href="admin_dashboard.php" class="btn-back">⬅️ กลับหน้าหลัก</a>
    </div>

    <!-- เมนูเปลี่ยน Tab -->
    <div class="tabs">
        <a href="admin_manage_users.php?tab=customer" class="tab-btn <?php echo ($current_tab == 'customer') ? 'active' : ''; ?>">
            👤 บัญชีลูกค้า (<?php echo $customers ? $customers->num_rows : 0; ?>)
        </a>
        <a href="admin_manage_users.php?tab=provider" class="tab-btn <?php echo ($current_tab == 'provider') ? 'active' : ''; ?>">
            💼 ผู้ประกอบการ (<?php echo $providers ? $providers->num_rows : 0; ?>)
        </a>
        <a href="admin_manage_users.php?tab=shop" class="tab-btn <?php echo ($current_tab == 'shop') ? 'active' : ''; ?>">
            🏪 รายชื่อร้านค้า (<?php echo $shops ? $shops->num_rows : 0; ?>)
        </a>
    </div>

    <!-- 1. ตารางลูกค้า -->
    <?php if ($current_tab == 'customer'): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>ชื่อ-นามสกุล</th>
                <th>อีเมล</th>
                <th>เบอร์โทร</th>
                <th>สถานะ</th>
                <th>การจัดการ</th>
            </tr>
            <?php while ($row = $customers->fetch_assoc()): ?>
                <?php $is_banned = ($row['customer_status'] ?? '') === 'banned'; ?>
                <tr>
                    <td><?php echo $row['customer_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['customer_name'] ?? 'ลูกค้า'); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['tel'] ?? '-'); ?></td>
                    <td>
                        <span class="badge <?php echo $is_banned ? 'badge-banned' : 'badge-active'; ?>">
                            <?php echo $is_banned ? '⛔ ถูกระงับ' : '✅ ปกติ'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($is_banned): ?>
                            <a href="admin_manage_users.php?action=unban&type=customer&id=<?php echo $row['customer_id']; ?>" class="btn-unban" onclick="return confirm('คืนสิทธิ์ให้ผู้ใช้งานนี้?');">ปลดล็อค</a>
                        <?php else: ?>
                            <a href="admin_manage_users.php?action=ban&type=customer&id=<?php echo $row['customer_id']; ?>" class="btn-ban" onclick="return confirm('ยืนยันระงับสิทธิ์บัญชีนี้?');">ระงับสิทธิ์</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

    <!-- 2. ตารางผู้ประกอบการ -->
    <?php if ($current_tab == 'provider'): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>ชื่อผู้ประกอบการ</th>
                <th>อีเมล</th>
                <th>เบอร์โทร</th>
                <th>สถานะ</th>
                <th>การจัดการ</th>
            </tr>
            <?php while ($row = $providers->fetch_assoc()): ?>
                <!-- 🛠️ ดึงสถานะจากคอลัมน์ provider_status -->
                <?php $is_banned = ($row['provider_status'] ?? '') === 'banned'; ?>
                <tr>
                    <td><?php echo $row['provider_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['provider_name'] ?? '-'); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['tel'] ?? '-'); ?></td>
                    <td>
                        <span class="badge <?php echo $is_banned ? 'badge-banned' : 'badge-active'; ?>">
                            <?php echo $is_banned ? '⛔ ถูกระงับ' : '✅ ปกติ'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($is_banned): ?>
                            <a href="admin_manage_users.php?action=unban&type=provider&id=<?php echo $row['provider_id']; ?>" class="btn-unban" onclick="return confirm('คืนสิทธิ์ผู้ประกอบการนี้?');">ปลดล็อค</a>
                        <?php else: ?>
                            <a href="admin_manage_users.php?action=ban&type=provider&id=<?php echo $row['provider_id']; ?>" class="btn-ban" onclick="return confirm('ยืนยันระงับสิทธิ์ผู้ประกอบการนี้?');">ระงับสิทธิ์</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

    <!-- 3. ตารางร้านค้า -->
    <?php if ($current_tab == 'shop'): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>ชื่อร้านโต๊ะจีน</th>
                <th>เจ้าของร้าน</th>
                <th>พื้นที่บริการ</th>
                <th>สถานะร้าน</th>
                <th>การจัดการ</th>
            </tr>
            <?php while ($row = $shops->fetch_assoc()): ?>
                <?php $is_banned = ($row['shop_status'] ?? '') === 'ระงับการใช้งาน'; ?>
                <tr>
                    <td><?php echo $row['shop_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($row['shop_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['provider_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['service_area'] ?? '-'); ?></td>
                    <td>
                        <span class="badge <?php echo $is_banned ? 'badge-banned' : 'badge-active'; ?>">
                            <?php echo htmlspecialchars($row['shop_status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($is_banned): ?>
                            <a href="admin_manage_users.php?action=unban&type=shop&id=<?php echo $row['shop_id']; ?>" class="btn-unban" onclick="return confirm('เปิดใช้งานร้านค้านี้อีกครั้ง?');">เปิดบริการ</a>
                        <?php else: ?>
                            <a href="admin_manage_users.php?action=ban&type=shop&id=<?php echo $row['shop_id']; ?>" class="btn-ban" onclick="return confirm('ยืนยันระงับร้านค้านี้?');">ระงับร้าน</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>

</div>

</body>
</html>