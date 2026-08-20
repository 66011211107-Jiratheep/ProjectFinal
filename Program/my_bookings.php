<?php
session_start();
require_once 'db.php';

// เช็กว่าล็อกอินหรือยัง
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.html");
    exit();
}

$customer_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? 0;

// ดึงข้อมูลการจองของลูกค้าคนนี้
$stmt = $conn->prepare("
    SELECT b.*, s.shop_name, p.package_name 
    FROM booking b
    LEFT JOIN banquetshop s ON b.shop_id = s.shop_id
    LEFT JOIN package p ON b.package_id = p.package_id
    WHERE b.customer_id = ?
    ORDER BY b.booking_id DESC
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการจอง - โต๊ะจีนออนไลน์</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f7f3e9; margin: 0; padding: 0; }
        .navbar { background-color: #8b0000; color: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; }
        .container { max-width: 1050px; margin: 30px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { color: #8b0000; margin-bottom: 20px; border-bottom: 2px solid #8b0000; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; vertical-align: middle; }
        th { background-color: #f4f4f4; color: #333; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; display: inline-block; }
        .badge-pending { background-color: #ffeaa7; color: #d63031; }
        .badge-approved { background-color: #d4edda; color: #155724; }
        .badge-paid { background-color: #28a745; color: white; }
        .badge-rejected { background-color: #f8d7da; color: #721c24; }
        
        /* ปุ่มชำระเงิน */
        .btn-pay {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: background 0.2s;
        }
        .btn-pay:hover { background-color: #218838; }
        .btn-back { display: inline-block; margin-bottom: 15px; color: #666; text-decoration: none; font-weight: bold; }
        .btn-back:hover { color: #8b0000; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-my-bookings customer-workspace">

    <div class="navbar">
        <a href="index.php">โต๊ะจีนออนไลน์</a>
        <span>👤 <?php echo htmlspecialchars($_SESSION['name'] ?? 'ลูกค้า'); ?></span>
    </div>

    <div class="container">
        <a href="index.php" class="btn-back">⬅ ย้อนกลับไปหน้าหลัก</a>
        <h2>📋 ประวัติการจองโต๊ะจีนของฉัน</h2>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>รหัสการจอง</th>
                        <th>ชื่อร้านค้า</th>
                        <th>แพ็กเกจ</th>
                        <th>วันที่จัดงาน</th>
                        <th>จำนวนโต๊ะ</th>
                        <th>ราคารวม</th>
                        <th>สถานะการจอง</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['booking_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['shop_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['package_name']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['event_date'])); ?> (<?php echo htmlspecialchars($row['event_time']); ?>)</td>
                            <td><?php echo $row['table_count']; ?> โต๊ะ</td>
                            <td>฿<?php echo number_format($row['total_price'], 2); ?></td>
                            <td>
                                <?php 
                                    $status = strtolower(trim($row['booking_status']));
                                    
                                    if ($status === 'pending' || $status === 'รออนุมัติ') {
                                        echo '<span class="badge badge-pending">⏳ รอร้านค้าอนุมัติ</span>';
                                    } elseif (in_array($status, ['approved', 'confirmed', 'อนุมัติ', 'อนุมัติแล้ว'])) {
                                        echo '<span class="badge badge-approved">อนุมัติแล้ว (รอชำระเงิน)</span>';
                                    } elseif (in_array($status, ['paid', 'ชำระเงินแล้ว'])) {
                                        echo '<span class="badge badge-paid">✔ ชำระเงินเรียบร้อย</span>';
                                    } else {
                                        echo '<span class="badge badge-rejected">' . htmlspecialchars($row['booking_status']) . '</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    $status = strtolower(trim($row['booking_status']));
                                    if (in_array($status, ['approved', 'confirmed', 'อนุมัติ', 'อนุมัติแล้ว'])): 
                                ?>
                                    <a href="payment.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-pay">💳 ชำระเงิน</a>
                                <?php elseif (in_array($status, ['paid', 'ชำระเงินแล้ว'])): ?>
                                    <span style="color: #28a745; font-weight: bold; font-size: 13px;">เสร็จสิ้น</span>
                                <?php else: ?>
                                    <span style="color: #aaa; font-size: 13px;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #777; padding: 40px 0;">คุณยังไม่มีประวัติการจองโต๊ะจีน</p>
        <?php endif; ?>
    </div>

</body>
</html>