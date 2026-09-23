-- FreshCart - Grocery Ordering System Database Schema
-- Database: freshcart_db

CREATE DATABASE IF NOT EXISTS `freshcart_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `freshcart_db`;

-- Drop tables in order of foreign key dependency
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('customer', 'admin') DEFAULT 'customer',
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `pincode` VARCHAR(20) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Products Table
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `unit` VARCHAR(50) NOT NULL DEFAULT 'kg',
  `image` VARCHAR(255) NULL,
  `status` ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Cart Table
CREATE TABLE `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `delivery_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivered', 'Cancelled') DEFAULT 'Pending',
  `delivery_name` VARCHAR(100) NOT NULL,
  `delivery_phone` VARCHAR(20) NOT NULL,
  `delivery_address` TEXT NOT NULL,
  `delivery_city` VARCHAR(100) NOT NULL,
  `delivery_pincode` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Order Items Table
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- SEED DATA
-- ==========================================

-- Insert Users (Password for Admin: Admin@123 | Password for Customer: Customer@123)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `address`, `city`, `pincode`) VALUES
(1, 'Store Administrator', 'admin@freshcart.com', '9876543210', '$2y$10$ZFkRO5l1DxgNT3LmjluZkOtkLY7qn1Pl0NABN7w/M5ZaC5kxHFJIK', 'admin', 'Head Office, DailyBasket Market Road', 'Mumbai', '400001'),
(2, 'Rahul Sharma', 'customer@freshcart.com', '9123456780', '$2y$10$6eHYA/ya9O6aSCq0wnOYbO8nnv/qq1RXjSMZ0.1vMdSY4keyOPRg.', 'customer', 'Flat 402, Green Meadows, MG Road', 'Mumbai', '400053');

-- Insert Categories
INSERT INTO `categories` (`id`, `name`, `description`, `image`) VALUES
(1, 'Fruits', 'Fresh and handpicked seasonal orchard fruits', 'fruits.png'),
(2, 'Vegetables', 'Farm-fresh organic and daily essential vegetables', 'vegetables.png'),
(3, 'Dairy', 'Pure milk, butter, cheese, and fresh curd', 'dairy.png'),
(4, 'Rice & Grains', 'Premium basmati rice, whole wheat, and pulses', 'grains.png'),
(5, 'Snacks', 'Crispy chips, biscuits, namkeen, and savory treats', 'snacks.png'),
(6, 'Beverages', 'Refreshing juices, tea, roasted coffee, and soft drinks', 'beverages.png'),
(7, 'Bakery', 'Freshly baked artisanal bread, buns, and cookies', 'bakery.png'),
(8, 'Household', 'Daily home cleaning, dishwashing, and essentials', 'household.png');

-- Insert Products
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `unit`, `image`, `status`) VALUES
-- Fruits
(1, 1, 'Royal Gala Apples', 'Crisp, sweet, and juicy imported red apples packed with vitamins.', 180.00, 45, 'kg', 'assets/images/products/apple.jpg', 'active'),
(2, 1, 'Robusta Bananas', 'Naturally ripened, energy-rich fresh bananas.', 50.00, 60, 'dozen', 'assets/images/products/banana.jpg', 'active'),
(3, 1, 'Nagpur Oranges', 'Sweet, tangy, vitamin-C rich citrus oranges.', 90.00, 30, 'kg', 'assets/images/products/orange.jpg', 'active'),
(4, 1, 'Sweet Pomegranate (Anar)', 'Deep ruby-red arils, super rich in antioxidants and iron.', 220.00, 18, 'kg', 'assets/images/products/pomegranate.jpg', 'active'),

-- Vegetables
(5, 2, 'Farm Fresh Tomatoes', 'Plump, firm, and ripe red hybrid cooking tomatoes.', 40.00, 5, 'kg', 'assets/images/products/tomato.jpg', 'active'),
(6, 2, 'Golden Potatoes', 'Freshly harvested dirt-free versatile cooking potatoes.', 35.00, 80, 'kg', 'assets/images/products/potato.jpg', 'active'),
(7, 2, 'Nashik Red Onions', 'Pungent, crispy essential cooking onions.', 45.00, 75, 'kg', 'assets/images/products/onion.jpg', 'active'),
(8, 2, 'Crunchy Orange Carrots', 'Tender and sweet local carrots, perfect for salads and cooking.', 55.00, 25, 'kg', 'assets/images/products/carrot.jpg', 'active'),
(9, 2, 'Green Capsicum (Bell Pepper)', 'Fresh crunchy bell peppers for stir fries and curries.', 65.00, 0, 'kg', 'assets/images/products/capsicum.jpg', 'out_of_stock'),

-- Dairy
(10, 3, 'Amul Taaza Toned Milk', 'Pasteurized homogenized toned milk pouch.', 56.00, 50, 'liter', 'assets/images/products/milk.jpg', 'active'),
(11, 3, 'Fresh Creamy Curd (Dahi)', 'Thick, creamy set curd prepared from pure cow milk.', 40.00, 35, '500g', 'assets/images/products/curd.jpg', 'active'),
(12, 3, 'Amul Salted Butter', 'Iconic Indian salted table butter made from wholesome cream.', 58.00, 40, '100g', 'assets/images/products/butter.jpg', 'active'),
(13, 3, 'Processed Cheddar Cheese Slices', 'Smooth melting cheese slices for burgers and sandwiches.', 140.00, 20, 'pack', 'assets/images/products/cheese.jpg', 'active'),

-- Rice & Grains
(14, 4, 'India Gate Basmati Rice Feast', 'Aromatic aged long-grain basmati rice for biryanis.', 130.00, 60, 'kg', 'assets/images/products/rice.jpg', 'active'),
(15, 4, 'Aashirvaad Shudh Chakki Atta', '100% pure whole wheat grain flour with zero maida.', 60.00, 45, 'kg', 'assets/images/products/wheat.jpg', 'active'),
(16, 4, 'Refined White Sugar', 'Clean, sparkling sulphurless crystal sugar.', 48.00, 100, 'kg', 'assets/images/products/sugar.jpg', 'active'),
(17, 4, 'Fortune Sunlite Sunflower Oil', 'Light and healthy enriched refined cooking sunflower oil.', 155.00, 30, 'liter', 'assets/images/products/cooking_oil.jpg', 'active'),

-- Snacks
(18, 5, 'Britannia Good Day Butter Biscuits', 'Rich butter cookies with delightful crunchy cashew bites.', 35.00, 50, 'pack', 'assets/images/products/biscuits.jpg', 'active'),
(19, 5, 'Lay\'s India\'s Magic Masala Chips', 'Crispy ridge-cut potato chips seasoned with spicy masala.', 20.00, 90, 'pack', 'assets/images/products/chips.jpg', 'active'),
(20, 5, 'Haldiram\'s Aloo Bhujia', 'Spicy crispy mint potato sev namkeen snack.', 55.00, 40, '200g', 'assets/images/products/namkeen.jpg', 'active'),

-- Beverages
(21, 6, 'Tata Tea Gold Leaf & Dust', 'Fine tea blend with 15% gently rolled aromatic long leaves.', 145.00, 35, '250g', 'assets/images/products/tea.jpg', 'active'),
(22, 6, 'Nescafé Classic Instant Coffee', 'Rich aroma and pure roasted coffee beans granules.', 195.00, 25, '100g', 'assets/images/products/coffee.jpg', 'active'),
(23, 6, 'Real Activ 100% Orange Juice', 'Refreshing pure orange fruit juice with zero added sugar.', 120.00, 4, 'liter', 'assets/images/products/juice.jpg', 'active'),

-- Bakery
(24, 7, 'Britannia 100% Whole Wheat Bread', 'Soft, high-fiber freshly baked brown bread loaf.', 45.00, 20, 'pack', 'assets/images/products/bread.jpg', 'active'),
(25, 7, 'Fresh Pav Buns (Pack of 6)', 'Super soft bakery pav buns, perfect for vada pav or bhaji.', 30.00, 30, 'pack', 'assets/images/products/buns.jpg', 'active'),

-- Household
(26, 8, 'Vim Lemon Dishwash Liquid Gel', 'Concentrated lemon degreaser gel for squeaky clean utensils.', 115.00, 40, '500ml', 'assets/images/products/dishwash.jpg', 'active'),
(27, 8, 'Surf Excel Easy Wash Detergent', 'Superior stain removal washing powder for fabrics.', 140.00, 30, 'kg', 'assets/images/products/detergent.jpg', 'active');

-- Sample Initial Orders for Demo Data
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `delivery_charge`, `status`, `delivery_name`, `delivery_phone`, `delivery_address`, `delivery_city`, `delivery_pincode`, `created_at`) VALUES
(1001, 2, 450.00, 30.00, 'Delivered', 'Rahul Sharma', '9123456780', 'Flat 402, Green Meadows, MG Road', 'Mumbai', '400053', '2026-09-18 10:30:00'),
(1002, 2, 335.00, 30.00, 'Preparing', 'Rahul Sharma', '9123456780', 'Flat 402, Green Meadows, MG Road', 'Mumbai', '400053', '2026-09-22 14:15:00'),
(1003, 2, 595.00, 0.00, 'Pending', 'Rahul Sharma', '9123456780', 'Flat 402, Green Meadows, MG Road', 'Mumbai', '400053', '2026-09-23 09:10:00');

-- Sample Order Items
INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES
(1001, 14, 'India Gate Basmati Rice Feast', 2, 130.00, 260.00),
(1001, 1, 'Royal Gala Apples', 1, 180.00, 180.00),
(1002, 10, 'Amul Taaza Toned Milk', 2, 56.00, 112.00),
(1002, 18, 'Britannia Good Day Butter Biscuits', 3, 35.00, 105.00),
(1002, 21, 'Tata Tea Gold Leaf & Dust', 1, 145.00, 145.00),
(1003, 17, 'Fortune Sunlite Sunflower Oil', 2, 155.00, 310.00),
(1003, 15, 'Aashirvaad Shudh Chakki Atta', 3, 60.00, 180.00),
(1003, 24, 'Britannia 100% Whole Wheat Bread', 2, 45.00, 90.00);

-- Initial Cart item for Rahul Sharma (to demonstrate cart functionality)
INSERT INTO `cart` (`user_id`, `product_id`, `quantity`) VALUES
(2, 5, 2),
(2, 10, 1);
