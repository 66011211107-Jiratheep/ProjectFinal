<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = intval($_POST['booking_id']);

    if ($booking_id <= 0) {
        echo "<script>alert('ข้อมูลไม่ถูกต้อง'); window.location.href='my_bookings.php';</script>";
        exit();
    }

    // จัดการอัปโหลดรูปภาพสลิป
    $slip_filename = '';
    if (isset($_FILES['slip']) && $_FILES['slip']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
        $slip_filename = 'slip_' . $booking_id . '_' . time() . '.' . $ext;
        $target_dir = 'uploads/';
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        move_uploaded_file($_FILES['slip']['tmp_name'], $target_dir . $slip_filename);
    }

    // อัปเดตสถานะในตาราง booking เป็น paid หรือ ชำระเงินแล้ว
    $stmt = $conn->prepare("UPDATE booking SET booking_status = 'paid' WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);

    if ($stmt->execute()) {
        echo "<script>
            alert('🎉 ชำระเงินเรียบร้อยแล้ว! ร้านค้าจะตรวจสอบหลักฐาน');
            window.location.href = 'my_bookings.php';
        </script>";
    } else {
        echo "<script>
            alert('❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            window.history.back();
        </script>";
    }
} else {
    header("Location: my_bookings.php");
    exit();
}
?>