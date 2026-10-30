<?php 
require_once __DIR__ . '/auth_check.php'; 
require_once '../config/db.php';

// 1. Lấy danh sách các biểu mẫu
$stmt = $pdo->query("
    SELECT f.id, f.title, 
           (SELECT COUNT(*) FROM responses r 
            JOIN form_versions v ON r.form_version_id = v.id 
            WHERE v.form_id = f.id) AS total_responses
    FROM forms f
    ORDER BY f.id ASC
");
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Xác định form đang được chọn xem
$selected_form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : ($forms[0]['id'] ?? 1);

$current_form_title = "Biểu mẫu";
foreach ($forms as $f) {
    if ($f['id'] == $selected_form_id) {
        $current_form_title = $f['title'];
        break;
    }
}

// 3. Lấy danh sách các câu hỏi của phiên bản form hiện tại
$stmt_fields = $pdo->prepare("
    SELECT f.id, f.label, f.field_type, f.options_json 
    FROM fields f
    JOIN form_versions v ON f.form_version_id = v.id
    WHERE v.form_id = ? AND v.is_active = 1
    ORDER BY f.display_order ASC
");
$stmt_fields->execute([$selected_form_id]);
$fields = $stmt_fields->fetchAll(PDO::FETCH_ASSOC);

// 4. Lấy danh sách các lượt gửi kèm câu trả lời
$stmt_responses = $pdo->prepare("
    SELECT r.id AS response_id, r.submitted_at, v.version_number
    FROM responses r
    JOIN form_versions v ON r.form_version_id = v.id
    WHERE v.form_id = ?
    ORDER BY r.submitted_at DESC
");
$stmt_responses->execute([$selected_form_id]);
$responses = $stmt_responses->fetchAll(PDO::FETCH_ASSOC);

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
    while ($row = $stmt_ans->fetch(PDO::FETCH_ASSOC)) {
        $answers_map[$row['response_id']][$row['field_id']] = $row['answer_value'];
    }
}

// 5. TỔNG HỢP DỮ LIỆU ĐA DẠNG CHO CẢ 5 LOẠI TRƯỜNG DỮ LIỆU
$total_count = count($responses);
$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
$recent_count = 0;
foreach ($responses as $resp) {
    if ($resp['submitted_at'] >= $seven_days_ago) {
        $recent_count++;
    }
}

$chart_dataset_all = [];
$default_chart_field_id = 0;

foreach (['radio', 'checkbox', 'number', 'date', 'text'] as $priority_type) {
    foreach ($fields as $f) {
        if ($f['field_type'] === $priority_type) {
            $default_chart_field_id = $f['id'];
            break 2;
        }
    }
}
if ($default_chart_field_id === 0 && !empty($fields)) {
    $default_chart_field_id = $fields[0]['id'];
}

foreach ($fields as $f) {
    $fid = $f['id'];
    $ftype = $f['field_type'];
    $counts = [];

    foreach ($responses as $resp) {
        $raw_val = $answers_map[$resp['response_id']][$fid] ?? '';
        if ($raw_val !== '') {
            if ($ftype === 'checkbox') {
                $parts = explode(',', $raw_val);
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '') {
                        $counts[$p] = ($counts[$p] ?? 0) + 1;
                    }
                }
            } else {
                $counts[$raw_val] = ($counts[$raw_val] ?? 0) + 1;
            }
        }
    }

    $chart_dataset_all[$fid] = [
        'field_id' => $fid,
        'label' => $f['label'],
        'type' => $ftype,
        'labels' => array_keys($counts),
        'values' => array_values($counts),
        'total_entries' => array_sum($counts)
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị Biểu Mẫu & Thu Thập Dữ Liệu - NXB Nông Nghiệp</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #16a34a;
            --primary-hover: #15803d;
            --primary-subtle: #f0fdf4;
            --sidebar-bg: #0f172a;
            --sidebar-card: #1e293b;
            --bg-page: #f8fafc;
            --border-subtle: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-page); 
            color: var(--text-dark); 
            min-height: 100vh; 
        }

        .sidebar { 
            background: var(--sidebar-bg); 
            height: 100vh; 
            position: sticky; 
            top: 0; 
            color: #fff; 
            padding: 24px 16px; 
            display: flex; 
            flex-direction: column; 
            border-right: 1px solid #1e293b; 
            z-index: 100; 
            overflow: hidden; 
        }

        .brand-header {
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            flex-shrink: 0;
        }

        .brand-badge-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
        }

        .sidebar a.nav-item-form { 
            color: #94a3b8; 
            text-decoration: none; 
            padding: 10px 14px; 
            display: block; 
            border-radius: 10px; 
            margin-bottom: 4px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        .sidebar a.nav-item-form:hover { 
            background: var(--sidebar-card); 
            color: #f1f5f9; 
        }
        .sidebar a.nav-item-form.active { 
            background: var(--primary); 
            color: #ffffff; 
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        }

        .sidebar-search-box { flex-shrink: 0; }
        .sidebar-search-box input {
            background-color: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #fff !important;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .sidebar-search-box input::placeholder { color: #64748b; }
        .sidebar-search-box input:focus {
            border-color: #22c55e !important;
            box-shadow: none !important;
        }

        .kpi-card { 
            background: #ffffff;
            border-radius: 16px; 
            border: 1px solid var(--border-subtle); 
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }
        .kpi-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.04);
            border-color: #cbd5e1;
        }

        .kpi-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        /* TÙY BIẾN Ô CHỌN CÂU HỎI BIỂU ĐỒ - CỐ ĐỊNH RỘNG VÀ ĐỆM AN TOÀN CHỐNG ĐÈ CHỮ */
        .chart-select-custom {
            width: 240px !important;
            height: 33px;
            font-size: 12px !important;
            font-weight: 600;
            color: #166534 !important;
            background-color: #ecfdf5 !important;
            border: 1.5px solid #a7f3d0 !important;
            border-radius: 8px;
            padding: 4px 34px 4px 12px !important;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2316a34a' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 12px 10px !important;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }
        .chart-select-custom:hover {
            background-color: #d1fae5 !important;
            border-color: #6ee7b7 !important;
        }
        .chart-select-custom:focus {
            border-color: #16a34a !important;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15) !important;
            outline: none;
        }

        .main-card { 
            background: #ffffff; 
            border-radius: 16px; 
            border: 1px solid var(--border-subtle); 
            box-shadow: 0 1px 4px rgba(0,0,0,0.03); 
            overflow: hidden;
        }

        .table-responsive { max-height: 520px; position: relative; }
        thead th { 
            position: sticky; 
            top: 0; 
            background: #f8fafc !important; 
            color: #475569 !important; 
            z-index: 10; 
            font-weight: 700; 
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border-subtle) !important;
        }
        
        .data-row { cursor: pointer; transition: background-color 0.15s ease; font-size: 0.92rem; }
        .table-hover tbody tr:hover td { background-color: #f8fafc !important; }

        th.sticky-col-right, td.sticky-col-right {
            position: sticky;
            right: 0;
            z-index: 5;
            box-shadow: -4px 0 8px rgba(0,0,0,0.03);
        }
        thead th.sticky-col-right {
            background-color: #f8fafc !important;
            z-index: 12;
        }
        .table-striped tbody tr:nth-of-type(odd) td.sticky-col-right {
            background-color: #fcfdfe !important;
        }
        .table-striped tbody tr:nth-of-type(even) td.sticky-col-right {
            background-color: #ffffff !important;
        }
        .table-hover tbody tr:hover td.sticky-col-right {
            background-color: #f8fafc !important;
        }

        .btn-primary-emerald {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #ffffff;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-primary-emerald:hover {
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.28);
        }

        .btn-excel-soft {
            background-color: #ecfdf5;
            color: #166534;
            border: 1px solid #a7f3d0;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .btn-excel-soft:hover {
            background-color: #d1fae5;
            border-color: #6ee7b7;
            color: #065f46;
        }

        .custom-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .custom-toast.show { opacity: 1; transform: translateY(0); }

        @page { size: landscape; margin: 8mm; }
        @media print {
            body { background: #ffffff !important; font-size: 10px !important; }
            .sidebar, .btn, .no-print, .input-group, #toolbarArea, .kpi-section, .sticky-col-right { display: none !important; }
            .col-md-9, .col-lg-10 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
            .card { box-shadow: none !important; border: none !important; }
            .table-responsive { max-height: none !important; overflow: visible !important; }
            #responsesTable { width: 100% !important; border-collapse: collapse !important; table-layout: auto !important; }
            #responsesTable th, #responsesTable td {
                min-width: unset !important;
                width: auto !important;
                padding: 5px 6px !important;
                border: 1px solid #333 !important;
                font-size: 9.5px !important;
                word-break: break-word !important;
                white-space: normal !important;
                color: #000 !important;
            }
            #responsesTable thead th { background-color: #f1f5f9 !important; color: #000 !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 px-0 sidebar">
            <div class="brand-header text-center">
                <div class="brand-badge-icon mb-2">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
                <h6 class="text-uppercase fw-bold text-light mb-0" style="letter-spacing: 0.5px;">NXB NÔNG NGHIỆP</h6>
                <small class="text-white-50" style="font-size: 11px;">Hệ Thống Quản Trị Khảo Sát</small>
            </div>

            <div class="d-flex flex-column gap-2 mb-3 flex-shrink-0">
                <a href="../index.php" class="btn btn-outline-light btn-sm py-2 d-flex align-items-center justify-content-center gap-2" style="border-color: #334155; color: #cbd5e1;">
                    <i class="bi bi-house-door"></i> Về Cổng Thông Tin Web
                </a>
              <a href="../forms.php" class="btn btn-outline-success text-light btn-sm py-2 d-flex align-items-center justify-content-center gap-2" style="border-color: #22c55e;">
                    <i class="bi bi-grid-fill text-success"></i> Xem danh sách biểu mẫu
              </a>
            </div>

            <div class="mb-3 sidebar-search-box">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent border-0 text-white-50 ps-2"><i class="bi bi-search"></i></span>
                    <input type="text" id="sidebarSearchForms" class="form-control ps-1" placeholder="Tìm nhanh biểu mẫu..." autocomplete="off">
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center px-1 mb-2 text-uppercase text-white-50 flex-shrink-0" style="font-size: 11px;">
                <span>Danh mục Form</span>
                <span class="badge bg-secondary" id="sidebarFormCounter"><?= count($forms) ?></span>
            </div>

            <div class="flex-grow-1 overflow-auto" id="sidebarFormsContainer">
                <?php foreach ($forms as $f): ?>
                    <a href="index.php?form_id=<?= $f['id'] ?>" 
                       class="nav-item-form sidebar-form-item <?= $selected_form_id == $f['id'] ? 'active' : '' ?>"
                       data-form-title="<?= strtolower(htmlspecialchars($f['title'])) ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($f['title']) ?>">
                                <?= htmlspecialchars($f['title']) ?>
                            </span>
                            <span class="badge bg-light text-dark rounded-pill" style="font-size: 11px;"><?= $f['total_responses'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="pt-3 border-top border-secondary border-opacity-25 mt-auto flex-shrink-0">
                <a href="../logout.php" class="btn btn-outline-danger btn-sm w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất hệ thống
                </a>
            </div>
        </div>

        <!-- Vùng nội dung chính -->
        <div class="col-md-9 col-lg-10 p-4">
            
            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center border-0 mb-4" style="background-color: #ecfdf5; color: #065f46;" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                    <div><strong>Thành công!</strong> Đã khởi tạo biểu mẫu mới vào cơ sở dữ liệu. Bạn có thể sao chép liên kết để gửi khảo sát ngay.</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center border-0 mb-4" style="background-color: #ecfdf5; color: #065f46;" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                    <div><strong>Cập nhật thành công!</strong> Biểu mẫu đã được lưu thay đổi. Khách hàng mở link sẽ thấy nội dung mới ngay lập tức.</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center border-0 mb-4" style="background-color: #ecfdf5; color: #065f46;" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                    <div>Đã xóa bản ghi phản hồi khỏi hệ thống thành công! Dữ liệu liên quan đã được dọn sạch.</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['form_deleted'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center border-0 mb-4" style="background-color: #ecfdf5; color: #065f46;" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                    <div><strong>Đã xóa biểu mẫu thành công!</strong> Toàn bộ câu hỏi và dữ liệu khảo sát liên quan đã được dọn dẹp sạch sẽ.</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- CỤM HEADER CHUẨN MỰC: NÚT "TẠO FORM MỚI" ĐƯA LÊN TRÊN & GOM NHÓM KHOA HỌC -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div style="min-width: 250px; max-width: 400px;">
                    <h4 class="fw-bold text-dark mb-1">Dữ Liệu Khảo Sát Đã Thu Thập</h4>
                    <div class="text-muted small text-truncate" title="<?= htmlspecialchars($current_form_title) ?>">
                        Biểu mẫu: <strong class="text-success"><?= htmlspecialchars($current_form_title) ?></strong> 
                        <span class="badge bg-light text-secondary border ms-1">Mã #<?= $selected_form_id ?></span>
                    </div>
                </div>

                <!-- CỤM ACTION TOOLBAR ĐƯỢC GOM NHÓM GỌN GÀNG, KHÔNG BAO GIỜ BỊ RỚT DÒNG HOẶC LỘN XỘN -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    
                    <!-- 1. Nút Tạo Biểu Mẫu Mới (Được đưa lên trên cùng nổi bật) -->
                    <a href="builder.php" class="btn btn-primary-emerald btn-sm py-2 px-3 shadow-sm d-flex align-items-center gap-1.5 text-nowrap">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Tạo form mới</span>
                    </a>

                    <!-- 2. Nhóm Quản Trị Cấu Trúc Form (Sửa / Xóa) -->
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <a href="edit.php?id=<?= $selected_form_id ?>" class="btn btn-outline-secondary py-2 px-2.5 d-flex align-items-center gap-1 text-nowrap" title="Chỉnh sửa cấu trúc biểu mẫu này">
                            <i class="bi bi-pencil-square text-primary"></i>
                            <span>Sửa</span>
                        </a>
                        <a href="delete_form.php?id=<?= $selected_form_id ?>" 
                           class="btn btn-outline-danger py-2 px-2.5 d-flex align-items-center gap-1 text-nowrap" 
                           onclick="return confirm('CẢNH BÁO XÓA FORM:\n\nBạn có chắc chắn muốn xóa vĩnh viễn biểu mẫu #<?= $selected_form_id ?> (<?= htmlspecialchars($current_form_title, ENT_QUOTES) ?>)?\n\nToàn bộ câu hỏi và mọi phản hồi của khách hàng sẽ bị xóa sạch!');"
                           title="Xóa vĩnh viễn biểu mẫu này">
                            <i class="bi bi-trash3"></i>
                            <span>Xóa</span>
                        </a>
                    </div>

                    <!-- 3. Nhóm Phân Phối & Xem Form (Copy link / Mở trang điền) -->
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <button type="button" class="btn btn-outline-success py-2 px-2.5 d-flex align-items-center gap-1 text-nowrap" onclick="copyAdminShareLink(<?= $selected_form_id ?>, '<?= htmlspecialchars($current_form_title, ENT_QUOTES) ?>')" title="Sao chép link gửi khách hàng">
                            <i class="bi bi-link-45deg fs-6"></i>
                            <span>Copy link</span>
                        </button>
                        <a href="../form.php?id=<?= $selected_form_id ?>" target="_blank" class="btn btn-outline-secondary py-2 px-2.5 d-flex align-items-center gap-1 text-nowrap" title="Mở trang điền dành cho khách">
                            <i class="bi bi-box-arrow-up-right"></i>
                            <span>Mở form</span>
                        </a>
                    </div>

                    <!-- 4. Nhóm Trích Xuất Dữ Liệu (Xuất Excel / In ấn) -->
                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                        <a href="export.php?form_id=<?= $selected_form_id ?>" class="btn btn-excel-soft py-2 px-2.5 d-flex align-items-center gap-1 text-nowrap" title="Xuất dữ liệu Excel (.csv)">
                            <i class="bi bi-file-earmark-excel-fill text-success"></i>
                            <span>Xuất Excel</span>
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary py-2 px-2.5" title="In danh sách dữ liệu (Ctrl+P)">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>

                </div>
            </div>

            <!-- KHỐI KPI & BIỂU ĐỒ TRÒN (ĐÃ FIX ĐỒNG BỘ 100% CHIỀU CAO VÀ RỘNG, KHÔNG DÍNH MŨI TÊN) -->
            <div class="row g-3 mb-4 kpi-section align-items-stretch">
                <div class="col-lg-7 d-flex flex-column justify-content-between gap-3">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="kpi-card h-100 d-flex justify-content-between align-items-center" style="min-height: 105px;">
                                <div>
                                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">TỔNG TIẾP NHẬN</div>
                                    <h2 class="fw-bold text-dark my-1"><?= $total_count ?></h2>
                                    <small class="text-muted">Toàn bộ lịch sử phản hồi</small>
                                </div>
                                <div class="kpi-icon-box" style="background-color: #ecfdf5; color: #16a34a;">
                                    <i class="bi bi-inbox-fill"></i>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="kpi-card h-100 d-flex justify-content-between align-items-center" style="min-height: 105px;">
                                <div>
                                    <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">PHẢN HỒI GẦN ĐÂY</div>
                                    <h2 class="fw-bold text-dark my-1"><?= $recent_count ?></h2>
                                    <small class="text-muted">7 ngày vừa qua</small>
                                </div>
                                <div class="kpi-icon-box" style="background-color: #eff6ff; color: #2563eb;">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card d-flex align-items-center gap-3" style="min-height: 98px;">
                        <div class="kpi-icon-box" style="background-color: #fefce8; color: #ca8a04;">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                        <div class="overflow-hidden" style="flex: 1;">
                            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">CHỈ SỐ TRỌNG YẾU</div>
                            <div class="fw-bold text-dark text-truncate mt-1" id="kpiFieldTitle">...</div>
                            <small class="text-muted" id="kpiFieldSub">...</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="kpi-card h-100 d-flex flex-column justify-content-between" style="min-height: 220px;">
                        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                            <div class="fw-bold text-dark small text-uppercase text-nowrap" style="letter-spacing: 0.5px;">
                                <i class="bi bi-pie-chart text-success me-1"></i> Biểu đồ
                            </div>
                            
                            <!-- ĐÃ FIX TRIỆT ĐỂ: CỐ ĐỊNH RỘNG 240PX CHO MỌI FORM, ĐỆM PHẢI 34PX CHỐNG ĐÈ MŨI TÊN -->
                            <select id="selectChartField" class="chart-select-custom shadow-sm" title="Chọn trường để phân tích dữ liệu">
                                <?php foreach ($fields as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= $f['id'] == $default_chart_field_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="position: relative; height: 165px; display: flex; justify-content: center; align-items: center;">
                            <canvas id="analyticsChart"></canvas>
                            <div id="noChartDataNotice" class="text-muted fst-italic small text-center" style="display: none;">
                                Chưa có dữ liệu phản hồi cho câu hỏi này
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thanh tìm kiếm -->
            <div id="toolbarArea" class="row g-2 align-items-center mb-3">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="liveSearchInput" class="form-control border-start-0 ps-0" placeholder="Tìm theo tên, SĐT, công ty, nội dung trả lời...">
                    </div>
                </div>
                <div class="col-md-6 col-lg-7 text-md-end text-muted small">
                    <span class="badge bg-light text-secondary border me-2"><i class="bi bi-info-circle me-1"></i>Nhấp đúp chuột để xem chi tiết phiếu</span>
                    <span>Đang hiển thị: <strong id="visibleCount" class="text-success"><?= count($responses) ?></strong> / <strong><?= count($responses) ?></strong> phản hồi</span>
                </div>
            </div>

            <!-- Bảng dữ liệu câu trả lời -->
            <div class="main-card">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0" id="responsesTable">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 70px;">Mã</th>
                                <th style="min-width: 150px;">Thời gian gửi</th>
                                <?php foreach ($fields as $field): ?>
                                    <th style="min-width: 170px;"><?= htmlspecialchars($field['label']) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center sticky-col-right" style="width: 110px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($responses)): ?>
                                <tr class="no-data-row">
                                    <td colspan="<?= count($fields) + 3 ?>" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                        Chưa có dữ liệu phản hồi nào được ghi nhận cho biểu mẫu này.
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
                                        
                                        <td class="text-center sticky-col-right">
                                            <div class="btn-group btn-group-sm" onclick="event.stopPropagation();">
                                                <button type="button" class="btn btn-outline-secondary btn-view-modal" 
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

<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);">
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
                        <tbody id="modalDetailBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div id="adminToast" class="custom-toast">
    <i class="bi bi-check-circle-fill text-success fs-5"></i>
    <span id="adminToastMsg">Đã sao chép liên kết biểu mẫu vào bộ nhớ tạm!</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const chartDataMap = <?= json_encode($chart_dataset_all, JSON_UNESCAPED_UNICODE) ?>;
let defaultFieldId = <?= (int)$default_chart_field_id ?>;
let activeChart = null;

const chartColors = [
    '#16a34a', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316', '#64748b', '#10b981', '#6366f1'
];

function updateChartDisplay(fieldId) {
    const data = chartDataMap[fieldId];
    const canvas = document.getElementById('analyticsChart');
    const notice = document.getElementById('noChartDataNotice');
    const titleEl = document.getElementById('kpiFieldTitle');
    const subEl = document.getElementById('kpiFieldSub');

    if (!data) return;

    titleEl.textContent = data.label;
    titleEl.title = data.label;

    if (data.labels && data.labels.length > 0) {
        canvas.style.display = 'block';
        notice.style.display = 'none';
        subEl.textContent = `Đã phân loại thành ${data.labels.length} nhóm dữ liệu (${data.total_entries} lượt ghi nhận)`;

        if (activeChart) {
            activeChart.destroy();
        }

        const ctx = canvas.getContext('2d');
        activeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: chartColors.slice(0, data.labels.length),
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
    } else {
        if (activeChart) {
            activeChart.destroy();
            activeChart = null;
        }
        canvas.style.display = 'none';
        notice.style.display = 'block';
        subEl.textContent = 'Chưa có dữ liệu phản hồi cho trường này';
    }
}

const selectChartElement = document.getElementById('selectChartField');
if (selectChartElement) {
    selectChartElement.addEventListener('change', function() {
        updateChartDisplay(this.value);
    });
}

if (defaultFieldId > 0) {
    updateChartDisplay(defaultFieldId);
}

const sidebarSearch = document.getElementById('sidebarSearchForms');
if (sidebarSearch) {
    sidebarSearch.addEventListener('input', function() {
        const keyword = this.value.toLowerCase().trim();
        const formItems = document.querySelectorAll('#sidebarFormsContainer .sidebar-form-item');
        let matchCount = 0;

        formItems.forEach(item => {
            const title = item.getAttribute('data-form-title') || '';
            if (title.includes(keyword)) {
                item.style.display = '';
                matchCount++;
            } else {
                item.style.display = 'none';
            }
        });

        document.getElementById('sidebarFormCounter').textContent = matchCount;
    });
}

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

function copyAdminShareLink(formId, formTitle) {
    const origin = window.location.origin;
    let path = window.location.pathname;
    let dir = path.substring(0, path.lastIndexOf('/'));
    let parentDir = dir.substring(0, dir.lastIndexOf('/'));
    const shareUrl = `${origin}${parentDir}/form.php?id=${formId}`;

    navigator.clipboard.writeText(shareUrl).then(() => {
        showToastMsg(`Đã sao chép liên kết "${formTitle}"! Sẵn sàng gửi cho người điền.`);
    }).catch(err => {
        const inputTemp = document.createElement('input');
        inputTemp.value = shareUrl;
        document.body.appendChild(inputTemp);
        inputTemp.select();
        document.execCommand('copy');
        document.body.removeChild(inputTemp);
        showToastMsg(`Đã sao chép liên kết biểu mẫu!`);
    });
}

function showToastMsg(msg) {
    const toast = document.getElementById('adminToast');
    const toastMsg = document.getElementById('adminToastMsg');
    toastMsg.innerText = msg;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

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