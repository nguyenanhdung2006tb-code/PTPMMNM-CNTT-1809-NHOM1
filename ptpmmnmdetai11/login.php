<?php
session_start();
require_once __DIR__ . '/config/db.php';

$db = $pdo ?? ($conn ?? null);
$error = '';
$is_registered = isset($_GET['registered']) && $_GET['registered'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $password !== '') {
        try {
            if ($db instanceof PDO) {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            }

            // Kiểm tra mật khẩu thông minh: chấp nhận cả mã băm lẫn chữ thường
            $password_valid = false;

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $password_valid = true;
                } elseif ($user['password'] === $password) {
                    $password_valid = true;
                    // Tự động nâng cấp mật khẩu thô trong CSDL thành mã băm an toàn ngay lập tức
                    $new_hashed = password_hash($password, PASSWORD_DEFAULT);
                    if ($db instanceof PDO) {
                        $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $upd->execute([$new_hashed, $user['id']]);
                    } else {
                        $upd = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $upd->bind_param("si", $new_hashed, $user['id']);
                        $upd->execute();
                    }
                }
            }

            if ($password_valid) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role']     = $user['role'];

                // Phân quyền điều hướng
                if ($user['role'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Tài khoản hoặc mật khẩu không chính xác!';
            }
        } catch (Exception $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Lịch Nông Nghiệp</title>
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
            <h1 class="text-2xl font-extrabold text-gray-800">Cổng đăng nhập</h1>
            <p class="text-sm text-gray-500 mt-1">Đăng nhập tài khoản khách hàng hoặc quản trị viên</p>
        </div>

        <div class="px-8 pb-8 pt-2">
            <?php if ($is_registered): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-medium">
                     Đăng ký tài khoản thành công! Vui lòng đăng nhập.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm font-medium">
                     <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Tên đăng nhập</label>
                    <input type="text" name="username" required placeholder="Nhập tên đăng nhập của bạn" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#34a853] focus:border-transparent text-sm">
                </div>

                <button type="submit" class="w-full py-3.5 bg-[#34a853] hover:bg-[#278a41] text-white rounded-xl font-bold shadow-lg shadow-green-600/20 transition-all text-sm mt-2">
                    Đăng nhập hệ thống
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-600">
                    Chưa có tài khoản? 
                    <a href="register.php" class="font-bold text-[#34a853] hover:underline">Đăng ký tại đây</a>
                </p>
                <a href="index.php" class="inline-block mt-3 text-xs text-gray-400 hover:text-gray-600 transition">
                    ← Quay về trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>