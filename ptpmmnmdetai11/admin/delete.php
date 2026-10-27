<?php require_once __DIR__ . '/auth_check.php'; ?>
<?php
require_once '../config/db.php';

// Kiểm tra tham số ID phản hồi và Form ID
if (isset($_GET['id']) && isset($_GET['form_id'])) {
    $responseId = intval($_GET['id']);
    $formId = intval($_GET['form_id']);

    // Do bảng answers có cấu hình FOREIGN KEY ON DELETE CASCADE,
    // xóa dòng này ở bảng responses sẽ tự động dọn sạch mọi câu trả lời liên quan.
    $stmt = $pdo->prepare("DELETE FROM responses WHERE id = ?");
    $stmt->execute([$responseId]);

    // Điều hướng quay về trang admin kèm thông báo xóa thành công
    header("Location: index.php?form_id=$formId&deleted=1");
    exit;
}

header("Location: index.php");
exit;