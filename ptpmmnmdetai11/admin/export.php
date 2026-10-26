<?php
require_once '../config/db.php';

// Lấy form_id từ URL (mặc định lấy Form 1)
$formId = isset($_GET['form_id']) ? intval($_GET['form_id']) : 1;

// 1. Lấy thông tin tiêu đề biểu mẫu
$stmtForm = $pdo->prepare("SELECT title FROM forms WHERE id = ?");
$stmtForm->execute([$formId]);
$formInfo = $stmtForm->fetch();
$formTitle = $formInfo ? $formInfo['title'] : "Bieu_Mau_$formId";

// 2. Lấy danh sách các câu hỏi (fields) của biểu mẫu này
$stmtFields = $pdo->prepare("
    SELECT f.id, f.label 
    FROM fields f
    JOIN form_versions fv ON f.form_version_id = fv.id
    WHERE fv.form_id = ? AND fv.is_active = 1
    ORDER BY f.display_order ASC
");
$stmtFields->execute([$formId]);
$fields = $stmtFields->fetchAll(PDO::FETCH_ASSOC);

// 3. Lấy danh sách các câu trả lời thuộc form này
$stmtResponses = $pdo->prepare("
    SELECT r.id, r.submitted_at 
    FROM responses r
    JOIN form_versions fv ON r.form_version_id = fv.id
    WHERE fv.form_id = ? 
    ORDER BY r.id DESC
");
$stmtResponses->execute([$formId]);
$responses = $stmtResponses->fetchAll(PDO::FETCH_ASSOC);

// Đặt tên file xuất ra
$fileName = "Bao_cao_Form_" . $formId . "_" . date('Ymd_His') . ".csv";

// Cấu hình Header để trình duyệt tự động tải file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

$output = fopen('php://output', 'w');

// Ghi chuỗi BOM UTF-8 để mở bằng Excel không bị lỗi font tiếng Việt
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 4. Tạo hàng tiêu đề cột
$headers = ['Mã phản hồi', 'Thời gian gửi'];
foreach ($fields as $field) {
    $headers[] = $field['label'];
}
fputcsv($output, $headers);

// 5. Điền dữ liệu chi tiết của từng lượt nộp
foreach ($responses as $resp) {
    $stmtAns = $pdo->prepare("SELECT field_id, answer_value FROM answers WHERE response_id = ?");
    $stmtAns->execute([$resp['id']]);
    $rawAnswers = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR);

    $row = [
        '#' . $resp['id'],
        date('d/m/Y H:i', strtotime($resp['submitted_at']))
    ];

    foreach ($fields as $field) {
        $val = isset($rawAnswers[$field['id']]) ? $rawAnswers[$field['id']] : '';
        $row[] = $val;
    }

    fputcsv($output, $row);
}

fclose($output);
exit;