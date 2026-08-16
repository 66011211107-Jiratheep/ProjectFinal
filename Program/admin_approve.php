<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('สำหรับผู้ดูแลระบบเท่านั้น'); window.location.href='login.html';</script>";
    exit();
}

$sql = "SELECT b.*, COALESCE(p.provider_name, 'ไม่ระบุชื่อ') AS provider_name 
        FROM banquetshop b 
        LEFT JOIN serviceprovider p ON b.provider_id = p.provider_id 
        WHERE b.shop_status = 'รอนุมัติ' 
        ORDER BY b.upload_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติร้านค้า - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0; }
        .container { max-width: 1200px; background: #fff; padding: 20px; margin: 20px auto; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        /* ตกแต่งส่วน Header และปุ่มออกจากระบบ */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #8b0000;
        }
        .header-bar h2 { color: #8b0000; margin: 0; }
        .btn-logout {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-logout:hover { background-color: #bd2130; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background: #8b0000; color: white; }
        .btn-approve { background: #28a745; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; display: inline-block; }
        .btn-reject { background: #dc3545; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; margin-top: 5px; }
        .reject-box { display: none; margin-top: 8px; }
        .img-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ccc; margin: 2px; }
        .doc-link { display: block; margin-bottom: 4px; color: #0056b3; text-decoration: none; }
        .doc-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="container">
    <!-- แถบด้านบน: หัวข้อ + ปุ่มออกจากระบบ -->
    <div class="header-bar">
        <h2>รายการร้านโต๊ะจีนที่รอการอนุมัติ</h2>
        <a href="logout.php" class="btn-logout" onclick="return confirm('คุณต้องการออกจากระบบหรือไม่?');">
            🚪 ออกจากระบบ
        </a>
    </div>

    <table>
        <tr>
            <th style="width: 12%;">วันที่ส่งเรื่อง</th>
            <th style="width: 20%;">ข้อมูลร้าน / ผู้ประกอบการ</th>
            <th style="width: 25%;">รายละเอียดที่ตั้ง</th>
            <th style="width: 15%;">รูปภาพ / ผลงาน</th>
            <th style="width: 15%;">เอกสารแนบ</th>
            <th style="width: 13%;">จัดการ</th>
        </tr>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['upload_date'])); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['shop_name']); ?></strong><br>
                        <small style="color: #666;">โดย: <?php echo htmlspecialchars($row['provider_name']); ?></small>
                        <?php if(!empty($row['shop_detail'])): ?>
                            <p style="font-size: 12px; color: #444; margin-top: 5px;"><?php echo htmlspecialchars($row['shop_detail']); ?></p>
                        <?php endif; ?>
                    </td>
                    <td>
                        <p style="margin: 0 0 5px 0;"><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                        <p style="margin: 0 0 5px 0;"><strong>พื้นที่บริการ:</strong> <?php echo htmlspecialchars($row['service_area']); ?></p>
                        <p style="margin: 0; font-size: 12px; color: #666;">
                            <strong>พิกัด:</strong> <?php echo htmlspecialchars($row['latitude']); ?>, <?php echo htmlspecialchars($row['longitude']); ?>
                        </p>
                    </td>
                    <td>
                        <!-- แสดงโลโก้ร้าน -->
                        <?php if(!empty($row['shop_logo'])): ?>
                            <div><small><strong>โลโก้:</strong></small><br>
                            <a href="uploads/<?php echo $row['shop_logo']; ?>" target="_blank">
                                <img src="uploads/<?php echo $row['shop_logo']; ?>" class="img-thumb" alt="Logo">
                            </a></div>
                        <?php endif; ?>

                        <!-- แสดงรูปภาพผลงาน (หลายรูป) -->
                        <?php if(!empty($row['shop_portfolio'])): ?>
                            <div style="margin-top: 5px;"><small><strong>ผลงาน:</strong></small><br>
                            <?php 
                                $portfolios = explode(',', $row['shop_portfolio']);
                                foreach($portfolios as $img): 
                                    $img = trim($img);
                                    if(!empty($img)):
                            ?>
                                <a href="uploads/<?php echo $img; ?>" target="_blank">
                                    <img src="uploads/<?php echo $img; ?>" class="img-thumb" alt="Portfolio">
                                </a>
                            <?php 
                                    endif;
                                endforeach; 
                            ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- แสดงเอกสารแนบ (หลายไฟล์) -->
                        <strong style="font-size: 13px;"><?php echo htmlspecialchars($row['doc_name']); ?></strong>
                        <div style="margin-top: 5px;">
                        <?php 
                        if(!empty($row['doc_file'])): 
                            $docs = explode(',', $row['doc_file']);
                            $i = 1;
                            foreach($docs as $doc):
                                $doc = trim($doc);
                                if(!empty($doc)):
                        ?>
                            <a href="docs/<?php echo $doc; ?>" target="_blank" class="doc-link">
                                📄 ไฟล์ที่ <?php echo $i++; ?> (ดาวน์โหลด)
                            </a>
                        <?php 
                                endif;
                            endforeach;
                        else: 
                            echo "-";
                        endif; 
                        ?>
                        </div>
                    </td>
                    <td>
                        <a href="admin_process_approve.php?action=approve&id=<?php echo $row['shop_id']; ?>" 
                           class="btn-approve" 
                           onclick="return confirm('ยืนยันการอนุมัติร้านค้านี้?');">
                           ✅ อนุมัติ
                        </a>

                        <button class="btn-reject" onclick="toggleRejectBox(<?php echo $row['shop_id']; ?>)">❌ ไม่อนุมัติ</button>

                        <div id="reject-box-<?php echo $row['shop_id']; ?>" class="reject-box">
                            <form action="admin_process_approve.php" method="POST">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="shop_id" value="<?php echo $row['shop_id']; ?>">
                                <textarea name="reject_reason" placeholder="ระบุเหตุผล..." required style="width:100%; height:50px; margin-top:5px; box-sizing:border-box;"></textarea>
                                <button type="submit" class="btn-reject" style="width:100%;">ส่งคำขอแก้ไข</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px; color: #666;">ไม่มีรายการร้านค้าที่รออนุมัติ</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<script>
function toggleRejectBox(id) {
    var box = document.getElementById('reject-box-' + id);
    box.style.display = (box.style.display === 'block') ? 'none' : 'block';
}
</script>

</body>
</html>