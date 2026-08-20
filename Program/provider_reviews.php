<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider' || !isset($_SESSION['provider_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.html';</script>";
    exit();
}

$provider_id = $_SESSION['provider_id'];

// ดึงข้อมูลร้านค้าของผู้ประกอบการ
$sql_shop = "SELECT * FROM banquetshop WHERE provider_id = '$provider_id' AND shop_status = 'อนุมัติ' LIMIT 1";
$res_shop = $conn->query($sql_shop);
$shop = $res_shop ? $res_shop->fetch_assoc() : null;

if (!$shop) {
    echo "<script>alert('ไม่พบข้อมูลร้านค้า'); window.location.href='provider_dashboard.php';</script>";
    exit();
}

$shop_id = $shop['shop_id'];

// คำนวณคะแนนเฉลี่ย
$avg_res = $conn->query("SELECT AVG(rating) as avg_score, COUNT(review_id) as total_reviews FROM review WHERE shop_id = '$shop_id'");
$stats = $avg_res ? $avg_res->fetch_assoc() : ['avg_score' => 0, 'total_reviews' => 0];

// เช็กคอลัมน์ชื่อลูกค้าอัตโนมัติ
$check_cust = $conn->query("SHOW TABLES LIKE 'customer'");
$cust_name_col = "c.customer_id";
if ($check_cust && $check_cust->num_rows > 0) {
    $cols = $conn->query("SHOW COLUMNS FROM customer");
    while ($c = $cols->fetch_assoc()) {
        if (in_array($c['Field'], ['fullname', 'name', 'cus_name', 'username'])) {
            $cust_name_col = "c." . $c['Field'];
            break;
        }
    }
}

// ดึงรายการรีวิวทั้งหมด
$sql_reviews = "SELECT r.*, $cust_name_col as customer_name 
                FROM review r
                LEFT JOIN customer c ON r.customer_id = c.customer_id
                WHERE r.shop_id = '$shop_id'
                ORDER BY r.review_date DESC";
$res_reviews = $conn->query($sql_reviews);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีวิวจากลูกค้า - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 850px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #8b0000; margin-top: 0; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; float: right; font-size: 14px; }
        .stat-box { background: #fff8e7; border: 1px solid #ffe8a1; padding: 15px 20px; border-radius: 6px; margin-bottom: 25px; display: flex; align-items: center; gap: 20px; }
        .rating-num { font-size: 38px; font-weight: bold; color: #d97706; }
        .stars-yellow { color: #f59e0b; font-size: 20px; }
        .review-card { border-bottom: 1px solid #eee; padding: 15px 0; }
        .review-card:last-child { border-bottom: none; }
        .reviewer-info { font-weight: bold; color: #333; display: flex; justify-content: space-between; margin-bottom: 5px; }
        .review-date { font-size: 12px; color: #888; font-weight: normal; }
        .comment-text { color: #444; margin: 8px 0; line-height: 1.5; }
        .review-img { max-width: 140px; max-height: 140px; border-radius: 6px; margin-top: 8px; border: 1px solid #ddd; }
    </style>
    <link rel="stylesheet" href="theme.css?v=20260820">
</head>
<body class="page-provider-reviews provider-ui">
<div class="container">
    <a href="provider_dashboard.php" class="btn-back">⬅ กลับ Dashboard</a>
    <h2>💬 รีวิวและความคิดเห็นจากลูกค้า</h2>
    <p><strong>ร้าน:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>

    <!-- สรุปคะแนนร้าน -->
    <div class="stat-box">
        <div class="rating-num"><?php echo number_format($stats['avg_score'] ?? 0, 1); ?></div>
        <div>
            <div class="stars-yellow">
                <?php 
                $score = round($stats['avg_score'] ?? 0);
                for($i=1; $i<=5; $i++) { echo $i <= $score ? '★' : '☆'; }
                ?>
            </div>
            <div style="color: #666; font-size: 14px; margin-top: 3px;">จากทั้งหมด <?php echo $stats['total_reviews']; ?> รีวิว</div>
        </div>
    </div>

    <!-- รายการรีวิว -->
    <h3>รายการรีวิวทั้งหมด</h3>
    <?php if ($res_reviews && $res_reviews->num_rows > 0): ?>
        <?php while ($r = $res_reviews->fetch_assoc()): ?>
            <div class="review-card">
                <div class="reviewer-info">
                    <span>👤 <?php echo htmlspecialchars($r['customer_name'] ?? 'ลูกค้า ID: '.$r['customer_id']); ?></span>
                    <span class="review-date">🕒 <?php echo date('d/m/Y H:i', strtotime($r['review_date'])); ?></span>
                </div>
                <div class="stars-yellow">
                    <?php for($i=1; $i<=5; $i++) { echo $i <= $r['rating'] ? '★' : '☆'; } ?>
                    <span style="color:#666; font-size:13px;">(<?php echo $r['rating']; ?>/5)</span>
                </div>
                <div class="comment-text">
                    <?php echo nl2br(htmlspecialchars($r['comment'] ?: 'ไม่มีข้อความความคิดเห็น')); ?>
                </div>
                <?php if (!empty($r['review_image'])): ?>
                    <a href="uploads/<?php echo $r['review_image']; ?>" target="_blank">
                        <img src="uploads/<?php echo $r['review_image']; ?>" class="review-img" alt="รูปรีวิว">
                    </a>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align: center; color: #888; padding: 30px;">ยังไม่มีรีวิวจากลูกค้าในขณะนี้</p>
    <?php endif; ?>
</div>
</body>
</html>