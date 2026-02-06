-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 06:36 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jenny_cosmetics_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_sales_report` (IN `start_date` DATE, IN `end_date` DATE)   BEGIN
    SELECT 
        o.order_number,
        o.order_date,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name,
        o.total_amount,
        o.status,
        COUNT(oi.order_item_id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE DATE(o.order_date) BETWEEN start_date AND end_date
    GROUP BY o.order_id
    ORDER BY o.order_date DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_product_details` (IN `product_id_param` INT)   BEGIN
    SELECT 
        p.*,
        c.category_name,
        c.parent_category_id
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = product_id_param;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

CREATE TABLE `administrators` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('Super Admin','Content Manager','Order Manager') DEFAULT 'Content Manager',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `administrators`
--

INSERT INTO `administrators` (`admin_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `last_login`, `created_at`, `is_active`) VALUES
(1, 'admin', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'admin@jennyscosmetics.com', NULL, 'Super Admin', NULL, '2026-02-06 04:57:46', 1);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Product category hierarchy for navigation';

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `parent_category_id`, `image_url`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Cosmetics', 'Makeup and beauty products', NULL, NULL, 1, 1, '2026-02-06 04:57:46', '2026-02-06 04:57:46'),
(2, 'Imitation Jewelry', 'Fashion jewelry and accessories', NULL, NULL, 2, 1, '2026-02-06 04:57:46', '2026-02-06 04:57:46'),
(3, 'Skincare', 'Face and body care products', NULL, NULL, 3, 1, '2026-02-06 04:57:46', '2026-02-06 04:57:46'),
(4, 'Fragrances', 'Perfumes and body sprays', NULL, NULL, 4, 1, '2026-02-06 04:57:46', '2026-02-06 04:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled','Refunded') DEFAULT 'Pending',
  `subtotal` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Credit Card','PayPal','Bank Transfer','Cash on Delivery') DEFAULT 'Credit Card',
  `payment_status` enum('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
  `shipping_address` text NOT NULL,
  `billing_address` text DEFAULT NULL,
  `shipping_method` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Customer order records with financial details';

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `trg_update_user_stats` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.status != 'Delivered' AND NEW.status = 'Delivered' THEN
        UPDATE users 
        SET total_orders = total_orders + 1,
            total_spent = total_spent + NEW.total_amount
        WHERE user_id = NEW.user_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `trg_update_stock_after_order` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    UPDATE products 
    SET quantity_in_stock = quantity_in_stock - NEW.quantity,
        total_sold = total_sold + NEW.quantity
    WHERE product_id = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `quantity_in_stock` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 10,
  `max_stock_level` int(11) DEFAULT 100,
  `image_url` varchar(255) DEFAULT NULL,
  `additional_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_images`)),
  `weight_grams` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_sold` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Main product catalog for cosmetics and jewelry';

-- --------------------------------------------------------

--
-- Table structure for table `product_attributes`
--

CREATE TABLE `product_attributes` (
  `attribute_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  `display_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shopping_carts`
--

CREATE TABLE `shopping_carts` (
  `cart_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site_backups`
--

CREATE TABLE `site_backups` (
  `backup_id` int(11) NOT NULL,
  `backup_name` varchar(255) NOT NULL,
  `backup_type` enum('Full','Database Only','Files Only') DEFAULT 'Database Only',
  `file_path` varchar(500) DEFAULT NULL,
  `file_size_mb` decimal(8,2) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `category` varchar(50) DEFAULT 'General',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `updated_at`) VALUES
(1, 'site_name', 'Jenny\'s Cosmetics & Jewelry', 'string', 'General', 'Website name', '2026-02-06 04:57:46'),
(2, 'site_email', 'info@jennyscosmetics.com', 'string', 'General', 'Default contact email', '2026-02-06 04:57:46'),
(3, 'currency', 'USD', 'string', 'General', 'Default currency', '2026-02-06 04:57:46'),
(4, 'tax_rate', '8.5', 'string', 'Order', 'Sales tax rate in percentage', '2026-02-06 04:57:46'),
(5, 'shipping_cost', '5.99', 'string', 'Order', 'Default shipping cost', '2026-02-06 04:57:46'),
(6, 'min_order_amount', '25.00', 'string', 'Order', 'Minimum order amount', '2026-02-06 04:57:46'),
(7, 'enable_registration', '1', 'boolean', 'User', 'Enable new user registration', '2026-02-06 04:57:46'),
(8, 'maintenance_mode', '0', 'boolean', 'System', 'Put site in maintenance mode', '2026-02-06 04:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_work` varchar(20) DEFAULT NULL,
  `phone_cell` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address_street` varchar(255) NOT NULL,
  `address_city` varchar(100) NOT NULL,
  `address_state` varchar(50) DEFAULT NULL,
  `address_zip` varchar(20) DEFAULT NULL,
  `address_country` varchar(50) DEFAULT 'USA',
  `customer_category` enum('Regular','VIP','Wholesale','Friend_Family') DEFAULT 'Regular',
  `remarks` text DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `total_orders` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores customer information for the e-commerce platform';

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_daily_sales`
-- (See below for the actual view)
--
CREATE TABLE `vw_daily_sales` (
`sale_date` date
,`total_orders` bigint(21)
,`daily_revenue` decimal(32,2)
,`average_order_value` decimal(14,6)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_top_customers`
-- (See below for the actual view)
--
CREATE TABLE `vw_top_customers` (
`user_id` int(11)
,`customer_name` varchar(101)
,`email` varchar(100)
,`phone_cell` varchar(20)
,`total_orders` bigint(21)
,`total_spent` decimal(32,2)
,`last_order_date` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_top_selling_products`
-- (See below for the actual view)
--
CREATE TABLE `vw_top_selling_products` (
`product_id` int(11)
,`product_name` varchar(200)
,`sku` varchar(50)
,`category_name` varchar(100)
,`total_quantity_sold` decimal(32,0)
,`total_revenue` decimal(32,2)
,`total_orders` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_daily_sales`
--
DROP TABLE IF EXISTS `vw_daily_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_daily_sales`  AS SELECT cast(`orders`.`order_date` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `daily_revenue`, avg(`orders`.`total_amount`) AS `average_order_value` FROM `orders` WHERE `orders`.`status` in ('Delivered','Shipped') GROUP BY cast(`orders`.`order_date` as date) ORDER BY cast(`orders`.`order_date` as date) AS `DESCdesc` ASC  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_top_customers`
--
DROP TABLE IF EXISTS `vw_top_customers`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_top_customers`  AS SELECT `u`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `customer_name`, `u`.`email` AS `email`, `u`.`phone_cell` AS `phone_cell`, count(distinct `o`.`order_id`) AS `total_orders`, sum(`o`.`total_amount`) AS `total_spent`, max(`o`.`order_date`) AS `last_order_date` FROM (`users` `u` join `orders` `o` on(`u`.`user_id` = `o`.`user_id`)) WHERE `o`.`status` in ('Delivered','Shipped') GROUP BY `u`.`user_id`, `u`.`first_name`, `u`.`last_name`, `u`.`email`, `u`.`phone_cell` ORDER BY sum(`o`.`total_amount`) DESC LIMIT 0, 1010  ;

-- --------------------------------------------------------

--
-- Structure for view `vw_top_selling_products`
--
DROP TABLE IF EXISTS `vw_top_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_top_selling_products`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `p`.`sku` AS `sku`, `c`.`category_name` AS `category_name`, sum(`oi`.`quantity`) AS `total_quantity_sold`, sum(`oi`.`total_price`) AS `total_revenue`, count(distinct `o`.`order_id`) AS `total_orders` FROM (((`products` `p` join `order_items` `oi` on(`p`.`product_id` = `oi`.`product_id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`order_id`)) join `categories` `c` on(`p`.`category_id` = `c`.`category_id`)) WHERE `o`.`status` in ('Delivered','Shipped') GROUP BY `p`.`product_id`, `p`.`product_name`, `p`.`sku`, `c`.`category_name` ORDER BY sum(`oi`.`quantity`) DESC LIMIT 0, 1010  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_action` (`action_type`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_parent_category` (`parent_category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`order_date`),
  ADD KEY `idx_orders_composite` (`user_id`,`status`,`order_date`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_order_items_composite` (`order_id`,`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_price` (`unit_price`),
  ADD KEY `idx_stock` (`quantity_in_stock`),
  ADD KEY `idx_products_stock_price` (`quantity_in_stock`,`unit_price`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`product_name`,`description`,`short_description`);

--
-- Indexes for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`attribute_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_product_user` (`product_id`,`user_id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `shopping_carts`
--
ALTER TABLE `shopping_carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `site_backups`
--
ALTER TABLE `site_backups`
  ADD PRIMARY KEY (`backup_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone_cell`),
  ADD KEY `idx_users_name_email` (`first_name`,`last_name`,`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrators`
--
ALTER TABLE `administrators`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `attribute_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shopping_carts`
--
ALTER TABLE `shopping_carts`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `site_backups`
--
ALTER TABLE `site_backups`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `shopping_carts`
--
ALTER TABLE `shopping_carts`
  ADD CONSTRAINT `shopping_carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `site_backups`
--
ALTER TABLE `site_backups`
  ADD CONSTRAINT `site_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `administrators` (`admin_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
