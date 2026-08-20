<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.html");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$customer_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? 0;

// ดึงรายละเอียดการจอง
$stmt = $conn->prepare("
    SELECT b.*, s.shop_name 
    FROM booking b
    LEFT JOIN banquetshop s ON b.shop_id = s.shop_id
    WHERE b.booking_id = ? AND b.customer_id = ?
");
$stmt->bind_param("ii", $booking_id, $customer_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "<script>alert('ไม่พบรายการจอง'); window.location.href='my_bookings.php';</script>";
    exit();
}

$booking = $res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ชำระเงิน - การจอง #<?php echo $booking['booking_id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f7f3e9; margin: 0; padding: 0; }
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { color: #8b0000; text-align: center; margin-bottom: 20px; }
        .info-box { background: #f9f9f9; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 15px; line-height: 1.6; }
        .price { font-size: 22px; color: #d90429; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn-submit { background: #27ae60; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer; }
        .btn-submit:hover { background: #219150; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-payment customer-workspace">

<div class="container">
    <h2>💳 ชำระเงินการจอง</h2>

    <div class="info-box">
        <div><strong>รหัสการจอง:</strong> #<?php echo $booking['booking_id']; ?></div>
        <div><strong>ร้านค้า:</strong> <?php echo htmlspecialchars($booking['shop_name']); ?></div>
        <div><strong>วันที่จัดงาน:</strong> <?php echo date('d/m/Y', strtotime($booking['event_date'])); ?></div>
        <hr style="border: 0; border-top: 1px solid #ddd; margin: 10px 0;">
        <div><strong>ยอดชำระทั้งสิ้น:</strong> <span class="price">฿<?php echo number_format($booking['total_price'], 2); ?></span></div>
    </div>

    <form action="payment_process.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">

        <div class="form-group">
            <label for="slip">แนบหลักฐานการโอนเงิน (สลิป):</label>
            <input type="file" name="slip" id="slip" accept="image/*" required>
        </div>

        <button type="submit" class="btn-submit">ยืนยันการชำระเงิน</button>
    </form>

    <a href="my_bookings.php" class="btn-back">ยกเลิก / กลับไปหน้าประวัติการจอง</a>
</div>

</body>
</html>