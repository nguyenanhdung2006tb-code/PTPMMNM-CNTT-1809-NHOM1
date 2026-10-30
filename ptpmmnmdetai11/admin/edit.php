<?php 
require_once __DIR__ . '/auth_check.php'; 
require_once '../config/db.php';

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;

// 1. Lấy thông tin form và phiên bản đang hoạt động
$stmt = $pdo->prepare("
    SELECT f.id, f.title, f.description, v.id AS version_id, v.version_number
    FROM forms f
    JOIN form_versions v ON f.id = v.form_id
    WHERE f.id = ? AND v.is_active = 1
    LIMIT 1
");
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    die("Không tìm thấy biểu mẫu cần chỉnh sửa hoặc biểu mẫu chưa được kích hoạt.");
}

$version_id = $form['version_id'];

// 2. Lấy danh sách câu hỏi hiện tại
$stmt_fields = $pdo->prepare("SELECT * FROM fields WHERE form_version_id = ? ORDER BY display_order ASC");
$stmt_fields->execute([$version_id]);
$existing_fields = $stmt_fields->fetchAll(PDO::FETCH_ASSOC);

// 3. Xử lý khi Admin nhấn LƯU THAY ĐỔI (POST)
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

            // Cập nhật thông tin tiêu đề và mô tả
            $stmt_u_form = $pdo->prepare("UPDATE forms SET title = ?, description = ? WHERE id = ?");
            $stmt_u_form->execute([$title, $description, $form_id]);

            // Xử lý danh sách câu hỏi
            $current_field_ids = array_column($existing_fields, 'id');
            $submitted_field_ids = [];

            $stmt_u_field = $pdo->prepare("
                UPDATE fields 
                SET label = ?, field_type = ?, options_json = ?, is_required = ?, display_order = ? 
                WHERE id = ? AND form_version_id = ?
            ");

            $stmt_i_field = $pdo->prepare("
                INSERT INTO fields (form_version_id, label, field_type, options_json, is_required, display_order) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $valid_types = ['text', 'number', 'date', 'radio', 'checkbox'];
            $order = 1;

            foreach ($questions as $q) {
                $field_id = !empty($q['field_id']) ? (int)$q['field_id'] : 0;
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
                    if ($field_id > 0 && in_array($field_id, $current_field_ids)) {
                        // Cập nhật câu hỏi cũ đã có
                        $stmt_u_field->execute([$label, $type, $options_json, $required, $order, $field_id, $version_id]);
                        $submitted_field_ids[] = $field_id;
                    } else {
                        // Thêm câu hỏi mới được tạo thêm
                        $stmt_i_field->execute([$version_id, $label, $type, $options_json, $required, $order]);
                        $submitted_field_ids[] = $pdo->lastInsertId();
                    }
                    $order++;
                }
            }

            // Xóa những câu hỏi bị Admin bấm nút thùng rác xóa bỏ
            $deleted_field_ids = array_diff($current_field_ids, $submitted_field_ids);
            if (!empty($deleted_field_ids)) {
                $in_clause = implode(',', array_fill(0, count($deleted_field_ids), '?'));
                
                // Dọn dẹp câu trả lời liên quan tới trường bị xóa
                $stmt_del_ans = $pdo->prepare("DELETE FROM answers WHERE field_id IN ($in_clause)");
                $stmt_del_ans->execute(array_values($deleted_field_ids));

                $stmt_del_fields = $pdo->prepare("DELETE FROM fields WHERE id IN ($in_clause) AND form_version_id = ?");
                $params = array_values($deleted_field_ids);
                $params[] = $version_id;
                $stmt_del_fields->execute($params);
            }

            $pdo->commit();
            header("Location: index.php?form_id=$form_id&updated=1");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Lỗi khi cập nhật biểu mẫu: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Biểu Mẫu - NXB Nông Nghiệp</title>
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
        <a href="index.php?form_id=<?= $form_id ?>" class="btn btn-outline-secondary btn-sm fw-semibold shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Quay lại Bảng Quản Trị
        </a>
    </div>

    <div class="builder-card">
        <div class="header-box">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.18); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                    <i class="bi bi-pencil-square text-white"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1">CHỈNH SỬA BIỂU MẪU #<?= $form_id ?></h3>
                    <p class="mb-0 text-white-50 small">Thay đổi tiêu đề, mô tả và cấu hình các trường dữ liệu thu thập</p>
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

            <form method="POST" id="editForm">
                <div class="card p-3 mb-4 bg-light border-0" style="border-radius: 14px;">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-success me-1"></i>1. Thông Tin Chung</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tên / Tiêu đề Biểu mẫu <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($form['title']) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Mục đích thu thập (Mô tả)</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($form['description']) ?></textarea>
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
                    <a href="index.php?form_id=<?= $form_id ?>" class="btn btn-light px-4">Hủy bỏ</a>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm" style="background-color: var(--primary);">
                        <i class="bi bi-check2-circle me-1"></i> Lưu Thay Đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let questionCounter = 0;

function addQuestion(fieldId = 0, defaultLabel = '', defaultType = 'text', defaultOptions = '', isRequired = true) {
    questionCounter++;
    const container = document.getElementById('questionsContainer');

    const card = document.createElement('div');
    card.className = 'question-card';
    card.id = `qCard_${questionCounter}`;
    card.innerHTML = `
        <input type="hidden" name="questions[${questionCounter}][field_id]" value="${fieldId}">
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
    addQuestion(0, '', 'text', '', true);
});

// Nạp tự động toàn bộ câu hỏi cũ của form lên màn hình
const existingQuestions = <?= json_encode($existing_fields, JSON_UNESCAPED_UNICODE) ?>;
window.addEventListener('DOMContentLoaded', () => {
    if (existingQuestions.length > 0) {
        existingQuestions.forEach(q => {
            let opts = '';
            if (q.options_json) {
                try {
                    const arr = JSON.parse(q.options_json);
                    opts = arr.join(', ');
                } catch(e) {}
            }
            addQuestion(q.id, q.label, q.field_type, opts, q.is_required == 1);
        });
    } else {
        addQuestion(0, '', 'text', '', true);
    }
});
</script>
</body>
</html>