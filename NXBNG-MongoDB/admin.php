<?php
require_once 'db.php';

// 1. Lấy danh sách các biểu mẫu để làm bộ lọc
$stmt = $pdo->query("
    SELECT f.id, f.title, 
           (SELECT COUNT(*) FROM responses r 
            JOIN form_versions v ON r.form_version_id = v.id 
            WHERE v.form_id = f.id) AS total_responses
    FROM forms f
    ORDER BY f.id ASC
");
$forms = $stmt->fetchAll();

// 2. Xác định form đang được chọn xem (mặc định form 1)
$selected_form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : ($forms[0]['id'] ?? 1);

// 3. Lấy danh sách các câu hỏi của phiên bản form hiện tại
$stmt_fields = $pdo->prepare("
    SELECT f.id, f.label, f.field_type 
    FROM fields f
    JOIN form_versions v ON f.form_version_id = v.id
    WHERE v.form_id = ? AND v.is_active = 1
    ORDER BY f.display_order ASC
");
$stmt_fields->execute([$selected_form_id]);
$fields = $stmt_fields->fetchAll();

// 4. Lấy danh sách các lượt gửi kèm câu trả lời
$stmt_responses = $pdo->prepare("
    SELECT r.id AS response_id, r.submitted_at, v.version_number
    FROM responses r
    JOIN form_versions v ON r.form_version_id = v.id
    WHERE v.form_id = ?
    ORDER BY r.submitted_at DESC
");
$stmt_responses->execute([$selected_form_id]);
$responses = $stmt_responses->fetchAll();

// Gom toàn bộ câu trả lời theo từng response_id
$response_ids = array_column($responses, 'response_id');
$answers_map = [];

if (!empty($response_ids)) {
    $in_clause = implode(',', array_fill(0, count($response_ids), '?'));
    $stmt_ans = $pdo->prepare("
        SELECT response_id, field_id, answer_value 
        FROM answers 
        WHERE response_id IN ($in_clause)
    ");
    $stmt_ans->execute($response_ids);
    while ($row = $stmt_ans->fetch()) {
        $answers_map[$row['response_id']][$row['field_id']] = $row['answer_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Trị Dữ Liệu Thu Thập - NXB Nông Nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #1e3a2b; min-height: 100vh; color: #fff; padding-top: 20px; }
        .sidebar a { color: #cfd8dc; text-decoration: none; padding: 12px 20px; display: block; border-radius: 8px; margin: 4px 12px; }
        .sidebar a:hover, .sidebar a.active { background: #2e7d32; color: #fff; font-weight: 600; }
        .main-card { background: #fff; border-radius: 12px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table-responsive { max-height: 580px; }
        thead th { position: sticky; top: 0; background: #2e7d32 !important; color: white !important; z-index: 10; font-weight: 600; }
        
        /* Tối ưu định dạng khi in ấn hoặc xuất PDF */
        @media print {
            .sidebar, .btn, .no-print, .input-group, #toolbarArea { display: none !important; }
            .col-md-9, .col-lg-10 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
            .table-responsive { max-height: none !important; overflow: visible !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar quản lý danh sách biểu mẫu -->
        <div class="col-md-3 col-lg-2 px-0 sidebar">
            <div class="text-center pb-3 border-bottom border-secondary mb-3">
                <i class="bi bi-file-earmark-spreadsheet-fill fs-2 text-warning"></i>
                <h6 class="mt-2 text-uppercase fw-bold text-light">NXB NÔNG NGHIỆP</h6>
                <small class="text-success-emphasis">Quản Trị Biểu Mẫu (Đề tài 11)</small>
            </div>
            
            <div class="px-3 mb-2 text-uppercase text-secondary" style="font-size: 11px;">Danh mục Biểu mẫu</div>
            <?php foreach ($forms as $f): ?>
                <a href="admin.php?form_id=<?= $f['id'] ?>" class="<?= $selected_form_id == $f['id'] ? 'active' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-truncate" style="max-width: 135px;" title="<?= htmlspecialchars($f['title']) ?>">
                            <?= htmlspecialchars($f['title']) ?>
                        </span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= $f['total_responses'] ?></span>
                    </div>
                </a>
            <?php endforeach; ?>

            <div class="mt-4 px-3">
                <hr class="border-secondary">
                <a href="form.php?id=<?= $selected_form_id ?>" target="_blank" class="btn btn-outline-success text-light text-center w-100 py-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Mở trang điền form
                </a>
            </div>
        </div>

        <!-- Vùng hiển thị bảng dữ liệu thu thập -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-success mb-1">Dữ Liệu Phản Hồi Đã Thu Thập</h4>
                    <p class="text-muted mb-0">Theo dõi thông tin khách hàng điền trực tiếp từ hệ thống biểu mẫu có điều kiện</p>
                </div>
                <div class="d-flex gap-2">
                    <!-- Nút xuất Excel (CSV UTF-8) -->
                    <a href="export.php?form_id=<?= $selected_form_id ?>" class="btn btn-success fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> Xuất Excel (CSV)
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary">
                        <i class="bi bi-printer me-1"></i> In danh sách
                    </button>
                    <a href="form.php?id=<?= $selected_form_id ?>" target="_blank" class="btn btn-outline-success">
                        <i class="bi bi-plus-circle me-1"></i> Gửi thử phản hồi
                    </a>
                </div>
            </div>

            <!-- Thanh công cụ tìm kiếm và thông tin số lượng -->
            <div id="toolbarArea" class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm theo tên, SĐT, công ty, địa chỉ...">
                    </div>
                </div>
                <div class="col-md-6 col-lg-7 text-md-end text-muted small">
                    <span>Đang hiển thị: <strong id="visibleCount" class="text-success"><?= count($responses) ?></strong> / <strong><?= count($responses) ?></strong> phản hồi</span>
                </div>
            </div>

            <!-- Bảng dữ liệu câu trả lời -->
            <div class="card main-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0" id="responsesTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 70px;">Mã</th>
                                    <th style="min-width: 150px;">Thời gian gửi</th>
                                    <?php foreach ($fields as $field): ?>
                                        <th style="min-width: 170px;"><?= htmlspecialchars($field['label']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($responses)): ?>
                                    <tr class="no-data-row">
                                        <td colspan="<?= count($fields) + 2 ?>" class="text-center py-4 text-muted">
                                            Chưa có dữ liệu phản hồi nào cho biểu mẫu này.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($responses as $resp): 
                                        $rid = $resp['response_id'];
                                    ?>
                                        <tr class="data-row">
                                            <td class="text-center fw-bold text-secondary">#<?= $rid ?></td>
                                            <td>
                                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($resp['submitted_at'])) ?></small>
                                            </td>
                                            <?php foreach ($fields as $field): 
                                                $val = $answers_map[$rid][$field['id']] ?? null;
                                            ?>
                                                <td>
                                                    <?php if ($val !== null && $val !== ''): ?>
                                                        <span class="text-dark"><?= htmlspecialchars($val) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted fst-italic" style="font-size: 13px;">(Bỏ trống / Ẩn)</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Xử lý bộ lọc tìm kiếm thời gian thực (Live Search)
document.getElementById('liveSearchInput').addEventListener('input', function() {
    const keyword = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll('#responsesTable tbody tr.data-row');
    let visibleTotal = 0;

    rows.forEach(row => {
        const rowContent = row.textContent.toLowerCase();
        if (rowContent.includes(keyword)) {
            row.style.display = '';
            visibleTotal++;
        } else {
            row.style.display = 'none';
        }
    });

    // Cập nhật bộ đếm hiển thị
    document.getElementById('visibleCount').textContent = visibleTotal;
});
</script>
</body>
</html>