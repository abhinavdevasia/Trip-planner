-- ============================================================
-- Travel Planner Database Schema (MySQL)
-- Database: travel_planner
-- ============================================================

CREATE DATABASE IF NOT EXISTS `travel_planner` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `travel_planner`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'USD',
    `avatar_url` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Destinations Table (Suggestions & Recommendations)
CREATE TABLE IF NOT EXISTS `destinations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `country` VARCHAR(100) NOT NULL,
    `region` VARCHAR(80) NOT NULL,
    `category` ENUM('Beach', 'Mountain', 'Cultural', 'City', 'Adventure', 'Relaxation') NOT NULL,
    `description` TEXT NOT NULL,
    `avg_cost_per_day` DECIMAL(10,2) NOT NULL,
    `best_season` VARCHAR(100) NOT NULL,
    `rating` DECIMAL(3,2) DEFAULT 4.80,
    `image_url` VARCHAR(500) NOT NULL,
    `tags` VARCHAR(255) DEFAULT 'Popular, Scenic, Highly Rated',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Trips Table
CREATE TABLE IF NOT EXISTS `trips` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `destination_id` INT DEFAULT NULL,
    `title` VARCHAR(150) NOT NULL,
    `location` VARCHAR(150) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_budget` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('Planned', 'Active', 'Completed') DEFAULT 'Planned',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Itinerary Items Table (Day-by-Day Activity Planner)
CREATE TABLE IF NOT EXISTS `itinerary_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `day_number` INT NOT NULL DEFAULT 1,
    `activity_date` DATE NOT NULL,
    `activity_time` TIME DEFAULT '09:00:00',
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `location` VARCHAR(150) DEFAULT NULL,
    `category` ENUM('Sightseeing', 'Dining', 'Transport', 'Activity', 'Accommodation') DEFAULT 'Sightseeing',
    `estimated_cost` DECIMAL(10,2) DEFAULT 0.00,
    `completed` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Expenses Table (Travel Expense Tracking)
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `trip_id` INT NOT NULL,
    `category` ENUM('Food & Dining', 'Transportation', 'Lodging', 'Activities & Sightseeing', 'Shopping', 'Miscellaneous') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `description` VARCHAR(255) NOT NULL,
    `payment_method` ENUM('Card', 'Cash', 'Mobile Pay', 'Other') DEFAULT 'Card',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`trip_id`) REFERENCES `trips`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Seed Sample Data
-- ============================================================

-- Seed User (Password: password123)
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `currency`, `avatar_url`) VALUES
(1, 'Alex Morgan', 'alex@example.com', '$2y$10$wT2H0L.gJ.LgKqW1g4z2ueQh0S9aV6oE2E4T4v.d8gJ3x9G0h0h0i', 'USD', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150');

-- Seed Destination Suggestions
INSERT INTO `destinations` (`id`, `name`, `country`, `region`, `category`, `description`, `avg_cost_per_day`, `best_season`, `rating`, `image_url`, `tags`) VALUES
(1, 'Kyoto', 'Japan', 'East Asia', 'Cultural', 'Immerse in ancient shrines, serene bamboo groves, and world-class traditional culinary art.', 140.00, 'Mar - May & Oct - Nov', 4.95, 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=800', 'Culture, Cherry Blossom, Temples'),
(2, 'Santorini', 'Greece', 'Europe', 'Beach', 'Iconic whitewashed villas overlooking the dazzling Aegean Sea with magical sunset views.', 220.00, 'May - September', 4.90, 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=800', 'Romantic, Sunset, Luxury'),
(3, 'Swiss Alps (Zermatt)', 'Switzerland', 'Europe', 'Mountain', 'Majestic peaks, world-class skiing, hiking trails, and cozy Alpine village atmosphere.', 260.00, 'Dec - Mar & Jun - Sep', 4.88, 'https://images.unsplash.com/photo-1530122037265-a5f1f91d3b99?w=800', 'Skiing, Hiking, Nature'),
(4, 'Bali', 'Indonesia', 'Southeast Asia', 'Relaxation', 'Tropical paradise featuring lush rice terraces, sacred temples, wellness retreats, and surf breaks.', 85.00, 'April - October', 4.85, 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=800', 'Tropical, Wellness, Budget-Friendly'),
(5, 'Paris', 'France', 'Europe', 'City', 'The City of Light offers world-renowned art museums, gourmet bakeries, and romantic boulevard walks.', 190.00, 'Apr - Jun & Sep - Nov', 4.82, 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800', 'Art, Architecture, Gastronomy'),
(6, 'Banff National Park', 'Canada', 'North America', 'Adventure', 'Turquoise glacial lakes, snow-capped Canadian Rockies, and abundant wildlife adventures.', 175.00, 'June - September', 4.92, 'https://images.unsplash.com/photo-1517411032315-54ef2cb783bb?w=800', 'Lakes, Wildlife, Hiking');

-- Seed Sample Trips
INSERT INTO `trips` (`id`, `user_id`, `destination_id`, `title`, `location`, `start_date`, `end_date`, `total_budget`, `status`, `notes`) VALUES
(1, 1, 1, 'Kyoto Autumn Escapade', 'Kyoto, Japan', '2026-11-10', '2026-11-17', 2200.00, 'Active', 'Remember to book Shinkansen bullet train passes in advance.'),
(2, 1, 4, 'Bali Summer Retreat', 'Bali, Indonesia', '2026-12-01', '2026-12-10', 1500.00, 'Planned', 'Focus on spa, yoga retreats in Ubud, and beach days in Seminyak.');

-- Seed Sample Itinerary Items for Trip 1
INSERT INTO `itinerary_items` (`id`, `trip_id`, `day_number`, `activity_date`, `activity_time`, `title`, `description`, `location`, `category`, `estimated_cost`, `completed`) VALUES
(1, 1, 1, '2026-11-10', '09:00:00', 'Fushimi Inari Shrine Hike', 'Walk through thousands of vibrant vermilion torii gates up Mt. Inari.', 'Fushimi Ward, Kyoto', 'Sightseeing', 0.00, 1),
(2, 1, 1, '2026-11-10', '13:00:00', 'Traditional Ramen Lunch at Gion', 'Savor authentic tonkotsu ramen in historic Gion district.', 'Gion, Kyoto', 'Dining', 25.00, 1),
(3, 1, 2, '2026-11-11', '08:30:00', 'Arashiyama Bamboo Grove Walk', 'Early morning stroll through the serene bamboo forest before crowd arrives.', 'Arashiyama', 'Activity', 15.00, 0),
(4, 1, 2, '2026-11-11', '14:00:00', 'Kinkaku-ji (Golden Pavilion)', 'Visit the iconic Zen temple covered in gold leaf overlooking the pond.', 'Kita Ward, Kyoto', 'Sightseeing', 12.00, 0);

-- Seed Sample Expenses for Trip 1
INSERT INTO `expenses` (`id`, `trip_id`, `category`, `amount`, `expense_date`, `description`, `payment_method`) VALUES
(1, 1, 'Transportation', 180.00, '2026-11-10', 'Haruka Express Train from KIX Airport to Kyoto', 'Card'),
(2, 1, 'Lodging', 650.00, '2026-11-10', 'Traditional Ryokan Stay (3 Nights)', 'Card'),
(3, 1, 'Food & Dining', 45.00, '2026-11-10', 'Welcome Kaiseki Dinner in Pontocho Alley', 'Cash'),
(4, 1, 'Activities & Sightseeing', 30.00, '2026-11-11', 'Tea Ceremony Experience Ticket', 'Card'),
(5, 1, 'Food & Dining', 22.00, '2026-11-11', 'Matcha Parfait & Street Food Snacks', 'Cash');
