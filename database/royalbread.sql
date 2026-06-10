-- Import this file inside the existing database selected in phpMyAdmin.
-- Do not create or switch database here because shared hosting users cannot create arbitrary database names.
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `promotions`;
DROP TABLE IF EXISTS `product_reviews`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `live_chat_threads`;
DROP TABLE IF EXISTS `live_chat_messages`;
DROP TABLE IF EXISTS `favorites`;
DROP TABLE IF EXISTS `customer_favorites`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS=1;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th5 28, 2026 lúc 03:57 PM
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
-- Cơ sở dữ liệu: `royalbread_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$pgi/ftwI351bNGeC19sJvO/OfzPzgBHmOWknlfgJpT7oYqTN.fh4G', 'RoyalBread Admin', '2026-05-19 04:16:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `sort_order`) VALUES
(1, 'Bánh Mì Chảo', 'banh-mi-chao', 1),
(2, 'Topping', 'topping', 2),
(3, 'Combo', 'combo', 3),
(4, 'Trà nhiệt đới', 'tra-nhiet-doi', 4),
(5, 'Bánh Mì Kẹp', 'banh-mi-kep', 5),
(6, 'Ăn Vặt', 'an-vat', 6),
(7, 'Cafe', 'cafe', 7),
(8, 'Đồ uống truyền thống', 'do-uong-truyen-thong', 8);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `sender` enum('customer','admin') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `points` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `created_at`, `points`) VALUES
(5, 'ngoviet213', '$2y$10$FJ8PuxUEv.PAEsxq7lPvdufqfzjEBTg73inJhUDZHJIvjnERXo942', 'Ngô Việt', NULL, NULL, '2026-05-19 07:51:34', 0),
(9, 'report_user', '$2y$10$qS468LiikK0nDem2ItfVRu/QeoOEwFnQsHbpIF3CaZaaEwnATSccS', 'Tran Minh Hoa', 'report@example.com', '0900001234', '2026-05-19 12:38:27', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_favorites`
--

CREATE TABLE `customer_favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `favorites`
--

CREATE TABLE `favorites` (
  `customer_id` int(10) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `live_chat_messages`
--

CREATE TABLE `live_chat_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `thread_id` int(10) UNSIGNED NOT NULL,
  `sender_type` varchar(20) NOT NULL,
  `sender_id` int(10) UNSIGNED DEFAULT NULL,
  `sender_name` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `live_chat_threads`
--

CREATE TABLE `live_chat_threads` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `live_chat_threads`
--

INSERT INTO `live_chat_threads` (`id`, `customer_id`, `status`, `last_message_at`, `created_at`, `updated_at`) VALUES
(1, 9, 'open', '2026-05-28 13:04:25', '2026-05-28 13:04:25', '2026-05-28 13:04:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL DEFAULT 0,
  `image_url` text DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `menu_items`
--

INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `image_url`, `is_featured`, `is_available`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Chảo truyền thống', 'Pate, xúc xích, trứng, thịt hun khói, ruốc, hành, rau dưa.', 35000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludu85qkg3rzf2@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 1, '2026-05-19 08:21:06', '2026-05-19 10:32:44'),
(2, 1, 'Chảo xá xíu', 'Pate, trứng, thịt xá xíu, xúc xích, ruốc, hành, rau dưa.', 35000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwfv1gqmimsbf9@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(3, 1, 'Chảo thịt nướng', 'Pate, trứng, thịt nướng, xúc xích, rau dưa.', 40000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludubqd67rxb4d@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(4, 1, 'Chảo lườn ngỗng', 'Pate, trứng, lườn ngỗng, xúc xích, rau dưa.', 40000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luek3p4bh5ht3c@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(5, 1, 'Chảo thịt quay', 'Pate, trứng, thịt quay, xúc xích, rau dưa.', 45000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwwxdp8axejf77@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(6, 1, 'Chảo đặc biệt', 'Pate, trứng, thịt hun khói, thịt xá xíu, xúc xích, ruốc, hành, rau dưa.', 45000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludu3yvr7fgx28@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(7, 1, 'Chảo bò sốt tiêu', 'Pate, bánh mì, trứng, xúc xích, bò sốt tiêu, rau dưa.', 50000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luehjjcgmx3lb1@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 7, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(8, 1, 'Chảo bít tết', 'Bò áp chảo, trứng, pate, xúc xích và bánh mì.', 75000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk5cjc1zuv89a@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 8, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(9, 1, 'Super chảo', 'Pate, trứng, xúc xích, thịt xá xíu, thịt hun khói, lườn ngỗng, bò sốt tiêu, ruốc, hành, rau dưa.', 100000, 'assets/images/uploads/img_6a0c5049f34411.25057564.png', 1, 1, 9, '2026-05-19 08:21:06', '2026-05-19 11:58:02'),
(10, 2, 'Bánh mì', 'Ổ bánh mì dùng kèm chảo hoặc topping.', 5000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebddaatudt40@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(11, 2, 'Xúc xích', 'Thêm xúc xích vào phần ăn.', 5000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luek9iwcho9d0c@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(12, 2, 'Trứng', 'Trứng ốp la thêm.', 5000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludu85qkg3rzf2@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(13, 2, 'Pate', 'Pate thêm cho phần bánh mì chảo.', 5000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludtalacv8k18d@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(14, 2, 'Thịt xá xíu', 'Topping thịt xá xíu thêm.', 10000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwfv1gqmimsbf9@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(15, 2, 'Thịt nướng', 'Topping thịt nướng thêm.', 15000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludubqd67rxb4d@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(16, 2, 'Thịt quay', 'Topping thịt quay thêm.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwwwqub568rdeb@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 7, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(17, 2, 'Lườn ngỗng', 'Topping lườn ngỗng thêm.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luel6iq293bz50@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 8, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(18, 2, 'Thịt bò', 'Topping bò thêm cho phần chảo.', 30000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mhhu4u7ps0sj31@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 9, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(19, 3, 'Chảo truyền thống/Xá xíu + Trà sữa/Trà thái', 'Combo chảo và đồ uống.', 50000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk56pwawk5f7f@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(20, 3, 'BM pate xúc xích/pate trứng/trứng xúc xích + Trà chanh/Đậu nành/Coca', 'Combo bánh mì kèm đồ uống.', 27000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk6idmpi2v8aa@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(21, 3, 'Chảo bít tết + Trà chanh/Coca/Trà sữa', 'Combo chảo bít tết và đồ uống.', 75000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk5cjc1zuv89a@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(22, 3, '2 chảo đặc biệt + 2 Trà chanh/Coca/Đậu nành', 'Combo cho 2 người.', 99000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk52mbmfshyb5@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(23, 3, '3 chảo đặc biệt + 3 Trà chanh/Coca/Đậu nành', 'Combo cho nhóm khách.', 150000, 'https://mms.img.susercontent.com/vn-11134517-820l4-mi07gvmk4rghe1@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(24, 3, 'Pizza + Trà sữa', 'Combo pizza kèm trà sữa.', 75000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luekp3jo38rjee@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(25, 3, 'Mỳ Ý sốt Bò Băm + Trà chanh/Coca/Trà thái', 'Combo mỳ ý và đồ uống.', 50000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lv4gsjzfjmk92c@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 7, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(26, 4, 'Trà chanh', 'Ly trà chanh mát lạnh.', 10000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lueb45xtpqfz2e@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(27, 4, 'Trà chanh size 1 lít', 'Phiên bản size lớn 1 lít.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luel0hwfjorj46@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(28, 4, 'Trà chanh nha đam', 'Trà chanh nha đam mát lạnh.', 15000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luei1v7b0lcv68@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(29, 4, 'Trà chanh hạt chia', 'Trà chanh hạt chia thanh mát.', 15000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luej3zn4m7td43@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(30, 4, 'Trà mãng cầu', 'Trà mãng cầu trái cây.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luej58ed4dnj05@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(31, 4, 'Trà đào / Trà hoa quả', 'Trà đào hoặc trà hoa quả mát lạnh.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luekxkvz7wup60@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(32, 4, 'Trà dâu tây', 'Trà dâu tây mát lạnh.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luekvwwh03kv9e@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 7, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(33, 4, 'Trà chanh leo', 'Trà chanh leo vị chua thanh.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luej88kau2a97b@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 8, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(34, 4, 'Trà đào chanh leo', 'Trà đào kết hợp chanh leo.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luekypshsjkv07@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 9, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(35, 4, 'Chanh leo', 'Nước chanh leo giải khát.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luej6rtw065bf5@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 10, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(36, 5, 'BM Super nhân', 'Bánh mì nhân đầy đặn.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludtbecybokf21@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(37, 5, 'Bánh mì thịt quay', 'Bánh mì kẹp thịt quay.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwwwqub568rdeb@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(38, 5, 'BM lườn ngỗng', 'Bánh mì kẹp lườn ngỗng.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luel6iq293bz50@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(39, 5, 'BM trứng xá xíu', 'Bánh mì trứng xá xíu.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebk46be1apac@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(40, 5, 'BM pate xá xíu', 'Bánh mì pate xá xíu.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebif84kz6nde@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(41, 5, 'BM pate thịt nướng', 'Bánh mì pate thịt nướng.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludtz87uy9zlb7@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(42, 5, 'BM trứng thịt nướng', 'Bánh mì trứng thịt nướng.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludtx4ivpyc14b@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 7, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(43, 5, 'BM thịt nướng xúc xích', 'Bánh mì thịt nướng xúc xích.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luec3qwz5s7la6@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 8, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(44, 5, 'BM xá xíu', 'Bánh mì xá xíu.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwfv7y9srra18a@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 9, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(45, 5, 'BM thịt xiên đặc biệt', 'Bánh mì thịt xiên đặc biệt.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebgmywm3hb50@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 10, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(46, 5, 'BM Hội An', 'Bánh mì kiểu Hội An.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lwfv6s6keiu189@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 11, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(47, 5, 'BM thịt hun khói', 'Bánh mì thịt hun khói.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lzydklb7zgrl90@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 12, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(48, 5, 'BM pate ruốc hành', 'Bánh mì pate ruốc hành.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludtalacv8k18d@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 13, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(49, 5, 'BM 2 trứng lá ngải', 'Bánh mì 2 trứng lá ngải cứu.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lxaz4f9cvysbd5@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 14, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(50, 5, 'BM pate trứng', 'Bánh mì pate trứng.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebddaatudt40@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 15, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(51, 5, 'BM pate xúc xích', 'Bánh mì pate xúc xích.', 15000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebf8vefksx51@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 16, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(52, 6, 'Mỳ Ý sốt Bò Băm', 'Mỳ ý sốt bò băm đậm vị.', 45000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lv4gsjzfjmk92c@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(53, 6, 'Mẹt thịt xiên (5 cái)', 'Mẹt thịt xiên nướng 5 cái.', 50000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebgmywm3hb50@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(54, 6, 'Mẹt nem chua rán', 'Mẹt nem chua rán ăn vặt.', 50000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luek7mxny3vj32@resize_ss750x750!@crop_w750_h750_cT', 1, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(55, 6, 'Khoai tây cong', 'Khoai tây cong giòn.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luekjn65f7khc2@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(56, 6, 'Khoai lang kén', 'Khoai lang kén chiên.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luekng3v16a9c7@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(57, 6, 'Xúc xích', 'Xúc xích ăn vặt.', 10000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luek9iwcho9d0c@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(58, 7, 'Đen đá', 'Cafe đen đá.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luel7x7qd53l43@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(59, 7, 'Nâu đá', 'Cafe nâu đá.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luel7x7qd53l43@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(60, 7, 'Bạc xỉu', 'Cafe bạc xỉu.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luel96ei8jypfc@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(61, 8, 'Nước ép', 'Nước ép trái cây.', 30000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luehh7j1akgve4@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 1, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(62, 8, 'Trà thái', 'Trà thái truyền thống.', 15000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lvcy0scdg0h08e@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 2, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(63, 8, 'Trà sữa truyền thống', 'Trà sữa truyền thống.', 20000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lueb93eg0rhbbb@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 3, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(64, 8, 'Trà sữa nướng', 'Trà sữa nướng.', 25000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lueb93eg0rhbbb@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 4, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(65, 8, 'Sữa đậu nành', 'Sữa đậu nành.', 10000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-lueb5ghzkvfj00@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 5, '2026-05-19 08:21:06', '2026-05-19 08:21:06'),
(66, 8, 'CocaCola', 'Nước ngọt CocaCola.', 10000, 'https://mms.img.susercontent.com/vn-11134517-7r98o-luehyfrpn95rd8@resize_ss750x750!@crop_w750_h750_cT', 0, 1, 6, '2026-05-19 08:21:06', '2026-05-19 08:21:06');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `contact_time` varchar(150) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id`, `customer_name`, `phone`, `contact_time`, `subject`, `message`, `status`, `created_at`) VALUES
(11, 'Tran Minh Hoa', '0900009999', 'Buoi toi', 'order', 'Can dat combo cho nhom 4 nguoi vao cuoi tuan.', 'new', '2026-05-19 12:38:27'),
(12, 'Le Thu Ha', '0900007777', 'Buoi sang', 'support', 'Muon hoi ve khung gio giao hang va phi ship.', 'new', '2026-05-19 12:38:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `note` text DEFAULT NULL,
  `total_amount` int(11) NOT NULL DEFAULT 0,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cod',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_amount` int(11) NOT NULL DEFAULT 0,
  `payment_status` varchar(30) NOT NULL DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `customer_name`, `customer_email`, `phone`, `address`, `note`, `total_amount`, `payment_method`, `status`, `created_at`, `updated_at`, `discount_amount`, `payment_status`) VALUES
(18, 9, 'Tran Minh Hoa', NULL, '0900001234', '12 Ly Thuong Kiet, Hai Duong', 'Giao gio trua. Khoang cach: 2.5 km | Phi ship: 15.000d', 109000, 'cod', 'completed', '2026-05-19 12:38:27', '2026-05-19 16:02:25', 0, 'unpaid'),
(19, 9, 'Tran Minh Hoa', NULL, '0900005678', '28 Da Tuong, Hai Duong', 'Giao sau 18h. Khoang cach: 1.2 km | Phi ship: 7.200d', 122200, 'bank_transfer', 'completed', '2026-05-19 12:38:27', '2026-05-19 12:38:27', 0, 'unpaid'),
(20, 9, 'Tran Minh Hoa', NULL, '0900003333', 'Ngo 5 Thanh Nien, Hai Duong', 'Khong hanh. Khoang cach: 3.8 km | Phi ship: 22.800d', 147800, 'cod', 'cancelled', '2026-05-19 12:38:27', '2026-05-19 16:02:21', 0, 'unpaid'),
(21, 5, 'Ngô Việt', NULL, '0394348389', 'Trường Đại học Thành Đông, Vũ Công Đán, Tứ Minh', 'Khoảng cách giao hàng: 5,26 km | Phí ship: 31.560đ | Cách tính: tự động theo địa chỉ', 130560, 'cod', 'completed', '2026-05-21 01:26:45', '2026-05-21 01:53:26', 0, 'unpaid'),
(22, 5, 'Ngô Việt', NULL, '0394348389', 'Số 3, Vũ Công Đán', 'Khoảng cách giao hàng: 5,25 km | Phí ship: 31.500đ | Cách tính: tự động theo địa chỉ', 241500, 'cod', 'completed', '2026-05-21 03:18:20', '2026-05-21 07:44:19', 0, 'unpaid');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED DEFAULT NULL,
  `menu_item_name` varchar(255) NOT NULL,
  `menu_item_image_url` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `menu_item_id`, `menu_item_name`, `menu_item_image_url`, `quantity`, `price`, `created_at`) VALUES
(35, 18, 1, 'Chao truyen thong', 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludu85qkg3rzf2@resize_ss750x750!@crop_w750_h750_cT', 2, 47000, '2026-05-19 12:38:27'),
(36, 18, 10, 'Banh mi', 'https://mms.img.susercontent.com/vn-11134517-7r98o-luebddaatudt40@resize_ss750x750!@crop_w750_h750_cT', 1, 5000, '2026-05-19 12:38:27'),
(37, 19, 8, 'Chao bit tet', 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk5cjc1zuv89a@resize_ss750x750!@crop_w750_h750_cT', 1, 75000, '2026-05-19 12:38:27'),
(38, 19, 28, 'Tra chanh', 'https://mms.img.susercontent.com/vn-11134517-7r98o-lueb45xtpqfz2e@resize_ss750x750!@crop_w750_h750_cT', 2, 10000, '2026-05-19 12:38:27'),
(39, 20, 19, 'Combo chao va tra sua', 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk56pwawk5f7f@resize_ss750x750!@crop_w750_h750_cT', 2, 50000, '2026-05-19 12:38:27'),
(40, 20, 26, 'Tra chanh', 'https://mms.img.susercontent.com/vn-11134517-7r98o-lueb45xtpqfz2e@resize_ss750x750!@crop_w750_h750_cT', 1, 10000, '2026-05-19 12:38:27'),
(41, 21, 22, '2 chảo đặc biệt + 2 Trà chanh/Coca/Đậu nành', 'https://mms.img.susercontent.com/vn-11134517-820l4-mhk52mbmfshyb5@resize_ss750x750!@crop_w750_h750_cT', 1, 99000, '2026-05-21 01:26:45'),
(42, 22, 9, 'Super chảo', 'assets/images/uploads/img_6a0c5049f34411.25057564.png', 1, 100000, '2026-05-21 03:18:20'),
(43, 22, 1, 'Chảo truyền thống', 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludu85qkg3rzf2@resize_ss750x750!@crop_w750_h750_cT', 2, 35000, '2026-05-21 03:18:20'),
(44, 22, 3, 'Chảo thịt nướng', 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludubqd67rxb4d@resize_ss750x750!@crop_w750_h750_cT', 1, 40000, '2026-05-21 03:18:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `review_title` varchar(150) NOT NULL DEFAULT '',
  `review_comment` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotions`
--

CREATE TABLE `promotions` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_tier` varchar(50) NOT NULL DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `menu_item_id` int(10) UNSIGNED NOT NULL,
  `rating` int(11) NOT NULL DEFAULT 5,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('about_text', 'RoyalBread tập trung vào bánh mì chảo, combo sáng và các món ăn nhanh đậm vị. Website được dựng theo mô hình MVC bằng PHP và MySQL để dễ quản trị, dễ thay đổi thực đơn và tiếp nhận lời nhắn của khách hàng.'),
('address', '28 Dã Tượng - TP Hải Dương (Sau nhà thi đấu Hải Dương)'),
('cart_recommend_item_1', '9'),
('cart_recommend_item_2', '22'),
('cart_recommend_item_3', '26'),
('cart_recommend_item_4', '36'),
('contact_hero_image', 'assets/images/uploads/img_6a0c161ce812d9.12417606.jpg'),
('home_banner_slide_1', 'assets/images/storefront-bg.jpg'),
('home_banner_slide_2', 'assets/images/home-hero-banner-2.png'),
('home_banner_slide_3', 'assets/images/home-hero-banner-3.png'),
('home_category_card_bread_image', ''),
('home_category_card_drink_image', 'assets/images/uploads/img_6a0c3b6782c2b4.99521605.jpg'),
('home_category_card_pan_image', ''),
('home_drink_item_1', ''),
('home_drink_item_2', ''),
('home_drink_item_3', '60'),
('home_drink_item_4', '26'),
('home_drink_item_5', '30'),
('home_drink_item_6', '61'),
('home_drink_item_7', '66'),
('home_signature_image', 'https://mms.img.susercontent.com/vn-11134517-7r98o-ludu85qkg3rzf2@resize_ss750x750!@crop_w750_h750_cT'),
('home_spotlight_item_1', '7'),
('home_spotlight_item_2', '24'),
('home_spotlight_item_3', '36'),
('home_spotlight_item_4', '25'),
('hotline', '0879866636'),
('map_lat', '20.9325516'),
('map_lon', '106.3295816'),
('map_query', '28 Dã Tượng, P. Lê Thanh Nghị, TP Hải Dương, Hải Dương, Việt Nam'),
('opening_hours', '07:00 - 22:00 mỗi ngày'),
('shopeefood_url', 'https://shopeefood.vn/hai-duong/banh-mi-hoang-gia-royalbread'),
('site_name', 'Bánh mì chảo Hoàng Gia - RoyalBread'),
('tagline', 'Bánh mì chảo nóng giòn, topping đầy đặn, lên món nhanh cho khách tại Hải Dương.');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_messages_session` (`session_id`);

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Chỉ mục cho bảng `customer_favorites`
--
ALTER TABLE `customer_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_customer_favorite` (`customer_id`,`menu_item_id`),
  ADD KEY `idx_customer_favorites_customer_created` (`customer_id`,`created_at`),
  ADD KEY `fk_customer_favorites_item` (`menu_item_id`);

--
-- Chỉ mục cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`customer_id`,`menu_item_id`),
  ADD KEY `fk_favorites_menu_item` (`menu_item_id`);

--
-- Chỉ mục cho bảng `live_chat_messages`
--
ALTER TABLE `live_chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_live_chat_messages_thread_created` (`thread_id`,`created_at`),
  ADD KEY `idx_live_chat_messages_thread_read` (`thread_id`,`is_read`);

--
-- Chỉ mục cho bảng `live_chat_threads`
--
ALTER TABLE `live_chat_threads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_live_chat_threads_status_updated` (`status`,`updated_at`),
  ADD KEY `fk_live_chat_threads_customer` (`customer_id`);

--
-- Chỉ mục cho bảng `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_menu_category` (`category_id`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_customer_id` (`customer_id`),
  ADD KEY `idx_orders_status_created` (`status`,`created_at`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_menu_item_id` (`menu_item_id`);

--
-- Chỉ mục cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_product_review_customer_item` (`customer_id`,`menu_item_id`),
  ADD KEY `idx_product_reviews_item_status` (`menu_item_id`,`status`),
  ADD KEY `fk_product_reviews_order` (`order_id`);

--
-- Chỉ mục cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reviews_customer` (`customer_id`),
  ADD KEY `fk_reviews_menu_item` (`menu_item_id`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `customer_favorites`
--
ALTER TABLE `customer_favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `live_chat_messages`
--
ALTER TABLE `live_chat_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `live_chat_threads`
--
ALTER TABLE `live_chat_threads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT cho bảng `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `customer_favorites`
--
ALTER TABLE `customer_favorites`
  ADD CONSTRAINT `fk_customer_favorites_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_customer_favorites_item` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_menu_item` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `live_chat_messages`
--
ALTER TABLE `live_chat_messages`
  ADD CONSTRAINT `fk_live_chat_messages_thread` FOREIGN KEY (`thread_id`) REFERENCES `live_chat_threads` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `live_chat_threads`
--
ALTER TABLE `live_chat_threads`
  ADD CONSTRAINT `fk_live_chat_threads_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `fk_product_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_reviews_item` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_product_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reviews_menu_item` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
