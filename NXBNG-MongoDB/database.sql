-- 1. Tạo Database
CREATE DATABASE IF NOT EXISTS form_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE form_system;

-- 2. Tạo tài khoản cho PHP kết nối
CREATE USER IF NOT EXISTS 'form_user'@'localhost' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON form_system.* TO 'form_user'@'localhost';
FLUSH PRIVILEGES;

-- 3. Bảng quản lý biểu mẫu
CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Bảng quản lý phiên bản biểu mẫu
CREATE TABLE IF NOT EXISTS form_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    version_number INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

-- 5. Bảng danh sách câu hỏi / trường dữ liệu
CREATE TABLE IF NOT EXISTS fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_version_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    field_type ENUM('text', 'number', 'date', 'radio', 'checkbox') NOT NULL,
    options_json TEXT NULL,
    is_required TINYINT(1) DEFAULT 0,
    display_order INT DEFAULT 0,
    FOREIGN KEY (form_version_id) REFERENCES form_versions(id) ON DELETE CASCADE
);

-- 6. Bảng điều kiện ẩn / hiện câu hỏi
CREATE TABLE IF NOT EXISTS conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_version_id INT NOT NULL,
    source_field_id INT NOT NULL,
    target_field_id INT NOT NULL,
    operator VARCHAR(20) DEFAULT 'equals',
    trigger_value VARCHAR(255) NOT NULL,
    action VARCHAR(20) DEFAULT 'show',
    FOREIGN KEY (form_version_id) REFERENCES form_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (source_field_id) REFERENCES fields(id) ON DELETE CASCADE,
    FOREIGN KEY (target_field_id) REFERENCES fields(id) ON DELETE CASCADE
);

-- 7. Bảng lưu lượt gửi của người dùng
CREATE TABLE IF NOT EXISTS responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_version_id INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_version_id) REFERENCES form_versions(id) ON DELETE CASCADE
);

-- 8. Bảng lưu chi tiết câu trả lời
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    field_id INT NOT NULL,
    answer_value TEXT,
    FOREIGN KEY (response_id) REFERENCES responses(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE
);
