<?php
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $provider_name    = mysqli_real_escape_string($conn, trim($_POST['provider_name'] ?? ''));
    $email            = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $tel              = mysqli_real_escape_string($conn, trim($_POST['tel'] ?? ''));
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ตรวจสอบรหัสผ่านซ้ำ
    if ($password !== $confirm_password) {
        echo "<script>alert('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน!'); window.history.back();</script>";
        exit();
    }

    // ตรวจสอบอีเมลซ้ำ
    $check_email = mysqli_query($conn, "SELECT provider_id FROM serviceprovider WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        echo "<script>alert('อีเมลนี้ ($email) มีในระบบแล้ว กรุณาใช้อีเมลอื่น'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $reg_date        = date('Y-m-d H:i:s');
    $provider_status = 'ปกติ';

    try {
        $sql = "INSERT INTO serviceprovider (provider_name, email, password, tel, reg_date, provider_status) 
                VALUES ('$provider_name', '$email', '$hashed_password', '$tel', '$reg_date', '$provider_status')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('สมัครสมาชิกผู้ประกอบการสำเร็จ! กรุณาเข้าสู่ระบบเพื่อลงทะเบียนร้านค้า'); window.location.href='login.html';</script>";
            exit();
        }
    } catch (Exception $e) {
        echo "<script>alert('เกิดข้อผิดพลาด: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }

} else {
    header("Location: register_provider.html");
    exit();
}
?>