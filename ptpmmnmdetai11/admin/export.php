<?php 
require_once __DIR__ . '/auth_check.php'; 
require_once '../config/db.php';

// Lấy form_id từ URL (mặc định lấy Form 1)
$formId = isset($_GET['form_id']) ? intval($_GET['form_id']) : 1;

// 1. Lấy thông tin tiêu đề biểu mẫu
$stmtForm = $pdo->prepare("SELECT title FROM forms WHERE id = ?");
$stmtForm->execute([$formId]);
$formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);
$formTitle = $formInfo ? $formInfo['title'] : "Biểu Mẫu $formId";

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

$fileName = "Bao_cao_Form_" . $formId . "_" . date('Ymd_His') . ".xls";

// Cấu hình Header xuất bảng tính Excel UTF-8
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if gte mso 9]>
    <xml>
    <x:ExcelWorkbook>
        <x:ExcelWorksheets>
            <x:ExcelWorksheet>
                <x:Name>Du_Lieu_Khao_Sat</x:Name>
                <x:WorksheetOptions>
                    <x:DisplayGridlines/>
                </x:WorksheetOptions>
            </x:ExcelWorksheet>
        </x:ExcelWorksheets>
    </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; }
        .excel-table { border-collapse: collapse; width: 100%; }
        
        /* Tiêu đề chính trang nhã */
        .header-title { 
            font-size: 15pt; 
            font-weight: bold; 
            color: #15803d; 
            text-align: center; 
            height: 42px;
            vertical-align: middle;
        }
        .header-sub { 
            font-size: 9.5pt; 
            color: #64748b; 
            text-align: center; 
            height: 24px;
            vertical-align: middle;
            font-style: italic;
        }
        
        /* Dòng tiêu đề cột: Màu xanh tươi chuẩn thương hiệu, viền mềm */
        .excel-table th { 
            background-color: #16a34a; 
            color: #ffffff; 
            font-weight: bold; 
            text-align: center; 
            border: 1px solid #15803d; 
            padding: 10px 12px; 
            font-size: 10.5pt;
            vertical-align: middle;
        }
        
        /* Dòng dữ liệu: Viền kẻ xám mỏng tinh tế, không còn viền đen thô */
        .excel-table td { 
            border: 1px solid #cbd5e1; 
            padding: 8px 10px; 
            font-size: 10pt; 
            vertical-align: middle; 
            color: #1e293b;
        }
        
        /* Hàng so le nhẹ nhàng giúp dễ đọc dòng dài */
        .row-even { background-color: #f8fafc; }
        .row-odd { background-color: #ffffff; }

        .text-center { text-align: center; }
        .text-cell { mso-number-format: "\@"; }
    </style>
</head>
<body>
    <table class="excel-table">
        <tr>
            <td colspan="<?= count($fields) + 2 ?>" class="header-title">
                BÁO CÁO DỮ LIỆU THU THẬP - NHÀ XUẤT BẢN NÔNG NGHIỆP
            </td>
        </tr>
        <tr>
            <td colspan="<?= count($fields) + 2 ?>" class="header-sub">
                Biểu mẫu: <?= htmlspecialchars($formTitle) ?> | Ngày xuất: <?= date('d/m/Y H:i') ?> | Tổng cộng: <?= count($responses) ?> bản ghi
            </td>
        </tr>
        <tr>
            <td colspan="<?= count($fields) + 2 ?>" style="border: none; height: 10px;"></td>
        </tr>
        <tr>
            <th style="width: 70px;">Mã</th>
            <th style="width: 140px;">Thời gian gửi</th>
            <?php foreach ($fields as $field): ?>
                <th><?= htmlspecialchars($field['label']) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php 
        $rowIndex = 0;
        foreach ($responses as $resp): 
            $rowIndex++;
            $rowClass = ($rowIndex % 2 === 0) ? 'row-even' : 'row-odd';
            $stmtAns = $pdo->prepare("SELECT field_id, answer_value FROM answers WHERE response_id = ?");
            $stmtAns->execute([$resp['id']]);
            $rawAnswers = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR);
        ?>
            <tr class="<?= $rowClass ?>">
                <td class="text-center text-cell" style="font-weight: 600; color: #475569;">#<?= $resp['id'] ?></td>
                <td class="text-center text-cell"><?= date('d/m/Y H:i', strtotime($resp['submitted_at'])) ?></td>
                <?php foreach ($fields as $field): 
                    $val = isset($rawAnswers[$field['id']]) ? trim($rawAnswers[$field['id']]) : '';
                ?>
                    <td class="text-cell">
                        <?= ($val !== '') ? htmlspecialchars($val) : '<span style="color:#94a3b8; font-style:italic;">(Bỏ trống)</span>' ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>