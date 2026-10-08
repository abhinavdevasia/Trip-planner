<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$user_id = get_current_user_id();

// Fetch summary metrics
$stmt_trips_cnt = $db->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ?");
$stmt_trips_cnt->execute([$user_id]);
$total_trips = $stmt_trips_cnt->fetchColumn();

$stmt_exp_sum = $db->prepare("SELECT SUM(e.amount) FROM expenses e JOIN trips t ON e.trip_id = t.id WHERE t.user_id = ?");
$stmt_exp_sum->execute([$user_id]);
$total_expenses = $stmt_exp_sum->fetchColumn() ?: 0.00;

$stmt_dest_cnt = $db->query("SELECT COUNT(*) FROM destinations");
$total_destinations = $stmt_dest_cnt->fetchColumn();

// Fetch active & upcoming trips
$stmt_trips = $db->prepare("
    SELECT t.*, d.image_url as dest_image, 
           (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE trip_id = t.id) as spent
    FROM trips t
    LEFT JOIN destinations d ON t.destination_id = d.id
    WHERE t.user_id = ?
    ORDER BY t.start_date ASC
");
$stmt_trips->execute([$user_id]);
$trips = $stmt_trips->fetchAll();

// Fetch top destination suggestions
$stmt_dest = $db->query("SELECT * FROM destinations ORDER BY rating DESC LIMIT 3");
$suggested_destinations = $stmt_dest->fetchAll();
?>

<div class="container">
    <!-- Hero Banner -->
    <div class="hero-banner">
        <div class="hero-content">
            <div class="hero-tag">
                <i class="fa-solid fa-sparkles"></i> AI-Powered Travel Intelligence
            </div>
            <h1 class="hero-title">Where will your next adventure take you?</h1>
            <p class="hero-desc">
                Seamlessly design day-by-day itineraries, track budgets in real-time, and discover handpicked world destinations tailored for your travel style.
            </p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn btn-primary" data-modal-target="new-trip-modal">
                    <i class="fa-solid fa-plane-departure"></i> Plan a New Trip
                </button>
                <a href="destinations.php" class="btn btn-secondary">
                    <i class="fa-solid fa-compass"></i> Explore Suggestions
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Overview Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon cyan">
                <i class="fa-solid fa-route"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_trips ?></div>
                <div class="stat-label">Total Planned Trips</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon emerald">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= format_currency($total_expenses) ?></div>
                <div class="stat-label">Total Expenses Tracked</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon indigo">
                <i class="fa-solid fa-earth-asia"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= $total_destinations ?></div>
                <div class="stat-label">Curated Destinations</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon coral">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value">94.2%</div>
                <div class="stat-label">Budget Accuracy Rate</div>
            </div>
        </div>
    </div>

    <!-- Active & Planned Trips -->
    <div class="section-header">
        <div>
            <h2 class="section-title"><i class="fa-solid fa-suitcase-rolling" style="color: var(--primary);"></i> Your Upcoming & Active Trips</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Manage itineraries and track live trip expenses</p>
        </div>
        <a href="trips.php" class="btn btn-secondary btn-sm">View All Trips &rarr;</a>
    </div>

    <?php if (empty($trips)): ?>
        <div style="text-align: center; padding: 3rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color); margin-bottom: 3rem;">
            <i class="fa-solid fa-plane-slash" style="font-size: 2.5rem; color: var(--text-dim); margin-bottom: 1rem;"></i>
            <h3>No trips created yet!</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start planning your dream vacation by setting up a destination and budget.</p>
            <button class="btn btn-primary" data-modal-target="new-trip-modal">
                <i class="fa-solid fa-plus"></i> Create First Trip
            </button>
        </div>
    <?php else: ?>
        <div class="grid-3" style="margin-bottom: 3.5rem;">
            <?php foreach ($trips as $trip): 
                $img = $trip['dest_image'] ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800';
                $pct = ($trip['total_budget'] > 0) ? min(100, round(($trip['spent'] / $trip['total_budget']) * 100)) : 0;
                
                $status_class = 'badge-cyan';
                if ($trip['status'] === 'Active') $status_class = 'badge-emerald';
                if ($trip['status'] === 'Completed') $status_class = 'badge-rose';
            ?>
                <div class="card">
                    <div class="card-img-wrapper">
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($trip['title']) ?>" class="card-img">
                        <span class="card-badge <?= $status_class ?>"><?= htmlspecialchars($trip['status']) ?></span>
                        <div class="card-rating">
                            <i class="fa-solid fa-calendar-days"></i>
                            <span><?= date('M d', strtotime($trip['start_date'])) ?> - <?= date('M d, Y', strtotime($trip['end_date'])) ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($trip['title']) ?></h3>
                        <p class="card-desc"><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?= htmlspecialchars($trip['location']) ?></p>
                        
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600;">
                                <span style="color: var(--text-muted);">Budget Spent</span>
                                <span><?= format_currency($trip['spent']) ?> / <?= format_currency($trip['total_budget']) ?></span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill <?= ($pct > 90) ? 'danger' : (($pct > 75) ? 'warning' : '') ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>

                        <div class="card-meta">
                            <span class="card-price"><?= format_currency($trip['total_budget']) ?> <span>total budget</span></span>
                            <a href="trip_details.php?id=<?= $trip['id'] ?>" class="btn btn-secondary btn-sm">
                                Manage Trip &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Destination Suggestions Preview -->
    <div class="section-header">
        <div>
            <h2 class="section-title"><i class="fa-solid fa-compass" style="color: var(--accent);"></i> Trending Destination Suggestions</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Handpicked travel spots with daily cost estimates</p>
        </div>
        <a href="destinations.php" class="btn btn-secondary btn-sm">Explore All &rarr;</a>
    </div>

    <div class="grid-3">
        <?php foreach ($suggested_destinations as $dest): ?>
            <div class="card">
                <div class="card-img-wrapper">
                    <img src="<?= htmlspecialchars($dest['image_url']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>" class="card-img">
                    <span class="card-badge badge-amber"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($dest['category']) ?></span>
                    <div class="card-rating">
                        <i class="fa-solid fa-star"></i>
                        <span><?= number_format($dest['rating'], 1) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($dest['name']) ?>, <?= htmlspecialchars($dest['country']) ?></h3>
                    <p class="card-desc"><?= htmlspecialchars($dest['description']) ?></p>

                    <div class="card-meta">
                        <span class="card-price"><?= format_currency($dest['avg_cost_per_day']) ?> <span>/ avg day</span></span>
                        <button class="btn btn-primary btn-sm" onclick="planTripForDestination(<?= $dest['id'] ?>, '<?= addslashes($dest['name']) ?>')">
                            <i class="fa-solid fa-plus"></i> Plan Trip
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
