-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 27, 2026 lúc 05:32 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `form_system`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `answers`
--

CREATE TABLE `answers` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `field_id` int(11) NOT NULL,
  `answer_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `answers`
--

INSERT INTO `answers` (`id`, `response_id`, `field_id`, `answer_value`) VALUES
(1, 1, 1, 'Nguyễn Văn An 1'),
(2, 1, 2, '0981056956'),
(3, 1, 3, 'khach1@gmail.com'),
(4, 1, 4, 'Doanh nghiệp / HTX'),
(5, 1, 5, 'Công ty CP Giống Cây Trồng Trung Ương'),
(6, 1, 6, '0101182200'),
(7, 1, 7, 'Lịch bloc cực đại'),
(8, 1, 8, '220'),
(9, 1, 9, 'Nhận trực tiếp tại NXB'),
(10, 1, 11, '2026-12-15'),
(11, 2, 1, 'Trần Thị Mai 2'),
(12, 2, 2, '0981339536'),
(13, 2, 3, 'khach2@gmail.com'),
(14, 2, 4, 'Cá nhân'),
(15, 2, 7, 'Lịch bloc cực đại'),
(16, 2, 8, '65'),
(17, 2, 9, 'Giao hàng tận nơi'),
(18, 2, 10, 'Số 39 Đường Giải Phóng, Hà Nội'),
(19, 2, 11, '2026-12-15'),
(20, 3, 1, 'Lê Hoàng Long 3'),
(21, 3, 2, '0982407503'),
(22, 3, 3, 'khach3@gmail.com'),
(23, 3, 4, 'Doanh nghiệp / HTX'),
(24, 3, 5, 'Công ty Phân Bón Sông Hồng'),
(25, 3, 6, '0101433434'),
(26, 3, 7, 'Lịch bloc cực đại'),
(27, 3, 8, '198'),
(28, 3, 9, 'Giao hàng tận nơi'),
(29, 3, 10, 'Số 147 Đường Giải Phóng, Hà Nội'),
(30, 3, 11, '2026-12-15'),
(31, 4, 1, 'Phạm Minh Đức 4'),
(32, 4, 2, '0987146367'),
(33, 4, 3, 'khach4@gmail.com'),
(34, 4, 4, 'Cá nhân'),
(35, 4, 7, 'Lịch bloc cực đại'),
(36, 4, 8, '133'),
(37, 4, 9, 'Nhận trực tiếp tại NXB'),
(38, 4, 11, '2026-12-15'),
(39, 5, 1, 'Hoàng Thu Trang 5'),
(40, 5, 2, '0982875627'),
(41, 5, 3, 'khach5@gmail.com'),
(42, 5, 4, 'Doanh nghiệp / HTX'),
(43, 5, 5, 'Công ty TNHH Nông Lâm Sản'),
(44, 5, 6, '0104088404'),
(45, 5, 7, 'Lịch bloc cực đại'),
(46, 5, 8, '416'),
(47, 5, 9, 'Giao hàng tận nơi'),
(48, 5, 10, 'Số 95 Đường Giải Phóng, Hà Nội'),
(49, 5, 11, '2026-12-15'),
(50, 6, 1, 'Đỗ Quốc Bảo 6'),
(51, 6, 2, '0987871770'),
(52, 6, 3, 'khach6@gmail.com'),
(53, 6, 4, 'Cá nhân'),
(54, 6, 7, 'Lịch bloc cực đại'),
(55, 6, 8, '262'),
(56, 6, 9, 'Giao hàng tận nơi'),
(57, 6, 10, 'Số 55 Đường Giải Phóng, Hà Nội'),
(58, 6, 11, '2026-12-15'),
(59, 7, 1, 'Vũ Thị Lan 7'),
(60, 7, 2, '0989300953'),
(61, 7, 3, 'khach7@gmail.com'),
(62, 7, 4, 'Doanh nghiệp / HTX'),
(63, 7, 5, 'HTX Nông Nghiệp Xanh Hà Nội'),
(64, 7, 6, '0107647099'),
(65, 7, 7, 'Lịch bloc cực đại'),
(66, 7, 8, '463'),
(67, 7, 9, 'Nhận trực tiếp tại NXB'),
(68, 7, 11, '2026-12-15'),
(69, 8, 1, 'Bùi Văn Hùng 8'),
(70, 8, 2, '0984774899'),
(71, 8, 3, 'khach8@gmail.com'),
(72, 8, 4, 'Cá nhân'),
(73, 8, 7, 'Lịch bloc cực đại'),
(74, 8, 8, '306'),
(75, 8, 9, 'Giao hàng tận nơi'),
(76, 8, 10, 'Số 94 Đường Giải Phóng, Hà Nội'),
(77, 8, 11, '2026-12-15'),
(78, 9, 1, 'Ngô Thanh Tùng 9'),
(79, 9, 2, '0988625968'),
(80, 9, 3, 'khach9@gmail.com'),
(81, 9, 4, 'Doanh nghiệp / HTX'),
(82, 9, 5, 'Trang trại Hữu Cơ Ba Vì'),
(83, 9, 6, '0103167753'),
(84, 9, 7, 'Lịch bloc cực đại'),
(85, 9, 8, '106'),
(86, 9, 9, 'Giao hàng tận nơi'),
(87, 9, 10, 'Số 120 Đường Giải Phóng, Hà Nội'),
(88, 9, 11, '2026-12-15'),
(89, 10, 1, 'Đặng Thảo Vy 10'),
(90, 10, 2, '0983155136'),
(91, 10, 3, 'khach10@gmail.com'),
(92, 10, 4, 'Cá nhân'),
(93, 10, 7, 'Lịch bloc cực đại'),
(94, 10, 8, '80'),
(95, 10, 9, 'Nhận trực tiếp tại NXB'),
(96, 10, 11, '2026-12-15'),
(97, 11, 1, 'Nguyễn Văn An 11'),
(98, 11, 2, '0989998446'),
(99, 11, 3, 'khach11@gmail.com'),
(100, 11, 4, 'Doanh nghiệp / HTX'),
(101, 11, 5, 'Công ty CP Giống Cây Trồng Trung Ương'),
(102, 11, 6, '0103888197'),
(103, 11, 7, 'Lịch bloc cực đại'),
(104, 11, 8, '240'),
(105, 11, 9, 'Giao hàng tận nơi'),
(106, 11, 10, 'Số 53 Đường Giải Phóng, Hà Nội'),
(107, 11, 11, '2026-12-15'),
(108, 12, 1, 'Trần Thị Mai 12'),
(109, 12, 2, '0988436982'),
(110, 12, 3, 'khach12@gmail.com'),
(111, 12, 4, 'Cá nhân'),
(112, 12, 7, 'Lịch bloc cực đại'),
(113, 12, 8, '235'),
(114, 12, 9, 'Giao hàng tận nơi'),
(115, 12, 10, 'Số 97 Đường Giải Phóng, Hà Nội'),
(116, 12, 11, '2026-12-15'),
(117, 13, 1, 'Lê Hoàng Long 13'),
(118, 13, 2, '0983329005'),
(119, 13, 3, 'khach13@gmail.com'),
(120, 13, 4, 'Doanh nghiệp / HTX'),
(121, 13, 5, 'Công ty Phân Bón Sông Hồng'),
(122, 13, 6, '0108863759'),
(123, 13, 7, 'Lịch bloc cực đại'),
(124, 13, 8, '446'),
(125, 13, 9, 'Nhận trực tiếp tại NXB'),
(126, 13, 11, '2026-12-15'),
(127, 14, 1, 'Phạm Minh Đức 14'),
(128, 14, 2, '0987596818'),
(129, 14, 3, 'khach14@gmail.com'),
(130, 14, 4, 'Cá nhân'),
(131, 14, 7, 'Lịch bloc cực đại'),
(132, 14, 8, '55'),
(133, 14, 9, 'Giao hàng tận nơi'),
(134, 14, 10, 'Số 127 Đường Giải Phóng, Hà Nội'),
(135, 14, 11, '2026-12-15'),
(136, 15, 1, 'Hoàng Thu Trang 15'),
(137, 15, 2, '0986029229'),
(138, 15, 3, 'khach15@gmail.com'),
(139, 15, 4, 'Doanh nghiệp / HTX'),
(140, 15, 5, 'Công ty TNHH Nông Lâm Sản'),
(141, 15, 6, '0104819934'),
(142, 15, 7, 'Lịch bloc cực đại'),
(143, 15, 8, '111'),
(144, 15, 9, 'Giao hàng tận nơi'),
(145, 15, 10, 'Số 140 Đường Giải Phóng, Hà Nội'),
(146, 15, 11, '2026-12-15'),
(147, 16, 12, 'Đỗ Quốc Bảo 16'),
(148, 16, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(149, 16, 14, 'Trồng trọt'),
(150, 16, 15, 'Chưa'),
(151, 16, 17, '225'),
(152, 16, 18, '2026-11-30'),
(153, 17, 12, 'Vũ Thị Lan 17'),
(154, 17, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(155, 17, 14, 'Trồng trọt'),
(156, 17, 15, 'Đã từng'),
(157, 17, 16, 'Sổ tay bảo vệ thực vật 2024'),
(158, 17, 17, '298'),
(159, 17, 18, '2026-11-30'),
(160, 18, 12, 'Bùi Văn Hùng 18'),
(161, 18, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(162, 18, 14, 'Trồng trọt'),
(163, 18, 15, 'Chưa'),
(164, 18, 17, '135'),
(165, 18, 18, '2026-11-30'),
(166, 19, 12, 'Ngô Thanh Tùng 19'),
(167, 19, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(168, 19, 14, 'Trồng trọt'),
(169, 19, 15, 'Đã từng'),
(170, 19, 16, 'Sổ tay bảo vệ thực vật 2024'),
(171, 19, 17, '153'),
(172, 19, 18, '2026-11-30'),
(173, 20, 12, 'Đặng Thảo Vy 20'),
(174, 20, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(175, 20, 14, 'Trồng trọt'),
(176, 20, 15, 'Chưa'),
(177, 20, 17, '250'),
(178, 20, 18, '2026-11-30'),
(179, 21, 12, 'Nguyễn Văn An 21'),
(180, 21, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(181, 21, 14, 'Trồng trọt'),
(182, 21, 15, 'Đã từng'),
(183, 21, 16, 'Sổ tay bảo vệ thực vật 2024'),
(184, 21, 17, '281'),
(185, 21, 18, '2026-11-30'),
(186, 22, 12, 'Trần Thị Mai 22'),
(187, 22, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(188, 22, 14, 'Trồng trọt'),
(189, 22, 15, 'Chưa'),
(190, 22, 17, '236'),
(191, 22, 18, '2026-11-30'),
(192, 23, 12, 'Lê Hoàng Long 23'),
(193, 23, 13, 'Kỹ thuật chăm sóc cây có múi vụ đông'),
(194, 23, 14, 'Trồng trọt'),
(195, 23, 15, 'Đã từng'),
(196, 23, 16, 'Sổ tay bảo vệ thực vật 2024'),
(197, 23, 17, '124'),
(198, 23, 18, '2026-11-30'),
(199, 24, 19, 'Phạm Minh Đức 24'),
(200, 24, 20, 'Website NXB'),
(201, 24, 21, 'Rất đẹp'),
(202, 24, 23, 'Có'),
(203, 24, 24, 'reader23@nongnghiep.vn'),
(204, 25, 19, 'Hoàng Thu Trang 25'),
(205, 25, 20, 'Website NXB'),
(206, 25, 21, 'Rất đẹp'),
(207, 25, 23, 'Có'),
(208, 25, 24, 'reader24@nongnghiep.vn'),
(209, 26, 19, 'Đỗ Quốc Bảo 26'),
(210, 26, 20, 'Website NXB'),
(211, 26, 21, 'Rất đẹp'),
(212, 26, 23, 'Có'),
(213, 26, 24, 'reader25@nongnghiep.vn'),
(214, 27, 19, 'Vũ Thị Lan 27'),
(215, 27, 20, 'Website NXB'),
(216, 27, 21, 'Rất đẹp'),
(217, 27, 23, 'Có'),
(218, 27, 24, 'reader26@nongnghiep.vn'),
(219, 28, 19, 'Bùi Văn Hùng 28'),
(220, 28, 20, 'Website NXB'),
(221, 28, 21, 'Rất đẹp'),
(222, 28, 23, 'Có'),
(223, 28, 24, 'reader27@nongnghiep.vn'),
(224, 29, 19, 'Ngô Thanh Tùng 29'),
(225, 29, 20, 'Website NXB'),
(226, 29, 21, 'Chưa đạt yêu cầu'),
(227, 29, 22, 'Chữ in hơi nhỏ, nên tăng độ tương phản'),
(228, 29, 23, 'Có'),
(229, 29, 24, 'reader28@nongnghiep.vn'),
(230, 30, 19, 'Đặng Thảo Vy 30'),
(231, 30, 20, 'Website NXB'),
(232, 30, 21, 'Rất đẹp'),
(233, 30, 23, 'Có'),
(234, 30, 24, 'reader29@nongnghiep.vn'),
(235, 31, 1, 'Nguyễn Anh Dũng '),
(236, 31, 2, '0912345677'),
(237, 31, 3, 'dd@35gmail.'),
(238, 31, 4, 'Cá nhân'),
(239, 31, 7, 'Lịch bloc cực đại'),
(240, 31, 8, '2'),
(241, 31, 9, 'Nhận trực tiếp tại NXB'),
(242, 31, 11, '2026-10-27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `conditions`
--

CREATE TABLE `conditions` (
  `id` int(11) NOT NULL,
  `form_version_id` int(11) NOT NULL,
  `source_field_id` int(11) NOT NULL,
  `target_field_id` int(11) NOT NULL,
  `operator` varchar(20) DEFAULT 'equals',
  `trigger_value` varchar(255) NOT NULL,
  `action` varchar(20) DEFAULT 'show'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `conditions`
--

INSERT INTO `conditions` (`id`, `form_version_id`, `source_field_id`, `target_field_id`, `operator`, `trigger_value`, `action`) VALUES
(1, 1, 4, 5, 'equals', 'Doanh nghiệp / HTX', 'show'),
(2, 1, 4, 6, 'equals', 'Doanh nghiệp / HTX', 'show'),
(3, 1, 9, 10, 'equals', 'Giao hàng tận nơi', 'show'),
(4, 2, 15, 16, 'equals', 'Đã từng', 'show'),
(5, 3, 21, 22, 'equals', 'Chưa đạt yêu cầu', 'show'),
(6, 3, 23, 24, 'equals', 'Có', 'show');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fields`
--

CREATE TABLE `fields` (
  `id` int(11) NOT NULL,
  `form_version_id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `field_type` enum('text','number','date','radio','checkbox') NOT NULL,
  `options_json` text DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `fields`
--

INSERT INTO `fields` (`id`, `form_version_id`, `label`, `field_type`, `options_json`, `is_required`, `display_order`) VALUES
(1, 1, 'Họ và tên người liên hệ', 'text', NULL, 1, 1),
(2, 1, 'Số điện thoại', 'text', NULL, 1, 2),
(3, 1, 'Email', 'text', NULL, 0, 3),
(4, 1, 'Đối tượng khách hàng', 'radio', '[\"C\\u00e1 nh\\u00e2n\",\"Doanh nghi\\u1ec7p \\/ HTX\"]', 1, 4),
(5, 1, 'Tên doanh nghiệp / Đơn vị', 'text', NULL, 0, 5),
(6, 1, 'Mã số thuế', 'text', NULL, 0, 6),
(7, 1, 'Loại ấn phẩm lịch muốn in', 'radio', '[\"L\\u1ecbch bloc c\\u1ef1c \\u0111\\u1ea1i\",\"L\\u1ecbch treo t\\u01b0\\u1eddng 7 t\\u1edd\",\"L\\u1ecbch \\u0111\\u1ec3 b\\u00e0n ch\\u1eef A\",\"S\\u1ed5 tay n\\u00f4ng v\\u1ee5\"]', 1, 7),
(8, 1, 'Số lượng dự kiến (cuốn/bộ)', 'number', NULL, 1, 8),
(9, 1, 'Hình thức nhận hàng', 'radio', '[\"Nh\\u1eadn tr\\u1ef1c ti\\u1ebfp t\\u1ea1i NXB\",\"Giao h\\u00e0ng t\\u1eadn n\\u01a1i\"]', 1, 9),
(10, 1, 'Địa chỉ giao hàng chi tiết', 'text', NULL, 0, 10),
(11, 1, 'Ngày mong muốn nhận hàng', 'date', NULL, 0, 11),
(12, 2, 'Họ tên tác giả / Chủ biên', 'text', NULL, 1, 1),
(13, 2, 'Tên bản thảo tác phẩm', 'text', NULL, 1, 2),
(14, 2, 'Lĩnh vực chuyên môn', 'radio', '[\"Tr\\u1ed3ng tr\\u1ecdt\",\"Ch\\u0103n nu\\u00f4i th\\u00fa y\",\"L\\u00e2m nghi\\u1ec7p\",\"Th\\u1ee7y s\\u1ea3n\"]', 1, 3),
(15, 2, 'Bạn đã từng xuất bản sách tại NXB chưa?', 'radio', '[\"\\u0110\\u00e3 t\\u1eebng\",\"Ch\\u01b0a\"]', 1, 4),
(16, 2, 'Tên tác phẩm đã xuất bản gần nhất', 'text', NULL, 0, 5),
(17, 2, 'Dự kiến số trang', 'number', NULL, 1, 6),
(18, 2, 'Thời gian nộp bản thảo hoàn chỉnh', 'date', NULL, 1, 7),
(19, 3, 'Họ và tên độc giả', 'text', NULL, 1, 1),
(20, 3, 'Bạn biết đến ấn phẩm từ nguồn nào?', 'radio', '[\"H\\u1ed9i ch\\u1ee3 s\\u00e1ch\",\"Website NXB\",\"\\u0110\\u01b0\\u1ee3c gi\\u1edbi thi\\u1ec7u\",\"M\\u1ea1ng x\\u00e3 h\\u1ed9i\"]', 1, 2),
(21, 3, 'Đánh giá chất lượng in ấn và màu sắc', 'radio', '[\"R\\u1ea5t \\u0111\\u1eb9p\",\"T\\u1ed1t\",\"B\\u00ecnh th\\u01b0\\u1eddng\",\"Ch\\u01b0a \\u0111\\u1ea1t y\\u00eau c\\u1ea7u\"]', 1, 3),
(22, 3, 'Chi tiết điểm chưa đạt yêu cầu cần cải thiện', 'text', NULL, 0, 4),
(23, 3, 'Bạn có muốn nhận bản tin khuyến nông qua Email không?', 'radio', '[\"C\\u00f3\",\"Kh\\u00f4ng\"]', 1, 5),
(24, 3, 'Email nhận bản tin', 'text', NULL, 0, 6);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `forms`
--

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `forms`
--

INSERT INTO `forms` (`id`, `title`, `description`, `created_at`) VALUES
(1, 'Phiếu đăng ký in lịch Tết & ấn phẩm Xuân theo yêu cầu (NXB Nông Nghiệp)', 'Dành cho cá nhân, đại lý và doanh nghiệp đăng ký đặt in các ấn phẩm lịch bloc, lịch bàn, lịch treo tường.', '2026-10-26 08:01:09'),
(2, 'Phiếu đăng ký thẩm định và xuất bản sách, tài liệu nông nghiệp', 'Tiếp nhận bản thảo, đề cương sách kỹ thuật cây trồng, chăn nuôi và khuyến nông.', '2026-10-26 08:01:09'),
(3, 'Phiếu khảo sát mức độ hài lòng độc giả năm 2026', 'Thu thập ý kiến đóng góp về nội dung và chất lượng in ấn các ấn phẩm NXB Nông Nghiệp.', '2026-10-26 08:01:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `form_versions`
--

CREATE TABLE `form_versions` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `version_number` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `form_versions`
--

INSERT INTO `form_versions` (`id`, `form_id`, `version_number`, `is_active`, `created_at`) VALUES
(1, 1, 1, 1, '2026-10-26 08:01:09'),
(2, 2, 1, 1, '2026-10-26 08:01:09'),
(3, 3, 1, 1, '2026-10-26 08:01:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `responses`
--

CREATE TABLE `responses` (
  `id` int(11) NOT NULL,
  `form_version_id` int(11) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `responses`
--

INSERT INTO `responses` (`id`, `form_version_id`, `submitted_at`) VALUES
(1, 1, '2026-10-22 05:01:10'),
(2, 1, '2026-10-23 05:01:10'),
(3, 1, '2026-10-22 12:01:10'),
(4, 1, '2026-10-24 20:01:10'),
(5, 1, '2026-10-25 21:01:10'),
(6, 1, '2026-10-22 02:01:10'),
(7, 1, '2026-10-22 06:01:10'),
(8, 1, '2026-10-22 16:01:10'),
(9, 1, '2026-10-24 17:01:11'),
(10, 1, '2026-10-25 04:01:11'),
(11, 1, '2026-10-24 07:01:11'),
(12, 1, '2026-10-25 17:01:11'),
(13, 1, '2026-10-25 01:01:11'),
(14, 1, '2026-10-23 16:01:11'),
(15, 1, '2026-10-22 07:01:11'),
(16, 2, '2026-10-25 23:01:11'),
(17, 2, '2026-10-22 07:01:11'),
(18, 2, '2026-10-24 21:01:11'),
(19, 2, '2026-10-23 06:01:11'),
(20, 2, '2026-10-21 17:01:11'),
(21, 2, '2026-10-25 17:01:12'),
(22, 2, '2026-10-23 20:01:12'),
(23, 2, '2026-10-24 19:01:12'),
(24, 3, '2026-10-23 21:01:12'),
(25, 3, '2026-10-23 03:01:12'),
(26, 3, '2026-10-22 10:01:12'),
(27, 3, '2026-10-22 07:01:12'),
(28, 3, '2026-10-21 05:01:12'),
(29, 3, '2026-10-22 11:01:12'),
(30, 3, '2026-10-24 18:01:12'),
(31, 1, '2026-10-26 08:03:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'client',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `role`, `created_at`) VALUES
(2, 'khach1', '$2y$10$WwO0jK047qZ355Hghn69j.rV1M8KzI17dZ80X0g.Tj69G4ZlE8w9W', 'Nguyễn Văn Khách', 'client', '2026-10-26 09:40:29'),
(3, 'khach2', '$2y$10$JzHMVvPLlL1JN5VZvCbpNu/IlFDy.e.uX4imZ6S52jQ/xPQR9Phjm', 'Lâm Nhạc Anh', 'client', '2026-10-27 03:25:14'),
(4, 'admin', '$2y$10$aE3GSC4TCWj1meT1CmifOe2soLDfnU0zvM9oG3oiBy5Zk/NFif7eW', 'Quản Trị Viên', 'admin', '2026-10-27 03:41:36');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Chỉ mục cho bảng `conditions`
--
ALTER TABLE `conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_version_id` (`form_version_id`),
  ADD KEY `source_field_id` (`source_field_id`),
  ADD KEY `target_field_id` (`target_field_id`);

--
-- Chỉ mục cho bảng `fields`
--
ALTER TABLE `fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_version_id` (`form_version_id`);

--
-- Chỉ mục cho bảng `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `form_versions`
--
ALTER TABLE `form_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Chỉ mục cho bảng `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_version_id` (`form_version_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `answers`
--
ALTER TABLE `answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=243;

--
-- AUTO_INCREMENT cho bảng `conditions`
--
ALTER TABLE `conditions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `fields`
--
ALTER TABLE `fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `form_versions`
--
ALTER TABLE `form_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `responses`
--
ALTER TABLE `responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`response_id`) REFERENCES `responses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `conditions`
--
ALTER TABLE `conditions`
  ADD CONSTRAINT `conditions_ibfk_1` FOREIGN KEY (`form_version_id`) REFERENCES `form_versions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conditions_ibfk_2` FOREIGN KEY (`source_field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conditions_ibfk_3` FOREIGN KEY (`target_field_id`) REFERENCES `fields` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fields`
--
ALTER TABLE `fields`
  ADD CONSTRAINT `fields_ibfk_1` FOREIGN KEY (`form_version_id`) REFERENCES `form_versions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `form_versions`
--
ALTER TABLE `form_versions`
  ADD CONSTRAINT `form_versions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`form_version_id`) REFERENCES `form_versions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
