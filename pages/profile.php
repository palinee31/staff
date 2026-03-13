<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// สมมติว่ามีการเช็ค Login
if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
$message = "";

/* ======================
   บันทึกข้อมูล
====================== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST['fullname']);
    $password = trim($_POST['password']);

    $signature_name = null;
    $folder = "../uploads/signatures/";

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    /* 1. จัดการการวาดลายเซ็น (Base64) มาก่อน */
    if (!empty($_POST['signature_base64'])) {
        $base64_string = $_POST['signature_base64'];

        // แยกส่วนหัวของ Base64 ออก (เช่น data:image/png;base64,)
        $image_parts = explode(";base64,", $base64_string);
        $image_base64 = base64_decode($image_parts[1]);

        $signature_name = "sign_drawn_" . time() . "_" . uniqid() . ".png";
        $file_path = $folder . $signature_name;

        // สร้างไฟล์รูปภาพจาก Base64
        file_put_contents($file_path, $image_base64);
    }
    /* 2. ถ้าไม่ได้วาด แต่มีการอัปโหลดไฟล์มาแทน */ elseif (!empty($_FILES['signature']['name'])) {
        $file_ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
        $signature_name = "sign_upload_" . time() . "_" . uniqid() . "." . $file_ext;

        move_uploaded_file(
            $_FILES['signature']['tmp_name'],
            $folder . $signature_name
        );
    }

    /* อัปเดตข้อมูล */
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET fullname=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $hash, $user_id]);
    } else {

        $sql = "UPDATE users SET fullname=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$fullname, $user_id]);
    }

    // อัปเดตชื่อไฟล์รูปลงฐานข้อมูล (ถ้ามีการวาดหรืออัปโหลดใหม่)
    if ($signature_name) {
        $stmt = $conn->prepare("UPDATE users SET signature_path=? WHERE id=?");
        $stmt->execute([$signature_name, $user_id]);
    }

    $_SESSION['fullname'] = $fullname;

    $message = "บันทึกข้อมูลเรียบร้อยแล้ว";
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าบัญชีผู้ใช้</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            /* ปรับพื้นหลังให้ดู Modern และสว่างตา */
            background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
            font-family: 'Sarabun', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 30px 0;
        }

        .card {
            border: none;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 25px;
            border-bottom: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        /* ตกแต่ง Input ให้ไอคอนผสานกับกล่องข้อความ */
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #667eea;
            border-radius: 12px 0 0 12px;
        }

        .form-control {
            border-radius: 0 12px 12px 0;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-left: none;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #667eea;
            background-color: #ffffff;
        }

        .form-control:focus+.input-group-text,
        .input-group-text:has(+ .form-control:focus) {
            border-color: #667eea;
            background-color: #ffffff;
        }

        /* ตกแต่งปุ่ม */
        .btn {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-secondary:hover {
            transform: translateY(-2px);
            background-color: #f1f5f9;
            color: #4a5568;
        }

        /* กล่องลายเซ็นปัจจุบัน */
        .signature-preview-box {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 20px;
            background: #f8fafc;
            text-align: center;
            margin-top: 5px;
            transition: all 0.3s ease;
        }

        .signature-preview-box:hover {
            border-color: #667eea;
        }

        .signature-preview-box img {
            max-height: 120px;
            border-radius: 8px;
        }

        /* กระดานวาดลายเซ็น */
        .signature-pad-wrapper {
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            background-color: #ffffff;
            touch-action: none;
            position: relative;
            box-shadow: inset 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        canvas#signature-pad {
            width: 100%;
            height: 220px;
            cursor: crosshair;
        }

        /* ตัวคั่น (Divider) */
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            color: #a0aec0;
            margin: 25px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider::before {
            margin-right: 15px;
        }

        .divider::after {
            margin-left: 15px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">

                <div class="card">
                    <div class="card-header text-white text-center">
                        <h5><i class="bi bi-person-gear me-2"></i>ตั้งค่าบัญชีผู้ใช้</h5>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        <?php if ($message): ?>
                            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i><?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form id="profileForm" method="POST" enctype="multipart/form-data">

                            <div class="mb-4">
                                <label class="form-label">ชื่อ - นามสกุล</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="fullname" class="form-control"
                                        value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">รหัสผ่านใหม่</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-primary">
                                    <i class="bi bi-pen-fill me-2"></i>วาดลายเซ็น
                                </label>
                                <div class="signature-pad-wrapper mb-2">
                                    <canvas id="signature-pad"></canvas>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>ใช้เมาส์หรือนิ้ววาดในกรอบด้านบน</small>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-3" id="clear-signature" style="border-radius: 8px;">
                                        <i class="bi bi-eraser me-1"></i>ล้าง
                                    </button>
                                </div>
                                <input type="hidden" name="signature_base64" id="signature_base64">
                            </div>

                            <div class="divider">
                                <span style="font-size: 0.9rem;">หรืออัปโหลดรูปภาพ</span>
                            </div>

                            <div class="mb-4">
                                <input type="file" name="signature" class="form-control" accept="image/png, image/jpeg, image/jpg" style="border-radius: 12px; border-left: 1px solid #e2e8f0;">
                            </div>

                            <?php if (!empty($user['signature_path'])): ?>
                                <div class="mb-5">
                                    <label class="form-label text-muted" style="font-size: 0.9em;">ลายเซ็นปัจจุบันของคุณ:</label>
                                    <div class="signature-preview-box">
                                        <img src="../uploads/signatures/<?= htmlspecialchars($user['signature_path']) ?>" alt="Current Signature">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4 d-flex gap-3">
                                <a href="../pages/checker_page.php" class="btn btn-outline-secondary w-50">
                                    <i class="bi bi-arrow-left me-2"></i>ย้อนกลับ
                                </a>

                                <button type="submit" class="btn btn-primary w-50">
                                    <i class="bi bi-save me-2"></i>บันทึกข้อมูล
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var canvas = document.getElementById('signature-pad');

            function resizeCanvas() {
                var ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
            }

            window.onresize = resizeCanvas;
            resizeCanvas();

            var signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)'
            });

            document.getElementById('clear-signature').addEventListener('click', function() {
                signaturePad.clear();
            });

            document.getElementById('profileForm').addEventListener('submit', function(e) {
                if (!signaturePad.isEmpty()) {
                    document.getElementById('signature_base64').value = signaturePad.toDataURL('image/png');
                }
            });
        });
    </script>

</body>

</html>