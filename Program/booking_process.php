<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่า customer_id จาก session
    $customer_id   = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? 0;
    
    // รับค่าจากฟอร์ม
    $shop_id       = intval($_POST['shop_id']);
    $package_id    = intval($_POST['package_id']);
    $event_date    = $_POST['event_date'];
    $event_time    = $_POST['event_time'];
    $table_count   = intval($_POST['table_count']);
    $event_address = $_POST['event_address'] ?? $_POST['event_location'] ?? '';
    $selected_srv  = $_POST['services'] ?? []; // รับอาร์เรย์บริการเสริมที่เลือก

    // ตรวจสอบข้อมูลเบื้องต้น
    if ($customer_id <= 0 || $shop_id <= 0 || $package_id <= 0) {
        echo "<script>alert('ข้อมูลไม่ถูกต้อง หรือยังไม่ได้ล็อกอิน'); window.location.href='login.html';</script>";
        exit();
    }

    // 1. ดึงราคาต่อโต๊ะจาก package เพื่อคำนวณราคารวมโต๊ะ
    $stmt_price = $conn->prepare("SELECT price_per_table FROM package WHERE package_id = ?");
    $stmt_price->bind_param("i", $package_id);
    $stmt_price->execute();
    $res_price = $stmt_price->get_result()->fetch_assoc();

    if (!$res_price) {
        echo "<script>alert('ไม่พบข้อมูลแพ็กเกจ'); window.history.back();</script>";
        exit();
    }

    $price_per_table = $res_price['price_per_table'];
    $total_price = $price_per_table * $table_count;

    // 2. คำนวณราคาบริการเสริมบวกเพิ่ม (ถ้าผู้ใช้เลือก)
    if (!empty($selected_srv)) {
        $stmt_srv_price = $conn->prepare("SELECT price FROM additional_service WHERE service_id = ?");
        foreach ($selected_srv as $srv_id) {
            $s_id = intval($srv_id);
            $stmt_srv_price->bind_param("i", $s_id);
            $stmt_srv_price->execute();
            $res_srv_price = $stmt_srv_price->get_result()->fetch_assoc();
            if ($res_srv_price) {
                $total_price += floatval($res_srv_price['price']);
            }
        }
    }

    // 3. บันทึกข้อมูลหลักลงตาราง booking
    $stmt = $conn->prepare("
        INSERT INTO booking 
        (customer_id, shop_id, package_id, event_date, event_time, table_count, total_price, booking_status, booking_date, event_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?)
    ");
    $stmt->bind_param("iiissids", $customer_id, $shop_id, $package_id, $event_date, $event_time, $table_count, $total_price, $event_address);

    if ($stmt->execute()) {
        $booking_id = $conn->insert_id; // ดึง ID การจองที่เพิ่งสร้างขึ้นมา

        // 4. บันทึกบริการเสริมที่เลือกลงตาราง booking_service_detail
        if (!empty($selected_srv)) {
            $stmt_srv_detail = $conn->prepare("INSERT INTO booking_service_detail (booking_id, service_id) VALUES (?, ?)");
            foreach ($selected_srv as $srv_id) {
                $s_id = intval($srv_id);
                $stmt_srv_detail->bind_param("ii", $booking_id, $s_id);
                $stmt_srv_detail->execute();
            }
        }

        // 5. ดึงรายการเมนูอาหารประจำแพ็กเกจมาบันทึกลงตาราง booking_menus
        $stmt_pkg_items = $conn->prepare("SELECT menu_id FROM package_items WHERE package_id = ?");
        $stmt_pkg_items->bind_param("i", $package_id);
        $stmt_pkg_items->execute();
        $res_pkg_items = $stmt_pkg_items->get_result();

        if ($res_pkg_items->num_rows > 0) {
            $stmt_b_menu = $conn->prepare("INSERT INTO booking_menus (booking_id, menu_id) VALUES (?, ?)");
            while ($item = $res_pkg_items->fetch_assoc()) {
                $m_id = intval($item['menu_id']);
                $stmt_b_menu->bind_param("ii", $booking_id, $m_id);
                $stmt_b_menu->execute();
            }
        }

        echo "<script>alert('ส่งคำขอจองเรียบร้อยแล้ว!'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error . "'); window.history.back();</script>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>