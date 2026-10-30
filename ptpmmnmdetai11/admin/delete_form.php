<?php
require_once __DIR__ . '/auth_check.php';
require_once '../config/db.php';

$form_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($form_id > 0) {
    try {
        $pdo->beginTransaction();

        // 1. Lấy tất cả phiên bản (form_versions) của form này
        $stmt_ver = $pdo->prepare("SELECT id FROM form_versions WHERE form_id = ?");
        $stmt_ver->execute([$form_id]);
        $version_ids = $stmt_ver->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($version_ids)) {
            $in_vers = implode(',', array_fill(0, count($version_ids), '?'));

            // 2. Lấy tất cả response_id để dọn sạch câu trả lời (answers)
            $stmt_resp = $pdo->prepare("SELECT id FROM responses WHERE form_version_id IN ($in_vers)");
            $stmt_resp->execute($version_ids);
            $response_ids = $stmt_resp->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($response_ids)) {
                $in_resps = implode(',', array_fill(0, count($response_ids), '?'));
                $stmt_del_ans = $pdo->prepare("DELETE FROM answers WHERE response_id IN ($in_resps)");
                $stmt_del_ans->execute($response_ids);

                $stmt_del_resps = $pdo->prepare("DELETE FROM responses WHERE id IN ($in_resps)");
                $stmt_del_resps->execute($response_ids);
            }

            // 3. Dọn dẹp điều kiện (conditions) và trường câu hỏi (fields)
            $stmt_del_cond = $pdo->prepare("DELETE FROM conditions WHERE form_version_id IN ($in_vers)");
            $stmt_del_cond->execute($version_ids);

            $stmt_del_flds = $pdo->prepare("DELETE FROM fields WHERE form_version_id IN ($in_vers)");
            $stmt_del_flds->execute($version_ids);

            // 4. Xóa phiên bản form
            $stmt_del_vers = $pdo->prepare("DELETE FROM form_versions WHERE form_id = ?");
            $stmt_del_vers->execute([$form_id]);
        }

        // 5. Xóa biểu mẫu trong bảng forms
        $stmt_del_form = $pdo->prepare("DELETE FROM forms WHERE id = ?");
        $stmt_del_form->execute([$form_id]);

        $pdo->commit();
        header("Location: index.php?form_deleted=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Lỗi khi xóa biểu mẫu: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}