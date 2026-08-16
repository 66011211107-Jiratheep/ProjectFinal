<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input_value = $_POST['username_or_email'] ?? $_POST['email'] ?? $_POST['username'] ?? '';
    $input = mysqli_real_escape_string($conn, trim($input_value));
    $password = $_POST['password'] ?? '';

    if (empty($input) || empty($password)) {
        echo "<script>alert('กรุณากรอกข้อมูลให้ครบถ้วน'); window.location.href='login.html';</script>";
        exit();
    }

    // 1. ตรวจสอบในตาราง admin (ใช้เฉพาะ username)
    $sql_admin = "SELECT * FROM admin WHERE username = '$input'";
    $res_admin = $conn->query($sql_admin);

    if ($res_admin && $res_admin->num_rows > 0) {
        $admin = $res_admin->fetch_assoc();
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            session_unset();
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_id'] = $admin['admin_id'] ?? $admin['id'];
            $_SESSION['name'] = $admin['name'] ?? 'ผู้ดูแลระบบ';
            
            header("Location: admin_approve.php");
            exit();
        } else {
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง'); window.location.href='login.html';</script>";
            exit();
        }
    }

    // 2. ตรวจสอบในตาราง serviceprovider (ผู้ประกอบการ)
    $sql_provider = "SELECT * FROM serviceprovider WHERE email = '$input'";
    $res_provider = $conn->query($sql_provider);

    if ($res_provider && $res_provider->num_rows > 0) {
        $row = $res_provider->fetch_assoc();
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            session_unset();
            
            $_SESSION['role'] = 'provider';
            $_SESSION['provider_id'] = (int)$row['provider_id']; 
            $_SESSION['name'] = $row['provider_name'] ?? 'ผู้ประกอบการ';

            $provider_id = $_SESSION['provider_id'];
            $check_shop = "SELECT shop_id FROM banquetshop WHERE provider_id = '$provider_id'";
            $res_shop = $conn->query($check_shop);

            if ($res_shop && $res_shop->num_rows > 0) {
                header("Location: provider_dashboard.php");
            } else {
                header("Location: add_shop.php");
            }
            exit();
        } else {
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง'); window.location.href='login.html';</script>";
            exit();
        }
    }

    // 3. ตรวจสอบในตาราง customer (ลูกค้า)
    $sql_customer = "SELECT * FROM customer WHERE email = '$input'";
    $res_customer = $conn->query($sql_customer);

    if ($res_customer && $res_customer->num_rows > 0) {
        $cust = $res_customer->fetch_assoc();
        
        if (password_verify($password, $cust['password']) || $password === $cust['password']) {
            session_unset();
            $_SESSION['role'] = 'customer';
            $_SESSION['customer_id'] = $cust['customer_id'] ?? $cust['id'];
            $_SESSION['name'] = $cust['fullname'] ?? $cust['first_name'] ?? $cust['name'] ?? 'ลูกค้า';

            header("Location: index.php");
            exit();
        } else {
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง'); window.location.href='login.html';</script>";
            exit();
        }
    }

    // กรณีไม่พบข้อมูลในตารางใดๆ เลย
    echo "<script>alert('ไม่พบบัญชีผู้ใช้นี้ในระบบ'); window.location.href='login.html';</script>";
    exit();
}
?>