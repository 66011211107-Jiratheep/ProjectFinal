<?php
session_start();
require_once 'db.php';

// 1. ตรวจสอบสิทธิ์การเป็น Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('สำหรับผู้ดูแลระบบเท่านั้น'); window.location.href='login.html';</script>";
    exit();
}

// 2. ดึง admin_id จาก Session (หากไม่มีให้ดึงค่า default หรือตั้งเป็น NULL)
$admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 1; 

// ----------------------------------------------------
// กรณีที่ 1: แอดมินกด "อนุมัติ" (รับค่าผ่าน GET)
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['id'])) {
    $shop_id = intval($_GET['id']);

    $sql = "UPDATE banquetshop 
            SET shop_status = 'อนุมัติ', 
                admin_id = '$admin_id', 
                reject_reason = NULL 
            WHERE shop_id = '$shop_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('อนุมัติร้านค้าเรียบร้อยแล้ว!'); window.location.href='admin_approve.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด: " . mysqli_real_escape_string($conn, mysqli_error($conn)) . "'); window.location.href='admin_approve.php';</script>";
    }
    exit();
}

// ----------------------------------------------------
// กรณีที่ 2: แอดมินกด "ไม่อนุมัติ / ให้แก้ไข" (รับค่าผ่าน POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'reject') {
    $shop_id = intval($_POST['shop_id']);
    $reject_reason = mysqli_real_escape_string($conn, trim($_POST['reject_reason'] ?? ''));

    if (empty($shop_id) || empty($reject_reason)) {
        echo "<script>alert('กรุณาระบุเหตุผลในการไม่อนุมัติ'); window.history.back();</script>";
        exit();
    }

    $sql = "UPDATE banquetshop 
            SET shop_status = 'ไม่อนุมัติ', 
                admin_id = '$admin_id', 
                reject_reason = '$reject_reason' 
            WHERE shop_id = '$shop_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('บันทึกสถานะไม่อนุมัติและแจ้งเหตุผลเรียบร้อยแล้ว'); window.location.href='admin_approve.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด: " . mysqli_real_escape_string($conn, mysqli_error($conn)) . "'); window.location.href='admin_approve.php';</script>";
    }
    exit();
}

// หากไม่มี action ใดๆ เข้ามา ให้ดีดกลับหน้าอนุมัติ
header("Location: admin_approve.php");
exit();
?>