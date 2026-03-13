<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$message = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'] ?? "";
    $username = $_POST['username'] ?? "";
    $password = $_POST['password'] ?? ""; 
    $department_id = 1;

    
    $sig_filename = null;
    $upload_dir = "../uploads/signatures/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

   
    if (!empty($_POST['signature_base64'])) {
        $img_data = $_POST['signature_base64'];
        $img_parts = explode(";base64,", $img_data);
        $image_type_aux = explode("image/", $img_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($img_parts[1]);
        
        $sig_filename = "sig_" . time() . "_" . $username . ".png";
        $target_path = $upload_dir . $sig_filename;
        
       
        file_put_contents($target_path, $image_base64);

    } 

    elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] == 0) {
        $ext = pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION);
        $sig_filename = "sig_" . time() . "_" . $username . "." . $ext;
        $target_path = $upload_dir . $sig_filename;
        move_uploaded_file($_FILES['signature_file']['tmp_name'], $target_path);
    }

    if ($fullname && $username && $password) {
       
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        
        if ($check->rowCount() > 0) {
            $message = "ชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว";
            $msg_type = "danger";
        } else {
           
            if ($sig_filename) {
               
                $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, department_id, signature_path) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$fullname, $username, $password, $department_id, $sig_filename])) {
                    $message = "ลงทะเบียนพร้อมลายเซ็นสำเร็จ!";
                    $msg_type = "success";
                    header("refresh:2;url=login.php");
                } else {
                    $message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                    $msg_type = "danger";
                }
            } else {
                $message = "กรุณาวาดลายเซ็น หรือ อัปโหลดภาพลายเซ็น";
                $msg_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ลงทะเบียนพนักงาน | โรงพยาบาลหนองพอก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --moph-green: #007d69; --soft-bg: #f4f7f6; }
        body { background-color: var(--soft-bg); font-family: 'Sarabun', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .register-card { width: 100%; max-width: 450px; background: #fff; border-radius: 16px; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #444; }
        .btn-submit { background: var(--moph-green); color: #fff; border: none; border-radius: 8px; padding: 12px; width: 100%; font-weight: 600; }
        
       
        #signature-pad { border: 2px dashed #ddd; border-radius: 8px; background: #fafafa; width: 100%; height: 150px; cursor: crosshair; touch-action: none; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold">สร้างบัญชีพนักงาน</h4>
            <p class="text-muted small">ข้อมูลนี้จะใช้สำหรับลงเวลาและเซ็นชื่อออนไลน์</p>
        </div>

        <?php if ($message != ""): ?>
            <div class="alert alert-<?= $msg_type ?> py-2 small text-center"><?= $message ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="registerForm">
            <div class="mb-3">
                <label class="form-label">ชื่อ-นามสกุล</label>
                <input type="text" name="fullname" class="form-control" placeholder="ระบุชื่อจริง-นามสกุล" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">ชื่อผู้ใช้งาน (Username)</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">รหัสผ่าน (Password)</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label text-primary"><i class="bi bi-pen-fill me-1"></i> ลายเซ็นของคุณ</label>
                
                <ul class="nav nav-tabs mb-3" id="signatureTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="draw-tab" data-bs-toggle="tab" data-bs-target="#draw-pane" type="button" role="tab">วาดลายเซ็น</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-pane" type="button" role="tab">อัปโหลดรูป</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="draw-pane" role="tabpanel" tabindex="0">
                        <canvas id="signature-pad"></canvas>
                        <input type="hidden" name="signature_base64" id="signature_base64">
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted" style="font-size: 0.75rem;">ใช้นิ้วหรือเมาส์วาดลายเซ็น</span>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clear-signature">ล้างลายเซ็น</button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="upload-pane" role="tabpanel" tabindex="0">
                        <input type="file" name="signature_file" id="signature_file" class="form-control" accept="image/*">
                        <div class="form-text" style="font-size: 0.75rem;">ไฟล์ .png หรือ .jpg (แนะนำพื้นหลังโปร่งใส)</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-submit mb-3">ลงทะเบียนและบันทึกลายเซ็น</button>
            <div class="text-center">
                <a href="login.php" class="text-decoration-none small text-muted">ย้อนกลับไปหน้าเข้าสู่ระบบ</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const canvas = document.getElementById('signature-pad');
        const ctx = canvas.getContext('2d');
        const clearBtn = document.getElementById('clear-signature');
        const base64Input = document.getElementById('signature_base64');
        const form = document.getElementById('registerForm');
        let isDrawing = false;
        let hasSignature = false;

       
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            ctx.scale(ratio, ratio);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#000';
        }
        window.addEventListener("resize", resizeCanvas);
        resizeCanvas();

       
        const startPosition = (e) => {
            isDrawing = true;
            hasSignature = true;
            draw(e);
        };

 
        const endPosition = () => {
            isDrawing = false;
            ctx.beginPath();
        };

    
        const draw = (e) => {
            if (!isDrawing) return;
            e.preventDefault();
            
            let clientX, clientY;
            if (e.type.includes('touch')) {
                const rect = canvas.getBoundingClientRect();
                clientX = e.touches[0].clientX - rect.left;
                clientY = e.touches[0].clientY - rect.top;
            } else {
                clientX = e.offsetX;
                clientY = e.offsetY;
            }

            ctx.lineTo(clientX, clientY);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(clientX, clientY);
        };

     
        canvas.addEventListener('mousedown', startPosition);
        canvas.addEventListener('mouseup', endPosition);
        canvas.addEventListener('mousemove', draw);

     
        canvas.addEventListener('touchstart', startPosition, {passive: false});
        canvas.addEventListener('touchend', endPosition);
        canvas.addEventListener('touchmove', draw, {passive: false});

     
        clearBtn.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSignature = false;
            base64Input.value = ""; 
        });

    
        form.addEventListener('submit', function(e) {
         
            const activeTab = document.querySelector('.nav-link.active').id;
            
            if (activeTab === 'draw-tab' && hasSignature) {
         
                base64Input.value = canvas.toDataURL("image/png");
            } else {
                base64Input.value = "";
            }
        });
    </script>
</body>
</html>