<?php
declare(strict_types=1);

function canComplete(array $session, int $eventId, int $now, int $ttl): bool
{
    // 1. Thời gian sống (ttl) phải lớn hơn 0
    if ($ttl <= 0) {
        return false;
    }

    // 2. Phải có bản nháp hợp lệ và đủ trường event_id, created_at
    if (!isset($session['draft']['data']['event_id'], $session['draft']['created_at'])) {
        return false;
    }

    // 3. Đúng sự kiện đang yêu cầu
    if ((int)$session['draft']['data']['event_id'] !== $eventId) {
        return false;
    }

    $createdAt = (int)$session['draft']['created_at'];

    // 4. Thời điểm tạo không được ở tương lai
    if ($createdAt > $now) {
        return false;
    }

    // 5. Kiểm tra thời hạn: đúng hoặc quá ngưỡng hết hạn đều từ chối
    if ($now - $createdAt >= $ttl) {
        return false;
    }

    return true;
}