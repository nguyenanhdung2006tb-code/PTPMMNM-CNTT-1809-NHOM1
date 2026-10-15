<?php
declare(strict_types=1);

final class RegistrationPolicy {
    public function decide(int $capacity, int $registered,
        bool $open, bool $waitingEnabled): string {
        
        // 1. Kiểm tra dữ liệu không hợp lệ: sức chứa <= 0, số đăng ký âm hoặc vượt sức chứa
        if ($capacity <= 0 || $registered < 0 || $registered > $capacity) {
            return 'rejected';
        }

        // 2. Sự kiện đang đóng
        if (!$open) {
            return 'rejected';
        }

        // 3. Sự kiện mở và còn chỗ
        if ($registered < $capacity) {
            return 'confirmed';
        }

        // 4. Sự kiện mở, hết chỗ và có bật danh sách chờ
        if ($waitingEnabled) {
            return 'waiting';
        }

        // 5. Sự kiện mở, hết chỗ nhưng không bật danh sách chờ
        return 'rejected';
    }
}