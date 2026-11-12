# QUY ĐỊNH ĐÓNG GÓP MÃ NGUỒN & CỘNG TÁC (CONTRIBUTING)

## 1. Tổ chức nhánh làm việc (Branching Model)
- `main`: Nhánh chính thức lưu trữ mã nguồn ổn định phục vụ nghiệm thu và phát hành.
- `feature/<ten-chuc-nang>`: Nhánh phát triển tính năng mới (ví dụ: `feature/form-editor`, `feature/csv-export`).
- `fix/<ten-loi>`: Nhánh xử lý sửa lỗi phát sinh trong quá trình kiểm thử.

## 2. Quy chuẩn đặt tên Commit
Mỗi commit cần mô tả rõ phạm vi thay đổi theo tiền tố chuẩn:
- `feat:` Bổ sung chức năng mới.
- `fix:` Sửa lỗi logic, giao diện hoặc bảo mật.
- `docs:` Cập nhật tài liệu báo cáo, hướng dẫn sử dụng (`README.md`).
- `test:` Bổ sung kịch bản kiểm thử hoặc dữ liệu mẫu (`database.sql`).

## 3. Quy trình Issue, Pull Request & Review
1. Ghi nhận yêu cầu hoặc lỗi cần xử lý thông qua mục **Issues** trên kho GitHub của nhóm.
2. Kiểm tra mã nguồn chạy ổn định trên môi trường XAMPP cục bộ và không chứa thông tin nhạy cảm trước khi đẩy lên nhánh.
3. Tạo **Pull Request** vào nhánh `main` để thành viên còn lại kiểm tra chéo (Code Review) và xác nhận tích hợp.