<?php
require_once 'config/db.php';

// Lấy toàn bộ các biểu mẫu đang hoạt động
$stmt = $pdo->query("
    SELECT f.id, f.title, f.description, v.version_number,
           (SELECT COUNT(*) FROM fields WHERE form_version_id = v.id) AS total_questions
    FROM forms f
    JOIN form_versions v ON f.id = v.form_id
    WHERE v.is_active = 1
    ORDER BY f.id ASC
");
$forms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cổng Biểu Mẫu Thu Thập Thông Tin - NXB Nông Nghiệp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { 
            background-color: #f4f6f9; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            animation: smoothFadeIn 0.35s ease-out;
        }
        @keyframes smoothFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header-section {
            background: linear-gradient(135deg, #1b3527 0%, #2e7d32 100%);
            color: white;
            padding: 45px 0 35px 0;
            margin-bottom: 35px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .form-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.25s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .form-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(46,125,50,0.15);
            border-color: #81c784;
        }
        .card-icon {
            width: 48px;
            height: 48px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<!-- Header điều hướng -->
<div class="header-section">
    <!-- ĐOẠN MỚI CHỈ GIỮ LẠI NÚT VỀ CỔNG THÔNG TIN -->
<div class="d-flex justify-content-start align-items-center mb-3">
    <a href="index.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Về Cổng Thông Tin
    </a>
</div>
        <div class="text-center">
            <span class="badge bg-light text-success fw-bold mb-2 px-3 py-2 text-uppercase">NXB Nông Nghiệp</span>
            <h2 class="fw-bold mb-2">HỆ THỐNG BIỂU MẪU KHẢO SÁT & ĐĂNG KÝ TRỰC TUYẾN</h2>
            <p class="text-light opacity-75 mb-0 max-w-xl mx-auto">
                Chọn đúng biểu mẫu phù hợp với nhu cầu để gửi thông tin và nhận hỗ trợ nhanh chóng từ Nhà xuất bản.
            </p>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <?php if (empty($forms)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted fs-5">Hiện chưa có biểu mẫu nào đang mở.</div>
            </div>
        <?php else: ?>
            <?php foreach ($forms as $f): ?>
                <div class="col-md-6 col-lg-6">
                    <div class="form-card p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="card-icon">
                                <i class="bi bi-ui-checks-grid"></i>
                            </div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                Biểu mẫu #<?= $f['id'] ?> (v<?= $f['version_number'] ?>)
                            </span>
                        </div>
                        
                        <h5 class="fw-bold text-dark mt-2 mb-2"><?= htmlspecialchars($f['title']) ?></h5>
                        <p class="text-muted small flex-grow-1">
                            <?= !empty($f['description']) ? htmlspecialchars($f['description']) : 'Phiếu thu thập dữ liệu và đăng ký dịch vụ theo quy chuẩn của NXB Nông Nghiệp.' ?>
                        </p>
                        
                        <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-3">
                            <small class="text-secondary">
                                <i class="bi bi-question-circle me-1"></i> <?= $f['total_questions'] ?> câu hỏi
                            </small>
                            <a href="form.php?id=<?= $f['id'] ?>" class="btn btn-success fw-semibold px-4">
                                Điền biểu mẫu <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>