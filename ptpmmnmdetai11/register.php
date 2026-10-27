<?php
session_start();
require_once __DIR__ . '/config/db.php';

$db = $pdo ?? ($conn ?? null);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   $fullname = trim($_POST['fullname'] ?? '');
$username = strtolower(trim($_POST['username'] ?? '')); // Tự động đưa về chữ thường

// Danh sách các tên nhạy cảm cấm người ngoài đăng ký
$reserved_names = ['admin', 'administrator', 'root', 'quantri', 'quantrivien'];
if (in_array($username, $reserved_names)) {
    $error = 'Tên tài khoản này thuộc hệ thống quản trị, không được phép đăng ký!';
}
    $phone    = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if ($fullname === '' || $username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ các trường thông tin bắt buộc!';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không trùng khớp!';
    } else {
        try {
            // Kiểm tra username đã tồn tại chưa
            if ($db instanceof PDO) {
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $exists = $stmt->fetch();
            } else {
                $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();
            }

            if ($exists) {
                $error = 'Tên tài khoản này đã được sử dụng. Vui lòng chọn tên khác!';
            } else {
                // Mã hóa mật khẩu bằng thuật toán BCRYPT trước khi lưu vào CSDL
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                if ($db instanceof PDO) {
                    $insert = $db->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, 'client')");
                    $insert->execute([$username, $hashed_password, $fullname]);
                } else {
                    $insert = $db->prepare("INSERT INTO users (username, password, fullname, role) VALUES (?, ?, ?, 'client')");
                    $insert->bind_param("sss", $username, $hashed_password, $fullname);
                    $insert->execute();
                }

                // Đăng ký xong chuyển hướng sang trang Đăng nhập
                header('Location: login.php?registered=1');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Lịch Nông Nghiệp</title>
    <link rel="icon" href="image/x2Mf_logo (1).png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { brand: { green: '#34a853', dark: '#278a41' } },
                    fontFamily: { sans: ['Be Vietnam Pro', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="pt-8 pb-4 text-center px-6">
            <a href="index.php" class="inline-block mb-3">
                <img src="image/x2Mf_logo (1).png" alt="Logo Lịch Nông Nghiệp" class="h-12 mx-auto object-contain">
            </a>
            <h1 class="text-2xl font-extrabold text-gray-800">Tạo tài khoản mới</h1>
            <p class="text-sm text-gray-500 mt-1">Đăng ký để gửi biểu mẫu in ấn và theo dõi đơn hàng</p>
        </div>

        <div class="px-8 pb-8 pt-2">
            <?php if ($error): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm font-medium">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Họ và tên *</label>
                    <input type="text" name="fullname" required placeholder="Ví dụ: Nguyễn Văn A" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Tên đăng nhập *</label>
                    <input type="text" name="username" required placeholder="Tên đăng nhập viết liền không dấu" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Số điện thoại liên hệ</label>
                    <input type="tel" name="phone" placeholder="Số điện thoại nhận báo giá" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Mật khẩu *</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Xác nhận mật khẩu *</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <button type="submit" class="w-full py-3.5 bg-[#34a853] hover:bg-[#278a41] text-white rounded-xl font-bold shadow-lg shadow-green-600/20 transition-all text-sm mt-2">
                    Hoàn tất Đăng ký
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-600">
                    Đã có tài khoản? 
                    <a href="login.php" class="font-bold text-[#34a853] hover:underline">Đăng nhập ngay</a>
                </p>
                <a href="index.php" class="inline-block mt-3 text-xs text-gray-400 hover:text-gray-600 transition">
                    ← Quay về trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>