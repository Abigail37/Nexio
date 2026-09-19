-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2026 at 02:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nexio`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Bags', 'This is the bag category!!!', 1, '2026-08-16 14:11:49', '2026-08-20 22:02:54'),
(2, 'Shoes', NULL, 1, '2026-08-16 15:30:41', '2026-08-20 12:02:52'),
(3, 'Soup Ingredients', NULL, 1, '2026-08-17 16:50:02', '2026-08-17 16:50:02'),
(6, 'Bola', NULL, 0, '2026-08-18 09:13:51', '2026-08-20 12:27:16'),
(9, 'Jemima', NULL, 1, '2026-08-24 10:14:46', '2026-08-24 10:14:54');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_otps`
--

CREATE TABLE `email_otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `otp_code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_otps`
--

INSERT INTO `email_otps` (`id`, `user_id`, `email`, `otp_code`, `expires_at`, `verified_at`, `attempts`, `created_at`) VALUES
(8, 24, 'estherjemima11@gmail.com', '$2y$10$UzABCkxseJOUEs.CEBoLW.P4EqoH2HKCWjVt.zBGMhqaObzDT8xIe', '2026-08-20 12:24:41', '2026-08-20 11:24:26', 1, '2026-08-20 10:19:41'),
(12, 28, 'abigailogunmola37@gmail.com', '$2y$10$.wZrVmxMtOQL6K/QAH4iueIwycHSW5ifLxtYTIjIIz7WOh8lS3nai', '2026-09-18 13:35:52', '2026-09-18 12:31:10', 1, '2026-09-18 11:30:52');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `transaction_type` enum('stock_in','sale','adjustment','return','damaged','expired') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(10) UNSIGNED NOT NULL,
  `new_stock` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `product_id`, `user_id`, `transaction_type`, `quantity`, `previous_stock`, `new_stock`, `reason`, `created_at`) VALUES
(1, 1, 7, 'sale', 1, 10, 9, 'Sale - Order #1', '2026-08-16 14:21:45'),
(2, 1, 7, 'sale', 3, 9, 6, 'Sale - Order #2', '2026-08-16 14:25:36'),
(3, 1, 7, 'stock_in', 4, 6, 10, 'New shipment recieved from supllier', '2026-08-16 15:14:08'),
(4, 1, 7, 'adjustment', 1, 10, 9, 'Physical stock count showed 9', '2026-08-16 15:17:47'),
(5, 2, 18, 'sale', 1, 10, 9, 'Sale - Order #3', '2026-08-16 23:28:21'),
(6, 2, 18, 'sale', 2, 9, 7, 'Sale - Order #5', '2026-08-17 09:30:00'),
(7, 1, 7, 'sale', 3, 9, 6, 'Sale - Order #7', '2026-08-17 13:33:53'),
(8, 4, 7, 'sale', 20, 20, 0, 'Sale - Order #8', '2026-08-17 22:18:23'),
(9, 1, 7, 'sale', 2, 6, 4, 'Sale - Order #9', '2026-08-18 11:25:01'),
(10, 4, 7, 'adjustment', 2, 0, 2, 'Addmore', '2026-08-18 18:21:11'),
(11, 4, 7, 'sale', 2, 2, 0, 'Sale - Order #10', '2026-08-18 18:22:56'),
(12, 3, 7, 'sale', 1, 50, 49, 'Sale - Order #11', '2026-08-21 20:50:41'),
(13, 2, 7, 'sale', 1, 7, 6, 'Sale - Order #12', '2026-08-26 11:23:17'),
(14, 4, 7, 'sale', 8, 100, 92, 'Sale - Order #13', '2026-09-16 12:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shipping_name` varchar(150) NOT NULL,
  `shipping_email` varchar(150) NOT NULL,
  `shipping_phone` varchar(30) NOT NULL,
  `shipping_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `status`, `total_amount`, `shipping_name`, `shipping_email`, `shipping_phone`, `shipping_address`, `notes`, `created_at`, `updated_at`) VALUES
(1, 7, 'processing', 1200000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'No 1, Behind BOI Alagbaka, Atibiti Faaye Layout', NULL, '2026-08-16 14:20:56', '2026-08-16 14:21:45'),
(2, 7, 'processing', 3600000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'No 1, Behind BOI Alagbaka, Atibiti Faaye Layout', NULL, '2026-08-16 14:25:00', '2026-08-16 14:25:36'),
(3, 18, 'processing', 12000.00, 'Cashier', 'abigailogunmola8@gmail.com', '09132041854', 'Opp First Bank, Off BOI Lane, Alagbaka', NULL, '2026-08-16 23:27:51', '2026-08-16 23:28:21'),
(4, 18, 'pending', 24000.00, 'Cashier', 'abigailogunmola8@gmail.com', '09132041854', 'Alagbon, Owode', NULL, '2026-08-17 09:26:38', '2026-08-17 09:26:38'),
(5, 18, 'processing', 24000.00, 'Cashier', 'abigailogunmola8@gmail.com', '09132041854', 'Opposite First Bank, Off BOI Lane, Alagbaka.', NULL, '2026-08-17 09:29:30', '2026-08-17 09:30:00'),
(6, 7, 'pending', 3600000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '08034652587', 'Sita,Akure', NULL, '2026-08-17 13:31:39', '2026-08-17 13:31:39'),
(7, 7, 'processing', 3600000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '08034652587', 'Sita Akure.', NULL, '2026-08-17 13:32:22', '2026-08-17 13:33:53'),
(8, 7, 'processing', 1000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'No 1, Behind BOI Alagbaka, Atibiti Faaye Layout', NULL, '2026-08-17 22:18:04', '2026-08-17 22:18:23'),
(9, 7, 'processing', 2400000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'No 1, Behind BOI Alagbaka, Atibiti Faaye Layout', NULL, '2026-08-18 11:21:56', '2026-08-21 21:21:57'),
(10, 7, 'completed', 100.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'No 1, Behind BOI Alagbaka, Atibiti Faaye Layout', NULL, '2026-08-18 18:21:34', '2026-08-21 20:51:41'),
(11, 7, 'processing', 7500.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'Alagbon, Owode', NULL, '2026-08-21 20:49:13', '2026-08-21 21:23:04'),
(12, 7, 'processing', 12000.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'Alagbon, Owode', NULL, '2026-08-26 11:21:42', '2026-08-26 11:23:17'),
(13, 7, 'processing', 400.00, 'Abigail Ogunmola', 'abigailogunmola37@gmail.com', '09132041854', 'Opp First Bank, Off BOI Lane, Alagbaka', NULL, '2026-09-16 12:15:05', '2026-09-16 12:30:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `price_at_purchase` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`, `subtotal`, `created_at`) VALUES
(1, 1, 1, 1, 1200000.00, 1200000.00, '2026-08-16 14:20:56'),
(2, 2, 1, 3, 1200000.00, 3600000.00, '2026-08-16 14:25:00'),
(3, 3, 2, 1, 12000.00, 12000.00, '2026-08-16 23:27:51'),
(4, 4, 2, 2, 12000.00, 24000.00, '2026-08-17 09:26:38'),
(5, 5, 2, 2, 12000.00, 24000.00, '2026-08-17 09:29:30'),
(6, 6, 1, 3, 1200000.00, 3600000.00, '2026-08-17 13:31:39'),
(7, 7, 1, 3, 1200000.00, 3600000.00, '2026-08-17 13:32:22'),
(8, 8, 4, 20, 50.00, 1000.00, '2026-08-17 22:18:04'),
(9, 9, 1, 2, 1200000.00, 2400000.00, '2026-08-18 11:21:56'),
(10, 10, 4, 2, 50.00, 100.00, '2026-08-18 18:21:34'),
(11, 11, 3, 1, 7500.00, 7500.00, '2026-08-21 20:49:13'),
(12, 12, 2, 1, 12000.00, 12000.00, '2026-08-26 11:21:42'),
(13, 13, 4, 8, 50.00, 400.00, '2026-09-16 12:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_setup_tokens`
--

CREATE TABLE `password_setup_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_setup_tokens`
--

INSERT INTO `password_setup_tokens` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES
(8, 18, '4c3999d9f62b5f8d0dc6128c3b75e2483ccc36d3e098ada0303b27259db824e4', '2026-08-15 15:33:19', '2026-08-15 14:04:37', '2026-08-15 13:03:19');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `payment_reference` varchar(150) NOT NULL,
  `transaction_reference` varchar(150) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'monnify',
  `status` enum('pending','successful','failed','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `payment_reference`, `transaction_reference`, `amount`, `payment_method`, `status`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'ORD_1_6a81c755c605e', 'ORD_1_6a81c755c605e', 1200000.00, 'paystack', 'successful', '2026-08-16 16:21:41', '2026-08-16 14:21:11', '2026-08-16 14:21:45'),
(2, 2, 'ORD_2_6a81c846b05c3', 'ORD_2_6a81c846b05c3', 3600000.00, 'paystack', 'successful', '2026-08-16 16:25:32', '2026-08-16 14:25:12', '2026-08-16 14:25:36'),
(3, 3, 'ORD_3_6a82477d1386a', 'ORD_3_6a82477d1386a', 12000.00, 'paystack', 'successful', '2026-08-17 01:28:18', '2026-08-16 23:27:58', '2026-08-16 23:28:21'),
(4, 4, 'ORD_4_6a82d3dc697da', 'ORD_4_6a82d3dc697da', 24000.00, 'paystack', 'pending', NULL, '2026-08-17 09:26:59', '2026-08-17 09:26:59'),
(5, 5, 'ORD_5_6a82d47d347fe', 'ORD_5_6a82d47d347fe', 24000.00, 'paystack', 'successful', '2026-08-17 11:29:56', '2026-08-17 09:29:35', '2026-08-17 09:30:00'),
(6, 7, 'ORD_7_6a830d6d63fa1', 'ORD_7_6a830d6d63fa1', 3600000.00, 'paystack', 'pending', NULL, '2026-08-17 13:32:30', '2026-08-17 13:32:30'),
(7, 7, 'ORD_7_6a830d6f52c4b', 'ORD_7_6a830d6f52c4b', 3600000.00, 'paystack', 'successful', '2026-08-17 15:32:54', '2026-08-17 13:32:32', '2026-08-17 13:33:53'),
(8, 8, 'ORD_8_6a8388a13bea0', 'ORD_8_6a8388a13bea0', 1000.00, 'paystack', 'successful', '2026-08-18 00:18:20', '2026-08-17 22:18:11', '2026-08-17 22:18:23'),
(9, 9, 'ORD_9_6a84405b90fa5', 'ORD_9_6a84405b90fa5', 2400000.00, 'paystack', 'successful', '2026-08-18 13:24:57', '2026-08-18 11:22:08', '2026-08-18 11:25:01'),
(10, 10, 'ORD_10_6a84a2b15bb54', 'ORD_10_6a84a2b15bb54', 100.00, 'paystack', 'successful', '2026-08-18 20:22:51', '2026-08-18 18:21:39', '2026-08-18 18:22:56'),
(11, 11, 'ORD_11_6a88b9cc44268', 'ORD_11_6a88b9cc44268', 7500.00, 'paystack', 'successful', '2026-08-21 22:49:59', '2026-08-21 20:49:18', '2026-08-21 20:50:41'),
(12, 12, 'ORD_12_6a8ecc50ad014', 'ORD_12_6a8ecc50ad014', 12000.00, 'paystack', 'pending', NULL, '2026-08-26 11:22:00', '2026-08-26 11:22:00'),
(13, 12, 'ORD_12_6a8ecc59058f0', 'ORD_12_6a8ecc59058f0', 12000.00, 'paystack', 'successful', '2026-08-26 13:23:05', '2026-08-26 11:22:03', '2026-08-26 11:23:17'),
(14, 13, 'ORD_13_6aaa8a2f9a8a5', 'ORD_13_6aaa8a2f9a8a5', 400.00, 'paystack', 'successful', '2026-09-16 14:23:22', '2026-09-16 12:23:13', '2026-09-16 12:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `low_stock_threshold` int(10) UNSIGNED NOT NULL DEFAULT 5,
  `category_id` int(10) UNSIGNED NOT NULL,
  `supplier_id` int(10) UNSIGNED DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock_quantity`, `low_stock_threshold`, `category_id`, `supplier_id`, `image_url`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Hermes', 'Latest hermes bag', 1200000.00, 4, 1, 1, NULL, NULL, 1, 7, '2026-08-16 14:17:23', '2026-08-24 10:13:45'),
(2, 'Boyfriend Shoe', 'latest shoe in town', 12000.00, 6, 0, 2, 1, NULL, 1, 7, '2026-08-16 15:32:02', '2026-08-26 11:23:17'),
(3, 'Peppersoup Ingredient', 'All in one peppersoup ingredient', 7500.00, 49, 5, 3, NULL, NULL, 1, 7, '2026-08-17 17:10:59', '2026-08-21 20:50:41'),
(4, 'Cashier', 'uhbjnk m,', 50.00, 92, 5, 1, NULL, NULL, 1, 7, '2026-08-17 17:12:02', '2026-09-16 12:23:26');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'store_name', 'Sales Management System', '2026-08-21 23:14:42'),
(2, 'store_email', '', '2026-08-21 23:14:07'),
(3, 'store_phone', '', '2026-08-21 23:14:07'),
(4, 'store_address', '', '2026-08-21 23:14:07');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `user_id`, `name`, `contact_email`, `contact_phone`, `address`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 7, 'Oyin Lola', 'abigailogunmola37@gmail.com', '09132041854', 'Alagbon, Owode, Oyo town', 0, '2026-08-16 15:22:07', '2026-08-21 23:18:17'),
(2, 7, 'Abigail Ogunmola', 'abigailogunmola1@gmail.com', '09132041854', 'No 1, Behind BOI Alagbaka, Atibiti Faaye Layout', 1, '2026-08-18 18:04:38', '2026-08-20 22:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `transaction_reference` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL,
  `status` enum('pending','successful','failed','refunded') NOT NULL DEFAULT 'pending',
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `order_id`, `transaction_reference`, `amount`, `payment_method`, `status`, `recorded_by`, `transaction_date`, `created_at`, `updated_at`) VALUES
(8, 10, 'TXN-20260821232220-6FDC6A', 100.00, 'bank_transfer', 'successful', 7, '2026-08-21 21:22:20', '2026-08-21 21:22:20', '2026-08-21 21:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','superuser','ceo','manager','sales_rep','cashier','supplier','delivery','accountant') NOT NULL DEFAULT 'customer',
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_protected` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `email_verified`, `is_active`, `is_protected`, `created_by`, `created_at`, `updated_at`) VALUES
(7, 'Abigail Ogunmola', 'abigailogunmola7@gmail.com', '$2y$10$mYGWIvsjNwB08DwvYSlqXepJNeJusmCXTKlmBZUEJ1HIMv.q6i2rq', 'superuser', 1, 1, 1, NULL, '2026-08-13 16:25:12', '2026-09-18 11:15:07'),
(18, 'Abigail Ogunmola', 'abigailogunmola344@gmail.com', '$2y$10$kqlW2zyjPPXNIBOLhdkSZO9rcHxifN3hRwgs1zPas7WgGSoIwVpje', 'ceo', 1, 1, 0, 7, '2026-08-15 13:03:19', '2026-08-21 23:17:48'),
(24, 'Jemima', 'estherjemima11@gmail.com', '$2y$10$h15LvGbczB7TO1kC4MAfpu/3zIqMvDs.WpFMPvWsKH36vk.MNtB5G', 'customer', 1, 1, 0, NULL, '2026-08-20 10:19:40', '2026-08-20 10:24:26'),
(28, 'Oyin Lola', 'abigailogunmola37@gmail.com', '$2y$10$UCx.Pwc0r.o1isd0yUYLNOyUp1RjgcN9qzYrTkWCFVj57bmcsbit.', 'customer', 1, 1, 0, NULL, '2026-09-18 11:30:52', '2026-09-18 11:31:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_cart` (`user_id`,`product_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_email_otps_user` (`user_id`),
  ADD KEY `idx_email_otps_email` (`email`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inventory_user` (`user_id`),
  ADD KEY `idx_inventory_product` (`product_id`),
  ADD KEY `idx_inventory_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `email` (`email`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_setup_user` (`user_id`),
  ADD KEY `idx_password_setup_token` (`token_hash`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `idx_payments_order` (`order_id`),
  ADD KEY `idx_payments_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_created_by` (`created_by`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_supplier` (`supplier_id`),
  ADD KEY `idx_products_stock` (`stock_quantity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_suppliers_user` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_reference` (`transaction_reference`),
  ADD KEY `idx_transactions_order_id` (`order_id`),
  ADD KEY `idx_transactions_recorded_by` (`recorded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_created_by` (`created_by`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_otps`
--
ALTER TABLE `email_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `email_otps`
--
ALTER TABLE `email_otps`
  ADD CONSTRAINT `fk_email_otps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inventory_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `password_setup_tokens`
--
ALTER TABLE `password_setup_tokens`
  ADD CONSTRAINT `fk_password_setup_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transactions_recorded_by` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
