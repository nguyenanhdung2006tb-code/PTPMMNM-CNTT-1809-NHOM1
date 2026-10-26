<?php
require_once '../config/db.php';

$error = null;

// XỬ LÝ LƯU BIỂU MẪU MỚI KHI BẤM SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $questions = $_POST['questions'] ?? [];

    if (empty($title)) {
        $error = "Vui lòng nhập tiêu đề cho biểu mẫu.";
    } elseif (empty($questions)) {
        $error = "Biểu mẫu phải có ít nhất một câu hỏi.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Thêm biểu mẫu vào bảng forms
            $stmt_form = $pdo->prepare("INSERT INTO forms (title, description, created_at) VALUES (?, ?, NOW())");
            $stmt_form->execute([$title, $description]);
            $new_form_id = $pdo->lastInsertId();

            // 2. Tạo phiên bản form_versions đầu tiên (v1, is_active = 1)
            $stmt_ver = $pdo->prepare("INSERT INTO form_versions (form_id, version_number, is_active, created_at) VALUES (?, 1, 1, NOW())");
            $stmt_ver->execute([$new_form_id]);
            $new_version_id = $pdo->lastInsertId();

            // 3. Lưu từng câu hỏi vào bảng fields (sử dụng đúng cột options_json và ENUM hợp lệ)
            $stmt_field = $pdo->prepare("
                INSERT INTO fields (form_version_id, label, field_type, options_json, is_required, display_order) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $valid_types = ['text', 'number', 'date', 'radio', 'checkbox'];
            $order = 1;

            foreach ($questions as $q) {
                $label = trim($q['label'] ?? '');
                $type = in_array($q['type'] ?? '', $valid_types) ? $q['type'] : 'text';
                $required = isset($q['required']) ? 1 : 0;
                $options_raw = trim($q['options'] ?? '');
                
                // Nếu là dạng trắc nghiệm radio/checkbox, chuẩn hóa danh sách lựa chọn thành JSON
                $options_json = null;
                if (in_array($type, ['radio', 'checkbox']) && !empty($options_raw)) {
                    $opts_array = array_map('trim', explode(',', $options_raw));
                    $options_json = json_encode(array_values(array_filter($opts_array)), JSON_UNESCAPED_UNICODE);
                }

                if (!empty($label)) {
                    $stmt_field->execute([$new_version_id, $label, $type, $options_json, $required, $order]);
                    $order++;
                }
            }

            $pdo->commit();
            // Điều hướng về trang Admin kèm thông báo thành công
            header("Location: index.php?form_id=$new_form_id&created=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Lỗi khi lưu biểu mẫu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Công Cụ Thiết Kế Biểu Mẫu - NXB Nông Nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .builder-card { background: #fff; border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header-box { background: linear-gradient(135deg, #1b3527 0%, #2e7d32 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 25px 30px; }
        .question-card { background: #ffffff; border: 1px solid #e0e6ed; border-left: 5px solid #2e7d32; border-radius: 8px; padding: 18px; margin-bottom: 16px; transition: all 0.2s; }
        .question-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-color: #2e7d32; }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 900px;">
    <div class="mb-3">
        <a href="../index.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Quay lại Trang Quản Trị
        </a>
    </div>

    <div class="builder-card shadow-sm">
        <div class="header-box">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-tools fs-1 text-warning"></i>
                <div>
                    <h3 class="fw-bold mb-1">CÔNG CỤ THIẾT KẾ BIỂU MẪU</h3>
                    <p class="mb-0 opacity-75 small">Xây dựng form thu thập dữ liệu động cho NXB Nông Nghiệp</p>
                </div>
            </div>
        </div>

        <div class="p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="builderForm">
                <!-- THÔNG TIN CHUNG BIỂU MẪU -->
                <div class="card p-3 mb-4 bg-light border-0">
                    <h5 class="fw-bold text-success mb-3"><i class="bi bi-info-circle me-1"></i> 1. Thông Tin Chung</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên / Tiêu đề Biểu mẫu <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Ví dụ: Phiếu đăng ký đặt mua tạp chí 2026..." required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Mục đích thu thập (Mô tả)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Mô tả tóm tắt nội dung để người điền nắm rõ..."></textarea>
                    </div>
                </div>

                <!-- DANH SÁCH CÂU HỎI -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-success mb-0"><i class="bi bi-list-check me-1"></i> 2. Danh Sách Câu Hỏi</h5>
                        <button type="button" class="btn btn-outline-success btn-sm fw-semibold" id="btnAddQuestion">
                            <i class="bi bi-plus-circle me-1"></i> Thêm câu hỏi
                        </button>
                    </div>

                    <div id="questionsContainer">
                        <!-- Danh sách câu hỏi sinh tự động bằng JS -->
                    </div>
                </div>

                <!-- NÚT HOÀN TẤT -->
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="../index.php" class="btn btn-light px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Lưu & Phát Hành Biểu Mẫu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let questionCounter = 0;

function addQuestion(defaultLabel = '', defaultType = 'text', defaultOptions = '', isRequired = true) {
    questionCounter++;
    const container = document.getElementById('questionsContainer');

    const card = document.createElement('div');
    card.className = 'question-card shadow-sm';
    card.id = `qCard_${questionCounter}`;
    card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="badge bg-success fs-6 fw-bold">Câu hỏi #${questionCounter}</span>
            <button type="button" class="btn btn-outline-danger btn-sm border-0 py-0" onclick="removeQuestion(${questionCounter})" title="Xóa câu này">
                <i class="bi bi-trash fs-5"></i>
            </button>
        </div>
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label small fw-semibold text-muted">Nội dung câu hỏi / Nhãn trường <span class="text-danger">*</span></label>
                <input type="text" name="questions[${questionCounter}][label]" class="form-control" value="${defaultLabel}" placeholder="Ví dụ: Họ và tên, Số điện thoại..." required>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold text-muted">Loại dữ liệu</label>
                <select name="questions[${questionCounter}][type]" class="form-select q-type-select" onchange="handleTypeChange(this, ${questionCounter})">
                    <option value="text" ${defaultType === 'text' ? 'selected' : ''}>Văn bản ngắn (Text)</option>
                    <option value="number" ${defaultType === 'number' ? 'selected' : ''}>Chữ số (Number)</option>
                    <option value="date" ${defaultType === 'date' ? 'selected' : ''}>Ngày tháng (Date)</option>
                    <option value="radio" ${defaultType === 'radio' ? 'selected' : ''}>Trắc nghiệm 1 đáp án (Radio)</option>
                    <option value="checkbox" ${defaultType === 'checkbox' ? 'selected' : ''}>Hộp kiểm chọn nhiều (Checkbox)</option>
                </select>
            </div>
            
            <div class="col-12 options-row" id="optionsRow_${questionCounter}" style="display: ${(defaultType === 'radio' || defaultType === 'checkbox') ? 'block' : 'none'};">
                <label class="form-label small fw-semibold text-warning-emphasis">
                    <i class="bi bi-card-checklist me-1"></i> Các lựa chọn (ngăn cách bằng dấu phẩy)
                </label>
                <input type="text" name="questions[${questionCounter}][options]" class="form-control" value="${defaultOptions}" placeholder="Ví dụ: Lựa chọn A, Lựa chọn B, Lựa chọn C">
            </div>

            <div class="col-12">
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" name="questions[${questionCounter}][required]" id="req_${questionCounter}" ${isRequired ? 'checked' : ''}>
                    <label class="form-check-label small" for="req_${questionCounter}">Bắt buộc người dùng phải điền câu này</label>
                </div>
            </div>
        </div>
    `;

    container.appendChild(card);
}

function removeQuestion(id) {
    const card = document.getElementById(`qCard_${id}`);
    if (card) card.remove();
}

function handleTypeChange(selectElement, id) {
    const optRow = document.getElementById(`optionsRow_${id}`);
    if (selectElement.value === 'radio' || selectElement.value === 'checkbox') {
        optRow.style.display = 'block';
    } else {
        optRow.style.display = 'none';
    }
}

document.getElementById('btnAddQuestion').addEventListener('click', () => {
    addQuestion();
});

// Khởi tạo sẵn 3 câu hỏi mẫu phù hợp với CSDL
window.addEventListener('DOMContentLoaded', () => {
    addQuestion('Họ và tên độc giả', 'text', '', true);
    addQuestion('Số điện thoại liên hệ', 'number', '', true);
    addQuestion('Chuyên đề quan tâm', 'radio', 'Trồng trọt & BVTV, Chăn nuôi gia súc, Thủy hải sản', true);
});
</script>
</body>
</html>