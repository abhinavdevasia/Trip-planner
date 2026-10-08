<?php
// ============================================================
// Travel Planner Configuration & Database Handler
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Default MySQL Settings)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'travel_planner');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get PDO Database Connection with Automatic Fallback to SQLite if MySQL is not available.
 * This guarantees the web app runs seamlessly in all PHP environments.
 */
function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $db_driver = 'mysql';

    try {
        // Attempt MySQL Connection
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback to SQLite for instant local preview if MySQL is offline
        try {
            $sqlite_path = __DIR__ . '/travel_planner.sqlite';
            $is_new = !file_exists($sqlite_path);
            $pdo = new PDO("sqlite:" . $sqlite_path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            if ($is_new) {
                initialize_sqlite_db($pdo);
            }
            return $pdo;
        } catch (Exception $ex) {
            die("<div style='font-family:sans-serif; padding:40px; text-align:center;'>
                <h2>Database Connection Failed</h2>
                <p>MySQL Error: " . htmlspecialchars($e->getMessage()) . "</p>
                <p>SQLite Fallback Error: " . htmlspecialchars($ex->getMessage()) . "</p>
            </div>");
        }
    }
}

/**
 * Auto-initialize SQLite database schema if running in fallback mode
 */
function initialize_sqlite_db($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            currency TEXT DEFAULT 'USD',
            avatar_url TEXT DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS destinations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            country TEXT NOT NULL,
            region TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT NOT NULL,
            avg_cost_per_day REAL NOT NULL,
            best_season TEXT NOT NULL,
            rating REAL DEFAULT 4.80,
            image_url TEXT NOT NULL,
            tags TEXT DEFAULT 'Popular, Scenic',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS trips (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            destination_id INTEGER,
            title TEXT NOT NULL,
            location TEXT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            total_budget REAL NOT NULL DEFAULT 0.00,
            status TEXT DEFAULT 'Planned',
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS itinerary_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            trip_id INTEGER NOT NULL,
            day_number INTEGER NOT NULL DEFAULT 1,
            activity_date DATE NOT NULL,
            activity_time TEXT DEFAULT '09:00:00',
            title TEXT NOT NULL,
            description TEXT,
            location TEXT,
            category TEXT DEFAULT 'Sightseeing',
            estimated_cost REAL DEFAULT 0.00,
            completed INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            trip_id INTEGER NOT NULL,
            category TEXT NOT NULL,
            amount REAL NOT NULL,
            expense_date DATE NOT NULL,
            description TEXT NOT NULL,
            payment_method TEXT DEFAULT 'Card',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Seed Data for SQLite
    $pdo->exec("
        INSERT INTO users (id, full_name, email, password, currency, avatar_url) VALUES
        (1, 'Alex Morgan', 'alex@example.com', '\$2y\$10\$wT2H0L.gJ.LgKqW1g4z2ueQh0S9aV6oE2E4T4v.d8gJ3x9G0h0h0i', 'USD', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150');

        INSERT INTO destinations (id, name, country, region, category, description, avg_cost_per_day, best_season, rating, image_url, tags) VALUES
        (1, 'Kyoto', 'Japan', 'East Asia', 'Cultural', 'Immerse in ancient shrines, serene bamboo groves, and world-class traditional culinary art.', 140.00, 'Mar - May & Oct - Nov', 4.95, 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=800', 'Culture, Cherry Blossom, Temples'),
        (2, 'Santorini', 'Greece', 'Europe', 'Beach', 'Iconic whitewashed villas overlooking the dazzling Aegean Sea with magical sunset views.', 220.00, 'May - September', 4.90, 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=800', 'Romantic, Sunset, Luxury'),
        (3, 'Swiss Alps (Zermatt)', 'Switzerland', 'Europe', 'Mountain', 'Majestic peaks, world-class skiing, hiking trails, and cozy Alpine village atmosphere.', 260.00, 'Dec - Mar & Jun - Sep', 4.88, 'https://images.unsplash.com/photo-1530122037265-a5f1f91d3b99?w=800', 'Skiing, Hiking, Nature'),
        (4, 'Bali', 'Indonesia', 'Southeast Asia', 'Relaxation', 'Tropical paradise featuring lush rice terraces, sacred temples, wellness retreats, and surf breaks.', 85.00, 'April - October', 4.85, 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=800', 'Tropical, Wellness, Budget-Friendly'),
        (5, 'Paris', 'France', 'Europe', 'City', 'The City of Light offers world-renowned art museums, gourmet bakeries, and romantic boulevard walks.', 190.00, 'Apr - Jun & Sep - Nov', 4.82, 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800', 'Art, Architecture, Gastronomy'),
        (6, 'Banff National Park', 'Canada', 'North America', 'Adventure', 'Turquoise glacial lakes, snow-capped Canadian Rockies, and abundant wildlife adventures.', 175.00, 'June - September', 4.92, 'https://images.unsplash.com/photo-1517411032315-54ef2cb783bb?w=800', 'Lakes, Wildlife, Hiking');

        INSERT INTO trips (id, user_id, destination_id, title, location, start_date, end_date, total_budget, status, notes) VALUES
        (1, 1, 1, 'Kyoto Autumn Escapade', 'Kyoto, Japan', '2026-11-10', '2026-11-17', 2200.00, 'Active', 'Remember to book Shinkansen bullet train passes in advance.'),
        (2, 1, 4, 'Bali Summer Retreat', 'Bali, Indonesia', '2026-12-01', '2026-12-10', 1500.00, 'Planned', 'Focus on spa, yoga retreats in Ubud, and beach days in Seminyak.');

        INSERT INTO itinerary_items (id, trip_id, day_number, activity_date, activity_time, title, description, location, category, estimated_cost, completed) VALUES
        (1, 1, 1, '2026-11-10', '09:00:00', 'Fushimi Inari Shrine Hike', 'Walk through thousands of vibrant vermilion torii gates up Mt. Inari.', 'Fushimi Ward, Kyoto', 'Sightseeing', 0.00, 1),
        (2, 1, 1, '2026-11-10', '13:00:00', 'Traditional Ramen Lunch at Gion', 'Savor authentic tonkotsu ramen in historic Gion district.', 'Gion, Kyoto', 'Dining', 25.00, 1),
        (3, 1, 2, '2026-11-11', '08:30:00', 'Arashiyama Bamboo Grove Walk', 'Early morning stroll through the serene bamboo forest before crowd arrives.', 'Arashiyama', 'Activity', 15.00, 0),
        (4, 1, 2, '2026-11-11', '14:00:00', 'Kinkaku-ji (Golden Pavilion)', 'Visit the iconic Zen temple covered in gold leaf overlooking the pond.', 'Kita Ward, Kyoto', 'Sightseeing', 12.00, 0);

        INSERT INTO expenses (id, trip_id, category, amount, expense_date, description, payment_method) VALUES
        (1, 1, 'Transportation', 180.00, '2026-11-10', 'Haruka Express Train from KIX Airport to Kyoto', 'Card'),
        (2, 1, 'Lodging', 650.00, '2026-11-10', 'Traditional Ryokan Stay (3 Nights)', 'Card'),
        (3, 1, 'Food & Dining', 45.00, '2026-11-10', 'Welcome Kaiseki Dinner in Pontocho Alley', 'Cash'),
        (4, 1, 'Activities & Sightseeing', 30.00, '2026-11-11', 'Tea Ceremony Experience Ticket', 'Card'),
        (5, 1, 'Food & Dining', 22.00, '2026-11-11', 'Matcha Parfait & Street Food Snacks', 'Cash');
    ");
}

// Initialize default guest user if session is unassigned
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Alex Morgan';
    $_SESSION['user_email'] = 'alex@example.com';
    $_SESSION['currency'] = '$';
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? 1;
}

function format_currency($amount, $symbol = '$') {
    return $symbol . number_format((float)$amount, 2);
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
