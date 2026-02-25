-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 05:10 AM
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
(1, 'admin', '$2y$10$EzFEPM3E8leciWrEiESNO.37MUXQLiyvmrk88Zafiys7xnu.dZTom', 'System Administrator', 'admin@jennyscosmetics.com', NULL, 'Super Admin', '2026-02-19 18:11:27', '2026-02-06 18:27:28', 1);

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
(1, 'Cosmetics', 'Makeup and beauty products', NULL, NULL, 1, 1, '2026-02-06 18:27:28', '2026-02-06 18:27:28'),
(2, 'Imitation Jewelry', 'Fashion jewelry and accessories', NULL, NULL, 2, 1, '2026-02-06 18:27:28', '2026-02-06 18:27:28'),
(3, 'Skincare', 'Face and body care products', NULL, NULL, 3, 1, '2026-02-06 18:27:28', '2026-02-06 18:27:28'),
(4, 'Fragrances', 'Perfumes and body sprays', NULL, NULL, 4, 1, '2026-02-06 18:27:28', '2026-02-06 18:27:28');

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
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_number`, `user_id`, `order_date`, `status`, `subtotal`, `tax_amount`, `shipping_amount`, `total_amount`, `payment_method`, `payment_status`, `shipping_address`, `billing_address`, `shipping_method`, `tracking_number`, `notes`, `ip_address`, `user_agent`) VALUES
(1, 'ORD202602206195', 2, '2026-02-20 05:23:38', 'Pending', 95.00, 8.08, 5.99, 109.07, 'Credit Card', 'Pending', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK, ', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK, ', NULL, NULL, NULL, NULL, NULL),
(2, 'ORD202602227007', 2, '2026-02-22 19:22:42', 'Pending', 95.00, 8.08, 5.99, 109.07, 'Credit Card', 'Pending', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'ORD202602228310', 2, '2026-02-22 19:36:22', 'Pending', 95.00, 8.08, 5.99, 109.07, 'Credit Card', 'Pending', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', NULL, NULL, '', NULL, NULL),
(4, 'ORD202602223306', 2, '2026-02-22 19:36:52', 'Pending', 75.00, 6.38, 5.99, 87.37, 'Credit Card', 'Pending', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', NULL, NULL, '', NULL, NULL),
(5, 'ORD202602232163', 3, '2026-02-23 08:21:25', 'Pending', 95.00, 8.08, 5.99, 109.07, 'Credit Card', 'Pending', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', '456 Oak Avenue, Apt 1B (or just 456 Oak Ave), NEW YORK,', NULL, NULL, '', NULL, NULL);

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
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `total_price`) VALUES
(1, 1, 25, 1, 95.00, 95.00),
(2, 3, 25, 1, 95.00, 95.00),
(3, 4, 17, 1, 75.00, 75.00),
(4, 5, 25, 1, 95.00, 95.00);

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

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `sku`, `description`, `short_description`, `category_id`, `unit_price`, `cost_price`, `quantity_in_stock`, `min_stock_level`, `max_stock_level`, `image_url`, `additional_images`, `weight_grams`, `dimensions`, `is_featured`, `is_active`, `created_at`, `updated_at`, `total_sold`, `rating`, `review_count`) VALUES
(1, 'Velvet Matte Lipstick - \"Ruby Woo\"', 'LIP-VM-001-RW', 'Experience the iconic shade loved by millions. Our Velvet Matte Lipstick in \"Ruby Woo\" delivers a true red carpet look. The unique formula combines high-impact color with a comfortable, velvety texture that stays put for up to 8 hours. Enriched with Vitamin E to keep lips hydrated. Starting at the cupid\'s bow, outline the lips with the bullet, then fill in the rest.', 'A highly pigmented, long-wearing matte lipstick that glides on smoothly without drying out your lips.', 1, 24.99, 8.50, 48, 10, 100, 'assets/images/products/1771443306_6996146a4054c.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:35:06', '2026-02-18 19:35:06', 0, 0.00, 0),
(2, 'Lash Blast Volume Mascara (Black)', 'MASC-VOL-202-BLK', 'Transform your lashes from thin to thick instantly. The Lash Blast Volume Mascara features a unique flexi-brush designed to reach every lash, coating them evenly for dramatic volume without the weight. The formula is designed to resist flaking, smudging, and clumping. Infused with conditioning oils to keep lashes soft. Washes off easily with warm water.', 'Get up to 10x the volume with our patent-pending brush that blasts away clumps for massive lashes.', 1, 19.99, 6.23, 54, 10, 100, 'assets/images/products/1771443545_6996155957797.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:39:05', '2026-02-19 08:37:15', 0, 0.00, 0),
(3, 'Skin Tint Hydrating Foundation - \"Ivory\"', 'FND-HYD-303-IV', 'Say goodbye to cakey makeup. Our Skin Tint Hydrating Foundation is a skincare-makeup hybrid that evens out skin tone while boosting moisture levels. Formulated with Hyaluronic Acid and Niacinamide. It leaves a natural, dewy finish that looks like skin, but better. Oil-free and non-comedogenic, making it perfect for sensitive and dry skin types. Apply one layer for a sheer tint, or build for more coverage.', 'A lightweight, breathable foundation that provides sheer to medium coverage while hydrating the skin for 24 hours.', 1, 34.50, 11.98, 28, 10, 100, 'assets/images/products/1771443672_699615d88dc9c.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:41:12', '2026-02-18 19:41:12', 0, 0.00, 0),
(4, 'Sunset Dream 9-Color Eyeshadow Palette', 'YE-PAL-987-SD', 'Unlock endless eye looks with the Sunset Dream Palette. This collection features 9 warm-toned shades ranging from soft champagnes to deep burgundies, all with a luxurious shimmer finish. The buttery soft powders have intense color payoff and can be used wet for a more intense metallic effect. Compact size with a built-in mirror.', 'A palette of 9 highly pigmented shimmer and metallic shades inspired by golden hour.', 1, 39.99, 14.74, 38, 10, 100, 'assets/images/products/1771443799_69961657ebc72.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:43:19', '2026-02-18 19:43:19', 0, 0.00, 0),
(5, 'Waterproof Gel Eyeliner Pencil (Black)', 'EYE-GEL-456-BLK', 'Define your eyes with precision. This waterproof gel eyeliner pencil glides on smoothly without tugging on the delicate eye area. The intense black pigment is perfect for tightlining, waterline application, or creating a classic wing. Smudge-proof, transfer-proof, and water-resistant for 24H wear. Features a convenient built-in sharpener in the cap.', 'A creamy, kohl-kajal pencil that delivers intense color and stays put all day without smudging.', 1, 16.50, 5.00, 60, 10, 100, 'assets/images/products/1771443925_699616d5b559b.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:45:25', '2026-02-18 19:47:40', 0, 0.00, 0),
(6, 'Glow Blush & Highlight Duo - \"Peach Fizz\"', 'FACE-DUO-789-PF', 'Simplify your routine with the perfect pairing. This Glow Duo includes a silky, blendable matte blush for a natural flush and a radiant highlighter to sculpt and illuminate the high points of your face. The sheer formula ensures you can\'t over-apply. Apply blush to the apples of the cheeks and sweep highlighter along the cheekbones.', 'A two-in-one palette featuring a soft matte blush and a complementary champagne highlighter.', 1, 28.00, 9.49, 44, 10, 100, 'assets/images/products/1771444035_699617433603a.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:47:15', '2026-02-19 08:37:15', 0, 0.00, 0),
(7, 'Brow Shaper Defining Gel (Tinted - Brunette)', 'BROW-GEL-112-BR', 'Achieve fluffy, groomed brows with our Brow Shaper Defining Gel. The microfibers in the gel cling to brow hairs and skin to fill in sparse areas, while the flexible hold keeps them in place all day without stiffness. Sweat and humidity resistant. Brush up and through brows in short, upward motions.', 'Tinted brow gel that tames, fills, and sets brows in place for a natural, fuller look.', 1, 21.00, 6.75, 43, 10, 100, 'assets/images/products/1771444244_699618147cfe7.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:50:44', '2026-02-19 08:37:15', 0, 0.00, 0),
(8, 'Makeup Setting Spray (Dewy Finish)', 'SET-SPR-001-DW', 'Lock your look in place without the powdery look. This fine-mist setting spray creates an invisible flexible barrier that prevents makeup from melting, fading, or settling into fine lines. Infused with aloe vera and green tea extract to refresh the skin. Hold the bottle 8-10 inches away from your face and spray in an \"X\" and \"T\" motion.', 'A weightless mist that locks in makeup for up to 16 hours while adding a healthy, dewy glow.', 1, 25.99, 8.25, 65, 10, 100, 'assets/images/products/1771444347_6996187b329f0.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:52:27', '2026-02-19 08:37:15', 0, 0.00, 0),
(9, 'Full Coverage Concealer - \"Vanilla\"', 'FACE-CON-567-VA', 'Instantly erase imperfections with our best-selling Full Coverage Concealer. The highly pigmented formula provides a flawless matte finish that lasts all day. The doe-foot applicator allows for precise placement. Perfect for brightening the under-eye area or covering spots. Set with powder for extra staying power.', 'A creamy, full-coverage concealer that hides dark circles, blemishes, and redness without creasing.', 1, 22.50, 6.98, 88, 10, 100, 'assets/images/products/1771444447_699618dfa16db.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:54:07', '2026-02-19 08:37:15', 0, 0.00, 0),
(10, 'Liquid Illuminating Drops - \"Rose Gold\"', 'FACE-LIQ-334-RG', 'Mix this liquid highlighter with your foundation for an all-over glow, or pat it onto the high points of your face for a stunning strobe effect. The lightweight, non-greasy formula blends seamlessly into the skin and is infused with Vitamin C. A little goes a long way.', 'Buildable liquid highlighter drops that give skin a natural, lit-from-within glow.', 1, 29.00, 10.24, 55, 10, 100, 'assets/images/products/1771444536_69961938f3af8.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:55:37', '2026-02-19 08:37:15', 0, 0.00, 0),
(11, 'Plumping Lip Gloss - \"Clear Crystal\"', 'LIP-PLG-890-CC', 'Get that perfect pout with our Plumping Lip Gloss. This clear gloss delivers a glass-like finish while ingredients like peppermint oil and hyaluronic acid work to smooth fine lines and boost volume. Can be worn alone or over lipstick.', 'A high-shine, non-sticky gloss that instantly plumps lips with a cooling tingling sensation.', 1, 18.00, 5.49, 75, 10, 100, 'assets/images/products/1771444614_69961986f3012.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:56:54', '2026-02-18 19:56:54', 0, 0.00, 0),
(12, 'Translucent Setting Powder', 'FACE-PWD-123-TR', 'Bake, set, and go! This finely-milled translucent powder is perfect for all skin tones. It blurs the appearance of pores and fine lines while absorbing excess oil for a soft-focus, photoshopped finish. Includes a velvet puff for easy application.', 'An ultra-sheer, lightweight powder that sets makeup and controls shine without adding color or texture.', 1, 27.00, 8.98, 78, 10, 100, 'assets/images/products/1771444703_699619df3438c.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 19:58:23', '2026-02-18 19:58:23', 0, 0.00, 0),
(13, 'Contour Stick - \"Light/Medium\"', 'FACE-CON-456-LM', 'Sculpt in seconds with our easy-to-use Contour Stick. The chubby stick glides directly onto the skin for precise application, while the creamy formula blends out seamlessly without streaking. The cool-toned brown mimics natural shadows to define cheekbones, jawline, and nose.', 'A creamy, blendable contour stick that makes sculpting and defining your features effortless.', 1, 30.98, 11.49, 19, 10, 100, 'assets/images/products/1771444812_69961a4c9b0e7.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 20:00:12', '2026-02-19 08:37:15', 0, 0.00, 0),
(14, 'Eyeshadow Primer Potion', 'EYE-PRM-001-UN', 'Make your eyeshadow pop and stay put! Apply a small amount of this primer to your eyelids to neutralize discoloration and create a smooth canvas. It grips onto powder and pigment, preventing creasing and fading even in humidity.', 'The essential base for vibrant, crease-free eyeshadow that lasts all day and night.', 1, 19.99, 5.99, 56, 10, 100, 'assets/images/products/1771444894_69961a9ece125.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 20:01:34', '2026-02-19 08:37:15', 0, 0.00, 0),
(15, 'Micellar Cleansing Water', 'SKN-MIC-987', 'The first step in any skincare routine. Our Micellar Water contains micelles that act like a magnet to lift away makeup and impurities without harsh rubbing. It is suitable for all skin types, even sensitive eyes. Soak a cotton pad and gently wipe across the face.', 'A gentle, no-rinse cleansing water that removes makeup, dirt, and oil while refreshing the skin.', 1, 14.96, 4.22, 54, 10, 100, 'assets/images/products/1771444988_69961afce7031.jpg', NULL, NULL, NULL, 0, 1, '2026-02-18 20:03:08', '2026-02-19 08:37:15', 0, 0.00, 0),
(16, 'Limited Edition Birthday Glow Kit (Value Set)', 'BNDL-GLOW-2024-BOX', 'Make every day your birthday with our most anticipated release of the year. The Birthday Glow Kit brings together the fan-favorite essentials to create a complete face look, from a dewy base to a stunning pop of color. Whether it\\\'s for a special occasion or treating yourself, this kit ensures you shine bright.', 'Celebrate in style with our ultimate birthday bundle! Includes 4 full-sized bestsellers for the perfect radiant look, packed in a collectible mirrored case.', 1, 89.00, 31.98, 15, 10, 100, 'assets/images/products/1771487509_6996c115984fe.jpg', NULL, NULL, NULL, 1, 1, '2026-02-19 07:51:49', '2026-02-19 08:47:07', 0, 0.00, 0),
(17, 'Blooming Bouquet Eau de Parfum', 'FRG-FLOR-1001-BB', 'Experience the essence of a spring garden with Blooming Bouquet. This sophisticated Eau de Parfum opens with fresh peony and rose petals, settles into a heart of jasmine, and rests on a base of white musk. Long-lasting and perfect for both office wear and romantic evenings.', 'A timeless floral scent with top notes of peony and rose, perfect for everyday elegance.', 4, 75.00, 28.50, 40, 10, 100, 'assets/images/products/1771487686_6996c1c669f0c.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 07:54:46', '2026-02-22 19:36:52', 1, 0.00, 0),
(18, 'Mediterranean Citrus Eau de Cologne', 'FRG-CIT-2002-MC', 'Wake up your senses with Mediterranean Citrus. This unisex cologne features sparkling top notes of Italian lemon and bergamot, a heart of orange blossom, and a dry-down of subtle amber. Light, refreshing, and perfect for hot summer days.', 'A zesty and refreshing burst of lemon, bergamot, and orange for an instant mood boost.', 4, 45.00, 15.75, 76, 10, 100, 'assets/images/products/1771487783_6996c22739754.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 07:56:23', '2026-02-19 08:37:15', 0, 0.00, 0),
(19, 'Woody Eau de Toilette', 'FRG-WOOD-3003-MW', 'Command attention with Midnight Wood. This powerful Eau de Toilette opens with fresh cardamom and cypress, leading to a heart of earthy cedarwood and vetiver, all anchored by a rich base of leather and amber. Ideal for evening wear and cold weather.', 'A bold and masculine scent featuring cedarwood, sandalwood, and a hint of leather.', 4, 69.00, 23.98, 40, 10, 100, 'assets/images/products/1771487914_6996c2aa850a1.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 07:57:47', '2026-02-19 07:58:34', 0, 0.00, 0),
(20, 'Vanilla Orchid Eau de Parfum', 'FRG-ORI-4004-VO', 'Indulge in the sweet embrace of Vanilla Orchid. This rich Eau de Parfum combines creamy Madagascar vanilla with exotic orchid and a hint of caramel. The base of sandalwood and musk creates a long-lasting, comforting trail. Perfect for date night.', 'A warm and sensual gourmand scent with vanilla, orchid, and a touch of caramel.', 4, 82.00, 31.00, 44, 10, 100, 'assets/images/products/1771488093_6996c35dec7a0.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:01:33', '2026-02-19 08:01:33', 0, 0.00, 0),
(21, 'Ocean Breeze Eau de Toilette', 'FRG-AQUA-5005-OB', 'Feel the sea spray on your skin with Ocean Breeze. This clean and invigorating scent features top notes of ozonic air and calone, a heart of seaweed and lavender, and a base of white musk and driftwood. Light, sporty, and universally appealing.', 'Dive into the fresh scent of sea salt, ozone, and white moss. Clean and invigorating.', 4, 55.00, 19.48, 62, 10, 100, 'assets/images/products/1771488200_6996c3c877de4.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:03:20', '2026-02-19 08:03:20', 0, 0.00, 0),
(22, 'Juicy Apple Eau de Parfum', 'FRG-FRUIT-6006-JA', 'Crisp and playful, Juicy Apple opens with red apple and cassis, followed by a floral heart of freesia, and a dry-down of vanilla and woods.', 'A playful and crisp scent featuring red apple, cassis, and a touch of vanilla.', 4, 68.00, 24.99, 54, 10, 100, 'assets/images/products/1771488307_6996c433e6fec.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:05:07', '2026-02-19 08:37:15', 0, 0.00, 0),
(23, 'Tuscan Leather Eau de Parfum', 'FRG-LEATH-7007-TL', 'The ultimate statement of sophistication. Tuscan Leather opens with a burst of raspberry and saffron, quickly diving into a heart of rich leather and birch tar, resting on a base of oud and amber. Powerful, long-lasting, and not for the faint of heart.', 'A luxurious and intense blend of fine Italian leather, saffron, and raspberry.', 4, 120.00, 45.00, 12, 10, 100, 'assets/images/products/1771488407_6996c497c70d3.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:06:47', '2026-02-19 08:06:47', 0, 0.00, 0),
(24, 'Green Tea & Cucumber Eau de Cologne', 'FRG-GREEN-8008-GT', 'Find your zen with this calming and refreshing fragrance. Notes of Japanese green tea and cool cucumber are uplifted by mint and lemon, settling into a soft base of musk. Perfect for daily wear or after a shower.', 'A clean, spa-like scent with green tea, cucumber, and a hint of mint.', 4, 39.00, 11.99, 22, 10, 100, 'assets/images/products/1771488522_6996c50a7a4f8.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:08:42', '2026-02-19 08:37:15', 0, 0.00, 0),
(25, 'Black Pepper & Incense Eau de Parfum', 'FRG-SPICE-9009-BP', 'Embrace the night with this intense and smoky fragrance. Top notes of black pepper and pink berry give way to a heart of burning incense and olibanum, grounded by a base of patchouli and vetiver. Intriguing and complex.', 'A dark and mysterious blend of black pepper, frankincense, and dark woods.', 4, 95.00, 36.00, 14, 10, 100, 'assets/images/products/1771488624_6996c57091eaa.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:10:24', '2026-02-23 08:21:25', 4, 0.00, 0),
(26, 'Coconut Summer Eau de Toilette', 'FRG-SUMR-1010-CS', 'Vacation in a bottle. This sun-soaked scent features creamy coconut water, tropical tiare flower, and a hint of sea salt and vanilla. Light, sweet, and utterly addictive. Ideal for summer festivals and beach days.', 'Transport yourself to a tropical beach with coconut water, tiare flower, and sea salt.', 4, 48.00, 15.97, 84, 10, 100, 'assets/images/products/1771488726_6996c5d64d3bd.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:12:06', '2026-02-19 08:37:15', 0, 0.00, 0),
(27, 'Royal Agarwood Oud Eau de Parfum (Private Collection)', 'LUX- OUD-0001-RA', 'Experience the pinnacle of perfumery with Royal Agarwood Oud. This extrait de parfum features one of the most expensive ingredients in the world—hand-harvested Laotian oud oil, aged for a decade. The opening is a dazzling sparkle of saffron and bergamot, which gives way to a heart of Turkish rose absolute and Indian jasmine. The base is a deep, smoky, and leathery oud anchored by rare ambergris and Mysore sandalwood. A single spray lasts over 24 hours on skin.', 'A masterpiece of rare Laotian oud layered with saffron, rose absolute, and ambergris for a truly regal presence.', 4, 450.00, 179.99, 39, 10, 100, 'assets/images/products/1771488958_6996c6bed684e.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:15:58', '2026-02-19 08:35:19', 0, 0.00, 0),
(28, 'Crystal Diamond Eau de Parfum (Limited Edition)', 'LUX-FLOR-0002-CD', 'More than a fragrance, this is a collectible work of art. Crystal Diamond opens with a burst of sparkling aldehydes and Italian bergamot, leading to a heart of the world\\\'s finest Grasse rose, jasmine grandiflorum, and orris butter. The base is a creamy blend of vanilla bourbon and white musk. The custom-designed bottle features actual Swarovski crystal elements on the collar, making it a stunning centerpiece for any vanity.', 'A dazzling floral-aldehyde housed in a Baccarat crystal bottle with a diamond-shaped stopper. An olfactory jewel.', 4, 650.00, 260.00, 95, 10, 100, 'assets/images/products/1771489055_6996c71f9d986.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:17:35', '2026-02-19 08:37:15', 0, 0.00, 0),
(29, 'Vintage Leather Reserve Eau de Parfum', 'LUX-LEATH-0003-VL', 'Step back in time with Vintage Leather Reserve. This artisanal fragrance utilizes a rare extraction method that captures the scent of 100-year-old Russian leather bindings. The opening is a complex blend of rum and davana, which melts into a heart of tobacco absolute and birch tar. The base is an incredibly realistic, supple leather accord supported by labdanum and castoreum. It is produced in extremely limited batches and comes in a hand-painted ceramic flacon.', 'A hand-crafted leather scent using a rare tincture of vintage Russian leather and tobacco absolute.', 4, 394.00, 149.99, 20, 10, 100, 'assets/images/products/1771489154_6996c7827a6f2.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:19:14', '2026-02-19 08:19:14', 0, 0.00, 0),
(30, 'Enigmatic Tuberose Eau de Parfum', 'LUX-WHITE-0004-ET', 'Enigmatic Tuberose is the definition of opulent femininity. It captures the narcotic, indolic beauty of tuberose at its most potent. The perfume opens with a green, almost spicy note of tuberose stem and cardamom, then bursts into a lush, creamy heart of tuberose absolute, jasmine sambac, and ylang-ylang. The base is a warm embrace of sandalwood and vanilla. The high price reflects the labor-intensive harvesting of the tuberose flowers, which must be picked by hand before sunrise to preserve their scent.', 'A hypnotic white floral featuring the \\\"greenest\\\" tuberose harvested before dawn in India.', 4, 525.00, 210.00, 90, 10, 100, 'assets/images/products/1771489380_6996c864af5fc.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:23:00', '2026-02-19 08:37:15', 0, 0.00, 0),
(31, 'Gucci Flora Gorgeous Gardenia Eau de Parfum', 'GUC-FLOR-1001-GG', 'Escape to a joyful, mystical world with Gucci Flora Gorgeous Gardenia Eau de Parfum. This enchanting fragrance draws its magic from the gardenia flower, whose splendour has been admired since ancient times. The composition opens with a sparkling Pear Blossom accord that transforms into a delicate Brown Sugar trail. The spectacular heart note of White Gardenia is paired with a sunny absolute of Jasmine Grandiflorum and Frangipani Flower, while a warm Patchouli base adds depth and longevity. \\r\\n\\r\\nEncased within an elongated pink lacquered glass bottle topped with a shiny gold cap, the design features the House\\\'s iconic Flora pattern—a painting of colourful flowers created by artist Vittorio Accornero for Gucci in 1966. The bottle is made with 10% recycled glass and water-based lacquer, while the packaging is manufactured from FSC-certified paper.', 'A joyful white floral scent built around the Gardenia flower, blended with solar Jasmine Grandiflorum Absolute for a magical, feminine aura.', 4, 118.00, 44.98, 100, 10, 100, 'assets/images/products/1771489441_6996c8a19156d.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 08:24:01', '2026-02-19 08:37:15', 0, 0.00, 0),
(32, 'Louis Vuitton Les Parfums Mille Feux Eau de Parfum', 'LV-MF-100ML', 'Mille Feux translates to \"a thousand fires,\" and this fragrance truly lives up to its name. Created by master perfumer Jacques Cavallier Belletrud for Louis Vuitton, the inspiration struck while observing an artisan crafting a raspberry-red leather handbag in the LV workshops—the leather\'s光泽 reminded him of ripe fruit, leading to this masterpiece.\r\n\r\nThe composition opens with a burst of sparkling berries and杏子-like osmanthus from China, which possesses a wild, apricot-like scent. This is elevated by noble iris and warm saffron, all wrapped in the richness of the finest leather accord. The result is an emotional explosion of fragrance—like fireworks illuminating the night sky.\r\n\r\nKey Features:\r\n\r\nConcentration: Eau de Parfum\r\n\r\nKey Notes: Chinese Osmanthus, Raspberry, Saffron, Iris, Premium Leather Accord\r\n\r\nLongevity: Exceptional (8-12 hours)\r\n\r\nOccasion: Evening galas, celebrations, making a statement', 'A radiant explosion of emotions inspired by the glow of leather and the sparkle of fireworks—a rare blend of Chinese osmanthus, saffron, iris, and precious leather.', 4, 2000.00, 849.97, 30, 10, 100, 'assets/images/products/1771489575_6996c927b9e96.jpg', NULL, NULL, NULL, 1, 1, '2026-02-19 08:26:15', '2026-02-19 08:46:57', 0, 0.00, 0),
(33, 'Showstopper American Diamond Necklace Set', 'IMJ-NEC-1001-AD', 'Make a grand entrance with this Showstopper Imitation Jewelry Set crafted from high-quality brass and featuring brilliant American diamonds. Unlike cheap imitations where stones are glued, every gemstone in this necklace set is secured using authentic prong setting from four sides, ensuring durability and a genuine diamond-like sparkle. The set undergoes approximately 40 hours of design work, from hand sketching to CAD conversion and 3D printing, resulting in intricate craftsmanship that rivals fine jewelry. The base metal is premium brass—an alloy of copper and zinc in ideal proportions—ensuring both malleability and long-lasting durability. The piece is electroplated with your choice of 24-carat gold for a warm yellow finish or rhodium for a cool silver tone. Includes a matching back chain with adjustable extensions to fit all neck sizes comfortably. With proper care—avoiding perfume sprays and cosmetic chemicals—this jewelry will maintain its shine for at least 10 years.\r\n\r\nMaterial: High-quality Brass base\r\n\r\nStone Setting: Prong setting (4 sides), no glue\r\n\r\nPlating: 24K Gold electroplating or Rhodium plating\r\n\r\nOccasion: Weddings, parties, festive celebrations\r\n\r\nPackaging: Protective hard plastic box with bubble wrap', 'A stunning brass necklace set featuring high-quality American diamonds in prong setting, designed for parties and weddings.', 2, 450.00, 17.97, 20, 10, 100, 'assets/images/products/1771507519_69970f3f67664.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:25:19', '2026-02-19 13:25:19', 0, 0.00, 0),
(34, 'Maharani Heritage Polki Kundan Bridal Set', 'ULTRA-KUN-1001-MH', 'Step into the shoes of a Maharani with this extraordinary Polki Kundan Bridal Set, representing the highest echelon of imitation jewelry craftsmanship. Unlike mass-produced pieces, this set is handcrafted by master artisans from Rajasthan who specialize in the ancient technique of Kundan setting—where stones are encased in pure gold-plated metal rather than glued. The set features premium Polki-style stones (uncut diamond simulants) with real silver foil backing, creating the same depth and reflection as genuine diamond Polki. Each stone is individually wrapped in 24-karat gold-plated foil before being set, a technique requiring days of meticulous labor. The reverse features authentic lac work and hand-painted Meenakari enamel depicting floral motifs. The necklace is substantial in weight (approximately 150 grams) with a adjustable chain and secure double-lock clasp. The matching earrings feature comfortable screw-back posts. Presented in a handcrafted rosewood box lined with raw silk and accompanied by a certificate of authenticity and care guide. This is an heirloom-quality piece designed to last generations.\r\n\r\nMaterial: High-grade brass, 24K gold plating, silver foil backing\r\n\r\nStones: Premium Polki-style uncut simulants\r\n\r\nCraftsmanship: Hand-set Kundan, lac work, Meenakari enamel\r\n\r\nWeight: Approximately 150 grams (necklace + earrings)\r\n\r\nSet Includes: Necklace + Matching Earrings\r\n\r\nOccasion: Destination weddings, royal-themed events, bridal trousseau', 'A museum-quality bridal set featuring uncut Polki-style stones, hand-set in 24-karat gold-plated brass with authentic lac work and real silver foil backing.', 2, 995.00, 419.00, 28, 10, 100, 'assets/images/products/1771507717_699710057547a.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:28:37', '2026-02-19 13:28:37', 0, 0.00, 0),
(35, 'Victorian Era Diamond Bridal Parure', 'ULTRA-VIC-2002-VP', 'Inspired by the grandeur of 19th-century European royal courts, this Victorian Era Bridal Parure is a masterpiece of imitation jewelry design. The set includes five matching pieces: a statement necklace, a coordinating bracelet, a pair of drop earrings, a cocktail ring, and a detachable brooch that can be worn separately or attached to the necklace. Each piece features premium 5A-grade cubic zirconia stones—the highest quality available in the market—exhibiting exceptional clarity, fire, and brilliance that closely mimics flawless diamonds. The stones are set in high-grade German silver (an alloy of copper, nickel, and zinc) with heavy rhodium plating that replicates the bright white finish of platinum. The setting is entirely handmade, with each stone secured using micro-prong and pave techniques requiring 80+ hours of labor. The necklace features a hidden safety chain and secure box clasp. The set arrives in a custom-designed velvet presentation case with individual compartments for each piece, along with a polishing cloth, care instructions, and a lifetime guarantee against stone loss.\r\n\r\nMaterial: German silver, heavy rhodium plating\r\n\r\nStones: Premium 5A cubic zirconia (D color, VVS clarity)\r\n\r\nSetting: Micro-prong and pave, hand-set\r\n\r\nSet Includes: Necklace + Earrings + Bracelet + Ring + Brooch\r\n\r\nOccasion: White weddings, red carpet events, milestone anniversaries', 'A complete 5-piece bridal parure inspired by Victorian-era European royalty, featuring premium 5A cubic zirconia in rhodium-plated German silver.', 2, 1050.00, 449.99, 25, 10, 100, 'assets/images/products/1771507862_69971096bce2e.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:31:02', '2026-02-19 13:31:02', 0, 0.00, 0),
(36, 'Nizam\'s Pearl & Diamond Royal Ensemble', 'ULTRA-PRL-3003-NP', 'Paying homage to the legendary wealth of the Nizams of Hyderabad, this Royal Ensemble captures the opulence of India\'s most extravagant rulers. The set features premium imitation pearls that replicate the size, luster, and subtle iridescence of genuine South Sea cultured pearls—each pearl is hand-selected for perfect roundness and surface quality. Interspersed among the pearls are rose-cut cubic zirconia stones that mimic the flat-bottomed, faceted diamonds favored in Mughal jewelry. All metalwork is crafted from high-grade brass with multiple layers of 22-karat gold electroplating, achieving a deep, rich color that improves with age. The 6-piece ensemble includes: a multi-strand pearl choker, a long pearl necklace (mala), matching jhumka earrings, a pearl matha patti (headpiece), a nose pin (nath), and a pair of pearl bangles. The craftsmanship includes traditional wire-drawing techniques and hand-knotting between each pearl—a detail that prevents loss and adds authenticity. Housed in a replica antique chest of drawers lined with velvet, this set makes an unforgettable bridal statement.', 'A lavish 6-piece ensemble inspired by the Nizams of Hyderabad, featuring high-luster South Sea cultured-style imitation pearls and rose-cut CZ diamonds in 22K gold plating.', 2, 1080.00, 460.00, 9, 10, 100, 'assets/images/products/1771507970_69971102ade3a.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:32:50', '2026-02-19 13:32:50', 0, 0.00, 0),
(37, 'Regal Polki Kundan Bridal Necklace Set', 'EXP-KUN-2001-RP', 'Step into royalty with this Regal Polki Kundan Bridal Set, crafted for the discerning bride who appreciates authentic traditional craftsmanship. The set features premium Polki-style uncut diamond simulants that capture the raw, organic beauty of genuine Polki stones. Each stone is hand-set using traditional kundan technique—where stones are encased in 22-karat gold-plated metal rather than glued, requiring hours of meticulous labor. The reverse features hand-painted meenakari enamel in rich red and green hues, depicting traditional floral motifs. The necklace is substantial in weight (approximately 120 grams) with a adjustable velvet strap for comfort and security. The matching earrings are traditional jhumka-style with multiple tiers of Polki stones and pearl drops. This heirloom-quality piece comes in a handcrafted wooden box lined with velvet and includes a certificate of authenticity.', 'A magnificent bridal set featuring premium Polki-style uncut stones, intricate kundan work, and hand-painted meenakari enamel in 22K gold plating.', 2, 349.00, 145.00, 33, 10, 100, 'assets/images/products/1771508103_699711879ce7a.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:35:03', '2026-02-19 13:35:03', 0, 0.00, 0),
(38, 'Diamond-Cut Luxury American Diamond Suite', 'EXP-DIA-2002-DC', 'Experience unparalleled brilliance with this Diamond-Cut Luxury American Diamond Suite. Unlike standard imitation diamonds, these premium stones feature precision diamond-cut faceting that maximizes light reflection, creating exceptional fire and sparkle. The stones are AAA-grade cubic zirconia set in high-quality German silver with heavy rhodium plating—the same finish used in platinum jewelry that provides a bright white appearance and resistance to tarnish. The design features an intricate floral pattern with multiple layers of stones in various cuts (round, pear, marquise). The necklace includes a hidden safety chain and secure box clasp. The matching earrings feature comfortable screw-back posts with additional safety backs. This set is versatile enough for both traditional and contemporary outfits, from silk sarees to modern lehengas. Presented in a custom-designed velvet case with individual compartments.', 'An exquisite suite featuring premium diamond-cut American diamonds in a modern floral design with rhodium-plated German silver finish.', 2, 295.00, 120.00, 38, 10, 100, 'assets/images/products/1771508216_699711f8c429e.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:36:56', '2026-02-19 13:36:56', 0, 0.00, 0),
(39, 'Sacred Temple Gold Bridal Collection', 'EXP-TMP-2003-TG', 'This Sacred Temple Gold Bridal Collection represents the grandeur of South Indian temple jewelry. Each piece features hand-carved motifs of deities, peacocks, and traditional patterns—crafted by master artisans from Tamil Nadu who specialize in this centuries-old art form. The collection includes five essential pieces: a heavy temple necklace (Kasu Mala), matching temple jhumka earrings, a waist belt (Oddiyanam), a matha patti (headpiece), and a pair of bajubandh (armlets). The base is high-quality brass with multiple layers of 22-karat gold electroplating, achieving a rich, authentic gold tone. Genuine-looking ruby-colored stones are set using traditional techniques, adding vibrant color. The total weight exceeds 200 grams, providing substantial presence on the wedding day. The collection arrives in a custom-designed wooden box with silk pouches for each piece.', 'An extensive South Indian temple jewelry collection featuring hand-carved deities, 22K gold plating, and genuine-looking ruby stones.', 2, 425.00, 174.98, 44, 10, 100, 'assets/images/products/1771508315_6997125b1d2c9.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 13:38:35', '2026-02-19 13:38:35', 0, 0.00, 0),
(40, 'Royal Ruby and Emerald Kundan Set', 'XP-RUB-2008-RE', 'This Royal Ruby and Emerald Kundan Set showcases the classic combination of red and green that defines traditional Indian jewelry. The set features high-quality simulated ruby and emerald stones with exceptional color saturation and clarity. Each stone is set using authentic Kundan technique—wrapped in gold-plated foil and encased in metal without adhesive—requiring skilled craftsmanship. The design incorporates traditional floral and paisley patterns with the rubies and emeralds arranged in alternating or complementary formations. The reverse features hand-painted meenakari enamel in coordinating colors. The necklace is substantial with multiple layers and a central pendant that rests beautifully on the neckline. The matching earrings are chandelier-style with multiple tiers of gemstones and pearl accents. This set is perfect for brides who want vibrant color in their trousseau or for anyone attending traditional celebrations.', 'A magnificent Kundan set featuring simulated ruby and emerald stones in traditional gold-plated setting with meenakari work.', 2, 365.00, 144.98, 50, 10, 100, 'assets/images/products/1771524937_69975349209e3.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:15:37', '2026-02-19 18:15:37', 0, 0.00, 0),
(41, 'White Gold Finish Diamond Choker', 'EXP-WHT-2009-WG', 'This White Gold Finish Diamond Choker offers contemporary elegance with its sleek, modern design. The choker features pave-set cubic zirconia stones covering the entire surface, creating a continuous field of brilliance. The stones are high-quality 5A grade with excellent clarity and fire, set using micro-pave techniques that require precision and skill. The metal is German silver with heavy rhodium plating, providing the bright white finish of platinum or white gold. The design is geometric and clean—perfect for the modern bride or for evening events. The choker features a hidden hinge and secure clasp with safety chain, ensuring comfortable wear throughout the night. This versatile piece works beautifully with both traditional and Western outfits, from silk sarees to evening gowns. Comes in a sleek white presentation box with velvet interior.', 'A stunning white gold-finished choker featuring pave-set cubic zirconia stones in modern geometric design.', 2, 195.00, 77.99, 69, 10, 100, 'assets/images/products/1771525042_699753b29e8f1.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:17:22', '2026-02-19 18:17:22', 0, 0.00, 0),
(42, 'Luxury Multi-Strand Pearl Necklace Set', 'EXP-PRL-2010-PL', 'This Luxury Multi-Strand Pearl Necklace Set embodies timeless elegance. The necklace features three strands of premium imitation pearls, each carefully selected for consistent size (10-12mm), shape, and exceptional luster. The pearls have a multi-layered coating that replicates the iridescence of genuine South Sea pearls, with subtle overtones of rose and cream. Each pearl is hand-knotted individually on silk thread—a detail that prevents loss if the strand breaks and adds to the authentic pearl jewelry aesthetic. The strands converge at a stunning gold-plated clasp adorned with cubic zirconia stones in a floral pattern. The matching earrings are classic pearl drops with the same CZ-accented settings. This set transitions effortlessly from day to evening, from professional settings to formal events. Presented in a velvet-lined jewelry case with a polishing cloth and care guide.', 'An elegant multi-strand pearl necklace featuring high-luster imitation South Sea pearls with diamond-studded gold-plated clasp.', 2, 280.00, 111.99, 80, 10, 100, 'assets/images/products/1771525139_69975413b4703.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:18:59', '2026-02-19 18:18:59', 0, 0.00, 0),
(43, 'Traditional Heavy Jhumka Set with Necklace', 'EXP-JHM-2011-JH', 'Make a statement with this Traditional Heavy Jhumka Set, celebrating the timeless appeal of Indian jhumka design. The oversized jhumka earrings feature the classic bell-shaped dome with intricate filigree work, multiple tiers of hanging elements, and pearl accents that sway gracefully with movement. The matching necklace incorporates the same design elements—filigree work, pearl drops, and coordinating motifs—creating a cohesive, polished look. All metal is high-quality brass with multiple layers of gold plating, providing a rich, warm tone. The craftsmanship includes hand-crafted filigree work and secure stone setting. Despite their substantial appearance, the earrings are designed for comfort with lightweight construction and secure post backs with additional safety. The necklace features an adjustable chain with lobster clasp. This set is perfect for brides, bridesmaids, or anyone attending traditional celebrations.', 'A complete set featuring oversized traditional jhumka earrings and matching necklace with intricate filigree work and pearl accents.', 2, 310.00, 123.99, 64, 10, 100, 'assets/images/products/1771525263_6997548f4b67e.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:21:03', '2026-02-19 18:21:03', 0, 0.00, 0),
(44, 'Emerald Cut CZ Statement Cocktail Ring', 'EXP-RNG-2012-EC', 'Command attention with this Emerald Cut CZ Statement Cocktail Ring, designed for those who love bold, sophisticated jewelry. The centerpiece is a substantial emerald-cut cubic zirconia stone (approximately 5 carat equivalent) with exceptional clarity and precision-cut faceting that maximizes brilliance. The stone is graded 5A—the highest quality available—with D color and VVS clarity. Surrounding the center stone is a halo of pavé-set round CZ stones that add extra sparkle and emphasize the size of the main stone. The setting is crafted from German silver with heavy rhodium plating, providing the bright white finish of platinum. The band features additional CZ stones set in channel setting along the shoulders. The ring is substantial in weight and presence, yet comfortable for evening wear. Adjustable sizing accommodates most finger sizes. Perfect for engagement parties, anniversary celebrations, or as a stunning right-hand ring.', 'A bold cocktail ring featuring a large emerald-cut cubic zirconia stone surrounded by pavé-set diamonds in rhodium-plated setting.', 2, 164.99, 64.99, 40, 10, 100, 'assets/images/products/1771525430_6997553606bc3.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:23:50', '2026-02-19 18:23:50', 0, 0.00, 0),
(45, 'Gold Bridal Waist Belt (Oddiyanam)', 'EXP-BEL-2013-WB', 'Complete your bridal ensemble with this elaborate Gold Bridal Waist Belt (Oddiyanam), an essential accessory for the traditional bride. The belt features a central decorative piece with intricate traditional motifs—including floral patterns, peacocks, and paisleys—set with brilliant American diamonds and accent pearls. The design extends to side panels with additional stone work, connected by adjustable gold chains that accommodate various waist sizes. The belt is crafted from high-quality brass with multiple layers of gold plating, providing a rich, warm tone that complements gold jewelry. The stones are securely set using prong and bezel techniques. The belt includes a secure clasp and additional safety chain. It sits comfortably on the hips, accentuating the silhouette of lehengas and sarees. The total length is adjustable from 26 to 36 inches. Presented in a velvet pouch inside a rigid box.', 'An elaborate gold-plated bridal waist belt featuring American diamonds, pearl accents, and traditional motifs with adjustable chain.', 2, 235.00, 94.00, 35, 10, 100, 'assets/images/products/1771525566_699755bee55a3.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:26:06', '2026-02-19 18:26:06', 0, 0.00, 0),
(46, 'Royal Passa and Tikka Headpiece Set', 'XP-PAS-2014-PT', 'This Royal Passa and Tikka Set offers a complete head ornamentation solution for the bride who wants elaborate, cohesive styling. The set includes a central tikka (forehead piece) with a stunning pendant featuring American diamonds and pearl drops, suspended from an adjustable beaded chain. The passa (side ornament) is designed to be worn on one side, draping gracefully along the hairline with multiple strands of pearls and CZ accents. Additional accessories include small decorative pieces (sita har) that can be pinned into the hair for extra volume and sparkle. All pieces feature high-quality gold-plated brass with premium American diamonds and imitation pearls. The craftsmanship includes secure stone setting and durable stringing. The set comes with discreet hair pins and hooks for secure attachment. Whether for a wedding or a formal cultural event, this headpiece set ensures you\'ll shine from every angle.', 'A complete royal headpiece set including passa (side ornament) and tikka (forehead piece) with matching accessories.', 2, 204.99, 82.00, 44, 10, 100, 'assets/images/products/1771525654_69975616df194.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:27:34', '2026-02-19 18:27:34', 0, 0.00, 0),
(47, 'Bridal Nath with Chain and Pearl Accents', 'EXP-NAT-2015-NT', 'The bridal nath is one of the most significant pieces in a traditional Indian bride\'s jewelry collection, and this elaborate design honors that importance. The nath features a large central imitation pearl (approximately 12mm) surrounded by intricate gold-plated metalwork set with cubic zirconia stones. From the central element hangs a delicate chain with additional pearl and CZ accents. A decorative chain attaches from the nath to the hair, secured with a discreet hook, ensuring the piece stays comfortably in place throughout the wedding festivities. The nath is designed for pierced noses with an adjustable screw mechanism that fits most nose piercings comfortably. The metal is high-quality brass with multiple layers of gold plating, providing durability and rich color. This piece adds authentic traditional detail to any bridal look and is also appropriate for other major celebrations. Comes in a velvet-lined box.', 'An elaborate bridal nose ring (nath) featuring a large central pearl with CZ accents, attached to a decorative chain that secures in the hair.', 2, 185.00, 73.99, 59, 10, 100, 'assets/images/products/1771525754_6997567acec70.png', NULL, NULL, NULL, 0, 1, '2026-02-19 18:29:14', '2026-02-19 18:29:14', 0, 0.00, 0),
(48, 'The Aurora Modern Diamond Jewelry Collection', 'FEAT-MOD-6666-AM', 'The Aurora Modern Diamond Jewelry Collection redefines luxury for the contemporary aesthetic. Inspired by modern architecture and clean geometric forms, this collection moves away from traditional ornate designs toward something fresh, versatile, and utterly sophisticated. Each piece features premium 5A-grade cubic zirconia stones—the highest quality available—with precision cuts that maximize brilliance and fire. The stones are set in a stunning two-tone combination of warm rose gold plating and cool rhodium-plated German silver, creating visual interest and versatility.\\r\\n\\r\\nThe design philosophy centers on \\\"less is more\\\"—clean lines, negative space, and architectural shapes replace heavy ornamentation. Yet the collection maintains presence through quality materials and flawless execution. Each piece is hand-finished by skilled artisans who ensure every stone is perfectly aligned and every surface smoothly polished. The result is jewelry that feels both modern and timeless, equally at home with power suits, cocktail dresses, or casual luxury wear.', 'A sleek, minimalist 7-piece jewelry collection featuring precision-cut 5A cubic zirconia in geometric settings with rose gold and rhodium two-tone finish—designed for the modern woman who values clean lines and understated elegance.', 2, 1298.99, 544.98, 0, 10, 100, 'assets/images/products/1771526053_699757a5ddbd6.jpg', NULL, NULL, NULL, 1, 1, '2026-02-19 18:34:13', '2026-02-19 18:35:10', 0, 0.00, 0),
(49, 'Vitamin C Brightening Serum with Hyaluronic Acid', 'SKN-VIT-1001-VC', 'Reveal a more radiant, youthful complexion with this Vitamin C Brightening Serum. Formulated with a stable 20% L-Ascorbic Acid (Vitamin C), this powerful antioxidant helps neutralize free radical damage, brighten dark spots, and boost collagen production for firmer, more elastic skin. Hyaluronic Acid provides intense hydration, plumping the skin to smooth fine lines and wrinkles, while Vitamin E soothes and nourishes. The lightweight, fast-absorbing formula layers beautifully under moisturizer and sunscreen. With consistent use, you\'ll notice a visible improvement in skin tone evenness and overall radiance. Suitable for most skin types, including sensitive skin. Dermatologist-tested and cruelty-free.', 'A potent antioxidant serum featuring 20% Vitamin C, Hyaluronic Acid, and Vitamin E to brighten skin tone and reduce fine lines.', 3, 28.10, 9.50, 90, 10, 100, 'assets/images/products/1771526345_699758c9ad794.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:39:05', '2026-02-19 18:39:05', 0, 0.00, 0),
(50, 'Crème de la Mer Moisturizer', 'LUX-LAM-2001-CLM', 'Experience the transformative power of Crème de la Mer, the world-renowned luxury moisturizer born from aerospace physicist Dr. Max Huber\'s 12-year, 6,000-formula quest to heal his burned skin. This iconic cream features the exclusive Miracle Broth™—a bio-fermentation process using nutrient-rich sea kelp, calcium, magnesium, potassium, iron, lecithin, and vitamins, gently infused with light and sound waves for 3-4 months. This potent elixir transforms the look of skin, dramatically reducing signs of aging while soothing dryness and irritation. The rich, luxurious texture melts into skin, delivering deep hydration and leaving complexion renewed, radiant, and resilient. Each jar is hand-filled and sealed to preserve potency. A single jar represents the pinnacle of skincare luxury, favored by celebrities and skincare connoisseurs worldwide. The global luxury skincare market continues to grow, with Crème de la Mer remaining a top performer.', 'The legendary cult-classic moisturizer fermented using potent sea kelp and other natural ingredients for visible transformation.', 3, 495.00, 164.99, 40, 10, 100, 'assets/images/products/1771526487_69975957c15f0.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:41:27', '2026-02-19 18:41:27', 0, 0.00, 0),
(51, 'PDRN Collagen Infusion Cream', 'SKN-PDR-1015-PC', 'Experience cutting-edge skincare technology with this PDRN Collagen Infusion Cream. PDRN (Polydeoxyribonucleotide) is a innovative ingredient derived from salmon DNA, known for its remarkable skin-regenerating properties. It works at the cellular level to stimulate collagen production, accelerate tissue repair, and improve skin elasticity and firmness. Combined with hydrolyzed collagen to plump and smooth, this cream delivers comprehensive anti-aging benefits. The rich, luxurious formula deeply hydrates while promoting visible rejuvenation—fine lines and wrinkles appear diminished, skin texture becomes smoother, and overall radiance is enhanced. With consistent use, you\\\'ll notice firmer, more resilient skin with a youthful bounce. This cream is part of a new generation of skincare that bridges the gap between professional treatments and at-home care.', 'An advanced anti-aging cream featuring Polydeoxyribonucleotide (PDRN) and collagen to promote skin regeneration and rejuvenation.', 3, 29.00, 11.99, 70, 10, 100, 'assets/images/products/1771526587_699759bb570f5.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:43:07', '2026-02-19 18:43:38', 0, 0.00, 0),
(52, 'Facial Treatment Essence', 'FACIALTR-662712', 'Discover the secret behind countless celebrity and editor testimonials—SK-II Facial Treatment Essence, the brand\'s iconic product that has sold every 2 seconds at its peak. The magic lies in PITERA™, a naturally derived bio-ingredient discovered in a sake brewery where elderly workers had youthful, wrinkled hands. This exclusive ingredient, produced through a unique yeast fermentation process, contains over 50 micronutrients including vitamins, minerals, amino acids, and organic acids that work in synergy to hydrate, smooth, and brighten. With daily use, this essence dramatically improves skin texture, minimizes pores, reduces dark spots, and enhances natural radiance. The lightweight, watery texture absorbs instantly, prepping skin to receive subsequent treatments. It\'s the foundation of any serious skincare routine and has achieved cult status worldwide. The global market for essences continues to grow, with SK-II maintaining its position as the gold standard.', 'SK-II\'s award-winning essence with 90% PITERA™, a bio-ingredient rich in vitamins, amino acids, and minerals that transforms skin texture and radiance.', 3, 235.00, 79.99, 54, 10, 100, 'assets/images/products/1771526724_69975a44c9f04.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:45:24', '2026-02-19 18:45:24', 0, 0.00, 0);
INSERT INTO `products` (`product_id`, `product_name`, `sku`, `description`, `short_description`, `category_id`, `unit_price`, `cost_price`, `quantity_in_stock`, `min_stock_level`, `max_stock_level`, `image_url`, `additional_images`, `weight_grams`, `dimensions`, `is_featured`, `is_active`, `created_at`, `updated_at`, `total_sold`, `rating`, `review_count`) VALUES
(53, 'Re-Nutriv Ultimate Diamond Transformative Energy Creme', 'RENUTRIV-808556', 'Enter the world of ultra-luxury with Estée Lauder\'s Re-Nutriv Ultimate Diamond Transformative Energy Creme, a masterpiece of skincare science and indulgence. This extraordinary formula combines the rarest, most potent ingredients from around the globe. Black Diamond Truffle, harvested by hand in France\'s Périgord region, delivers powerful antioxidant protection and revitalizing energy to aging skin cells. Genuine diamond powder adds an instantaneous luminous glow while working over time to refine skin\'s texture. The result is skin that looks and feels transformed—visibly lifted, deeply hydrated, and radiant with health. The sensorial experience is equally indulgent: the rich, silky texture melts into skin, releasing a luxurious aroma that makes application a daily ritual of self-care. Housed in a heavy, jewel-like jar, this cream is a statement piece for any vanity and a testament to Estée Lauder\'s commitment to uncompromising quality.', 'A transformative anti-aging creme infused with diamond powder and black diamond truffle extract to revitalize aging skin.', 3, 350.00, 119.98, 45, 10, 100, 'assets/images/products/1771526861_69975acdd06e5.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:47:41', '2026-02-19 18:47:41', 0, 0.00, 0),
(54, 'Supremÿa At Night: The Supreme Anti-Aging Cream', 'SUPREMAA-895876', 'Supremÿa At Night represents the culmination of over 40 years of phytocosmetry expertise from Sisley Paris. This extraordinary night cream is formulated with over 50 plant-based active ingredients, including rare extracts from Alpine plants, Madagascan algae, and Brazilian native flowers. The innovative formula targets all visible signs of aging while you sleep, when skin is most receptive to regeneration. It visibly resculpts facial contours, diminishes deep wrinkles, improves firmness, and restores the radiant, youthful glow that fades with age. The texture is uniquely luxurious—rich and enveloping yet surprisingly breathable, it creates the perfect environment for overnight renewal. The fragrance, a blend of essential oils including rose and magnolia, transforms application into an aromatherapeutic experience. Clinical studies show visible results in just 4 weeks. This is skincare for those who demand nothing less than excellence.', 'Sisley\'s most advanced anti-aging night cream, harnessing the power of over 50 plant-based ingredients to resculpt and regenerate skin overnight.', 3, 785.00, 265.00, 15, 10, 100, 'assets/images/products/1771526944_69975b2072e8d.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:49:04', '2026-02-19 18:49:04', 0, 0.00, 0),
(55, 'Orchidée Impériale Rich Cream', 'ORCHIDEI-027029', 'For over 15 years, Guerlain has dedicated itself to unlocking the secrets of orchids—flowers renowned for their extraordinary longevity and regenerative capabilities. Orchidée Impériale Rich Cream represents the pinnacle of this research, utilizing Orchid Molecular Engineering™ to identify and extract the most potent cellular youth molecules from rare orchid species. The formula incorporates extracts from up to 10,000 orchids to create a single jar, targeting all dimensions of aging at the cellular level. The rich, comforting texture is specifically designed for normal to dry skin, providing intense nourishment while visibly lifting, firming, and smoothing. With each application, skin appears more radiant, contours are refined, and the visible signs of aging are diminished. The cream is presented in a heavy glass jar inspired by an Imperial-era Guerlain design, making it a beautiful objet d\'art for your vanity. It\'s the choice of women who appreciate the intersection of science, nature, and luxury.', 'A revolutionary anti-aging cream harnessing the regenerative power of orchids through Guerlain\'s exclusive Orchid Molecular Engineering™.', 3, 440.00, 150.00, 40, 10, 100, 'assets/images/products/1771527086_69975baebc74d.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:51:26', '2026-02-19 18:51:26', 0, 0.00, 0),
(56, 'The Concentrate', 'THECONCE-123075', 'When your skin needs intensive care, The Concentrate by La Mer delivers immediate and visible results. This legendary serum is formulated with an extraordinary concentration of the brand\'s signature Miracle Broth™, combined with exclusive Marine Botanical Extracts to accelerate skin\'s natural renewal process. It\'s designed specifically for skin that\'s stressed—whether from environmental aggressors, post-procedure sensitivity, or visible signs of aging. The Concentrate creates an invisible protective barrier that helps shield skin from further irritation while working beneath the surface to strengthen, soothe, and restore. With regular use, skin becomes visibly stronger, calmer, and more resilient, with a smooth, even texture and radiant glow. The unique texture is comforting yet weightless, absorbing quickly to deliver potent actives where they\'re needed most. A single dropperful is enough to transform compromised skin. It\'s the ultimate investment in skin health and recovery.', 'A potent, fast-acting serum that dramatically soothes, strengthens, and restores the look of compromised or aging skin.', 3, 460.00, 155.00, 25, 10, 100, 'assets/images/products/1771527163_69975bfb39bfa.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:52:43', '2026-02-19 18:52:43', 0, 0.00, 0),
(57, 'The Rich Cream', 'THERICHC-209722', 'Augustinus Bader has taken the skincare world by storm, and The Rich Cream is at the heart of this phenomenon. Developed by Professor Augustinus Bader, a world-renowned German scientist specializing in stem cell biology and regenerative medicine, this cream features the patented TFC8® (Trigger Factor Complex) technology. This advanced complex contains a precise blend of natural amino acids, high-grade vitamins, and synthesized molecules that work with your skin\'s own cells to guide nutrients to where they\'re needed most, supporting cellular renewal and repair. The Rich Cream is specifically formulated for dry to very dry skin, providing deep, lasting hydration while visibly reducing the appearance of fine lines, wrinkles, and hyperpigmentation. With consistent use, skin becomes visibly healthier, more radiant, and remarkably transformed. The fragrance-free, dermatologist-tested formula has earned a devoted following among celebrities, editors, and skincare experts worldwide. It\'s proof that cutting-edge science and luxurious texture can coexist.', 'The cult-favorite moisturizer featuring patented TFC8® technology to activate skin\'s natural renewal processes for visible transformation.', 3, 290.00, 100.00, 30, 10, 100, 'assets/images/products/1771527269_69975c652dd28.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:54:29', '2026-02-19 18:54:29', 0, 0.00, 0),
(58, 'Prime Regenera I Intensive Cream', 'PRIMEREG-311100', 'Valmont, the prestigious Swiss brand born from the renowned Clinique Valmont medical center, brings decades of cellular anti-aging expertise to Prime Regenera I Intensive Cream. This extraordinary formula is built around the brand\'s signature ingredient: highly purified DNA and RNA extracts derived from salmon sperm. These nucleic acids are bio-identical to those found in human skin and work at the cellular level to stimulate regeneration, improve cellular communication, and extend the lifespan of skin cells. The result is visible transformation—skin appears smoother, firmer, more radiant, and visibly younger. The rich, velvety texture provides deep nourishment while the innovative complex works beneath the surface. Valmont products are favored by royalty, celebrities, and those who seek the pinnacle of Swiss skincare technology. The minimalist, elegant packaging reflects the brand\'s medical heritage and commitment to results over marketing.', 'A Swiss cellular anti-aging cream featuring DNA and RNA extracts to stimulate cellular regeneration and restore youthful vitality.', 3, 380.00, 130.00, 40, 10, 100, 'assets/images/products/1771527421_69975cfdcc33f.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 18:57:01', '2026-02-19 18:57:01', 0, 0.00, 0),
(59, 'Prodigy Re-Plasty Age Repair Cream', 'PRODIGYR-729592', 'Helena Rubinstein, the brand that revolutionized beauty with the first modern day cream in 1902, continues its legacy of innovation with Prodigy Re-Plasty Age Repair Cream. This advanced formula is the result of decades of research into skin regeneration and repair. The key ingredient, Pro-Xylane™, is a patented sugar molecule derived from beech wood that stimulates the production of glycosaminoglycans (GAGs) in the skin, restoring volume and density from within. Combined with multi-molecular Hyaluronic Acid that targets different layers of the skin, this cream delivers unprecedented hydration and plumping. The result is visible correction of even the deepest wrinkles, restored facial contours, and a dramatic improvement in skin firmness and elasticity. The rich, comforting texture is a pleasure to apply, and the results are visible from the very first use. This is serious anti-aging for those who demand visible, measurable results.', 'A powerful age-repair cream formulated with Pro-Xylane™ and Hyaluronic Acid to visibly correct deep wrinkles and restore volume.', 3, 395.00, 135.00, 20, 10, 100, 'assets/images/products/1771527868_69975ebc67d47.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 19:04:28', '2026-02-19 19:04:28', 0, 0.00, 0),
(60, 'Sublimage La Crème', 'SUBLIMAG-900546', 'Sublimage La Crème represents the culmination of Chanel\'s research into the extraordinary properties of the Vanilla Planifolia flower from Madagascar. Unlike the vanilla used in perfumery, this specific variety is cultivated exclusively for skincare, with its pods hand-harvested at peak maturity to extract the most potent active molecules. The result is the Planifolia PFA (Polyfractioning Active) complex, a patented ingredient that targets all visible signs of aging. This cream visibly smooths wrinkles, improves firmness, and restores the radiant, even-toned complexion of youth. The sensorial experience is pure Chanel—the rich, silky texture melts into skin, releasing a subtle, sophisticated fragrance that makes application a daily luxury. The elegant black and white jar is a statement piece, reflecting the timeless elegance of the House of Chanel. It\'s the choice of women who appreciate the intersection of luxury, science, and French sophistication.', 'Chanel\'s ultimate anti-aging cream, harnessing the extraordinary regenerative power of the Vanilla Planifolia flower from Madagascar.', 3, 525.00, 180.00, 30, 10, 100, 'assets/images/products/1771527950_69975f0e64d07.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 19:05:50', '2026-02-19 19:05:50', 0, 0.00, 0),
(61, 'Prestige La Crème', 'PRESTIGE-030295', 'Dior Prestige La Crème is a love letter to the Rose de Granville, a flower so extraordinary that Dior cultivates it exclusively for this collection. Grown on the coast of Normandy under harsh conditions, this rose has developed remarkable resilience and regenerative properties. Dior\'s scientists discovered that its cells contain unique molecules that help skin defend against environmental stressors and maintain its youthful vitality. The cream features the exclusive Rose de Granville cell water and rose sap extracts, combined with micro-nutrients to visibly transform aging skin. With each application, wrinkles appear smoothed, firmness is restored, and complexion radiates with new life. The texture is exquisite—rich yet weightless, it glides onto skin and absorbs instantly, leaving a satiny finish. The rose-scented fragrance is subtle and sophisticated, turning daily application into a moment of self-care. Encased in a beautifully designed jar, this cream is as much a pleasure to behold as it is to use.', 'A rejuvenating cream harnessing the power of the Rose de Granville, hand-harvested and cultivated exclusively for Dior.', 3, 340.00, 115.00, 45, 10, 100, 'assets/images/products/1771528074_69975f8a67c0b.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 19:07:54', '2026-02-19 19:07:54', 0, 0.00, 0),
(62, 'Secret Programming Cream', 'SECRETPR-199214', 'Su:m37, the luxury Korean brand renowned for its fermentation-based skincare, offers its masterpiece in the Secret Programming Cream. This extraordinary moisturizer is built around the brand\'s exclusive Cytosis® complex, a fermentation-based ingredient that contains over 100 types of plant-derived active ingredients working in synergy to support skin\'s natural renewal cycle. The formula mimics the skin\'s own metabolic processes, helping to optimize cell turnover and regeneration for visibly younger-looking skin. With consistent use, wrinkles are smoothed, skin texture is refined, and a radiant, healthy glow emerges. The rich, nourishing texture is deeply comforting yet absorbs completely, leaving skin soft, supple, and transformed. Housed in an elegant glass jar with gold accents, this cream represents the pinnacle of K-beauty luxury and innovation. It\'s the choice of those who appreciate the traditional Korean fermentation wisdom combined with cutting-edge skincare science.', 'A luxurious Korean anti-aging cream featuring fermented ingredients and the brand\'s exclusive Cytosis® complex for skin regeneration.', 3, 290.00, 100.00, 60, 10, 100, 'assets/images/products/1771528242_69976032c35a6.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 19:10:42', '2026-02-19 19:10:42', 0, 0.00, 0),
(63, 'Time Response Intensive Cream', 'TIMERESP-331927', 'Amorepacific, Korea\'s premier luxury beauty house, has spent decades researching the extraordinary properties of green tea grown on Jeju Island. The Time Response Intensive Cream represents the pinnacle of this research, utilizing the rare and potent Divine Green Tea—a variety so exclusive that only the first harvest of the season, with the highest concentration of amino acids and antioxidants, is used. The tea plants are cultivated using traditional methods passed down through generations, and each leaf is hand-picked at dawn to preserve its potency. The formula features the patented AP Active Green Tea Fermented Extract, created through a unique fermentation process that unlocks the full regenerative potential of the tea. This cream visibly reverses multiple signs of aging—wrinkles appear diminished, skin regains its firmness and elasticity, and a radiant, youthful luminosity is restored. The rich, luxurious texture melts into skin, delivering intense nourishment and transformation with every application. It\'s the ultimate expression of Korean beauty philosophy: achieving radiant, healthy skin through harmony with nature and scientific innovation.', 'Amorepacific\'s most advanced anti-aging cream, harnessing the power of rare green tea from the brand\'s own Jeju Island tea fields.', 3, 480.00, 165.00, 45, 10, 100, 'assets/images/products/1771528385_699760c15b706.jpg', NULL, NULL, NULL, 0, 1, '2026-02-19 19:13:05', '2026-02-19 19:13:05', 0, 0.00, 0),
(64, 'The Timeless Radiance Complete Skincare Regimen', 'THETIMEL-485309', 'The Timeless Radiance Complete Skincare Regimen is the ultimate investment in your skin\'s future. This carefully curated collection brings together six of the most effective luxury skincare products, each selected for its exceptional formulation and proven results. Together, they form a complete daily and nightly routine that addresses every sign of aging—from fine lines and wrinkles to loss of firmness, dullness, and dehydration.\r\n\r\nDeveloped in consultation with leading dermatologists and skincare experts, this regimen follows the principle of layering active ingredients in the correct order for maximum efficacy. Each product complements and enhances the others, creating a synergistic effect greater than the sum of its parts. The result is visibly transformed skin—smoother, firmer, more radiant, and dramatically younger-looking.', 'A comprehensive 6-piece luxury skincare regimen featuring a curated selection of the world\'s most effective anti-aging products, designed to work synergistically for complete skin transformation.', 3, 895.00, 375.00, 0, 10, 100, 'assets/images/products/1771528536_699761583fafe.jpg', NULL, NULL, NULL, 1, 1, '2026-02-19 19:15:36', '2026-02-19 19:15:36', 0, 0.00, 0),
(65, 'Versace Eros Excellence Diamond Edition', 'VERSACEE-063419', 'The Versace Eros Excellence Diamond Edition represents the pinnacle of the house\'s fragrance artistry. Created for the most discerning collectors, this limited edition release transforms the iconic Eros fragrance into a true objet d\'art. Only 500 individually numbered bottles have been produced worldwide, each one hand-finished by master artisans in Italy.\r\n\r\nThe fragrance itself contains the highest concentration of Eros ever created—an extrait de parfum formulation featuring the finest ingredients sourced from around the world. The composition opens with top notes of Italian lemon, mandarin, and mint, leading to a heart of ambroxan, geranium, and clary sage, resting on a base of Madagascar vanilla, cedarwood, and sandalwood. This extrait concentration ensures exceptional longevity of 12+ hours on skin with remarkable sillage.', 'An ultra-exclusive limited edition presentation of the iconic Versace Eros, housed in a hand-crafted crystal flacon adorned with gold accents and presented in a luxury collector\'s case.', 4, 2100.00, 840.00, 0, 10, 100, 'assets/images/products/1771529126_699763a63925b.jpg', NULL, NULL, NULL, 1, 1, '2026-02-19 19:25:26', '2026-02-19 19:25:26', 0, 0.00, 0),
(66, 'The Age-Defying Regeneration Complete Skincare System', 'THEAGEDE-306830', 'The Age-Defying Regeneration Complete Skincare System represents the pinnacle of anti-aging science and luxury skincare. Developed over a decade by a team of Nobel Prize-winning cellular biologists and dermatologists, this comprehensive 9-piece system addresses every visible sign of aging at the cellular level. Unlike ordinary skincare that merely hydrates or temporarily plumps, this regimen actively communicates with skin cells to optimize their function, accelerate renewal, and restore youthful vitality.\r\n\r\nThe system is built around the patented RegenCell-9™ Complex, a proprietary blend of nine growth factors, peptides, and stem cell extracts that work synergistically to mimic the skin\'s natural regenerative processes. Clinical studies show visible results in as little as 7 days, with dramatic transformation by day 28.', 'A comprehensive 9-piece anti-aging skincare system featuring breakthrough regenerative technology, clinically proven to reverse visible signs of aging in just 28 days.', 3, 1250.00, 525.00, 0, 10, 100, 'assets/images/products/1771529359_6997648f91aec.jpg', NULL, NULL, NULL, 1, 1, '2026-02-19 19:29:19', '2026-02-19 19:29:19', 0, 0.00, 0);

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
(1, 'site_name', 'Jenny\'s Cosmetics & Jewelry', 'string', 'General', 'Website name', '2026-02-06 18:27:28'),
(2, 'site_email', 'info@jennyscosmetics.com', 'string', 'General', 'Default contact email', '2026-02-06 18:27:28'),
(3, 'currency', 'USD', 'string', 'General', 'Default currency', '2026-02-06 18:27:28'),
(4, 'tax_rate', '8.5', 'string', 'Order', 'Sales tax rate in percentage', '2026-02-06 18:27:28'),
(5, 'shipping_cost', '5.99', 'string', 'Order', 'Default shipping cost', '2026-02-06 18:27:28'),
(6, 'min_order_amount', '25.00', 'string', 'Order', 'Minimum order amount', '2026-02-06 18:27:28'),
(7, 'enable_registration', '1', 'boolean', 'User', 'Enable new user registration', '2026-02-06 18:27:28'),
(8, 'maintenance_mode', '0', 'boolean', 'System', 'Put site in maintenance mode', '2026-02-06 18:27:28');

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `phone_work`, `phone_cell`, `date_of_birth`, `address_street`, `address_city`, `address_state`, `address_zip`, `address_country`, `customer_category`, `remarks`, `registration_date`, `last_login`, `is_active`, `total_orders`, `total_spent`) VALUES
(1, 'Kabir', 'Baloch', 'kabirbaloch4444@gmail.com', '$2y$10$I4uYnDf.1ZB4IVV.e6yTCeH0H2lIJqlEUgUnB81PeSxMQX1sdLuV2', NULL, '+923102910900', NULL, 'Faqeer colony baloch walfare society', 'Karachi', NULL, NULL, 'Canada', 'Regular', NULL, '2026-02-13 18:29:56', NULL, 1, 0, 0.00),
(2, 'Kabir', 'Baloch', 'kabirkb654321@gmail.com', '$2y$10$/7hr7pVHCZ7YbPS.Iz/OgOHk.F8z77z18M63suS0JcGVNc7myRsMm', NULL, '+923102910900', NULL, '456 Oak Avenue, Apt 1B (or just 456 Oak Ave)', 'NEW YORK', NULL, NULL, '', 'Regular', NULL, '2026-02-20 05:22:44', '2026-02-22 16:02:25', 1, 0, 0.00),
(3, 'Kabir', 'Baloch', 'kabirfinish54321@gmail.com', '$2y$10$QfM4.txpMB4LcBIz/Np0CunxLhg4UUVf2MggdK1DYBTMG1YEEE0Uq', NULL, '+923102910900', NULL, '456 Oak Avenue, Apt 1B (or just 456 Oak Ave)', 'NEW YORK', NULL, NULL, '', 'Regular', NULL, '2026-02-22 20:09:27', '2026-02-25 04:08:58', 1, 0, 0.00);

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_daily_sales`  AS SELECT cast(`orders`.`order_date` as date) AS `sale_date`, count(0) AS `total_orders`, sum(`orders`.`total_amount`) AS `daily_revenue`, avg(`orders`.`total_amount`) AS `average_order_value` FROM `orders` WHERE `orders`.`status` in ('Delivered','Shipped') GROUP BY cast(`orders`.`order_date` as date) ORDER BY cast(`orders`.`order_date` as date) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_top_customers`
--
DROP TABLE IF EXISTS `vw_top_customers`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_top_customers`  AS SELECT `u`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `customer_name`, `u`.`email` AS `email`, `u`.`phone_cell` AS `phone_cell`, count(distinct `o`.`order_id`) AS `total_orders`, sum(`o`.`total_amount`) AS `total_spent`, max(`o`.`order_date`) AS `last_order_date` FROM (`users` `u` join `orders` `o` on(`u`.`user_id` = `o`.`user_id`)) WHERE `o`.`status` in ('Delivered','Shipped') GROUP BY `u`.`user_id`, `u`.`first_name`, `u`.`last_name`, `u`.`email`, `u`.`phone_cell` ORDER BY sum(`o`.`total_amount`) DESC LIMIT 0, 10 ;

-- --------------------------------------------------------

--
-- Structure for view `vw_top_selling_products`
--
DROP TABLE IF EXISTS `vw_top_selling_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_top_selling_products`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `p`.`sku` AS `sku`, `c`.`category_name` AS `category_name`, sum(`oi`.`quantity`) AS `total_quantity_sold`, sum(`oi`.`total_price`) AS `total_revenue`, count(distinct `o`.`order_id`) AS `total_orders` FROM (((`products` `p` join `order_items` `oi` on(`p`.`product_id` = `oi`.`product_id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`order_id`)) join `categories` `c` on(`p`.`category_id` = `c`.`category_id`)) WHERE `o`.`status` in ('Delivered','Shipped') GROUP BY `p`.`product_id`, `p`.`product_name`, `p`.`sku`, `c`.`category_name` ORDER BY sum(`oi`.`quantity`) DESC LIMIT 0, 10 ;

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
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
