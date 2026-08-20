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

    // 1. ตรวจสอบในตาราง admin
    $sql_admin = "SELECT * FROM admin WHERE username = '$input'";
    $res_admin = $conn->query($sql_admin);

    if ($res_admin && $res_admin->num_rows > 0) {
        $admin = $res_admin->fetch_assoc();
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            session_unset();
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_id'] = $admin['admin_id'] ?? $admin['id'];
            $_SESSION['name'] = $admin['name'] ?? 'ผู้ดูแลระบบ';
            
            header("Location: admin_dashboard.php");
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

        // ⛔ เช็ค 1: สถานะผู้ประกอบการถูกแบน (แก้ไขชื่อคอลัมน์เป็น provider_status)
        if (isset($row['provider_status']) && $row['provider_status'] === 'banned') {
            echo "<script>alert('บัญชีผู้ประกอบการนี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'); window.location.href='login.html';</script>";
            exit();
        }

        if (password_verify($password, $row['password']) || $password === $row['password']) {
            $provider_id = (int)$row['provider_id'];

            // ⛔ เช็ค 2: สถานะร้านค้าของผู้ประกอบการนี้ถูกระงับ
            $check_shop = "SELECT shop_id, shop_status FROM banquetshop WHERE provider_id = '$provider_id'";
            $res_shop = $conn->query($check_shop);

            if ($res_shop && $res_shop->num_rows > 0) {
                $shop = $res_shop->fetch_assoc();
                
                if (isset($shop['shop_status']) && $shop['shop_status'] === 'ระงับการใช้งาน') {
                    echo "<script>alert('ร้านค้าของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'); window.location.href='login.html';</script>";
                    exit();
                }

                session_unset();
                $_SESSION['role'] = 'provider';
                $_SESSION['provider_id'] = $provider_id; 
                $_SESSION['name'] = $row['provider_name'] ?? 'ผู้ประกอบการ';
                
                header("Location: provider_dashboard.php");
            } else {
                session_unset();
                $_SESSION['role'] = 'provider';
                $_SESSION['provider_id'] = $provider_id; 
                $_SESSION['name'] = $row['provider_name'] ?? 'ผู้ประกอบการ';
                
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

        // ⛔ เช็คสถานะแบนของลูกค้า
        if (isset($cust['customer_status']) && $cust['customer_status'] === 'banned') {
            echo "<script>alert('บัญชีลูกค้านี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ'); window.location.href='login.html';</script>";
            exit();
        }

        if (password_verify($password, $cust['password']) || $password === $cust['password']) {
            session_unset();
            $_SESSION['role'] = 'customer';
            $_SESSION['customer_id'] = $cust['customer_id'];
            $_SESSION['name'] = $cust['customer_name'] ?? 'ลูกค้า';

            header("Location: index.php");
            exit();
        } else {
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง'); window.location.href='login.html';</script>";
            exit();
        }
    }

    // กรณีไม่พบข้อมูลในตารางใดๆ
    echo "<script>alert('ไม่พบบัญชีผู้ใช้นี้ในระบบ'); window.location.href='login.html';</script>";
    exit();
}
?>