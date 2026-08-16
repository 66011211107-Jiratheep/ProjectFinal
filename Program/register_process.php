<?php
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = $_POST['customer_name'];
    $email = $_POST['email'];
    $tel = $_POST['tel'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<script>alert('รหัสผ่านไม่ตรงกัน!'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $check_email = "SELECT * FROM Customer WHERE email = '$email'";
    $result = $conn->query($check_email);

    if ($result->num_rows > 0) {
        echo "<script>alert('อีเมลนี้ถูกใช้งานแล้ว!'); window.history.back();</script>";
    } else {
        $sql = "INSERT INTO Customer (customer_name, email, password, tel) 
                VALUES ('$customer_name', '$email', '$hashed_password', '$tel')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('สมัครสมาชิกลูกค้าสำเร็จ!'); window.location.href='login.html';</script>";
        } else {
            echo "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}
?>
