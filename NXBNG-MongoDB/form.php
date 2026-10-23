<?php
require_once 'db.php';

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$message = '';
$message_type = '';

// 1. Lấy thông tin biểu mẫu và phiên bản đang hoạt động
$stmt = $pdo->prepare("
    SELECT f.id AS form_id, f.title, f.description, v.id AS version_id, v.version_number 
    FROM forms f
    JOIN form_versions v ON f.id = v.form_id
    WHERE f.id = ? AND v.is_active = 1
    LIMIT 1
");
$stmt->execute([$form_id]);
$form = $stmt->fetch();

if (!$form) {
    die("Biểu mẫu không tồn tại hoặc chưa được kích hoạt.");
}

$version_id = $form['version_id'];

// 2. Lấy danh sách các trường (fields) của biểu mẫu
$stmt = $pdo->prepare("SELECT * FROM fields WHERE form_version_id = ? ORDER BY display_order ASC");
$stmt->execute([$version_id]);
$fields = $stmt->fetchAll();

// 3. Lấy danh sách điều kiện ẩn/hiện (conditions)
$stmt = $pdo->prepare("SELECT * FROM conditions WHERE form_version_id = ?");
$stmt->execute([$version_id]);
$conditions = $stmt->fetchAll();

// 4. Xử lý khi người dùng nhấn GỬI BIỂU MẪU (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_answers = $_POST['fields'] ?? [];
    $errors = [];

    // Kiểm tra trường bắt buộc ở máy chủ (Server-side validation)
    foreach ($fields as $field) {
        $fid = $field['id'];
        $val = isset($submitted_answers[$fid]) ? trim($submitted_answers[$fid]) : '';

        if ($field['is_required'] && $val === '') {
            $errors[] = "Vui lòng nhập/chọn thông tin cho trường: " . htmlspecialchars($field['label']);
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Lưu lượt gửi mới vào responses
            $stmt_resp = $pdo->prepare("INSERT INTO responses (form_version_id, submitted_at) VALUES (?, NOW())");
            $stmt_resp->execute([$version_id]);
            $response_id = $pdo->lastInsertId();

            // Lưu từng câu trả lời vào answers
            $stmt_ans = $pdo->prepare("INSERT INTO answers (response_id, field_id, answer_value) VALUES (?, ?, ?)");
            foreach ($fields as $field) {
                $fid = $field['id'];
                $val = $submitted_answers[$fid] ?? '';
                if ($val !== '') {
                    $stmt_ans->execute([$response_id, $fid, $val]);
                }
            }

            $pdo->commit();
            $message = "Gửi biểu mẫu thành công! Dữ liệu đã được lưu vào hệ thống.";
            $message_type = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Lỗi khi lưu dữ liệu: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($form['title']) ?> - NXB Nông Nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .form-container { max-width: 760px; margin: 40px auto; background: #fff; padding: 35px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .header-box { border-bottom: 2px solid #28a745; padding-bottom: 15px; margin-bottom: 25px; }
        .form-label { font-weight: 600; color: #333; }
        .text-required { color: #dc3545; }
        .condition-hidden { display: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <!-- Tiêu đề biểu mẫu -->
        <div class="header-box text-center">
            <span class="badge bg-success mb-2">HỆ THỐNG BIỂU MẪU THU THẬP DỮ LIỆU</span>
            <h3 class="fw-bold text-success"><?= htmlspecialchars($form['title']) ?></h3>
            <p class="text-muted mb-1"><?= htmlspecialchars($form['description']) ?></p>
            <small class="text-secondary">(Phiên bản áp dụng: v<?= $form['version_number'] ?>)</small>
        </div>

        <!-- Thông báo kết quả -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form nhập liệu -->
        <form method="POST" id="dynamicForm">
            <?php foreach ($fields as $field): 
                $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
            ?>
                <div class="mb-3 field-group" id="field-wrap-<?= $field['id'] ?>" data-field-id="<?= $field['id'] ?>">
                    <label class="form-label">
                        <?= htmlspecialchars($field['label']) ?>
                        <?php if ($field['is_required']): ?>
                            <span class="text-required">*</span>
                        <?php endif; ?>
                    </label>

                    <?php if ($field['field_type'] === 'text'): ?>
                        <input type="text" name="fields[<?= $field['id'] ?>]" class="form-control form-input" <?= $field['is_required'] ? 'required' : '' ?>>

                    <?php elseif ($field['field_type'] === 'number'): ?>
                        <input type="number" name="fields[<?= $field['id'] ?>]" class="form-control form-input" <?= $field['is_required'] ? 'required' : '' ?>>

                    <?php elseif ($field['field_type'] === 'date'): ?>
                        <input type="date" name="fields[<?= $field['id'] ?>]" class="form-control form-input" <?= $field['is_required'] ? 'required' : '' ?>>

                    <?php elseif ($field['field_type'] === 'radio'): ?>
                        <div>
                            <?php foreach ($options as $optIndex => $optValue): ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input form-input" type="radio" 
                                           name="fields[<?= $field['id'] ?>]" 
                                           id="opt_<?= $field['id'] ?>_<?= $optIndex ?>" 
                                           value="<?= htmlspecialchars($optValue) ?>" 
                                           <?= ($field['is_required'] && $optIndex === 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="opt_<?= $field['id'] ?>_<?= $optIndex ?>">
                                        <?= htmlspecialchars($optValue) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-success btn-lg px-5">Xác nhận gửi biểu mẫu</button>
            </div>
        </form>
    </div>
</div>

<!-- Kịch bản xử lý điều kiện ẩn/hiện động -->
<script>
const conditions = <?= json_encode($conditions) ?>;

function evaluateConditions() {
    // Thu thập giá trị hiện tại của tất cả các trường
    const fieldValues = {};
    document.querySelectorAll('.field-group').forEach(group => {
        const fid = group.dataset.fieldId;
        const checkedRadio = group.querySelector('input[type="radio"]:checked');
        const standardInput = group.querySelector('input[type="text"], input[type="number"], input[type="date"]');
        
        if (checkedRadio) {
            fieldValues[fid] = checkedRadio.value;
        } else if (standardInput) {
            fieldValues[fid] = standardInput.value;
        } else {
            fieldValues[fid] = '';
        }
    });

    // Mặc định ẩn các trường là mục tiêu của điều kiện show
    conditions.forEach(cond => {
        const targetWrap = document.getElementById('field-wrap-' + cond.target_field_id);
        if (targetWrap) {
            targetWrap.classList.add('condition-hidden');
            const input = targetWrap.querySelector('.form-input');
            if (input) input.disabled = true; // Vô hiệu hóa để không gửi lên nếu bị ẩn
        }
    });

    // Kiểm tra điều kiện thỏa mãn để hiển thị lại
    conditions.forEach(cond => {
        const currentVal = fieldValues[cond.source_field_id];
        const targetWrap = document.getElementById('field-wrap-' + cond.target_field_id);

        if (targetWrap && currentVal === cond.trigger_value) {
            targetWrap.classList.remove('condition-hidden');
            const input = targetWrap.querySelector('.form-input');
            if (input) input.disabled = false;
        }
    });
}

// Bắt sự kiện người dùng thay đổi dữ liệu trên biểu mẫu
document.addEventListener('DOMContentLoaded', () => {
    evaluateConditions();
    document.getElementById('dynamicForm').addEventListener('change', evaluateConditions);
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>