<?php
require_once __DIR__ . '/../config/db.php';

$dep  = $_GET['dep'] ?? 'all';
$date = $_GET['date'] ?? date("Y-m-d");

$params = [$date];

$sql = "
    SELECT 
        u.fullname,
        d.department_name,
        DATE_FORMAT(t.time_in,'%H:%i') AS time_in
    FROM time_logs t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN departments d ON t.department_id = d.id
    WHERE t.work_date = ?
";

if ($dep != "all" && !empty($dep)) {
    $sql .= " AND t.department_id=?";
    $params[] = $dep;
}

$sql .= " ORDER BY t.time_in ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($rows);

$html = "
<table class='table table-hover align-middle mb-0'>
    <thead class='table-light'>
        <tr class='text-secondary'>
            <th class='py-3' style='width: 45%;'>ชื่อ-นามสกุลเจ้าหน้าที่</th>
            <th class='py-3'>แผนก/หน่วยงาน</th>
            <th class='py-3 text-center'>เวลาเข้าเวร</th>
        </tr>
    </thead>
    <tbody>
";

if ($total > 0) {
    foreach ($rows as $r) {
        $html .= "
        <tr>
            <td class='fw-bold text-dark'>
                <i class='fas fa-user-circle me-2 text-muted'></i>" . htmlspecialchars($r['fullname']) . "
            </td>
            <td>
                <span class='badge bg-light text-dark border'>" . htmlspecialchars($r['department_name']) . "</span>
            </td>
            <td class='text-center'>
                <span class='badge bg-success-subtle text-success px-3 py-2 fw-bold' style='font-size: 0.9rem;'>
                    <i class='far fa-clock me-1'></i>" . $r['time_in'] . " น.
                </span>
            </td>
        </tr>
        ";
    }
} else {
    $html .= "
    <tr>
        <td colspan='3' class='text-center py-5'>
            <div class='text-muted'>
                <i class='fas fa-info-circle fa-2x mb-3 opacity-25'></i>
                <p class='mb-0'>ยังไม่มีข้อมูลการปฏิบัติงานในวันที่เลือก</p>
            </div>
        </td>
    </tr>
    ";
}

$html .= "</tbody></table>";

header('Content-Type: application/json');
echo json_encode([
    "html" => $html,
    "total" => $total
]);
