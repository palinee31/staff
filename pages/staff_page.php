<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_POST['go_time'])) {
    if (!empty($_POST['department_id']) && $_POST['department_id'] != "all") {
        $_SESSION['department_id'] = $_POST['department_id'];
        header("Location: time.php");
        exit;
    } else {
        $error = "กรุณาระบุแผนกที่ต้องการเข้าใช้งาน";
    }
}

$stmt = $conn->query("SELECT id, department_name FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบลงเวลาปฏิบัติงาน | Official System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Sarabun', sans-serif;
        }

        .main-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 20px;
        }

        .digital-clock {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-radius: 50px;
            padding: 10px 25px;
            display: inline-block;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-top: 15px;
        }

        .btn-action {
            border-radius: 12px;
            padding: 12px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-badge {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 12px;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">

                <div class="card main-card">
                    <div class="header-section text-center position-relative">

                        <a href="../auth/logout.php"
                            class="btn btn-light btn-sm position-absolute end-0 top-0 m-3 shadow-sm">
                            <i class="fas fa-right-from-bracket me-1"></i> ออกจากระบบ
                        </a> <i class="fas fa-clock-rotate-left fa-3x mb-3 text-warning"></i>
                        <h2 class="fw-bold mb-1">ระบบลงเวลาปฏิบัติงาน</h2>
                        <p class="mb-3 opacity-75">Staff Time Attendance Management System</p>

                        <div class="digital-clock">
                            <div id="current-date" class="small mb-1"></div>
                            <div id="current-time" class="fs-3 fw-bold"></div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                                <i class="fas fa-exclamation-circle me-2 fs-5"></i>
                                <div><?= $error ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-4 text-center">
                                <label class="form-label fw-bold h5 text-dark mb-3">กรุณาเลือกแผนกปฏิบัติงาน</label>
                                <select name="department_id" id="department" class="form-select form-select-lg text-center shadow-sm" style="border-radius: 15px; border-color: #0d6efd;">
                                    <option value="all">-- คลิกเพื่อเลือกแผนก --</option>
                                    <?php foreach ($departments as $dep): ?>
                                        <option value="<?= $dep['id'] ?>">
                                            <?= htmlspecialchars($dep['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-outline-secondary btn-action w-100" onclick="openStaffPopup()">
                                        <i class="fas fa-clipboard-list me-2"></i> ตรวจรายชื่อวันนี้
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="go_time" class="btn btn-primary btn-action w-100 shadow">
                                        <i class="fas fa-arrow-right-to-bracket me-2"></i> เข้าหน้าลงเวลา
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card-footer text-center py-3 bg-light text-muted small">
                        <i class="fas fa-shield-halved me-1"></i> ระบบรักษาความปลอดภัยข้อมูลภายในหน่วยงาน
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="staffModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold" id="depTitle">รายชื่อเจ้าหน้าที่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted mb-1"><i class="fas fa-calendar-day me-1"></i> ประจำวันที่</label>
                            <input type="date" id="searchDate" class="form-control rounded-pill" onchange="loadStaff()">
                        </div>
                        <div class="col-md-5">
                            <label class="small fw-bold text-muted mb-1">
                                <i class="fas fa-building me-1"></i> เลือกแผนก
                            </label>

                            <select id="selectDepartment" class="form-select rounded-pill" onchange="changeDepartment()">
                                <option value="">-- เลือกแผนก --</option>

                                <?php foreach ($departments as $dep): ?>
                                    <option value="<?= $dep['id'] ?>">
                                        <?= htmlspecialchars($dep['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-badge text-center">
                                <small class="d-block text-muted text-uppercase mb-1" style="font-size: 10px;">ยอดรวมวันนี้</small>
                                <span id="totalStaff" class="h4 fw-bold text-primary">0</span> <small class="text-muted">คน</small>
                            </div>
                        </div>
                    </div>

                    <div id="staffList" class="table-responsive" style="min-height: 250px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function updateDateTime() {
            const now = new Date();
            const dateOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            document.getElementById('current-date').textContent = now.toLocaleDateString('th-TH', dateOptions);

            const timeString = now.toLocaleTimeString('th-TH', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            document.getElementById('current-time').textContent = timeString + " น.";
        }

        updateDateTime();
        setInterval(updateDateTime, 1000);

        let currentDep = "";
        let refreshTimer = null;

        function openStaffPopup() {
            let select = document.getElementById("department");
            currentDep = select.value;

            document.getElementById("depTitle").innerHTML = "<i class='fas fa-users-viewfinder me-2 text-primary'></i>" + select.options[select.selectedIndex].text;
            document.getElementById("searchDate").value = new Date().toISOString().slice(0, 10);

            let myModal = new bootstrap.Modal(document.getElementById('staffModal'));
            myModal.show();
            loadStaff();

            if (refreshTimer) clearInterval(refreshTimer);
            refreshTimer = setInterval(loadStaff, 10000);
        }

        function loadStaff() {
            let date = document.getElementById("searchDate").value;
            fetch(`load_staff.php?dep=${currentDep}&date=${date}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById("staffList").innerHTML = data.html;
                    document.getElementById("totalStaff").innerText = data.total;
                })
                .catch(err => console.error("Error loading staff:", err));
        }

        function searchDepartment() {
            let input = document.getElementById("searchDepartment").value.toLowerCase();
            let select = document.getElementById("department");
            let options = select.getElementsByTagName("option");

            for (let i = 0; i < options.length; i++) {
                let txt = options[i].text.toLowerCase();
                options[i].style.display = txt.includes(input) ? "" : "none";
            }
        }

        function changeDepartment() {

            let dep = document.getElementById("selectDepartment").value;

            if (dep !== "") {
                currentDep = dep;
                loadStaff();
            }

        }
    </script>

</body>

</html>