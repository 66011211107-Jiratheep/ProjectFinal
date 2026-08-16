<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อนทำการจอง'); window.location.href='login.html';</script>";
    exit();
}

$shop_id = isset($_GET['shop_id']) ? intval($_GET['shop_id']) : 0;

// 1. ดึงข้อมูลร้านค้า
$stmt = $conn->prepare("SELECT * FROM banquetshop WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$shop = $stmt->get_result()->fetch_assoc();

if (!$shop) {
    echo "<script>alert('ไม่พบข้อมูลร้านค้า'); window.location.href='index.php';</script>";
    exit();
}

// 2. ดึงรายการแพ็กเกจ
$stmt_pkg = $conn->prepare("SELECT * FROM package WHERE shop_id = ?");
$stmt_pkg->bind_param("i", $shop_id);
$stmt_pkg->execute();
$res_pkg = $stmt_pkg->get_result();

// 3. ดึงบริการเสริม (additional_service) ของร้านนี้
$stmt_srv = $conn->prepare("SELECT * FROM additional_service WHERE shop_id = ?");
$stmt_srv->bind_param("i", $shop_id);
$stmt_srv->execute();
$res_srv = $stmt_srv->get_result();

// 4. ดึงวันที่ถูกจองแล้ว
$booked_dates = [];
$stmt_booking = $conn->prepare("
    SELECT event_date, COUNT(*) as total_booking 
    FROM booking 
    WHERE shop_id = ? AND booking_status IN ('pending', 'confirmed', 'completed', 'รอนุมัติ', 'อนุมัติแล้ว') 
    GROUP BY event_date
");
$stmt_booking->bind_param("i", $shop_id);
$stmt_booking->execute();
$res_booking = $stmt_booking->get_result();

while ($b = $res_booking->fetch_assoc()) {
    $count = intval($b['total_booking']);
    if ($count >= 2) {
        $booked_dates[] = [
            'title' => '❌ เต็มแล้ว (2/2)',
            'start' => $b['event_date'],
            'color' => '#dc3545',
            'allDay' => true,
            'isFull' => true
        ];
    } else {
        $booked_dates[] = [
            'title' => '⚠️ จองแล้ว 1/2 คิว',
            'start' => $b['event_date'],
            'color' => '#ffc107',
            'textColor' => '#000000',
            'allDay' => true,
            'isFull' => false
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองโต๊ะจีน - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f7f3e9; padding: 20px; }
        .container-box { max-width: 800px; margin: 20px auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { color: #8b0000; margin-top: 0; text-align: center; }
        .btn-back { display: inline-block; margin-bottom: 15px; color: #666; text-decoration: none; font-weight: bold; }
        #calendar { margin-top: 20px; font-size: 14px; }
        .fc-daygrid-day { cursor: pointer; }
        .fc-daygrid-day:hover { background-color: #f0f8ff; }
        .instruction-badge { background: #e9ecef; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 15px; font-weight: 500; }
        #bookingFormSection { display: none; margin-top: 25px; border-top: 2px dashed #8b0000; padding-top: 20px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-weight: bold; margin-bottom: 6px; color: #333; }
        input[type="text"], input[type="number"], input[type="time"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-family: inherit; }
        .service-list { background: #fdf8f5; border: 1px solid #eedad0; padding: 12px; border-radius: 6px; }
        .service-item { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
        .service-item:last-child { margin-bottom: 0; }
        .selected-date-display { background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: bold; text-align: center; }
        .btn-submit { background: #8b0000; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; }
        .btn-submit:hover { background: #a00000; }
    </style>
</head>
<body>

<div class="container-box">
    <a href="shop_detail.php?shop_id=<?php echo $shop_id; ?>" class="btn-back">⬅ ย้อนกลับไปหน้าร้านค้า</a>
    <h2>📅 ปฏิทินเช็กวันว่าง - <?php echo htmlspecialchars($shop['shop_name']); ?></h2>
    
    <div class="instruction-badge">
        👉 คลิกเลือก <strong>"วันที่ว่าง"</strong> บนปฏิทินเพื่อเริ่มทำการจองโต๊ะจีน (รับสูงสุด 2 คิว/วัน)
    </div>

    <div id="calendar"></div>

    <div id="bookingFormSection">
        <h3 style="color: #8b0000; margin-top: 0;">📝 ฟอร์มรายละเอียดการจอง</h3>
        <div class="selected-date-display" id="dateText">คุณเลือกวันที่: -</div>

        <form action="booking_process.php" method="POST">
            <input type="hidden" name="shop_id" value="<?php echo $shop['shop_id']; ?>">
            <input type="hidden" name="event_date" id="event_date_input">

            <div class="form-group">
                <label>เลือกแพ็กเกจอาหารหลัก:</label>
                <select name="package_id" required>
                    <option value="">-- กรุณาเลือกแพ็กเกจ --</option>
                    <?php 
                    $res_pkg->data_seek(0);
                    while ($pkg = $res_pkg->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $pkg['package_id']; ?>">
                            <?php echo htmlspecialchars($pkg['package_name']); ?> (฿<?php echo number_format($pkg['price_per_table']); ?> / โต๊ะ)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- บริการเสริม ดึงจากตาราง additional_service -->
            <?php if ($res_srv && $res_srv->num_rows > 0): ?>
            <div class="form-group">
                <label>เลือกบริการเสริมเพิ่มเติม (ถ้าต้องการ):</label>
                <div class="service-list">
                    <?php while ($srv = $res_srv->fetch_assoc()): ?>
                        <div class="service-item">
                            <label style="font-weight: normal; cursor: pointer;">
                                <input type="checkbox" name="services[]" value="<?php echo $srv['service_id'] ?? $srv['id']; ?>"> 
                                <?php echo htmlspecialchars($srv['service_name'] ?? $srv['name']); ?>
                            </label>
                            <span style="color: #8b0000; font-weight: bold;">
                                +฿<?php echo number_format($srv['price']); ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>เวลาเริ่มจัดงาน:</label>
                <input type="time" name="event_time" required>
            </div>

            <div class="form-group">
                <label>จำนวนโต๊ะที่ต้องการ:</label>
                <input type="number" name="table_count" min="1" value="5" required>
            </div>

            <div class="form-group">
                <label>สถานที่จัดงาน:</label>
                <textarea name="event_address" rows="3" placeholder="ตัวอย่าง: บ้านเลขที่... หมู่... ตำบล... อำเภอ..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">ยืนยันการจองวันที่เลือก</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var bookedEvents = <?php echo json_encode($booked_dates); ?>;
    var today = new Date().toISOString().split('T')[0];

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'th',
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        events: bookedEvents,
        dateClick: function(info) {
            if (info.dateStr < today) {
                alert('❌ ไม่สามารถเลือกวันที่ผ่านมาแล้วได้ครับ');
                return;
            }

            var existingEvent = bookedEvents.find(function(event) {
                return event.start === info.dateStr;
            });

            if (existingEvent && existingEvent.isFull) {
                alert('❌ วันที่นี้ถูกจองเต็มแล้ว (2/2 คิว) กรุณาเลือกวันอื่นครับ');
                document.getElementById('bookingFormSection').style.display = 'none';
            } else {
                document.getElementById('bookingFormSection').style.display = 'block';
                document.getElementById('event_date_input').value = info.dateStr;
                
                var dateParts = info.dateStr.split('-');
                var extraMsg = (existingEvent && !existingEvent.isFull) ? ' (คิวที่ 2)' : '';
                document.getElementById('dateText').innerText = '✅ คุณเลือกวันที่จัดงาน: ' + dateParts[2] + '/' + dateParts[1] + '/' + (parseInt(dateParts[0]) + 543) + extraMsg;
                
                document.getElementById('bookingFormSection').scrollIntoView({ behavior: 'smooth' });
            }
        }
    });

    calendar.render();
});
</script>

</body>
</html>