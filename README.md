# ĐỀ TÀI 11: XÂY DỰNG CÔNG CỤ TẠO BIỂU MẪU CÓ ĐIỀU KIỆN VÀ QUẢN LÝ DỮ LIỆU THU THẬP

- **Học phần:** Phát triển phần mềm mã nguồn mở
- **Đơn vị:** Khoa Công nghệ Thông tin – Trường Đại học Đại Nam (Lớp CNTT 1809 – Nhóm 1)
- **Giảng viên hướng dẫn:** ThS. Lê Tuấn Anh
- **Sinh viên thực hiện:**
  - **Nguyễn Anh Dũng** – MSV: `1871020167` (Trưởng nhóm / Backend PHP, PDO, Động cơ điều kiện Client/Server, Bảo mật RBAC, Dashboard & Xuất báo cáo)
  - **Ngô Gia Bảo** – MSV: `1871020072` (Phân tích nghiệp vụ, Thiết kế CSDL 3NF/ERD, Xây dựng bộ kịch bản kiểm thử TC-01 đến TC-13)

---

## 1. PHẠM VI CHỨC NĂNG & TÌNH HUỐNG SỬ DỤNG
Hệ thống được phát triển nhằm số hóa quy trình thu thập dữ liệu đặt in ấn phẩm và khảo sát tại Nhà xuất bản Nông nghiệp, bao gồm 5 nhóm chức năng chính:
1. **Quản lý biểu mẫu động (`admin/builder.php`, `admin/edit.php`, `admin/delete_form.php`):** Khởi tạo, chỉnh sửa cấu trúc câu hỏi (Text, Number, Date, Radio, Checkbox), phân tách đáp án thông minh bằng ký tự `/` hoặc `|` và xóa biểu mẫu kèm dữ liệu liên quan an toàn qua PDO Transaction.
2. **Khai báo & Đánh giá logic điều kiện (`conditions`, `form.php`):** Thiết lập quy tắc ẩn/hiện trường dữ liệu dựa trên câu trả lời của câu hỏi nguồn; tự động ẩn/hiện tức thì phía trình duyệt bằng Vanilla JS và đối chiếu lại quy tắc trên máy chủ PHP khi nhận dữ liệu POST.
3. **Điền biểu mẫu & Thu thập dữ liệu (`forms.php`, `form.php`):** Tự động điền thông tin định danh từ phiên đăng nhập, kiểm tra dữ liệu bắt buộc và lưu đồng bộ vào 2 bảng `responses` và `answers`.
4. **Thống kê, Trực quan hóa & Xuất báo cáo (`admin/index.php`, `admin/export.php`):** Dashboard tổng hợp lượt phản hồi, biểu đồ phân tích đa năng cho cả 5 loại trường dữ liệu và trích xuất báo cáo Excel (CSV) chuẩn bảng mã tiếng Việt UTF-8 (kèm ký tự BOM).
5. **Xác thực & Phân quyền truy cập (`login.php`, `register.php`, `admin/auth_check.php`):** Mã hóa mật khẩu một chiều bằng giải thuật `BCRYPT`, chống SQL Injection bằng `PDO Prepared Statements` và phân tách quyền `admin` / `client`.

---

## 2. PHÂN ĐỊNH MÃ NGUỒN & TÀI NGUYÊN TÁI SỬ DỤNG
- **Phần mã nhóm tự phát triển:**
  - Toàn bộ kiến trúc xử lý Backend PHP 8.x, kết nối CSDL PDO (`config/db.php`), xác thực và phân quyền RBAC (`login.php`, `register.php`, `check_login.php`, `admin/auth_check.php`).
  - Các module quản trị vòng đời biểu mẫu (`admin/builder.php`, `admin/edit.php`, `admin/delete_form.php`, `admin/delete.php`, `admin/export.php`, `admin/index.php`).
  - Động cơ đánh giá điều kiện thời gian thực bằng JavaScript thuần và bộ kiểm tra hợp lệ phía máy chủ (`form.php`, `forms.php`).
  - Cấu trúc cơ sở dữ liệu quan hệ 7 bảng chuẩn 3NF và bộ dữ liệu mẫu (`database/database.sql`, `seed.php`).
- **Thư viện & Tài nguyên mã nguồn mở sử dụng lại:**
  - **Bootstrap 5.3 & Bootstrap Icons** (Giấy phép MIT): Chuẩn hóa lưới giao diện và các thành phần biểu mẫu.
  - **Tailwind CSS (CDN)** (Giấy phép MIT): Xây dựng giao diện cổng thông tin và bảng điều khiển quản trị.
  - **Google Fonts (`Plus Jakarta Sans`, `Be Vietnam Pro`)** (Giấy phép SIL Open Font License): Bộ phông chữ hiển thị tiếng Việt.

---

## 3. HƯỚNG DẪN CÀI ĐẶT VÀ CHẠY DỰ ÁN
1. Khởi động **Apache** và **MySQL** trên **XAMPP** (cổng mặc định `8088` hoặc `80`).
2. Sao chép thư mục `ptpmmnmdetai11` vào thư mục gốc của XAMPP (`htdocs/ptpmmnmdetai11`).
3. Mở **phpMyAdmin** (`http://localhost:8088/phpmyadmin/`), tạo cơ sở dữ liệu mới tên **`form_system`** (bảng mã `utf8mb4_unicode_ci`) và **Import** tệp `ptpmmnmdetai11/database/database.sql`.
4. Kiểm tra thông số kết nối trong tệp `ptpmmnmdetai11/config/db.php` (`host=localhost`, `dbname=form_system`, `user=root`, `pass=''`).
5. Truy cập ứng dụng trên trình duyệt:
   - **Cổng thông tin & Biểu mẫu (Client):** `http://localhost:8088/ptpmmnmdetai11/forms.php`
   - **Bảng điều khiển Quản trị (Admin):** `http://localhost:8088/ptpmmnmdetai11/admin/index.php`

---

## 4. TÀI KHOẢN NGHIỆM THU (DỮ LIỆU MẪU)
- **Tài khoản Quản trị viên (`admin`):**
  - Tên đăng nhập: `admin`
  - Mật khẩu: `123456`
- **Tài khoản Khách hàng (`client`):**
  - Tên đăng nhập: `khachhang1` (hoặc `khach1`)
  - Mật khẩu: `123456`

---

## 5. HƯỚNG DẪN KIỂM THỬ CÁC LUỒNG TRỌNG TÂM
1. **Kiểm thử phân quyền RBAC:** Đăng nhập bằng tài khoản `client`, sau đó truy cập trực tiếp đường dẫn `/admin/index.php` -> Hệ thống tự động chặn và điều hướng về `login.php`.
2. **Kiểm thử logic điều kiện thời gian thực:** Mở *"Phiếu đăng ký in lịch Tết..."* (`form.php?id=1`), chuyển đổi mục *"Đối tượng khách hàng"* giữa **Cá nhân** và **Doanh nghiệp / HTX** -> Các trường *Tên doanh nghiệp* và *Mã số thuế* tự động ẩn/hiện tức thì và vô hiệu hóa dữ liệu khi ẩn.
3. **Kiểm thử toàn vẹn dữ liệu & Xuất báo cáo:** Gửi biểu mẫu hợp lệ, kiểm tra bản ghi đồng bộ trên 2 bảng `responses` và `answers`, sau đó nhấn **Xuất Excel** tại Dashboard để kiểm tra file `.csv` hiển thị đúng tiếng Việt có dấu.