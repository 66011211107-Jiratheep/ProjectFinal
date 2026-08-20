<?php
session_start();
require_once 'db.php';

// ตรวจสอบสิทธิ์ผู้ประกอบการ
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'provider' ||
    !isset($_SESSION['provider_id'])
) {
    echo "<script>
        alert('กรุณาเข้าสู่ระบบในฐานะผู้ประกอบการก่อน');
        window.location.href='login.html';
    </script>";
    exit();
}

$provider_id = $_SESSION['provider_id'];

// ดึงข้อมูลร้านล่าสุดของผู้ประกอบการ
$sql = "SELECT * FROM banquetshop 
        WHERE provider_id = '$provider_id'
        ORDER BY shop_id DESC
        LIMIT 1";

$result = $conn->query($sql);

if (!$result) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล: " . $conn->error);
}

$shop = $result->fetch_assoc();

// ถ้ายังไม่มีร้าน
if (!$shop) {
    echo "<script>
        alert('ไม่พบข้อมูลร้านค้า กรุณากรอกข้อมูลร้านค้าก่อน');
        window.location.href='add_shop.php';
    </script>";
    exit();
}

// ----------------------------
// ข้อมูลสำหรับแสดงผล
// ----------------------------
$provider_name = $_SESSION['name'] ?? 'ผู้ประกอบการ';
$shop_name     = $shop['shop_name'] ?? 'ร้านโต๊ะจีน';
$status        = $shop['shop_status'] ?? 'รอนุมัติ';

// รูป Logo ร้าน
$shop_logo = '';

if (!empty($shop['shop_logo'])) {
    $shop_logo = 'uploads/' . $shop['shop_logo'];
}

// ตรวจสอบสถานะ
$isApproved = in_array(
    $status,
    ['อนุมัติ', 'อนุมัติแล้ว', 'approved']
);

$isRejected = in_array(
    $status,
    ['ไม่อนุมัติ', 'rejected']
);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        ระบบจัดการร้านค้า -
        <?php echo htmlspecialchars($shop_name); ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, "Tahoma", sans-serif;
            background: #f5f5f5;
            color: #333;
        }


        /* =========================
           SIDEBAR
        ========================== */

        .sidebar {
            width: 260px;
            height: 100vh;

            position: fixed;
            top: 0;
            left: 0;

            background: #292d32;

            color: white;

            padding: 24px 14px;

            overflow-y: auto;
        }


        .sidebar-title {
            padding: 5px 12px 22px 12px;

            font-size: 20px;
            font-weight: bold;

            border-bottom: 1px solid rgba(255,255,255,0.10);

            margin-bottom: 22px;
        }


        .sidebar-shop {
            padding: 0 12px 20px;

            color: #aeb4bb;

            font-size: 13px;

            line-height: 1.6;
        }


        .sidebar-shop strong {
            display: block;

            color: white;

            font-size: 15px;
        }


        .sidebar a {
            display: flex;

            align-items: center;

            gap: 11px;

            width: 100%;

            padding: 14px 15px;

            margin-bottom: 6px;

            border-radius: 9px;

            color: #d6d6d6;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s;
        }


        .sidebar a:hover {
            background: #3a3e43;
            color: white;
        }


        .sidebar a.active {
            background: #3b3f44;
            color: white;
        }


        .sidebar .logout {
            margin-top: 28px;

            color: #ff8d8d;
        }


        .sidebar .logout:hover {
            background: rgba(255, 70, 70, 0.10);
            color: #ffaaaa;
        }


        /* =========================
           MAIN CONTENT
        ========================== */

        .main-content {
            margin-left: 260px;

            padding: 35px;

            min-height: 100vh;
        }


        /* =========================
           HEADER PROFILE
        ========================== */

        .header-profile {

            background: white;

            border: 1px solid #e5e5e5;

            border-radius: 15px;

            padding: 24px 28px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.04);

            margin-bottom: 25px;
        }


        .profile-detail {

            display: flex;

            align-items: center;

            gap: 20px;
        }


        /* รูปร้าน */

        .shop-avatar {

            width: 78px;
            height: 78px;

            border-radius: 50%;

            object-fit: cover;

            background: #f5eeee;

            border: 3px solid #ead2d5;
        }


        .shop-avatar-empty {

            width: 78px;
            height: 78px;

            border-radius: 50%;

            background: #f4e8e9;

            border: 3px solid #ead2d5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 34px;
        }


        .profile-info h2 {

            margin: 0 0 7px 0;

            font-size: 22px;

            color: #27384a;
        }


        .profile-info p {

            margin: 3px 0;

            color: #888;

            font-size: 13px;
        }


        .shop-name {

            color: #991f2b;

            font-weight: bold;
        }


        /* =========================
           STATUS BADGE
        ========================== */

        .status {

            display: inline-block;

            padding: 5px 10px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: bold;

            margin-left: 7px;

            vertical-align: middle;
        }


        .status-approved {

            background: #e7f6ed;

            color: #218448;
        }


        .status-pending {

            background: #fff4d9;

            color: #9a6b00;
        }


        .status-rejected {

            background: #fde8e8;

            color: #b42318;
        }


        /* =========================
           BUTTON
        ========================== */

        .btn {

            display: inline-block;

            padding: 11px 18px;

            background: #991f2b;

            color: white;

            text-decoration: none;

            border-radius: 9px;

            font-size: 14px;

            font-weight: bold;

            transition: 0.2s;
        }


        .btn:hover {

            background: #7e1721;

            transform: translateY(-1px);
        }


        /* =========================
           DASHBOARD TITLE
        ========================== */

        .section-title {

            margin: 0 0 18px;

            font-size: 19px;

            color: #333;
        }


        /* =========================
           CARD GRID
        ========================== */

        .dashboard-grid {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(210px, 1fr));

            gap: 20px;
        }


        .dashboard-card {

            background: white;

            border: 1px solid #e5e5e5;

            border-radius: 15px;

            padding: 25px;

            min-height: 200px;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.04);

            transition: 0.2s;
        }


        .dashboard-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 8px 20px rgba(0,0,0,0.07);
        }


        .card-icon {

            width: 48px;
            height: 48px;

            border-radius: 12px;

            background: #f6eaeb;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 23px;

            margin-bottom: 18px;
        }


        .dashboard-card h3 {

            margin: 0 0 10px;

            font-size: 17px;

            color: #333;
        }


        .dashboard-card p {

            margin: 0;

            min-height: 50px;

            color: #777;

            font-size: 13px;

            line-height: 1.7;
        }


        .dashboard-card .btn {

            margin-top: 17px;

            padding: 9px 15px;

            font-size: 13px;
        }


        /* =========================
           ALERT STATUS
        ========================== */

        .alert-box {

            background: white;

            border-radius: 15px;

            padding: 28px;

            border: 1px solid #e5e5e5;

            box-shadow:
                0 3px 12px rgba(0,0,0,0.04);
        }


        .alert-warning {

            border-left: 5px solid #e3a008;
        }


        .alert-danger {

            border-left: 5px solid #c62828;
        }


        .alert-box h3 {

            margin-top: 0;
        }


        .alert-box p {

            color: #666;

            line-height: 1.7;
        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 1200px) {

            .dashboard-grid {

                grid-template-columns:
                    repeat(2, minmax(220px, 1fr));
            }

        }


        @media (max-width: 768px) {

            .sidebar {

                width: 100%;
                height: auto;

                position: relative;
            }


            .main-content {

                margin-left: 0;

                padding: 18px;
            }


            .header-profile {

                flex-direction: column;

                align-items: flex-start;
            }


            .dashboard-grid {

                grid-template-columns: 1fr;
            }


            .profile-detail {

                align-items: flex-start;
            }

        }

    </style>

</head>


<body>


<!-- ==================================
     SIDEBAR
=================================== -->

<div class="sidebar">

    <div class="sidebar-title">

        🏪 Provider Control

    </div>


    <div class="sidebar-shop">

        ร้านของคุณ

        <strong>
            <?php echo htmlspecialchars($shop_name); ?>
        </strong>

    </div>


    <a href="provider_dashboard.php"
       class="active">

        📌 หน้าแรกผู้ประกอบการ

    </a>


    <?php if ($isApproved): ?>


        <a href="manage_packages.php">

            🍱 จัดการแพ็กเกจโต๊ะจีน

        </a>


        <a href="manage_menu.php">

            🍲 อาหาร / บริการเสริม

        </a>


        <a href="provider_bookings.php">

            📅 ตรวจสอบการจอง

        </a>


        <a href="provider_reviews.php">

            💬 รีวิวจากลูกค้า

        </a>


    <?php endif; ?>


    <a href="edit_shop.php">

        ⚙️ ข้อมูลร้านค้า

    </a>


    <a href="logout.php"
       class="logout"
       onclick="return confirm('ยืนยันออกจากระบบ?');">

        🚪 ออกจากระบบ

    </a>

</div>



<!-- ==================================
     MAIN CONTENT
=================================== -->

<div class="main-content">


    <!-- ===============================
         HEADER
    ================================ -->

    <div class="header-profile">


        <div class="profile-detail">


            <?php if (!empty($shop_logo)): ?>

                <img
                    src="<?php echo htmlspecialchars($shop_logo); ?>"
                    class="shop-avatar"
                    alt="Logo ร้าน">

            <?php else: ?>

                <div class="shop-avatar-empty">

                    🍽️

                </div>

            <?php endif; ?>



            <div class="profile-info">


                <h2>

                    ยินดีต้อนรับ:
                    <?php
                    echo htmlspecialchars($provider_name);
                    ?>

                    <?php if ($isApproved): ?>

                        <span class="status status-approved">
                            ✓ อนุมัติแล้ว
                        </span>

                    <?php elseif ($isRejected): ?>

                        <span class="status status-rejected">
                            ✕ ไม่ผ่านการอนุมัติ
                        </span>

                    <?php else: ?>

                        <span class="status status-pending">
                            ⏳ รออนุมัติ
                        </span>

                    <?php endif; ?>

                </h2>


                <p>

                    ร้าน:
                    <span class="shop-name">

                        <?php
                        echo htmlspecialchars($shop_name);
                        ?>

                    </span>

                </p>


                <p>

                    จัดการข้อมูลร้าน แพ็กเกจ อาหาร
                    การจอง และรีวิวของลูกค้าได้จากเมนูด้านซ้าย

                </p>


            </div>

        </div>



        <div>

            <a href="edit_shop.php"
               class="btn">

                ⚙️ แก้ไขข้อมูลร้านค้า

            </a>

        </div>


    </div>



    <!-- ==================================
         ร้านได้รับอนุมัติ
    =================================== -->

    <?php if ($isApproved): ?>


        <h2 class="section-title">
            ภาพรวมการจัดการร้านค้า
        </h2>


        <div class="dashboard-grid">


            <!-- PACKAGE -->

            <div class="dashboard-card">

                <div class="card-icon">
                    🍱
                </div>

                <h3>
                    แพ็กเกจโต๊ะจีน
                </h3>

                <p>
                    เพิ่ม แก้ไข หรือลบแพ็กเกจโต๊ะจีน
                    ราคา และรายละเอียดของแต่ละชุด
                </p>

                <a href="manage_packages.php"
                   class="btn">

                    ไปที่หน้าจัดการ

                </a>

            </div>



            <!-- MENU -->

            <div class="dashboard-card">

                <div class="card-icon">
                    🍲
                </div>

                <h3>
                    อาหาร / บริการเสริม
                </h3>

                <p>
                    จัดการรายการอาหาร รูปภาพ ราคา
                    และบริการเสริมต่าง ๆ ของร้าน
                </p>

                <a href="manage_menu.php"
                   class="btn">

                    ไปที่หน้าจัดการ

                </a>

            </div>



            <!-- BOOKING -->

            <div class="dashboard-card">

                <div class="card-icon">
                    📅
                </div>

                <h3>
                    การจองของลูกค้า
                </h3>

                <p>
                    ตรวจสอบรายละเอียดการจอง
                    สถานะงาน และหลักฐานการชำระเงิน
                </p>

                <a href="provider_bookings.php"
                   class="btn">

                    ตรวจสอบการจอง

                </a>

            </div>



            <!-- REVIEW -->

            <div class="dashboard-card">

                <div class="card-icon">
                    💬
                </div>

                <h3>
                    รีวิวจากลูกค้า
                </h3>

                <p>
                    ตรวจสอบคะแนน ความคิดเห็น
                    และรีวิวที่ลูกค้าให้กับร้านของคุณ
                </p>

                <a href="provider_reviews.php"
                   class="btn">

                    ดูรีวิวลูกค้า

                </a>

            </div>


        </div>



    <!-- ==================================
         ร้านไม่ผ่านอนุมัติ
    =================================== -->

    <?php elseif ($isRejected): ?>


        <div class="alert-box alert-danger">

            <h3>
                ❌ ร้านค้าไม่ผ่านการอนุมัติ
            </h3>


            <p>

                <strong>เหตุผล:</strong>

                <?php

                echo htmlspecialchars(
                    $shop['reject_reason']
                    ?? 'ไม่ระบุเหตุผล'
                );

                ?>

            </p>


            <p>

                กรุณาแก้ไขข้อมูลร้านค้าตามคำแนะนำ
                จากผู้ดูแลระบบ แล้วส่งข้อมูลเพื่อ
                ขออนุมัติใหม่อีกครั้ง

            </p>


            <a href="edit_shop.php"
               class="btn">

                ✏️ แก้ไขและยื่นอนุมัติใหม่

            </a>

        </div>



    <!-- ==================================
         รออนุมัติ
    =================================== -->

    <?php else: ?>


        <div class="alert-box alert-warning">

            <h3>
                ⏳ ร้านค้ากำลังรอการอนุมัติ
            </h3>


            <p>

                ขณะนี้ข้อมูลร้าน
                <strong>

                    <?php
                    echo htmlspecialchars($shop_name);
                    ?>

                </strong>

                อยู่ระหว่างการตรวจสอบโดยผู้ดูแลระบบ

            </p>


            <p>

                เมื่อร้านได้รับการอนุมัติแล้ว
                ระบบจะเปิดเมนูจัดการแพ็กเกจ
                อาหาร การจอง และรีวิวให้โดยอัตโนมัติ

            </p>


            <a href="edit_shop.php"
               class="btn">

                ⚙️ ตรวจสอบข้อมูลร้านค้า

            </a>

        </div>


    <?php endif; ?>


</div>


</body>
</html>