<?php
session_start();
require_once __DIR__ . '/../config/db.php';
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$checker_id = $_SESSION['id'];
$checker_name = $_SESSION['fullname'];

// ดึงลายเซ็น
$stmt = $conn->prepare("SELECT signature_path FROM users WHERE id = ?");
$stmt->execute([$checker_id]);
$checker_signature = $stmt->fetchColumn();

// ดึงแผนก
$stmt = $conn->prepare("SELECT * FROM departments ORDER BY department_name");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// สถิติ Dashboard
$total_today = $conn->query("SELECT COUNT(*) FROM time_logs WHERE work_date = CURDATE()")->fetchColumn();
$checked = $conn->query("SELECT COUNT(*) FROM time_logs WHERE checked_by IS NOT NULL")->fetchColumn();
$not_checked = $conn->query("SELECT COUNT(*) FROM time_logs WHERE checked_by IS NULL AND time_out IS NOT NULL")->fetchColumn();

$department = $_GET['department'] ?? "";
$name = $_GET['name'] ?? "";
$date = $_GET['date'] ?? "";

// Pagination & Query
$params = [];
$limit = 5; // จำนวนรายการต่อหน้า
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT t.*, u.fullname, d.department_name, c.fullname AS checker_name 
        FROM time_logs t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN departments d ON t.department_id = d.id 
        LEFT JOIN users c ON t.checked_by = c.id 
        WHERE 1";

if ($department) {
    $sql .= " AND t.department_id=?";
    $params[] = $department;
}
if ($name) {
    $sql .= " AND u.fullname LIKE ?";
    $params[] = "%$name%";
}
if ($date) {
    $sql .= " AND t.work_date=?";
    $params[] = $date;
}

// นับจำนวนทั้งหมดสำหรับ Pagination
$count_sql = str_replace(
    "SELECT t.*, u.fullname, d.department_name, c.fullname AS checker_name",
    "SELECT COUNT(*)",
    $sql
);
$stmt_count = $conn->prepare($count_sql);
$stmt_count->execute($params);
$total_rows = $stmt_count->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$sql .= " ORDER BY t.work_date DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คัดแยกข้อมูล
$waiting_logs = [];
$checked_logs = [];
foreach ($logs as $log) {
    if (!empty($log['time_out'])) {
        if (!$log['checked_by']) {
            $waiting_logs[] = $log;
        } else {
            $checked_logs[] = $log;
        }
    }
}

usort($checked_logs, function ($a, $b) {
    $timeA = strtotime($a['checked_at'] ?? '0');
    $timeB = strtotime($b['checked_at'] ?? '0');
    return $timeB <=> $timeA;
});

// บันทึกการอนุมัติ
if (isset($_POST['approve'])) {
    $id = $_POST['id'];
    if (!empty($checker_signature)) {
        $signature_path_to_save = "uploads/signatures/" . $checker_signature;
        $stmt = $conn->prepare("
            UPDATE time_logs 
            SET checked_by = ?, signature = ?, checked_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$checker_id, $signature_path_to_save, $id]);
    }
    header("Location: checker_page.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบตรวจเวลา - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/chacker.css">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7fa;
        }

        /* Dashboard Cards */
        .dashboard-card {
            border-radius: 12px;
            border: none;
            transition: transform 0.2s;
            border-left: 5px solid;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
        }

        /* Custom Pagination matching user's image */
        .custom-pagination {
            gap: 8px;
        }

        .custom-pagination .page-item .page-link {
            border: none;
            color: #4a5568;
            background-color: #f8f9fa;
            border-radius: 12px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 1.1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }

        .custom-pagination .page-item.active .page-link {
            background-color: #003366;
            /* Dark blue from the image */
            color: white;
            box-shadow: 0 4px 8px rgba(0, 51, 102, 0.3);
        }

        .custom-pagination .page-item .page-link:hover:not(.active) {
            background-color: #e2e8f0;
        }

        .custom-pagination .page-item.disabled .page-link {
            background-color: #f8f9fa;
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Tables & Tabs */
        .nav-pills .nav-link {
            color: #6c757d;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-pills .nav-link.active {
            background-color: #003366;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
        }

        .table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .content-section {
            animation: fadeIn 0.4s ease;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm" style="background-color: #1a202c;">
        <div class="container">
            <a href="staff_page.php" class="btn text-white text-decoration-none border-0">
                <i class="bi bi-chevron-left"></i> ย้อนกลับ
            </a>

            <div class="navbar-brand fw-bold mx-auto">
                <i class="bi bi-clock-history me-2"></i>ระบบลงเวลาปฏิบัติงาน
            </div>

            <div class="d-flex align-items-center gap-3 pe-2">
                <span class="text-white me-2 d-none d-md-block small">
                    ยินดีต้อนรับ, <strong><?= htmlspecialchars($checker_name) ?></strong>
                </span>
                <a href="profile.php" class="text-white" title="ตั้งค่าบัญชี" style="text-decoration: none; opacity: 1; transition: 0.3s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'">
                    <i class="bi bi-gear-fill fs-5"></i>
                </a>
                <a href="../auth/logout.php" class="text-white" title="ออกจากระบบ" style="text-decoration: none; opacity: 1; transition: 0.3s;" onmouseover="this.style.opacity='0.7'" onmouseout="this.style.opacity='1'" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')">
                    <i class="bi bi-power fs-5"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h4 mb-0 text-dark fw-bold">แผงควบคุมการตรวจสอบ</h1>
            <span class="badge bg-white text-primary shadow-sm p-2 px-3 rounded-pill border fw-medium">
                <i class="bi bi-calendar3 me-1"></i> <?= date('d M Y') ?>
            </span>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm dashboard-card h-100 py-3" style="border-left-color: #0d6efd;">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1 small tracking-wide">วันนี้มาทำงาน</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="h2 mb-0 fw-bold text-dark"><?= $total_today ?></div>
                            <i class="bi bi-people fs-2 text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm dashboard-card h-100 py-3" style="border-left-color: #198754;">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1 small tracking-wide">ตรวจเสร็จแล้ว</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="h2 mb-0 fw-bold text-dark"><?= $checked ?></div>
                            <i class="bi bi-check2-circle fs-2 text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm dashboard-card h-100 py-3" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1 small tracking-wide">รอการตรวจสอบ</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="h2 mb-0 fw-bold text-dark"><?= $not_checked ?></div>
                            <i class="bi bi-hourglass-split fs-2 text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex mb-4">
            <ul class="nav nav-pills bg-white p-1 rounded-pill shadow-sm border" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4" onclick="showSection('section-waiting', this)">
                        <i class="bi bi-clock me-1"></i> รอตรวจ
                        <?php if ($not_checked > 0): ?>
                            <span class="badge bg-danger ms-1 rounded-circle"><?= $not_checked ?></span>
                        <?php endif; ?>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4" onclick="showSection('section-checked', this)">
                        <i class="bi bi-check-all me-1"></i> ตรวจแล้ว
                    </button>
                </li>
            </ul>
        </div>

        <div class="table-container shadow-sm">
            <div id="section-waiting" class="content-section table-purple">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <h5 class="text-dark fw-bold m-0"><i class="bi bi-circle-fill text-warning me-2 small"></i>รายการที่รอการตรวจสอบ</h5>

                    <div class="input-group shadow-sm" style="max-width: 250px;">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-funnel text-muted"></i></span>
                        <select id="departmentFilter" class="form-select border-start-0" onchange="filterDepartment()">
                            <option value="all">ทุกแผนก</option>
                            <?php
                            if (!empty($waiting_logs)) {
                                $unique_depts = array_unique(array_column($waiting_logs, 'department_name'));
                                sort($unique_depts);
                                foreach ($unique_depts as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach;
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="waitingTable">
                        <thead>
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>แผนก</th>
                                <th>เวลา เข้า-ออก</th>
                                <th width="150" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($waiting_logs as $log):
                                $time_in = strtotime($log['time_in']);
                                $time_out = strtotime($log['time_out']);
                                $diff_hours = ($time_out - $time_in) / 3600;
                            ?>
                                <tr class="log-row" data-department="<?= htmlspecialchars($log['department_name']) ?>">
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['fullname']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime($log['work_date'])) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border px-2 py-1"><?= htmlspecialchars($log['department_name']) ?></span></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="text-success small fw-semibold"><i class="bi bi-box-arrow-in-right me-1"></i><?= date('H:i', strtotime($log['time_in'])) ?></span>
                                            <span class="text-danger small fw-semibold"><i class="bi bi-box-arrow-right me-1"></i><?= date('H:i', strtotime($log['time_out'])) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($checker_signature)): ?>
                                            <button type="button" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#confirmModal<?= $log['id'] ?>">
                                                ตรวจสอบ <i class="bi bi-arrow-right-short"></i>
                                            </button>

                                            <div class="modal fade" id="confirmModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content shadow-lg border-0 rounded-4">
                                                        <div class="modal-header border-0 pb-0 mt-2 px-4">
                                                            <h5 class="modal-title fw-bold text-dark">ข้อมูลการลงเวลา</h5>
                                                            <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-center pt-2 px-4">
                                                            <h2 class="fw-bolder mb-4 text-primary" style="font-size: 2.2rem;">
                                                                <?= date('d/m/Y', strtotime($log['work_date'])) ?>
                                                            </h2>
                                                            <div class="row text-center mb-4 border rounded-3 mx-1 py-2 bg-light">
                                                                <div class="col-4 border-end py-2">
                                                                    <div class="text-muted small mb-1">เข้างาน</div>
                                                                    <h4 class="fw-bold text-success mb-0"><?= date('H:i', strtotime($log['time_in'])) ?></h4>
                                                                </div>
                                                                <div class="col-4 border-end py-2">
                                                                    <div class="text-muted small mb-1">ออกงาน</div>
                                                                    <h4 class="fw-bold text-danger mb-0"><?= date('H:i', strtotime($log['time_out'])) ?></h4>
                                                                </div>
                                                                <div class="col-4 py-2">
                                                                    <div class="text-muted small mb-1">รวม (ชม.)</div>
                                                                    <h4 class="fw-bold text-dark mb-0"><?= number_format($diff_hours, 2) ?></h4>
                                                                </div>
                                                            </div>
                                                            <div class="p-3 text-start mb-3 mx-1 bg-white border rounded-3">
                                                                <div class="text-muted small mb-1"><i class="bi bi-chat-left-text me-1"></i> หมายเหตุ / ภารกิจ:</div>
                                                                <div class="fw-medium text-dark">
                                                                    <?= !empty($log['note']) ? htmlspecialchars($log['note']) : '<span class="text-muted fst-italic">ไม่มีการระบุหมายเหตุ</span>' ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0 px-4 pb-4">
                                                            <form method="POST" class="w-100 m-0" onsubmit="handleApprove(event, this, '<?= htmlspecialchars($log['fullname']) ?>')">
                                                                <input type="hidden" name="id" value="<?= $log['id'] ?>">
                                                                <button type="submit" class="btn btn-primary text-white w-100 py-3 rounded-pill fw-bold fs-5 shadow-sm">
                                                                    <i class="bi bi-check-circle me-2"></i>ยืนยันการตรวจสอบ
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-danger small mb-1">ไม่พบลายเซ็น</div>
                                            <a href="profile.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">ตั้งค่าลายเซ็น</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                            if (empty($waiting_logs)): ?>
                                <tr>
                                    <td colspan='4' class='text-center py-5 text-muted'>
                                        <i class='bi bi-inbox fs-1 d-block mb-2 opacity-50'></i>ไม่มีข้อมูลรอการตรวจสอบ
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr id="empty-filter-msg" style="display: none;">
                                <td colspan='4' class='text-center py-5 text-muted'>
                                    <i class='bi bi-funnel fs-1 d-block mb-2 opacity-50'></i>ไม่พบข้อมูลในแผนกที่เลือก
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="section-checked" class="content-section table-green" style="display:none;">
                <h5 class="mb-4 text-dark fw-bold"><i class="bi bi-circle-fill text-success me-2 small"></i>ประวัติการตรวจสอบที่เสร็จสิ้น</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ชื่อเจ้าหน้าที่</th>
                                <th>แผนก</th>
                                <th>เข้า-ออก</th>
                                <th>ผู้ตรวจ</th>
                                <th width="150" class="text-center">รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($checked_logs as $log):
                                $time_in = strtotime($log['time_in']);
                                $time_out = strtotime($log['time_out']);
                                $diff_hours = ($time_out - $time_in) / 3600;
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['fullname']) ?></div>
                                        <div class="small text-muted"><i class="bi bi-calendar-check me-1"></i><?= date('d/m/Y', strtotime($log['work_date'])) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border px-2 py-1"><?= htmlspecialchars($log['department_name']) ?></span></td>
                                    <td class="small fw-medium">
                                        <span class="text-success"><i class="bi bi-box-arrow-in-right me-1"></i><?= date('H:i', strtotime($log['time_in'])) ?></span><br>
                                        <span class="text-danger"><i class="bi bi-box-arrow-right me-1"></i><?= date('H:i', strtotime($log['time_out'])) ?></span>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-success bg-success bg-opacity-10 py-1 px-2 rounded d-inline-block">
                                            <i class="bi bi-person-check-fill me-1"></i><?= htmlspecialchars($log['checker_name']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-success btn-sm w-100 fw-bold shadow-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#viewModal<?= $log['id'] ?>">
                                            <i class="bi bi-search"></i> ดูข้อมูล
                                        </button>

                                        <div class="modal fade" id="viewModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content shadow-lg border-0 rounded-4">
                                                    <div class="modal-header border-0 pb-0 mt-2 px-4">
                                                        <h5 class="modal-title fw-bold text-dark">รายละเอียดการลงเวลา</h5>
                                                        <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-center pt-2 px-4 pb-4">
                                                        <h2 class="fw-bolder mb-4 text-success" style="font-size: 2.2rem;">
                                                            <?= date('d/m/Y', strtotime($log['work_date'])) ?>
                                                        </h2>
                                                        <div class="row text-center mb-4 border rounded-3 mx-1 py-2 bg-light">
                                                            <div class="col-4 border-end py-2">
                                                                <div class="text-muted small mb-1">เข้างาน</div>
                                                                <h4 class="fw-bold text-success mb-0"><?= date('H:i', strtotime($log['time_in'])) ?></h4>
                                                            </div>
                                                            <div class="col-4 border-end py-2">
                                                                <div class="text-muted small mb-1">ออกงาน</div>
                                                                <h4 class="fw-bold text-danger mb-0"><?= date('H:i', strtotime($log['time_out'])) ?></h4>
                                                            </div>
                                                            <div class="col-4 py-2">
                                                                <div class="text-muted small mb-1">รวม (ชม.)</div>
                                                                <h4 class="fw-bold text-dark mb-0"><?= number_format($diff_hours, 2) ?></h4>
                                                            </div>
                                                        </div>
                                                        <div class="p-3 text-start mb-3 mx-1 bg-white border rounded-3">
                                                            <div class="text-muted small mb-1"><i class="bi bi-chat-left-text me-1"></i> หมายเหตุ / ภารกิจ:</div>
                                                            <div class="fw-medium text-dark">
                                                                <?= !empty($log['note']) ? htmlspecialchars($log['note']) : '<span class="text-muted fst-italic">ไม่มีการระบุหมายเหตุ</span>' ?>
                                                            </div>
                                                        </div>
                                                        <div class="p-3 mx-1 border border-success border-opacity-25 rounded-3" style="background-color: #f8fff9;">
                                                            <div class="text-success small fw-bold mb-2">
                                                                <i class="bi bi-check-circle-fill me-1"></i> ตรวจสอบเรียบร้อยแล้ว
                                                            </div>
                                                            <div class="bg-white p-2 rounded border d-inline-block mb-2 shadow-sm">
                                                                <img src="../<?= htmlspecialchars($log['signature']) ?>" width="120" class="object-fit-contain">
                                                            </div>
                                                            <div class="small text-dark fw-bold">ลงชื่อ: <?= htmlspecialchars($log['checker_name']) ?></div>
                                                            <?php if (!empty($log['checked_at'])): ?>
                                                                <div class="small text-muted mt-1" style="font-size: 0.8rem;">
                                                                    เวลาที่ตรวจ: <?= date('d/m/Y H:i', strtotime($log['checked_at'])) ?> น.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                            if (empty($checked_logs)) echo "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='bi bi-inbox fs-1 d-block mb-2 opacity-50'></i>ไม่พบข้อมูลการตรวจสอบ</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav class="mt-4 d-flex justify-content-center">
                    <ul class="pagination custom-pagination mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $page - 1 ?>" aria-label="Previous">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $page + 1 ?>" aria-label="Next">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let activeTab = sessionStorage.getItem("activeTab");

            if (activeTab === "section-checked") {

                let checkedBtn = document.querySelector('button[onclick*="section-checked"]');
                if (checkedBtn) showSection('section-checked', checkedBtn);
            } else {

                let waitingBtn = document.querySelector('button[onclick*="section-waiting"]');
                if (waitingBtn) showSection('section-waiting', waitingBtn);
            }
        });


        function showSection(sectionId, btn) {
            document.querySelectorAll('.content-section').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));

            const targetSection = document.getElementById(sectionId);
            targetSection.style.display = 'block';
            btn.classList.add('active');


            sessionStorage.setItem("activeTab", sectionId);
        }


        function handleApprove(event, form, name) {
            event.preventDefault();
            Swal.fire({
                title: 'ยืนยันการตรวจสอบ',
                html: `คุณแน่ใจหรือไม่ว่าต้องการอนุมัติเวลาของ <br><b class="text-primary fs-5">${name}</b> ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#003366',
                cancelButtonColor: '#e74a3b',
                confirmButtonText: 'ยืนยันอนุมัติ',
                cancelButtonText: 'ยกเลิก',
                reverseButtons: true,
                backdrop: `rgba(0,0,0,0.6)`
            }).then((result) => {
                if (result.isConfirmed) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'approve';
                    input.value = '1';
                    form.appendChild(input);

                    const modalEl = form.closest('.modal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        title: 'กำลังบันทึกข้อมูล...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });
                    form.submit();
                }
            });
        }

        function filterDepartment() {
            const selectedDept = document.getElementById('departmentFilter').value;
            const rows = document.querySelectorAll('#section-waiting .log-row');
            let visibleCount = 0;

            rows.forEach(row => {
                if (selectedDept === 'all' || row.getAttribute('data-department') === selectedDept) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptyMsgRow = document.getElementById('empty-filter-msg');
            if (visibleCount === 0 && rows.length > 0) {
                emptyMsgRow.style.display = '';
            } else {
                emptyMsgRow.style.display = 'none';
            }
        }
    </script>
</body>

</html>