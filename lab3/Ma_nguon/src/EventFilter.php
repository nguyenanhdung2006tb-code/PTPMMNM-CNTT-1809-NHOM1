<?php
declare(strict_types=1);

function filterEvents(
    array $events, 
    string $query, 
    string $category = '', 
    bool $availableOnly = false
): array {
    $result = [];

    foreach ($events as $event) {
        // 1. Kiểm tra từ khóa (rỗng hoặc xuất hiện trong tiêu đề)
        $matchesQuery = ($query === '' || stripos($event['title'], $query) !== false);

        // 2. Kiểm tra loại/danh mục (rỗng hoặc trùng khớp chính xác)
        $matchesCategory = ($category === '' || $event['category'] === $category);

        // 3. Kiểm tra còn chỗ (không chọn lọc còn chỗ HOẶC sức chứa > số người đã đăng ký)
        $matchesAvailable = (!$availableOnly || ($event['capacity'] > $event['registered']));

        // Kết hợp cả 3 điều kiện theo toán tử AND
        if ($matchesQuery && $matchesCategory && $matchesAvailable) {
            $result[] = $event;
        }
    }

    return $result;
}