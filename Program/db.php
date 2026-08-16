<?php
$host = "localhost";
$user = "root";          
$pass = "";              
$dbname = "restaurant";  

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
?>