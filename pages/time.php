<?php
session_start();
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id       = $_SESSION['id'];
$fullname      = $_SESSION['fullname'];
$department_id = $_SESSION['department_id'] ?? 1;

$today = date("Y-m-d");
$message = "";
$msg_type = "success";

function getLatestLog($conn, $user_id, $today)
{
    $stmt = $conn->prepare("SELECT * FROM time_logs 
                            WHERE user_id=? AND work_date=? 
                            ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id, $today]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$log = getLatestLog($conn, $user_id, $today);

if (isset($_POST['change_department'])) {
    $department_id = $_POST['department_id'];
    $_SESSION['department_id'] = $department_id;
    $message = "เปลี่ยนแผนกปฏิบัติงานเรียบร้อยแล้ว";
}

if (isset($_POST['save_all_time'])) {
    $time_in_val  = $_POST['manual_time_in'];
    $time_out_val = $_POST['manual_time_out'];
    $note         = $_POST['note'] ?? "";

    $full_time_in  = $today . " " . $time_in_val . ":00";
    $full_time_out = $today . " " . $time_out_val . ":00";

    $ts_in  = strtotime($full_time_in);
    $ts_out = strtotime($full_time_out);

    if ($ts_out < $ts_in) {
        $ts_out += 86400;
        $full_time_out = date("Y-m-d H:i:s", $ts_out);
    }

    $hours = number_format(($ts_out - $ts_in) / 3600, 2);

    $stmt = $conn->prepare("INSERT INTO time_logs (user_id, department_id, work_date, time_in, time_out, work_hours, note) 
                            VALUES(?,?,?,?,?,?,?)");
    $stmt->execute([$user_id, $department_id, $today, $full_time_in, $full_time_out, $hours, $note]);

    header("Location: time.php");
    exit;
}

$page        = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$search_date = isset($_GET['date']) ? $_GET['date'] : "";
$limit = 5;
$page  = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$params = [$user_id];
$where_clause = "WHERE t.user_id = ?";

if (!empty($search_date)) {
    $where_clause .= " AND t.work_date = ?";
    $params[] = $search_date;
}

$stmt_count = $conn->prepare("SELECT COUNT(*) FROM time_logs t $where_clause");
$stmt_count->execute($params);
$total_rows = $stmt_count->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt_total = $conn->prepare("SELECT SUM(work_hours) FROM time_logs t $where_clause");
$stmt_total->execute($params);
$total_hours = $stmt_total->fetchColumn() ?: 0;

$query = "SELECT t.*, d.department_name, u.fullname AS checker FROM time_logs t 
          LEFT JOIN departments d ON t.department_id=d.id 
          LEFT JOIN users u ON t.checked_by=u.id 
          $where_clause 
          ORDER BY t.id DESC LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$history_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$departments = $conn->query("SELECT * FROM departments")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบลงเวลาปฏิบัติงาน</title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <link rel="stylesheet" href="../css/time.css">
</head>

<body>
    <nav class="top-navbar">
        <a href="staff_page.php" class="btn-back-nav"><i class="fas fa-chevron-left"></i> ย้อนกลับ</a>
        <div class="h6 mb-0 text-center flex-grow-1"><i class="fas fa-history"></i> ระบบลงเวลาปฏิบัติงาน</div>

        <a href="../auth/logout.php" class="btn-logout-nav" onclick="return confirm('คุณต้องการออกจากระบบใช่หรือไม่?')">
            <i class="fas fa-power-off"></i>
        </a>
    </nav>

    <div class="container py-4" style="max-width: 850px;">

        <div class="card border-0 shadow-sm mb-4" style="border-radius: 5px; overflow: hidden;">
            <div class="header-clock-card">
                <div id="date-label" class="small opacity-75"></div>
                <h1 id="clock">00:00:00</h1>
                <div class="user-info-top">
                    <i class="fas fa-user-circle"></i> ผู้ใช้งาน: <strong><?= htmlspecialchars($fullname) ?></strong>
                </div>
            </div>
            <div class="p-4">
                <form method="POST" class="row g-2">
                    <div class="col-9">
                        <select name="department_id" class="form-select rounded-3">
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $department_id == $d['id'] ? 'selected' : '' ?>><?= $d['department_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-3">
                        <button name="change_department" class="btn btn-primary w-100 rounded-3">เปลี่ยน</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card main-card p-4 mb-4">
            <form method="POST">
                <div class="row g-3 mb-3 text-center">
                    <div class="col-6">
                        <label class="small text-muted d-block">เวลาเข้า</label>
                        <input type="time" name="manual_time_in" class="form-control form-control-lg text-center fw-bold border-0 bg-light" value="<?= date('H:i') ?>">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted d-block">เวลาออก</label>
                        <input type="time" name="manual_time_out" class="form-control form-control-lg text-center fw-bold border-0 bg-light" value="<?= date('H:i') ?>">
                    </div>
                    <div class="col-12">
                        <input type="text" name="note" class="form-control border-0 bg-light" placeholder="ระบุหมายเหตุ/ภารกิจ...">
                    </div>
                </div>
                <button name="save_all_time" class="btn btn-dark btn-lg w-100 rounded-4 py-3 shadow-sm fw-bold">บันทึกข้อมูลการปฏิบัติงาน</button>
            </form>
        </div>

        <div class="card main-card p-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>ประวัติการลงเวลา</h5>
                <span class="badge bg-primary rounded-pill px-3 py-2">สะสมชั่วโมงร่วม <?= number_format($total_hours, 2) ?> ชม.</span>
            </div>
            <form method="GET" class="search-box">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($search_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-dark w-100"><i class="fas fa-search"></i> ค้นหา</button>
                    </div>
                    <div class="col-md-3">
                        <a href="time.php" class="btn btn-outline-secondary w-100">ล้างค่า</a>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 15%;">วันที่</th>
                            <th style="width: 20%;">เวลา (เข้า-ออก)</th>
                            <th style="width: 35%;">หมายเหตุ / รายละเอียด</th>
                            <th class="text-center" style="width: 10%;">ชม.</th>
                            <th style="width: 20%;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($history_logs) > 0): ?>
                            <?php foreach ($history_logs as $row):
                                $p_date = date('d/m/Y', strtotime($row['work_date']));
                                $p_in   = date('H:i', strtotime($row['time_in']));
                                $p_out  = date('H:i', strtotime($row['time_out']));
                                $p_note = htmlspecialchars($row['note'] ?: '-');
                            ?>
                                <tr onclick="showFullDetails('<?= $p_date ?>', '<?= $p_in ?>', '<?= $p_out ?>', '<?= $row['work_hours'] ?>', '<?= htmlspecialchars($row['checker'] ?? 'รอยืนยัน') ?>', '<?= !empty($row['signature']) ? '../' . $row['signature'] : '' ?>', '<?= !empty($row['photo_path']) ? '../uploads/' . $row['photo_path'] : '' ?>', '<?= $p_note ?>')" style="cursor:pointer">
                                    <td><strong><?= $p_date ?></strong></td>
                                    <td>
                                        <span class="text-success fw-bold"><?= $p_in ?></span>
                                        <i class="fas fa-caret-right mx-1 text-muted"></i>
                                        <span class="text-danger fw-bold"><?= $p_out ?></span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="<?= $p_note ?>">
                                            <i class="far fa-sticky-note me-1 text-warning"></i>
                                            <span class="text-muted small"><?= $p_note ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold text-primary"><?= $row['work_hours'] ?></td>
                                    <td>
                                        <span class="status-badge <?= $row['checker'] ? 'bg-verified' : 'bg-pending' ?>">
                                            <i class="fas <?= $row['checker'] ? 'fa-check-circle' : 'fa-clock' ?> me-1"></i>
                                            <?= $row['checker'] ? 'ตรวจแล้ว' : 'รอยืนยัน' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">ไม่พบข้อมูลการลงเวลา</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal fade" id="detailModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content custom-design shadow-lg">
                    <div class="modal-header border-0 pt-4 px-4 pb-0">
                        <h5 class="modal-title fw-bold text-muted">ข้อมูลการลงเวลา</h5>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <h1 id="detDate" class="fw-bold mb-4" style="font-size: 2.8rem; color: #2d3436;"></h1>

                        <div class="row align-items-center mb-4">
                            <div class="col-4">
                                <small class="detail-label">เข้างาน</small>
                                <p id="detIn" class="detail-value-large text-success"></p>
                            </div>
                            <div class="col-1">
                                <div class="divider-v"></div>
                            </div>
                            <div class="col-2 px-0">
                                <small class="detail-label">ออกงาน</small>
                                <p id="detOut" class="detail-value-large text-danger"></p>
                            </div>
                            <div class="col-1">
                                <div class="divider-v"></div>
                            </div>
                            <div class="col-4">
                                <small class="detail-label">รวม</small>
                                <p id="detHours" class="detail-value-large" style="font-size: 1.8rem;"></p>
                            </div>
                        </div>

                        <div class="detail-box">
                            <span class="detail-label">หมายเหตุ / ภารกิจ:</span>
                            <div id="detNote" class="fw-bold text-dark fs-5"></div>
                        </div>

                        <div class="detail-box">
                            <span class="detail-label">ผู้ตรวจสอบ:</span>
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="detChecker" class="fw-bold fs-5"></div>
                                <div id="sigArea">
                                    <img id="detSignature" src="" style="height: 60px; mix-blend-mode: multiply; display:none;">
                                    <small id="noSigText" class="text-muted" style="display:none;">(รอยืนยัน)</small>
                                </div>
                            </div>
                        </div>

                        <div id="photoContainer" class="mt-3 text-start" style="display:none;">
                            <span class="detail-label ps-2">รูปถ่ายหลักฐาน:</span>
                            <img id="detPhoto" src="" class="img-fluid rounded-4 shadow-sm mt-2" style="max-height: 250px; width: 100%; object-fit: cover; cursor: pointer;" onclick="window.open(this.src)">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center custom-pagination">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?p=<?= $page - 1 ?>&date=<?= $search_date ?>"><i class="fas fa-chevron-left"></i></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?p=<?= $i ?>&date=<?= $search_date ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?p=<?= $page + 1 ?>&date=<?= $search_date ?>"><i class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById("date-label").innerHTML = now.toLocaleDateString('th-TH', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            document.getElementById("clock").innerHTML = now.toLocaleTimeString('th-TH', {
                hour12: false
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function showFullDetails(date, timeIn, timeOut, hours, checker, sig, photo, note) {
            document.getElementById("detDate").innerText = date;
            document.getElementById("detIn").innerText = timeIn;
            document.getElementById("detOut").innerText = timeOut;
            document.getElementById("detHours").innerText = hours + " ชม.";
            document.getElementById("detChecker").innerText = checker;
            document.getElementById("detNote").innerText = note || "-";

            const detPhoto = document.getElementById("detPhoto");
            const photoCont = document.getElementById("photoContainer");
            if (photo && photo.length > 15) {
                detPhoto.src = photo;
                photoCont.style.display = "block";
            } else {
                photoCont.style.display = "none";
            }

            const detSig = document.getElementById("detSignature");
            const noSig = document.getElementById("noSigText");
            if (sig && sig.length > 5) {
                detSig.src = sig;
                detSig.style.display = "block";
                noSig.style.display = "none";
            } else {
                detSig.style.display = "none";
                noSig.style.display = "block";
            }

            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }

        function showZoom(src) {
            if (!src || src.includes('undefined') || src === '') return;
            document.getElementById('imgZoom').src = src;
            var zoomModal = new bootstrap.Modal(document.getElementById('zoomImageModal'));
            zoomModal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const detPhoto = document.getElementById('detPhoto');
            const detSignature = document.getElementById('detSignature');

            if (detPhoto) {
                detPhoto.onclick = function() {
                    showZoom(this.src);
                };
            }
            if (detSignature) {
                detSignature.onclick = function() {
                    showZoom(this.src);
                };
            }
        });
    </script>
    <div class="modal fade" id="zoomImageModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0 text-center">
                <div class="text-end mb-2">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <img id="imgZoom" src="" class="img-fluid rounded-4 shadow-lg" style="max-height: 85vh; cursor: pointer;" data-bs-dismiss="modal">
                <div class="mt-3">
                    <span class="badge rounded-pill bg-dark px-4 py-2 opacity-75">คลิกที่รูปเพื่อปิด</span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>