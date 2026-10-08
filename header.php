<?php
require_once __DIR__ . '/config.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wanderlust - Intelligent Travel Planner</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<!-- Navigation Header -->
<nav class="navbar">
    <a href="index.php" class="brand">
        <div class="brand-icon">
            <i class="fa-solid fa-compass"></i>
        </div>
        <span>Wanderlust</span>
    </a>

    <ul class="nav-links">
        <li class="nav-item <?= ($current_page === 'index.php') ? 'active' : '' ?>">
            <a href="index.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
        </li>
        <li class="nav-item <?= ($current_page === 'destinations.php') ? 'active' : '' ?>">
            <a href="destinations.php"><i class="fa-solid fa-earth-americas"></i> Destinations</a>
        </li>
        <li class="nav-item <?= ($current_page === 'trips.php' || $current_page === 'trip_details.php') ? 'active' : '' ?>">
            <a href="trips.php"><i class="fa-solid fa-plane-departure"></i> My Trips</a>
        </li>
        <li class="nav-item <?= ($current_page === 'expenses.php') ? 'active' : '' ?>">
            <a href="expenses.php"><i class="fa-solid fa-wallet"></i> Expense Tracker</a>
        </li>
    </ul>

    <div class="nav-user">
        <div class="user-profile">
            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150" alt="Avatar" class="user-avatar">
            <span class="user-name">Alex Morgan</span>
        </div>
        <button class="btn btn-primary btn-sm" data-modal-target="new-trip-modal">
            <i class="fa-solid fa-plus"></i> New Trip
        </button>
    </div>
</nav>

<!-- Modal: Quick Create Trip -->
<div class="modal-overlay" id="new-trip-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-plane"></i> Plan New Trip</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="api.php" method="POST">
            <input type="hidden" name="action" value="create_trip">
            <div class="form-group">
                <label class="form-label">Trip Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Summer Vacation in Tokyo" required>
            </div>
            <div class="form-group">
                <label class="form-label">Destination Suggestion (Optional)</label>
                <select name="destination_id" class="form-control">
                    <option value="">Custom Location / Select Suggestion</option>
                    <?php
                    $db = get_db_connection();
                    $stmt = $db->query("SELECT id, name, country FROM destinations ORDER BY name ASC");
                    while ($d = $stmt->fetch()) {
                        echo "<option value='{$d['id']}'>" . htmlspecialchars($d['name']) . ", " . htmlspecialchars($d['country']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Location / City</label>
                <input type="text" name="location" class="form-control" placeholder="e.g. Kyoto, Japan" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Total Budget ($)</label>
                <input type="number" step="0.01" name="total_budget" class="form-control" placeholder="2000.00" required>
            </div>
            <div class="form-group">
                <label class="form-label">Notes & Goals</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Important reminders, travel goals..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-location-arrow"></i> Create Trip</button>
            </div>
        </form>
    </div>
</div>
