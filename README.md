# ĐỀ TÀI 11: XÂY DỰNG CÔNG CỤ TẠO BIỂU MẪU CÓ ĐIỀU KIỆN VÀ QUẢN LÝ DỮ LIỆU THU THẬP

- **Học phần:** Phát triển phần mềm mã nguồn mở
- **Sinh viên thực hiện:**
  - Nguyễn Anh Dũng - MSV: 1871020167 (Trưởng nhóm / Backend)
  - Ngô Gia Bảo - MSV: 1871020072 (Cơ sở dữ liệu / Kiểm thử)

---

## 1. CÔNG NGHỆ SỬ DỤNG
- Backend: PHP 8.x, kết nối cơ sở dữ liệu an toàn bằng PDO Prepared Statements.
- Cơ sở dữ liệu: MySQL (form_system gồm 7 bảng quan hệ chuẩn 3NF).
- Frontend: Vanilla JavaScript (xử lý logic ẩn/hiện điều kiện thời gian thực), Bootstrap 5.

---

## 2. TÀI KHOẢN ĐĂNG NHẬP NGHIỆM THU
- **Tài khoản Quản trị (Admin):**
  - Tên đăng nhập: `admin`
  - Mật khẩu: `123456`
  - Quyền hạn: Quản trị biểu mẫu, xem dashboard thống kê, xóa phản hồi, xuất file Excel CSV.
- **Tài khoản Người dùng (Client):**
  - Tên đăng nhập: `khachhang1`
  - Mật khẩu: `123456`
  - Quyền hạn: Xem và điền biểu mẫu rẽ nhánh, bị chặn truy cập thư mục `/admin/`.

---

## 3. CÁCH CÀI ĐẶT VÀ CHẠY DỰ ÁN
1. Khởi động Apache và MySQL trong XAMPP.
2. Đặt thư mục code `ptpmmnmdetai11` vào thư mục `C:\xampp\htdocs\`.
3. Mở phpMyAdmin tạo database tên `form_system`, sau đó import file `database/database.sql`.
4. Truy cập hệ thống trên trình duyệt:
   - Trang người dùng: `http://localhost/ptpmmnmdetai11/forms.php`
   - Trang quản trị: `http://localhost/ptpmmnmdetai11/admin/index.php`

---

## 4. TÀI NGUYÊN TÁI SỬ DỤNG
- Bootstrap 5.3 (Giấy phép MIT License).
- Toàn bộ mã nguồn xử lý logic Backend và JavaScript do nhóm tự phát triển.