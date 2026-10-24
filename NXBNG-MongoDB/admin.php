<?php
require_once 'db.php';

// 1. Lấy danh sách các biểu mẫu
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

// Lấy thông tin tiêu đề form đang chọn
$current_form_title = "Biểu mẫu";
foreach ($forms as $f) {
    if ($f['id'] == $selected_form_id) {
        $current_form_title = $f['title'];
        break;
    }
}

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

// 5. TÍNH TOÁN DỮ LIỆU KPI & BIỂU ĐỒ (CHART.JS)
$total_count = count($responses);
$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
$recent_count = 0;

$chart_field = null;
foreach ($fields as $f) {
    if ($f['field_type'] === 'radio') {
        $chart_field = $f;
        break;
    }
}

$chart_counts = [];
foreach ($responses as $resp) {
    if ($resp['submitted_at'] >= $seven_days_ago) {
        $recent_count++;
    }

    if ($chart_field) {
        $val = $answers_map[$resp['response_id']][$chart_field['id']] ?? '';
        if ($val !== '') {
            $chart_counts[$val] = ($chart_counts[$val] ?? 0) + 1;
        }
    }
}

$chart_labels = array_keys($chart_counts);
$chart_values = array_values($chart_counts);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Quản Trị Dữ Liệu Thu Thập - NXB Nông Nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #1b3527; min-height: 100vh; color: #fff; padding-top: 20px; }
        .sidebar a.nav-item { color: #cfd8dc; text-decoration: none; padding: 12px 20px; display: block; border-radius: 8px; margin: 4px 12px; }
        .sidebar a.nav-item:hover, .sidebar a.nav-item.active { background: #2e7d32; color: #fff; font-weight: 600; }
        .main-card { background: #fff; border-radius: 12px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .kpi-card { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .table-responsive { max-height: 520px; position: relative; }
        thead th { position: sticky; top: 0; background: #2e7d32 !important; color: white !important; z-index: 10; font-weight: 600; }
        
        .data-row { cursor: pointer; transition: background-color 0.15s ease; }
        .table-hover tbody tr:hover td { background-color: #e8f5e9 !important; }

        /* GHIM CỐ ĐỊNH CỘT THAO TÁC Ở MÉP PHẢI */
        th.sticky-col-right, td.sticky-col-right {
            position: sticky;
            right: 0;
            z-index: 5;
            box-shadow: -4px 0 8px rgba(0,0,0,0.06);
        }
        thead th.sticky-col-right {
            background-color: #2e7d32 !important;
            z-index: 12;
        }
        .table-striped tbody tr:nth-of-type(odd) td.sticky-col-right {
            background-color: #f9fbf9 !important;
        }
        .table-striped tbody tr:nth-of-type(even) td.sticky-col-right {
            background-color: #ffffff !important;
        }
        .table-hover tbody tr:hover td.sticky-col-right {
            background-color: #e8f5e9 !important;
        }
        
        @media print {
            .sidebar, .btn, .no-print, .input-group, #toolbarArea, .kpi-section, .sticky-col-right { display: none !important; }
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
                <small class="text-success-emphasis">Quản Trị Biểu Mẫu</small>
            </div>

            <!-- NÚT TẠO BIỂU MẪU MỚI (CÔNG CỤ BUILDER) -->
            <div class="px-3 mb-3">
                <a href="builder.php" class="btn btn-warning text-dark fw-bold w-100 py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-plus-circle-fill fs-5"></i> Tạo biểu mẫu mới
                </a>
            </div>
            
            <div class="px-3 mb-2 text-uppercase text-secondary" style="font-size: 11px;">Danh mục Biểu mẫu</div>
            <?php foreach ($forms as $f): ?>
                <a href="admin.php?form_id=<?= $f['id'] ?>" class="nav-item <?= $selected_form_id == $f['id'] ? 'active' : '' ?>">
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

        <!-- Vùng hiển thị Dashboard chính -->
        <div class="col-md-9 col-lg-10 p-4">
            <!-- Thông báo tạo form thành công -->
            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                    <div>
                        <strong>Thành công!</strong> Đã khởi tạo biểu mẫu mới vào hệ thống MySQL. Bạn có thể mở link biểu mẫu để thử nghiệm ngay.
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Thông báo xóa thành công -->
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                    <div>Đã xóa phản hồi khỏi hệ thống thành công! Dữ liệu đã được dọn sạch.</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold text-success mb-1">Dữ Liệu Phản Hồi Đã Thu Thập</h4>
                    <p class="text-muted mb-0">Biểu mẫu đang xem: <strong class="text-dark"><?= htmlspecialchars($current_form_title) ?></strong></p>
                </div>
                <div class="d-flex gap-2">
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

            <!-- KHỐI KPI & BIỂU ĐỒ THỐNG KÊ -->
            <div class="row g-3 mb-4 kpi-section">
                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="card kpi-card bg-white p-3 h-100 border-start border-4 border-success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-semibold">TỔNG LƯỢT TIẾP NHẬN</div>
                                        <h2 class="fw-bold text-success my-1"><?= $total_count ?></h2>
                                        <small class="text-muted">Toàn bộ lịch sử biểu mẫu</small>
                                    </div>
                                    <div class="bg-success-subtle p-3 rounded-circle text-success fs-3">
                                        <i class="bi bi-inbox-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="card kpi-card bg-white p-3 h-100 border-start border-4 border-primary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small fw-semibold">PHẢN HỒI GẦN ĐÂY</div>
                                        <h2 class="fw-bold text-primary my-1"><?= $recent_count ?></h2>
                                        <small class="text-muted">Phát sinh trong 7 ngày qua</small>
                                    </div>
                                    <div class="bg-primary-subtle p-3 rounded-circle text-primary fs-3">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card kpi-card bg-white p-3 border-start border-4 border-warning">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-warning-subtle p-3 rounded-circle text-warning-emphasis fs-3">
                                        <i class="bi bi-pie-chart-fill"></i>
                                    </div>
                                    <div>
                                        <div class="text-muted small fw-semibold">CHỈ SỐ TRỌNG YẾU</div>
                                        <div class="fw-bold text-dark mt-1">
                                            <?= $chart_field ? htmlspecialchars($chart_field['label']) : 'Phân tích số liệu' ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= count($chart_counts) > 0 ? 'Đã phân loại thành ' . count($chart_counts) . ' nhóm dữ liệu' : 'Chưa có phân loại trắc nghiệm' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card kpi-card bg-white p-3 h-100">
                        <div class="fw-bold text-success mb-2 small text-uppercase">
                            <i class="bi bi-bar-chart-steps me-1"></i> Phân Bố Theo: <?= $chart_field ? htmlspecialchars($chart_field['label']) : 'Cơ cấu' ?>
                        </div>
                        <div style="position: relative; height: 180px; display: flex; justify-content: center; align-items: center;">
                            <?php if (!empty($chart_values)): ?>
                                <canvas id="analyticsChart"></canvas>
                            <?php else: ?>
                                <span class="text-muted fst-italic small">Chưa có dữ liệu trắc nghiệm để vẽ biểu đồ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thanh tìm kiếm & hướng dẫn thao tác nhanh -->
            <div id="toolbarArea" class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm kiếm theo tên, SĐT, công ty, địa chỉ...">
                    </div>
                </div>
                <div class="col-md-6 col-lg-7 text-md-end text-muted small">
                    <span class="badge bg-light text-secondary border me-2"><i class="bi bi-info-circle me-1"></i>Nhấp đúp chuột vào hàng bất kỳ để xem chi tiết</span>
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
                                    <th class="text-center sticky-col-right" style="width: 105px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($responses)): ?>
                                    <tr class="no-data-row">
                                        <td colspan="<?= count($fields) + 3 ?>" class="text-center py-4 text-muted">
                                            Chưa có dữ liệu phản hồi nào cho biểu mẫu này.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($responses as $resp): 
                                        $rid = $resp['response_id'];
                                        $formatted_time = date('d/m/Y H:i', strtotime($resp['submitted_at']));
                                        
                                        $row_details = [];
                                        foreach ($fields as $f) {
                                            $row_details[] = [
                                                'label' => $f['label'],
                                                'value' => $answers_map[$rid][$f['id']] ?? ''
                                            ];
                                        }
                                    ?>
                                        <tr class="data-row" title="Nhấp đúp chuột để xem chi tiết phiếu">
                                            <td class="text-center fw-bold text-secondary">#<?= $rid ?></td>
                                            <td>
                                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $formatted_time ?></small>
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
                                            
                                            <!-- CỘT THAO TÁC CỐ ĐỊNH Ở MÉP PHẢI -->
                                            <td class="text-center sticky-col-right">
                                                <div class="btn-group btn-group-sm shadow-sm" onclick="event.stopPropagation();">
                                                    <button type="button" class="btn btn-outline-primary btn-view-modal" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#detailModal"
                                                            data-id="<?= $rid ?>"
                                                            data-time="<?= $formatted_time ?>"
                                                            data-details='<?= htmlspecialchars(json_encode($row_details, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'
                                                            title="Xem chi tiết phiếu">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="delete.php?id=<?= $rid ?>&form_id=<?= $selected_form_id ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Bạn có chắc chắn muốn xóa phản hồi #<?= $rid ?> này không?\nThao tác này sẽ dọn sạch toàn bộ câu trả lời liên quan!');"
                                                       title="Xóa phản hồi">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
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

<!-- MODAL POPUP: XEM CHI TIẾT PHIẾU THU THẬP -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="detailModalLabel">
                    <i class="bi bi-file-earmark-text me-2"></i>Chi Tiết Phiếu Thu Thập <span id="modalResponseId"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-light border mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div><strong>Biểu mẫu:</strong> <span class="text-success fw-semibold"><?= htmlspecialchars($current_form_title) ?></span></div>
                    <div class="text-muted small"><i class="bi bi-clock me-1"></i>Thời gian nộp: <strong id="modalSubmitTime" class="text-dark"></strong></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 38%;">Nội dung trường thông tin</th>
                                <th style="width: 62%;">Thông tin khách hàng điền</th>
                            </tr>
                        </thead>
                        <tbody id="modalDetailBody">
                            <!-- Nạp tự động qua JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 1. Biểu đồ Chart.js
<?php if (!empty($chart_values)): ?>
const ctx = document.getElementById('analyticsChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: [
                '#10b981', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6', '#64748b'
            ],
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12, font: { size: 11 } }
            }
        }
    }
});
<?php endif; ?>

// 2. Tìm kiếm tức thì (Live Search)
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

    document.getElementById('visibleCount').textContent = visibleTotal;
});

// 3. Xử lý Popup Xem Chi Tiết
const detailModal = document.getElementById('detailModal');
if (detailModal) {
    detailModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const responseId = button.getAttribute('data-id');
        const submitTime = button.getAttribute('data-time');
        const details = JSON.parse(button.getAttribute('data-details') || '[]');

        document.getElementById('modalResponseId').textContent = '#' + responseId;
        document.getElementById('modalSubmitTime').textContent = submitTime;

        const tbody = document.getElementById('modalDetailBody');
        tbody.innerHTML = '';

        details.forEach(item => {
            const tr = document.createElement('tr');
            
            const tdLabel = document.createElement('td');
            tdLabel.className = 'fw-semibold text-secondary';
            tdLabel.textContent = item.label;

            const tdValue = document.createElement('td');
            if (!item.value || item.value.trim() === '') {
                tdValue.innerHTML = '<span class="text-muted fst-italic">(Bỏ trống / Ẩn do điều kiện)</span>';
            } else {
                tdValue.className = 'fw-medium text-dark';
                tdValue.textContent = item.value;
            }

            tr.appendChild(tdLabel);
            tr.appendChild(tdValue);
            tbody.appendChild(tr);
        });
    });
}

// 4. Nhấp đúp chuột vào hàng để mở Popup xem chi tiết
document.querySelectorAll('#responsesTable tbody tr.data-row').forEach(row => {
    row.addEventListener('dblclick', function(e) {
        const viewBtn = this.querySelector('.btn-view-modal');
        if (viewBtn) {
            viewBtn.click();
        }
    });
});
</script>
</body>
</html>