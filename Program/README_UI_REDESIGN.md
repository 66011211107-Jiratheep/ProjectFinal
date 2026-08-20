# UI Redesign - ระบบเว็บไซต์ตัวกลางธุรกิจโต๊ะจีน

รอบนี้ปรับเฉพาะ Presentation/UI เป็นหลัก โดยไม่เปลี่ยนฐานข้อมูลหรือ flow การทำงานของ backend

## สิ่งที่เพิ่ม/ปรับ
- เพิ่ม `theme.css` เป็น design system กลางของทั้งระบบ
- ปรับโทนสี Burgundy / Warm Gold / Off-white ให้ดูสะอาดและเป็นระบบเดียวกัน
- ปรับ Home ให้มี Hero Search และ card ร้านค้าที่อ่านง่ายขึ้น
- ปรับ Login / Register / Provider Register ให้เป็น auth card แบบใหม่
- ปรับ Shop Detail, Booking, Payment, Booking History
- ปรับ Provider Dashboard / Manage Menu / Manage Packages / Bookings / Reviews / Shop forms
- ปรับ Admin Dashboard / Approval / User management / Profile
- เพิ่ม responsive viewport ให้หน้าที่ยังไม่มี
- เพิ่ม responsive CSS สำหรับมือถือ/แท็บเล็ต

## หมายเหตุ
ไฟล์ที่อ้างอิงจากเมนูแต่ยังไม่มีในโค้ดเดิม (ยังไม่ได้สร้างในรอบ UI นี้):
- `profile.php`
- `admin_manage_categories.php`
- `admin_manage_reviews.php`
- `admin_transactions.php`

ไฟล์ `restaurant.sql` ที่แนบมากับชุดส่งคืนไม่ได้ถูกแก้ไข
## Dropdown profile fix (2026-08-20)
- ขยายพื้นที่ hover ระหว่างปุ่มชื่อผู้ใช้กับเมนู เพื่อไม่ให้ dropdown หายตอนเลื่อนเมาส์ลง
- เพิ่ม grace period 700ms ก่อนปิดเมนูเมื่อเมาส์หลุดออก
- คลิกปุ่มชื่อผู้ใช้เพื่อ pin/unpin เมนูได้ และกด Esc เพื่อปิด
- เพิ่ม `dropdown.js` ใช้ร่วมกับ `index.php` และ `shop_detail.php`

