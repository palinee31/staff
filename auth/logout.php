<?php
session_start();
session_destroy(); // ล้างค่าทั้งหมดใน Session
header("Location: login.php"); // ส่งกลับไปหน้า Login
exit;
?>