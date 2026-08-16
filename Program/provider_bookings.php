<?php
session_start();
require_once 'db.php';

// 1. ตรวจสอบ Session การเข้าสู่ระบบ
$provider_id = $_SESSION['provider_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

if ($provider_id <= 0) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.html';</script>";
    exit();
}

// 2. ดึงข้อมูลร้านค้าจาก provider_id
$sql_shop = "SELECT * FROM banquetshop WHERE provider_id = '$provider_id' LIMIT 1";
$res_shop = $conn->query($sql_shop);
$shop = $res_shop ? $res_shop->fetch_assoc() : null;

// Fallback: ถ้าไม่เจอ ให้ดึงร้านค้าแรกในระบบ
if (!$shop) {
    $res_shop_first = $conn->query("SELECT * FROM banquetshop ORDER BY shop_id ASC LIMIT 1");
    $shop = $res_shop_first ? $res_shop_first->fetch_assoc() : null;
}

if (!$shop) {
    echo "<script>alert('ไม่พบข้อมูลร้านค้าในระบบ'); window.location.href='provider_dashboard.php';</script>";
    exit();
}

$shop_id = $shop['shop_id'];

// 3. อัปเดตสถานะการจอง (อนุมัติ / ยกเลิก)
if (isset($_GET['action']) && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    $action = $_GET['action'];
    
    $new_status = '';
    if ($action === 'approve') {
        $new_status = 'confirmed';
    } elseif ($action === 'cancel') {
        $new_status = 'cancelled';
    }

    if ($new_status != '') {
        $stmt = $conn->prepare("UPDATE booking SET booking_status = ? WHERE booking_id = ? AND shop_id = ?");
        $stmt->bind_param("sii", $new_status, $booking_id, $shop_id);
        $stmt->execute();
        echo "<script>alert('อัปเดตสถานะการจองเรียบร้อยแล้ว'); window.location.href='provider_bookings.php';</script>";
        exit();
    }
}

// 4. ตรวจสอบคอลัมน์ตาราง customer อัตโนมัติ
$check_cust = $conn->query("SHOW TABLES LIKE 'customer'");
$has_cust = ($check_cust && $check_cust->num_rows > 0);

$cust_name_col = "b.customer_id"; 
$cust_phone_col = "''";

if ($has_cust) {
    $cols = $conn->query("SHOW COLUMNS FROM customer");
    $cust_cols = [];
    while ($c = $cols->fetch_assoc()) {
        $cust_cols[] = $c['Field'];
    }
    
    foreach (['fullname', 'customer_name', 'name', 'cus_name', 'username', 'first_name'] as $col) {
        if (in_array($col, $cust_cols)) {
            $cust_name_col = "c." . $col;
            break;
        }
    }
    
    foreach (['phone', 'telephone', 'tel', 'mobile'] as $col) {
        if (in_array($col, $cust_cols)) {
            $cust_phone_col = "c." . $col;
            break;
        }
    }
}

// 5. ดึงรายการจองสำหรับร้านค้านี้
$sql_bookings = "SELECT b.*, p.package_name, $cust_name_col as customer_name, $cust_phone_col as customer_phone 
                 FROM booking b
                 LEFT JOIN package p ON b.package_id = p.package_id ";
if ($has_cust) {
    $sql_bookings .= " LEFT JOIN customer c ON b.customer_id = c.customer_id ";
}
$sql_bookings .= " WHERE b.shop_id = '$shop_id' ORDER BY b.event_date ASC, b.event_time ASC";

$res_bookings = $conn->query($sql_bookings);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการจอง - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 1050px; margin: auto; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #8b0000; margin-top: 0; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; float: right; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: top; }
        th { background: #8b0000; color: white; }
        .badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .bg-warning { background: #ffc107; color: #212529; }
        .bg-success { background: #28a745; color: white; }
        .bg-danger { background: #dc3545; color: white; }
        .btn-approve { background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; margin-right: 4px; display: inline-block; }
        .btn-cancel { background: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; }
        .date-highlight { background: #fff3cd; font-weight: bold; padding: 3px 6px; border-radius: 3px; color: #856404; }
    </style>
</head>
<body>
<div class="container">
    <a href="provider_dashboard.php" class="btn-back">⬅ กลับ Dashboard</a>
    <h2>📅 รายการคิวงาน / การจองโต๊ะจีน</h2>
    <p><strong>ร้าน:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?> (Shop ID: <?php echo $shop_id; ?>)</p>

    <table>
        <thead>
            <tr>
                <th>วันที่จัดงาน & เวลา</th>
                <th>ข้อมูลผู้จอง (ลูกค้า)</th>
                <th>สิ่งที่สั่งจอง</th>
                <th>สถานที่จัดงาน</th>
                <th>ราคารวม</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($res_bookings && $res_bookings->num_rows > 0): ?>
            <?php while ($b = $res_bookings->fetch_assoc()): ?>
                <?php 
                // ดึงค่าสถานะมาทำความสะอาด (ตัวพิมพ์เล็ก + ตัดช่องว่าง)
                $status = trim(strtolower($b['booking_status'] ?? ''));
                ?>
                <tr>
                    <td>
                        <span class="date-highlight">📅 <?php echo date('d/m/Y', strtotime($b['event_date'])); ?></span><br>
                        ⏰ เวลา: <?php echo date('H:i', strtotime($b['event_time'])); ?> น.
                    </td>
                    <td>
                        👤 <strong><?php echo htmlspecialchars($b['customer_name'] ?? 'ลูกค้า ID: '.$b['customer_id']); ?></strong><br>
                        📞 เบอร์โทร: <?php echo htmlspecialchars($b['customer_phone'] ?? '-'); ?>
                    </td>
                    <td>
                        🍱 <strong><?php echo htmlspecialchars($b['package_name'] ?? 'แพ็กเกจ ID: '.$b['package_id']); ?></strong><br>
                        🪑 จำนวน: <strong><?php echo $b['table_count']; ?></strong> โต๊ะ
                    </td>
                    <td>📍 <?php echo nl2br(htmlspecialchars($b['event_address'] ?? 'ไม่ระบุ')); ?></td>
                    <td><strong><?php echo number_format($b['total_price'], 2); ?></strong> บาท</td>
                    <td>
                        <?php 
                        if ($status === 'confirmed' || $status === 'อนุมัติแล้ว') {
                            echo '<span class="badge bg-success">อนุมัติแล้ว</span>';
                        } elseif ($status === 'cancelled' || $status === 'ยกเลิก' || $status === 'ปฏิเสธ') {
                            echo '<span class="badge bg-danger">ปฏิเสธแล้ว</span>';
                        } elseif ($status === 'completed' || $status === 'เสร็จสิ้น') {
                            echo '<span class="badge bg-success" style="background:#17a2b8;">เสร็จสิ้น</span>';
                        } else {
                            echo '<span class="badge bg-warning">รอนุมัติ</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        // เช็กว่าถ้ายังไม่อนุมัติหรือปฏิเสธ ให้แสดงปุ่มจัดการ
                        if ($status === 'pending' || $status === 'รอนุมัติ' || $status === ''): 
                        ?>
                            <a href="provider_bookings.php?action=approve&booking_id=<?php echo $b['booking_id']; ?>" class="btn-approve" onclick="return confirm('ยืนยันอนุมัติงานนี้?');">อนุมัติ</a>
                            <a href="provider_bookings.php?action=cancel&booking_id=<?php echo $b['booking_id']; ?>" class="btn-cancel" onclick="return confirm('ยืนยันปฏิเสธการจองนี้?');">ปฏิเสธ</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center; padding: 25px; color: #777;">ยังไม่มีรายการจองเข้ามาในระบบของร้านนี้</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>