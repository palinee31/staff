<?php
require_once __DIR__ . '/../config/db.php';

$staff_id = $_POST['staff_id'];

$folder = "../uploads/signature/";

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$file_name = "sign_" . $staff_id . "_" . time() . ".png";

$path = $folder . $file_name;


if (isset($_POST['signature'])) {

    $data = $_POST['signature'];

    $data = str_replace('data:image/png;base64,', '', $data);

    $data = base64_decode($data);

    file_put_contents($path, $data);
}


if (isset($_FILES['file'])) {

    $upload = $folder . time() . "_" . $_FILES['file']['name'];

    move_uploaded_file($_FILES['file']['tmp_name'], $upload);

    $path = $upload;
}


$sql = "UPDATE time_logs SET signature=? WHERE staff_id=? ORDER BY checkin DESC LIMIT 1";

$stmt = $conn->prepare($sql);

$stmt->execute([$path, $staff_id]);

echo "ok";
