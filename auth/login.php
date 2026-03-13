<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? "";
    $password = $_POST['password'] ?? "";
    $role     = $_POST['role'] ?? "";

    if ($username == "" || $password == "" || $role == "") {
        $error = "กรุณากรอกข้อมูลให้ครบทุกช่อง";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password == $user['password']) {
            $_SESSION['id']       = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $role;
            $_SESSION['department_id'] = $user['department_id'] ?? 1;

            if ($role == "staff") {
                header("Location: ../pages/staff_page.php");
            } else {
                header("Location: ../pages/checker_page.php");
            }
            exit();
        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>เข้าสู่ระบบ | โรงพยาบาลหนองพอก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --moph-green: #007d69; /* สีเขียวทางการกระทรวงสาธารณสุข */
            --dark-text: #2c3e50;
            --soft-bg: #f4f7f6;
            --border-color: #dee2e6;
        }

        body {
            background-color: var(--soft-bg);
            font-family: 'Sarabun', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            padding: 45px 35px;
        }

        .logo-box {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-box img {
            width: 110px;
            height: auto;
            margin-bottom: 15px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .hospital-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 4px;
        }

        .system-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--moph-green);
            box-shadow: 0 0 0 3px rgba(0, 125, 105, 0.1);
        }

        /* Role Switch Style */
        .role-group {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .role-option { flex: 1; }
        .role-option input { display: none; }
        
        .role-btn {
            display: block;
            padding: 10px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            font-size: 0.9rem;
            background: #fff;
            color: #495057;
        }

        .role-option input:checked + .role-btn {
            border-color: var(--moph-green);
            background: var(--moph-green);
            color: #fff;
            font-weight: 600;
        }

        .btn-submit {
            background: #212529; /* สีดำทางการ */
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: var(--moph-green);
            transform: translateY(-1px);
        }

        .error-msg {
            background: #fff5f5;
            color: #dc3545;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #feb2b2;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo-box">
            <img src="../LOGO/nongphok_logo.png" alt="โลโก้โรงพยาบาล" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/f/f7/Ministry_of_Public_Health_Thailand_Logo.svg/1024px-Ministry_of_Public_Health_Thailand_Logo.svg.png'">
            <div class="hospital-title">โรงพยาบาลหนองพอก</div>
            <div class="system-subtitle text-uppercase">Time Attendance System</div>
        </div>

        <?php if ($error != ""): ?>
            <div class="error-msg">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">ชื่อผู้ใช้งาน</label>
                <input type="text" name="username" class="form-control" placeholder="ระบุ Username" required>
            </div>

<div class="mb-4">
    <label class="form-label">รหัสผ่าน</label>
    <div style="position: relative; width: 100%;">
        <input type="password" id="password" name="password" 
               class="form-control" placeholder="ระบุ Password" required 
               style="padding-right: 40px; width: 100%;">
        
        <span id="togglePasswordContainer" 
              style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; display: flex; align-items: center; justify-content: center; opacity: 0.3;"> <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            
            <svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off" style="display: none;">
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.06M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
        </span>
    </div>
</div>

            <label class="form-label">เข้าใช้งานในบทบาท</label>
            <div class="role-group">
                <div class="role-option">
                    <input type="radio" name="role" value="staff" id="staff" required>
                    <label for="staff" class="role-btn">เจ้าหน้าที่</label>
                </div>
                <div class="role-option">
                    <input type="radio" name="role" value="checker" id="checker">
                    <label for="checker" class="role-btn">ผู้ตรวจสอบ</label>
                </div>
            </div>

         <button type="submit" class="btn btn-submit">
    <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
</button>

<div class="text-center mt-3">
    <span class="small text-muted">ยังไม่มีบัญชี?</span> 
    <a href="register.php" class="small text-decoration-none fw-bold" style="color: var(--moph-green);">ลงทะเบียนใหม่</a>
</div>
        </form>
        

        <div class="text-center mt-4 pt-3 border-top" style="font-size: 0.75rem; color: #adb5bd;">
            NONG PHOK HOSPITAL &copy; 2026
        </div>
    </div>
</body>
<script>
    const togglePasswordContainer = document.querySelector('#togglePasswordContainer');
    const password = document.querySelector('#password');
    const eyeIcon = document.querySelector('#eyeIcon');
    const eyeOffIcon = document.querySelector('#eyeOffIcon');

    togglePasswordContainer.addEventListener('click', function () {
        // 1. สลับ type ระหว่าง password และ text
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // 2. สลับการแสดงผลของไอคอน
        if (type === 'password') {
            eyeIcon.style.display = 'block';
            eyeOffIcon.style.display = 'none';
        } else {
            eyeIcon.style.display = 'none';
            eyeOffIcon.style.display = 'block';
        }
        
        // 3. (เลือกทำ) อาจจะทำให้ไอคอนเข้มขึ้นนิดหน่อยเวลาเปิดดู
        togglePasswordContainer.style.opacity = type === 'password' ? '0.3' : '0.6';
    });
</script>
</html>
