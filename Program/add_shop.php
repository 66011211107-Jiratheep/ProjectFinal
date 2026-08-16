<?php
session_start();
// ตรวจสอบว่าผู้ประกอบการล็อกอินเข้ามาหรือยัง
if (!isset($_SESSION['provider_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบก่อน'); window.location.href='login.html';</script>";
    exit();
}

// กำหนดบทบาทเซสชันหากยังไม่ได้ตั้งค่า
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'provider';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียนร้านโต๊ะจีน</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            background: #ffffff;
            padding: 25px;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #8b0000;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        input[type="text"], input[type="number"], textarea, input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            resize: vertical;
            height: 80px;
        }
        .btn-submit {
            width: 100%;
            background-color: #8b0000;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #a00000;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>ลงทะเบียนข้อมูลร้านโต๊ะจีน</h2>
    
    <!-- แก้ไข action เป็นไฟล์ add_shop_process.php -->
    <form action="add_shop_process.php" method="POST" enctype="multipart/form-data">
        
        <div class="form-group">
            <label>ชื่อร้านโต๊ะจีน:</label>
            <input type="text" name="shop_name" placeholder="เช่น นิวโภชนา โต๊ะจีน" required>
        </div>

        <div class="form-group">
            <label>รายละเอียดร้าน:</label>
            <textarea name="shop_detail" placeholder="เช่น รับจัดโต๊ะจีนงานแต่ง งานบุญ เมนูหลากหลาย..."></textarea>
        </div>

        <div class="form-group">
            <label>ที่ตั้งร้าน (ที่อยู่):</label>
            <textarea name="address" placeholder="เลขที่ ถนน ตําบล อำเภอ จังหวัด" required></textarea>
        </div>

        <div class="form-group">
            <label>พิกัด ละติจูด (Latitude):</label>
            <input type="number" step="any" name="latitude" placeholder="เช่น 16.12345678" required>
        </div>

        <div class="form-group">
            <label>พิกัด ลองจิจูด (Longitude):</label>
            <input type="number" step="any" name="longitude" placeholder="เช่น 103.1234568" required>
        </div>

        <div class="form-group">
            <label>พื้นที่ให้บริการ:</label>
            <input type="text" name="service_area" placeholder="เช่น ภาคอีสาน หรือ มหาสารคาม และจังหวัดใกล้เคียง" required>
        </div>

        <div class="form-group">
            <label>ชื่อเอกสารประกอบการสมัคร:</label>
            <input type="text" name="doc_name" placeholder="เช่น ใบอนุญาตประกอบกิจการ" required>
        </div>

        <div class="form-group">
            <label>ไฟล์เอกสาร (PDF / ZIP) - สามารถเลือกได้หลายไฟล์:</label>
            <!-- ใส่ [] และ multiple เพื่อเลือกได้หลายไฟล์ -->
            <input type="file" name="doc_file[]" accept=".pdf,.zip,.rar" multiple required>
        </div>

        <div class="form-group">
            <label>โลโก้ร้าน (รูปภาพ):</label>
            <input type="file" name="shop_logo" accept="image/*">
        </div>

        <div class="form-group">
            <label>ภาพผลงานร้าน (รูปภาพ) - สามารถเลือกได้หลายรูป:</label>
            <!-- ใส่ [] และ multiple เพื่อเลือกได้หลายรูป -->
            <input type="file" name="shop_portfolio[]" accept="image/*" multiple>
        </div>

        <button type="submit" class="btn-submit">บันทึกและส่งข้อมูลให้แอดมินตรวจสอบ</button>
    </form>
</div>

</body>
</html>