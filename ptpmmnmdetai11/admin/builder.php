<?php 
require_once __DIR__ . '/auth_check.php'; 
require_once '../config/db.php';

$error = null;

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

            $stmt_form = $pdo->prepare("INSERT INTO forms (title, description, created_at) VALUES (?, ?, NOW())");
            $stmt_form->execute([$title, $description]);
            $new_form_id = $pdo->lastInsertId();

            $stmt_ver = $pdo->prepare("INSERT INTO form_versions (form_id, version_number, is_active, created_at) VALUES (?, 1, 1, NOW())");
            $stmt_ver->execute([$new_form_id]);
            $new_version_id = $pdo->lastInsertId();

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --primary-subtle: #ecfdf5;
            --bg-page: #f8fafc;
            --border-subtle: #e2e8f0;
            --text-heading: #0f172a;
        }
        body { 
            background-color: var(--bg-page); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }
        .builder-card { 
            background: #fff; 
            border-radius: 20px; 
            border: 1px solid var(--border-subtle); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            overflow: hidden;
        }
        .header-box { 
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%); 
            color: #fff; 
            padding: 30px 36px; 
        }
        .question-card { 
            background: #ffffff; 
            border: 1.5px solid var(--border-subtle); 
            border-left: 4px solid var(--primary); 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 16px; 
            transition: all 0.2s ease; 
        }
        .question-card:hover { 
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.08); 
            border-color: #86efac; 
        }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 900px;">
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Quay lại Bảng Quản Trị
        </a>
    </div>

    <div class="builder-card">
        <div class="header-box">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.18); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                    <i class="bi bi-ui-checks-grid text-white"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">CÔNG CỤ THIẾT KẾ BIỂU MẪU</h3>
                    <p class="mb-0 text-white-50 small">Khởi tạo và cấu hình trường dữ liệu thu thập động cho NXB Nông Nghiệp</p>
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
                <div class="card p-3 mb-4 bg-light border-0" style="border-radius: 14px;">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-success me-1"></i>1. Thông Tin Chung</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên / Tiêu đề Biểu mẫu <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Ví dụ: Phiếu đăng ký đặt mua tạp chí 2026..." required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Mục đích thu thập (Mô tả)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Mô tả tóm tắt nội dung để người điền nắm rõ..."></textarea>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-list-check text-success me-1"></i>2. Danh Sách Câu Hỏi</h5>
                        <button type="button" class="btn btn-outline-success btn-sm fw-semibold" id="btnAddQuestion">
                            <i class="bi bi-plus-circle me-1"></i> Thêm câu hỏi
                        </button>
                    </div>

                    <div id="questionsContainer"></div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="index.php" class="btn btn-light px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" style="background-color: var(--primary);">
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
    card.className = 'question-card';
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
                <label class="form-label small fw-semibold text-success">
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

window.addEventListener('DOMContentLoaded', () => {
    addQuestion('Họ và tên độc giả', 'text', '', true);
    addQuestion('Số điện thoại liên hệ', 'number', '', true);
    addQuestion('Chuyên đề quan tâm', 'radio', 'Trồng trọt & BVTV, Chăn nuôi gia súc, Thủy hải sản', true);
});
</script>
</body>
</html>