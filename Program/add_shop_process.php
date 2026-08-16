<?php
session_start();
require_once 'db.php';

// ปิด strict mode ชั่วคราวเพื่อให้เราดึง error มาดูชัดๆ
mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'provider') {
    echo "<script>alert('กรุณาเข้าสู่ระบบในฐานะผู้ประกอบการก่อน'); window.location.href='login.html';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. ตรวจสอบการมีอยู่ของ provider_id
    $provider_id = $_SESSION['provider_id'] ?? null;
    if (empty($provider_id)) {
        echo "<script>alert('ไม่พบรหัสผู้ประกอบการในระบบ'); window.location.href='login.html';</script>";
        exit();
    }

    $shop_name    = mysqli_real_escape_string($conn, $_POST['shop_name'] ?? '');
    $shop_detail  = mysqli_real_escape_string($conn, $_POST['shop_detail'] ?? '');
    $address      = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $latitude     = mysqli_real_escape_string($conn, $_POST['latitude'] ?? '');
    $longitude    = mysqli_real_escape_string($conn, $_POST['longitude'] ?? '');
    $service_area = mysqli_real_escape_string($conn, $_POST['service_area'] ?? '');
    $doc_name     = mysqli_real_escape_string($conn, $_POST['doc_name'] ?? '');
    $upload_date  = date('Y-m-d H:i:s');
    $shop_status  = 'รอนุมัติ';

    if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }
    if (!file_exists('docs')) { mkdir('docs', 0777, true); }

    // ตรวจสอบว่ามีร้านเดิมอยู่แล้วหรือไม่
    $check_shop = mysqli_query($conn, "SELECT shop_id, shop_logo, shop_portfolio, doc_file FROM banquetshop WHERE provider_id = '$provider_id'");
    $existing_shop = mysqli_fetch_assoc($check_shop);

    // 1. โลโก้
    $shop_logo = $existing_shop['shop_logo'] ?? '';
    if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
        $ext = pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION);
        $shop_logo = 'logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
        move_uploaded_file($_FILES['shop_logo']['tmp_name'], 'uploads/' . $shop_logo);
    }

    // 2. ผลงาน
    $portfolio_array = [];
    if (!empty($existing_shop['shop_portfolio'])) {
        $portfolio_array = explode(',', $existing_shop['shop_portfolio']);
    }
    if (isset($_FILES['shop_portfolio']) && is_array($_FILES['shop_portfolio']['name'])) {
        foreach ($_FILES['shop_portfolio']['name'] as $key => $name) {
            if ($_FILES['shop_portfolio']['error'][$key] == 0) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $new_port_name = 'port_' . time() . '_' . $key . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['shop_portfolio']['tmp_name'][$key], 'uploads/' . $new_port_name)) {
                    $portfolio_array[] = $new_port_name;
                }
            }
        }
    }
    $shop_portfolio = implode(',', array_filter($portfolio_array));

    // 3. เอกสาร
    $doc_array = [];
    if (!empty($existing_shop['doc_file'])) {
        $doc_array = explode(',', $existing_shop['doc_file']);
    }
    if (isset($_FILES['doc_file']) && is_array($_FILES['doc_file']['name'])) {
        foreach ($_FILES['doc_file']['name'] as $key => $name) {
            if ($_FILES['doc_file']['error'][$key] == 0) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $new_doc_name = 'doc_' . time() . '_' . $key . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['doc_file']['tmp_name'][$key], 'docs/' . $new_doc_name)) {
                    $doc_array[] = $new_doc_name;
                }
            }
        }
    }
    $doc_file = implode(',', array_filter($doc_array));

    // สร้างคำสั่ง SQL
    if ($existing_shop) {
        $sql = "UPDATE banquetshop SET 
                    shop_name = '$shop_name', 
                    shop_detail = '$shop_detail', 
                    shop_logo = '$shop_logo', 
                    shop_portfolio = '$shop_portfolio', 
                    address = '$address', 
                    latitude = '$latitude', 
                    longitude = '$longitude', 
                    service_area = '$service_area', 
                    doc_name = '$doc_name', 
                    doc_file = '$doc_file', 
                    upload_date = '$upload_date', 
                    shop_status = '$shop_status' 
                WHERE provider_id = '$provider_id'";
    } else {
        $sql = "INSERT INTO banquetshop 
                (provider_id, shop_name, shop_detail, shop_logo, shop_portfolio, address, latitude, longitude, service_area, doc_name, doc_file, upload_date, shop_status) 
                VALUES 
                ('$provider_id', '$shop_name', '$shop_detail', '$shop_logo', '$shop_portfolio', '$address', '$latitude', '$longitude', '$service_area', '$doc_name', '$doc_file', '$upload_date', '$shop_status')";
    }

    // ทำการประมวลผลพร้อมตรวจจับ Error ละเอียด
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('บันทึกข้อมูลร้านค้าเรียบร้อยแล้ว รอแอดมินอนุมัติ'); window.location.href='provider_dashboard.php';</script>";
        exit();
    } else {
        // พ่น Error จากระบบออกมาดูโดยตรง
        echo "<div style='padding:20px; background:#fff0f0; border:2px solid red;'>";
        echo "<h3 style='color:red;'>เกิดข้อผิดพลาดในการบันทึกข้อมูล (Database Error)</h3>";
        echo "<strong>SQL Query:</strong> <pre>" . htmlspecialchars($sql) . "</pre><br>";
        echo "<strong>Error Message:</strong> <b style='color:darkred;'>" . mysqli_error($conn) . "</b>";
        echo "</div>";
        exit();
    }
}
?>