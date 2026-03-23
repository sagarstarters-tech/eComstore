-- phpMyAdmin SQL Dump
-- Shipping Module Structure

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `shipping_settings`
-- Stores global toggles (e.g. Free shipping eligible)
--

CREATE TABLE IF NOT EXISTS `shipping_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping default settings
INSERT IGNORE INTO `shipping_settings` (`setting_key`, `setting_value`, `description`) VALUES
('free_shipping_enabled', '1', '1=Yes, 0=No'),
('free_shipping_min_amount', '1000.00', 'Minimum cart value required for free standard shipping'),
('default_flat_rate', '80.00', 'Standard flat rate shipping block'),
('tax_on_shipping_percentage', '0', 'Tax percentage applied strictly to shipping cost. (0 for none)'),
('cod_extra_charge', '50.00', 'Additional fee for Cash On Delivery selection');


-- --------------------------------------------------------

--
-- Table structure for table `shipping_methods`
-- E.g Standard, Express, Same Day
--

CREATE TABLE IF NOT EXISTS `shipping_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `estimated_delivery` varchar(50) DEFAULT NULL,
  `base_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Defaults
INSERT IGNORE INTO `shipping_methods` (`name`, `description`, `estimated_delivery`, `base_cost`, `is_active`, `display_order`) VALUES
('Standard Shipping', 'Regular delivery via common carriers.', '3-5 Business Days', 80.00, 1, 1),
('Express Shipping', 'Priority fastest method available.', '1-2 Business Days', 150.00, 1, 2),
('Same Day Delivery', 'Local delivery before 8PM.', 'Today', 250.00, 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `shipping_zones`
-- e.g "North India", "Metro Cities", "International"
--

CREATE TABLE IF NOT EXISTS `shipping_zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `shipping_rules`
-- For complex condition handling (e.g weight based)
--

CREATE TABLE IF NOT EXISTS `shipping_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) NOT NULL,
  `rule_type` enum('weight_based', 'category_based', 'qty_based', 'pincode_based') NOT NULL,
  `condition_value` text NOT NULL COMMENT 'JSON or CSV representing threshold (e.g > 5kg)',
  `fee_modifier` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Value to add/subtract or multiply based on rule',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `order_shipping_details`
-- Ties an order to the shipping module logs for historical records
--

CREATE TABLE IF NOT EXISTS `order_shipping_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `shipping_method_id` int(11) DEFAULT NULL,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_estimate` varchar(100) DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipping_provider` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
