<?php 
require_once __DIR__ . '/check_login.php'; 
require_once __DIR__ . '/config/db.php';

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$message = '';
$message_type = '';
$is_submitted_success = false;

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

    foreach ($fields as $field) {
        $fid = $field['id'];
        $val = $submitted_answers[$fid] ?? '';

        if (is_array($val)) {
            $val = implode(', ', $val);
        } else {
            $val = trim($val);
        }

        if ($field['is_required'] && $val === '') {
            $errors[] = "Vui lòng nhập/chọn thông tin cho trường: " . htmlspecialchars($field['label']);
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt_resp = $pdo->prepare("INSERT INTO responses (form_version_id, submitted_at) VALUES (?, NOW())");
            $stmt_resp->execute([$version_id]);
            $response_id = $pdo->lastInsertId();

            $stmt_ans = $pdo->prepare("INSERT INTO answers (response_id, field_id, answer_value) VALUES (?, ?, ?)");
            foreach ($fields as $field) {
                $fid = $field['id'];
                $val = $submitted_answers[$fid] ?? '';
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }
                $val = trim($val);
                if ($val !== '') {
                    $stmt_ans->execute([$response_id, $fid, $val]);
                }
            }

            $pdo->commit();
            $is_submitted_success = true;
            $message = "Gửi biểu mẫu thành công! Dữ liệu của bạn đã được ghi nhận vào hệ thống.";
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --primary-subtle: #ecfdf5;
            --bg-body: #f8fafc;
            --card-border: #e2e8f0;
            --text-heading: #0f172a;
            --text-body: #334155;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            min-height: 100vh;
            padding: 30px 0 60px 0;
        }

        .form-shell {
            max-width: 740px;
            margin: 0 auto;
        }

        .top-nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .btn-nav-back {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid var(--card-border);
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .btn-nav-back:hover {
            background-color: #f1f5f9;
            color: var(--primary);
            border-color: #cbd5e1;
        }

        .form-header-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            margin-bottom: 20px;
        }

        .form-header-strip {
            height: 8px;
            background: linear-gradient(90deg, #15803d, #22c55e);
        }

        .form-header-body {
            padding: 32px 32px 28px 32px;
        }

        .form-header-title {
            color: var(--text-heading);
            font-weight: 700;
            font-size: 1.55rem;
            margin-bottom: 12px;
        }

        .form-header-desc {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .form-meta-tag {
            background-color: var(--primary-subtle);
            color: var(--primary-hover);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .question-card {
            background: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }

        .question-card:focus-within {
            border-color: #86efac;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.08);
        }

        .question-label {
            font-weight: 600;
            color: var(--text-heading);
            font-size: 1rem;
            margin-bottom: 12px;
            display: block;
        }

        .text-required {
            color: #dc2626;
            font-weight: bold;
            margin-left: 2px;
        }

        .form-control, .form-select {
            border-radius: 9px;
            border: 1.5px solid #cbd5e1;
            padding: 10px 14px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }

        .custom-radio-option {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .custom-radio-option:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        .custom-radio-option input[type="radio"]:checked + label,
        .custom-radio-option input[type="checkbox"]:checked + label {
            font-weight: 600;
            color: var(--primary);
        }

        .custom-radio-option label {
            cursor: pointer;
            width: 100%;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        /* Nút gửi phản hồi màu xanh tươi sáng, bắt mắt */
        .btn-submit-form {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            padding: 12px 34px;
            border-radius: 10px;
            border: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.28);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit-form:hover {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(22, 163, 74, 0.35);
        }

        .success-box {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--card-border);
            padding: 48px 32px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .success-icon-wrap {
            width: 80px;
            height: 80px;
            background-color: var(--primary-subtle);
            color: var(--primary);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 20px;
        }

        .condition-hidden {
            display: none !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-shell">

        <div class="top-nav-bar">
            <a href="forms.php" class="btn-nav-back">
                <i class="bi bi-arrow-left"></i>
                <span>Danh sách biểu mẫu</span>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin/index.php" class="btn btn-outline-success btn-sm fw-semibold">
                    <i class="bi bi-gear-fill me-1"></i>Về trang Quản Trị
                </a>
            <?php endif; ?>
        </div>

        <?php if ($is_submitted_success): ?>
            <div class="success-box">
                <div class="success-icon-wrap">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Gửi biểu mẫu thành công!</h3>
                <p class="text-secondary mx-auto mb-4" style="max-width: 520px; font-size: 0.98rem;">
                    Câu trả lời của bạn đối với biểu mẫu <strong>"<?= htmlspecialchars($form['title']) ?>"</strong> đã được lưu trữ an toàn vào hệ thống của NXB Nông Nghiệp.
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="form.php?id=<?= $form['form_id'] ?>" class="btn btn-outline-secondary px-4 py-2 fw-semibold">
                        <i class="bi bi-arrow-repeat me-1"></i>Gửi phản hồi khác
                    </a>
                    <a href="forms.php" class="btn btn-submit-form px-4 py-2">
                        <i class="bi bi-grid-fill"></i>Xem danh sách biểu mẫu
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="form-header-card">
                <div class="form-header-strip"></div>
                <div class="form-header-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="form-meta-tag">
                            <i class="bi bi-shield-check"></i>NXB Nông Nghiệp
                        </span>
                        <span class="badge border" style="background-color: #ecfdf5; color: #16a34a; border-color: #bbf7d0 !important;">
                            <i class="bi bi-check2-circle me-1"></i>Biểu mẫu trực tuyến
                        </span>
                    </div>
                    <h1 class="form-header-title"><?= htmlspecialchars($form['title']) ?></h1>
                    <p class="form-header-desc mb-0">
                        <?= !empty($form['description']) ? htmlspecialchars($form['description']) : 'Vui lòng cung cấp đầy đủ thông tin bên dưới để hoàn tất việc gửi khảo sát.' ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($message) && $message_type === 'danger'): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= $message ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="dynamicForm">
                <?php foreach ($fields as $field): 
                    $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
                    
                    $default_value = '';
                    $label_lower = mb_strtolower($field['label'], 'UTF-8');
                    if (isset($_SESSION['fullname']) && (str_contains($label_lower, 'họ và tên') || str_contains($label_lower, 'họ tên') || str_contains($label_lower, 'người liên hệ'))) {
                        $default_value = htmlspecialchars($_SESSION['fullname']);
                    }
                ?>
                    <div class="question-card field-group" id="field-wrap-<?= $field['id'] ?>" data-field-id="<?= $field['id'] ?>">
                        <label class="question-label">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['is_required']): ?>
                                <span class="text-required" title="Trường bắt buộc">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($field['field_type'] === 'text'): ?>
                            <input type="text" name="fields[<?= $field['id'] ?>]" value="<?= $default_value ?>" class="form-control form-input" placeholder="Nhập câu trả lời..." <?= $field['is_required'] ? 'required' : '' ?>>

                        <?php elseif ($field['field_type'] === 'number'): ?>
                            <input type="number" name="fields[<?= $field['id'] ?>]" class="form-control form-input" placeholder="Nhập số lượng hoặc giá trị số..." <?= $field['is_required'] ? 'required' : '' ?>>

                        <?php elseif ($field['field_type'] === 'date'): ?>
                            <input type="date" name="fields[<?= $field['id'] ?>]" class="form-control form-input" <?= $field['is_required'] ? 'required' : '' ?>>

                        <?php elseif ($field['field_type'] === 'radio'): ?>
                            <div>
                                <?php foreach ($options as $optIndex => $optValue): ?>
                                    <div class="custom-radio-option">
                                        <input class="form-check-input form-input mt-0" type="radio" 
                                               name="fields[<?= $field['id'] ?>]" 
                                               id="opt_<?= $field['id'] ?>_<?= $optIndex ?>" 
                                               value="<?= htmlspecialchars($optValue) ?>" 
                                               <?= ($field['is_required'] && $optIndex === 0) ? 'checked' : '' ?>>
                                        <label for="opt_<?= $field['id'] ?>_<?= $optIndex ?>">
                                            <?= htmlspecialchars($optValue) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($field['field_type'] === 'checkbox'): ?>
                            <div>
                                <?php foreach ($options as $optIndex => $optValue): ?>
                                    <div class="custom-radio-option">
                                        <input class="form-check-input form-input mt-0" type="checkbox" 
                                               name="fields[<?= $field['id'] ?>][]" 
                                               id="chk_<?= $field['id'] ?>_<?= $optIndex ?>" 
                                               value="<?= htmlspecialchars($optValue) ?>">
                                        <label for="chk_<?= $field['id'] ?>_<?= $optIndex ?>">
                                            <?= htmlspecialchars($optValue) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                        <?php else: ?>
                            <!-- Nhánh dự phòng tự động sửa lỗi rỗng ô: Mặc định vẽ ô Text nếu kiểu chưa định danh -->
                            <input type="text" name="fields[<?= $field['id'] ?>]" class="form-control form-input" placeholder="Nhập câu trả lời..." <?= $field['is_required'] ? 'required' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-2">
                    <button type="submit" class="btn-submit-form">
                        <i class="bi bi-send-fill"></i>
                        <span>Gửi phản hồi</span>
                    </button>
                    <span class="text-muted small"><span class="text-required">*</span> Thông tin bắt buộc</span>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>

<script>
const conditions = <?= json_encode($conditions) ?>;

function evaluateConditions() {
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

    conditions.forEach(cond => {
        const targetWrap = document.getElementById('field-wrap-' + cond.target_field_id);
        if (targetWrap) {
            targetWrap.classList.add('condition-hidden');
            const input = targetWrap.querySelector('.form-input');
            if (input) input.disabled = true;
        }
    });

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

document.addEventListener('DOMContentLoaded', () => {
    const formElement = document.getElementById('dynamicForm');
    if (formElement) {
        evaluateConditions();
        formElement.addEventListener('change', evaluateConditions);
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>