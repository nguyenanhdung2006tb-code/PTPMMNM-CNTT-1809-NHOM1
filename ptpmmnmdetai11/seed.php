<?php
require_once 'config/db.php';

try {
    // 0. Xóa dữ liệu cũ để tránh trùng lặp
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE answers;");
    $pdo->exec("TRUNCATE TABLE responses;");
    $pdo->exec("TRUNCATE TABLE conditions;");
    $pdo->exec("TRUNCATE TABLE fields;");
    $pdo->exec("TRUNCATE TABLE form_versions;");
    $pdo->exec("TRUNCATE TABLE forms;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Chuẩn bị sẵn các câu lệnh riêng biệt
    $stmt_form = $pdo->prepare("INSERT INTO forms (title, description) VALUES (?, ?)");
    $stmt_ver  = $pdo->prepare("INSERT INTO form_versions (form_id, version_number, is_active) VALUES (?, 1, 1)");
    $stmt_field = $pdo->prepare("INSERT INTO fields (form_version_id, label, field_type, options_json, is_required, display_order) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_cond  = $pdo->prepare("INSERT INTO conditions (form_version_id, source_field_id, target_field_id, operator, trigger_value, action) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_resp  = $pdo->prepare("INSERT INTO responses (form_version_id, submitted_at) VALUES (?, ?)");
    $stmt_ans   = $pdo->prepare("INSERT INTO answers (response_id, field_id, answer_value) VALUES (?, ?, ?)");

    // ==========================================
    // 1. BIỂU MẪU 1: Đặt in lịch Tết theo yêu cầu
    // ==========================================
    $stmt_form->execute([
        'Phiếu đăng ký in lịch Tết & ấn phẩm Xuân theo yêu cầu (NXB Nông Nghiệp)',
        'Dành cho cá nhân, đại lý và doanh nghiệp đăng ký đặt in các ấn phẩm lịch bloc, lịch bàn, lịch treo tường.'
    ]);
    $form1_id = $pdo->lastInsertId();

    $stmt_ver->execute([$form1_id]);
    $ver1_id = $pdo->lastInsertId();

    // 11 trường của Form 1
    $stmt_field->execute([$ver1_id, 'Họ và tên người liên hệ', 'text', null, 1, 1]);
    $f1_name = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Số điện thoại', 'text', null, 1, 2]);
    $f1_phone = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Email', 'text', null, 0, 3]);
    $f1_email = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Đối tượng khách hàng', 'radio', json_encode(['Cá nhân', 'Doanh nghiệp / HTX']), 1, 4]);
    $f1_custype = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Tên doanh nghiệp / Đơn vị', 'text', null, 0, 5]);
    $f1_company = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Mã số thuế', 'text', null, 0, 6]);
    $f1_tax = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Loại ấn phẩm lịch muốn in', 'radio', json_encode(['Lịch bloc cực đại', 'Lịch treo tường 7 tờ', 'Lịch để bàn chữ A', 'Sổ tay nông vụ']), 1, 7]);
    $f1_type = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Số lượng dự kiến (cuốn/bộ)', 'number', null, 1, 8]);
    $f1_qty = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Hình thức nhận hàng', 'radio', json_encode(['Nhận trực tiếp tại NXB', 'Giao hàng tận nơi']), 1, 9]);
    $f1_delivery = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Địa chỉ giao hàng chi tiết', 'text', null, 0, 10]);
    $f1_address = $pdo->lastInsertId();

    $stmt_field->execute([$ver1_id, 'Ngày mong muốn nhận hàng', 'date', null, 0, 11]);
    $f1_date = $pdo->lastInsertId();

    // 3 Điều kiện ẩn/hiện của Form 1
    $stmt_cond->execute([$ver1_id, $f1_custype, $f1_company, 'equals', 'Doanh nghiệp / HTX', 'show']);
    $stmt_cond->execute([$ver1_id, $f1_custype, $f1_tax, 'equals', 'Doanh nghiệp / HTX', 'show']);
    $stmt_cond->execute([$ver1_id, $f1_delivery, $f1_address, 'equals', 'Giao hàng tận nơi', 'show']);

    // ==========================================
    // 2. BIỂU MẪU 2: Đăng ký xuất bản bản thảo
    // ==========================================
    $stmt_form->execute([
        'Phiếu đăng ký thẩm định và xuất bản sách, tài liệu nông nghiệp',
        'Tiếp nhận bản thảo, đề cương sách kỹ thuật cây trồng, chăn nuôi và khuyến nông.'
    ]);
    $form2_id = $pdo->lastInsertId();

    $stmt_ver->execute([$form2_id]);
    $ver2_id = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Họ tên tác giả / Chủ biên', 'text', null, 1, 1]);
    $f2_author = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Tên bản thảo tác phẩm', 'text', null, 1, 2]);
    $f2_title = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Lĩnh vực chuyên môn', 'radio', json_encode(['Trồng trọt', 'Chăn nuôi thú y', 'Lâm nghiệp', 'Thủy sản']), 1, 3]);
    $f2_field = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Bạn đã từng xuất bản sách tại NXB chưa?', 'radio', json_encode(['Đã từng', 'Chưa']), 1, 4]);
    $f2_experienced = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Tên tác phẩm đã xuất bản gần nhất', 'text', null, 0, 5]);
    $f2_prev_book = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Dự kiến số trang', 'number', null, 1, 6]);
    $f2_pages = $pdo->lastInsertId();

    $stmt_field->execute([$ver2_id, 'Thời gian nộp bản thảo hoàn chỉnh', 'date', null, 1, 7]);
    $f2_deadline = $pdo->lastInsertId();

    $stmt_cond->execute([$ver2_id, $f2_experienced, $f2_prev_book, 'equals', 'Đã từng', 'show']);

    // ==========================================
    // 3. BIỂU MẪU 3: Khảo sát ý kiến độc giả
    // ==========================================
    $stmt_form->execute([
        'Phiếu khảo sát mức độ hài lòng độc giả năm 2026',
        'Thu thập ý kiến đóng góp về nội dung và chất lượng in ấn các ấn phẩm NXB Nông Nghiệp.'
    ]);
    $form3_id = $pdo->lastInsertId();

    $stmt_ver->execute([$form3_id]);
    $ver3_id = $pdo->lastInsertId();

    $stmt_field->execute([$ver3_id, 'Họ và tên độc giả', 'text', null, 1, 1]);
    $f3_reader = $pdo->lastInsertId();

    $stmt_field->execute([$ver3_id, 'Bạn biết đến ấn phẩm từ nguồn nào?', 'radio', json_encode(['Hội chợ sách', 'Website NXB', 'Được giới thiệu', 'Mạng xã hội']), 1, 2]);
    $f3_source = $pdo->lastInsertId();

    $stmt_field->execute([$ver3_id, 'Đánh giá chất lượng in ấn và màu sắc', 'radio', json_encode(['Rất đẹp', 'Tốt', 'Bình thường', 'Chưa đạt yêu cầu']), 1, 3]);
    $f3_rating = $pdo->lastInsertId();

    $stmt_field->execute([$ver3_id, 'Chi tiết điểm chưa đạt yêu cầu cần cải thiện', 'text', null, 0, 4]);
    $f3_reason = $pdo->lastInsertId();

    $stmt_field->execute([$ver3_id, 'Bạn có muốn nhận bản tin khuyến nông qua Email không?', 'radio', json_encode(['Có', 'Không']), 1, 5]);
    $f3_newsletter = $pdo->lastInsertId();

    $stmt_field->execute([$ver3_id, 'Email nhận bản tin', 'text', null, 0, 6]);
    $f3_email = $pdo->lastInsertId();

    $stmt_cond->execute([$ver3_id, $f3_rating, $f3_reason, 'equals', 'Chưa đạt yêu cầu', 'show']);
    $stmt_cond->execute([$ver3_id, $f3_newsletter, $f3_email, 'equals', 'Có', 'show']);

    // ==========================================
    // 4. TẠO 30 LƯỢT TRẢ LỜI MẪU (RESPONSES & ANSWERS)
    // ==========================================
    $names = ['Nguyễn Văn An', 'Trần Thị Mai', 'Lê Hoàng Long', 'Phạm Minh Đức', 'Hoàng Thu Trang', 'Đỗ Quốc Bảo', 'Vũ Thị Lan', 'Bùi Văn Hùng', 'Ngô Thanh Tùng', 'Đặng Thảo Vy'];
    $companies = ['Công ty CP Giống Cây Trồng Trung Ương', 'HTX Nông Nghiệp Xanh Hà Nội', 'Công ty Phân Bón Sông Hồng', 'Trang trại Hữu Cơ Ba Vì', 'Công ty TNHH Nông Lâm Sản'];

    for ($i = 0; $i < 30; $i++) {
        $name = $names[$i % count($names)] . ' ' . ($i + 1);
        $hoursAgo = rand(1, 120);
        $submittedAt = date('Y-m-d H:i:s', time() - ($hoursAgo * 3600));

        if ($i < 15) {
            // 15 lượt cho Form 1
            $stmt_resp->execute([$ver1_id, $submittedAt]);
            $rid = $pdo->lastInsertId();

            $isCorp = ($i % 2 === 0);
            $deliveryType = ($i % 3 === 0) ? 'Nhận trực tiếp tại NXB' : 'Giao hàng tận nơi';

            $stmt_ans->execute([$rid, $f1_name, $name]);
            $stmt_ans->execute([$rid, $f1_phone, '098' . rand(1000000, 9999999)]);
            $stmt_ans->execute([$rid, $f1_email, 'khach' . ($i + 1) . '@gmail.com']);
            $stmt_ans->execute([$rid, $f1_custype, $isCorp ? 'Doanh nghiệp / HTX' : 'Cá nhân']);
            if ($isCorp) {
                $stmt_ans->execute([$rid, $f1_company, $companies[$i % count($companies)]]);
                $stmt_ans->execute([$rid, $f1_tax, '010' . rand(1000000, 9999999)]);
            }
            $stmt_ans->execute([$rid, $f1_type, 'Lịch bloc cực đại']);
            $stmt_ans->execute([$rid, $f1_qty, (string)rand(50, 500)]);
            $stmt_ans->execute([$rid, $f1_delivery, $deliveryType]);
            if ($deliveryType === 'Giao hàng tận nơi') {
                $stmt_ans->execute([$rid, $f1_address, 'Số ' . rand(1, 150) . ' Đường Giải Phóng, Hà Nội']);
            }
            $stmt_ans->execute([$rid, $f1_date, '2026-12-15']);
        } elseif ($i < 23) {
            // 8 lượt cho Form 2
            $stmt_resp->execute([$ver2_id, $submittedAt]);
            $rid = $pdo->lastInsertId();

            $stmt_ans->execute([$rid, $f2_author, $name]);
            $stmt_ans->execute([$rid, $f2_title, 'Kỹ thuật chăm sóc cây có múi vụ đông']);
            $stmt_ans->execute([$rid, $f2_field, 'Trồng trọt']);
            $stmt_ans->execute([$rid, $f2_experienced, ($i % 2 === 0) ? 'Đã từng' : 'Chưa']);
            if ($i % 2 === 0) {
                $stmt_ans->execute([$rid, $f2_prev_book, 'Sổ tay bảo vệ thực vật 2024']);
            }
            $stmt_ans->execute([$rid, $f2_pages, (string)rand(120, 300)]);
            $stmt_ans->execute([$rid, $f2_deadline, '2026-11-30']);
        } else {
            // 7 lượt cho Form 3
            $stmt_resp->execute([$ver3_id, $submittedAt]);
            $rid = $pdo->lastInsertId();

            $rating = ($i === 28) ? 'Chưa đạt yêu cầu' : 'Rất đẹp';
            $stmt_ans->execute([$rid, $f3_reader, $name]);
            $stmt_ans->execute([$rid, $f3_source, 'Website NXB']);
            $stmt_ans->execute([$rid, $f3_rating, $rating]);
            if ($rating === 'Chưa đạt yêu cầu') {
                $stmt_ans->execute([$rid, $f3_reason, 'Chữ in hơi nhỏ, nên tăng độ tương phản']);
            }
            $stmt_ans->execute([$rid, $f3_newsletter, 'Có']);
            $stmt_ans->execute([$rid, $f3_email, 'reader' . $i . '@nongnghiep.vn']);
        }
    }

    echo "=== NẠP DỮ LIỆU THÀNH CÔNG! ===\n";
    echo "- Đã tạo đủ 3 biểu mẫu có logic điều kiện.\n";
    echo "- Đã nạp đủ 30 lượt trả lời mẫu vào CSDL MySQL.\n";

} catch (PDOException $e) {
    echo "LỖI NẠP DỮ LIỆU: " . $e->getMessage() . "\n";
}
?>