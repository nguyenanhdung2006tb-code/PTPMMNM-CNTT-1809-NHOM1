<?php require_once __DIR__ . '/check_login.php'; ?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';

// Lấy danh sách các biểu mẫu đang hoạt động
$stmt = $pdo->query("
    SELECT f.id, f.title, f.description, v.version_number,
           (SELECT COUNT(*) FROM fields WHERE form_version_id = v.id) AS total_questions
    FROM forms f
    JOIN form_versions v ON f.id = v.form_id
    WHERE v.is_active = 1
    ORDER BY f.id ASC
");
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
$role = $_SESSION['role'] ?? 'client';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Mục Biểu Mẫu Trực Tuyến - NXB Nông Nghiệp</title>
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
            --text-heading: #0f172a;
            --text-body: #475569;
            --border-subtle: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .app-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--border-subtle);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand-badge {
            background-color: var(--primary-subtle);
            color: var(--primary-hover);
            font-weight: 700;
            font-size: 0.82rem;
            padding: 5px 12px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Banner mới: Màu xanh tươi đồng bộ trang chủ */
        .hero-banner {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 60%, #22c55e 100%);
            color: #ffffff;
            padding: 48px 0 42px 0;
            margin-bottom: 35px;
            box-shadow: 0 10px 25px -5px rgba(22, 163, 74, 0.2);
        }

        .search-box {
            position: relative;
            max-width: 620px;
            margin: 22px auto 0 auto;
        }

        .search-box input {
            height: 52px;
            border-radius: 50px;
            padding-left: 48px;
            padding-right: 45px;
            border: 2px solid transparent;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .search-box input:focus {
            border-color: #86efac;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.15rem;
            pointer-events: none;
        }

        .clear-search {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.1rem;
            display: none;
        }
        .clear-search:hover { color: #334155; }

        .form-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            padding: 24px;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .form-card:hover {
            transform: translateY(-4px);
            border-color: #86efac;
            box-shadow: 0 12px 24px rgba(22, 163, 74, 0.08);
        }

        .form-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background-color: var(--primary-subtle);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 16px;
        }

        .form-title {
            color: var(--text-heading);
            font-weight: 700;
            font-size: 1.12rem;
            line-height: 1.45;
            margin-bottom: 8px;
        }

        .form-desc {
            color: var(--text-body);
            font-size: 0.88rem;
            line-height: 1.55;
            flex-grow: 1;
            margin-bottom: 20px;
        }

        .card-footer-action {
            border-top: 1px solid #f1f5f9;
            padding-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .btn-fill {
            background-color: var(--primary);
            color: #ffffff;
            font-weight: 600;
            border-radius: 9px;
            padding: 8px 18px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-fill:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
        }

        .btn-copy {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid var(--border-subtle);
            font-weight: 500;
            border-radius: 9px;
            padding: 8px 12px;
            font-size: 0.88rem;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-copy:hover {
            background-color: #f1f5f9;
            color: var(--text-heading);
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
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .custom-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <header class="app-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-secondary btn-sm fw-semibold">
                    <i class="bi bi-house-door me-1"></i>Về Trang Chủ
                </a>
                <span class="brand-badge"><i class="bi bi-book-half me-1"></i>NXB Nông Nghiệp</span>
                <span class="text-secondary small d-none d-md-inline">| Cổng Khảo Sát</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small d-none d-sm-inline">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <?php if ($role === 'admin'): ?>
                    <a href="admin/index.php" class="btn btn-outline-success btn-sm fw-semibold">
                        <i class="bi bi-speedometer2 me-1"></i>Trang Quản Trị
                    </a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline-danger btn-sm" title="Đăng xuất">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </header>

    <section class="hero-banner text-center">
        <div class="container">
            <h2 class="fw-bold mb-2">HỆ THỐNG BIỂU MẪU & KHẢO SÁT TRỰC TUYẾN</h2>
            <p class="text-light opacity-90 mb-0 mx-auto" style="max-width: 650px; font-size: 0.95rem;">
                Chọn biểu mẫu phù hợp để cung cấp thông tin hoặc sao chép liên kết trực tiếp gửi tới đối tác và khách hàng.
            </p>

            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm nhanh tên biểu mẫu hoặc nội dung khảo sát..." autocomplete="off">
                <i class="bi bi-x-circle-fill clear-search" id="clearSearchBtn" title="Xóa tìm kiếm"></i>
            </div>
        </div>
    </section>

    <main class="container pb-5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold text-dark mb-0">Tất cả biểu mẫu khả dụng</h5>
                <small class="text-muted" id="formsCounter">Đang hiển thị <?= count($forms) ?> biểu mẫu</small>
            </div>
        </div>

        <div id="noResults" class="text-center py-5 d-none">
            <div class="text-muted fs-1 mb-2"><i class="bi bi-search"></i></div>
            <h6 class="fw-bold text-secondary">Không tìm thấy biểu mẫu nào phù hợp!</h6>
            <p class="text-muted small">Hãy thử tìm kiếm với từ khóa khác như "lịch", "sách", "in ấn"...</p>
        </div>

        <div class="row g-4" id="formListContainer">
            <?php if (empty($forms)): ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted fs-5">Hiện tại chưa có biểu mẫu nào đang mở.</div>
                </div>
            <?php else: ?>
                <?php foreach ($forms as $f): ?>
                    <div class="col-md-6 col-lg-6 form-card-wrapper" 
                         data-title="<?= strtolower(htmlspecialchars($f['title'])) ?>" 
                         data-desc="<?= strtolower(htmlspecialchars($f['description'] ?? '')) ?>">
                        <div class="form-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="form-icon">
                                    <i class="bi bi-ui-checks"></i>
                                </div>
                                <span class="badge border px-2 py-1" style="background-color: #ecfdf5; color: #16a34a; border-color: #bbf7d0 !important;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Đang mở tiếp nhận
                                </span>
                            </div>

                            <h5 class="form-title"><?= htmlspecialchars($f['title']) ?></h5>
                            <p class="form-desc">
                                <?= !empty($f['description']) ? htmlspecialchars($f['description']) : 'Phiếu thu thập dữ liệu và khảo sát theo quy chuẩn của NXB Nông Nghiệp.' ?>
                            </p>

                            <div class="card-footer-action">
                                <small class="text-muted">
                                    <i class="bi bi-question-circle me-1"></i><?= $f['total_questions'] ?> trường câu hỏi
                                </small>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn-copy" onclick="copyFormLink(<?= $f['id'] ?>, '<?= htmlspecialchars($f['title'], ENT_QUOTES) ?>')" title="Sao chép liên kết biểu mẫu này">
                                        <i class="bi bi-link-45deg"></i>
                                        <span class="d-none d-sm-inline">Sao chép link</span>
                                    </button>
                                    <a href="form.php?id=<?= $f['id'] ?>" class="btn-fill">
                                        <span>Điền biểu mẫu</span>
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small mt-auto">
        <div class="container">
            © 2026 Bản quyền thuộc Nhà xuất bản Nông Nghiệp - Dự án Phần mềm Nguồn Mở (Nhóm 1)
        </div>
    </footer>

    <div id="copyToast" class="custom-toast">
        <i class="bi bi-check-circle-fill text-success fs-5"></i>
        <span id="toastMsg">Đã sao chép liên kết biểu mẫu vào bộ nhớ tạm!</span>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearchBtn');
        const formWrappers = document.querySelectorAll('.form-card-wrapper');
        const noResults = document.getElementById('noResults');
        const counter = document.getElementById('formsCounter');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            if (query.length > 0) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }

            formWrappers.forEach(card => {
                const title = card.getAttribute('data-title');
                const desc = card.getAttribute('data-desc');

                if (title.includes(query) || desc.includes(query)) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0 && formWrappers.length > 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }

            counter.innerText = `Đang hiển thị ${visibleCount} biểu mẫu`;
        });

        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            formWrappers.forEach(card => card.style.display = '');
            noResults.classList.add('d-none');
            counter.innerText = `Đang hiển thị ${formWrappers.length} biểu mẫu`;
            searchInput.focus();
        });

        function copyFormLink(formId, formTitle) {
            const origin = window.location.origin;
            const path = window.location.pathname;
            const dir = path.substring(0, path.lastIndexOf('/'));
            const shareUrl = `${origin}${dir}/form.php?id=${formId}`;

            navigator.clipboard.writeText(shareUrl).then(() => {
                showToast(`Đã sao chép liên kết "${formTitle}"! Bạn có thể gửi link này cho người khác.`);
            }).catch(err => {
                const inputTemp = document.createElement('input');
                inputTemp.value = shareUrl;
                document.body.appendChild(inputTemp);
                inputTemp.select();
                document.execCommand('copy');
                document.body.removeChild(inputTemp);
                showToast(`Đã sao chép liên kết biểu mẫu!`);
            });
        }

        function showToast(msg) {
            const toast = document.getElementById('copyToast');
            const toastMsg = document.getElementById('toastMsg');
            toastMsg.innerText = msg;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>
</html>