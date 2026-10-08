<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();

// Fetch all destinations
$stmt = $db->query("SELECT * FROM destinations ORDER BY rating DESC");
$destinations = $stmt->fetchAll();

// Extract unique categories for filter bar
$categories = ['all', 'Beach', 'Mountain', 'Cultural', 'City', 'Adventure', 'Relaxation'];
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-earth-americas" style="color: var(--primary);"></i> Destination Suggestions & Insights</h1>
            <p class="page-subtitle">Discover curated travel spots around the globe with daily budget guidelines and best season tips.</p>
        </div>
        <button class="btn btn-primary" data-modal-target="new-trip-modal">
            <i class="fa-solid fa-plus"></i> Create Custom Trip
        </button>
    </div>

    <!-- Filter Buttons -->
    <div class="filter-bar">
        <?php foreach ($categories as $cat): ?>
            <button class="filter-btn <?= ($cat === 'all') ? 'active' : '' ?>" data-category="<?= strtolower($cat) ?>">
                <?= ucfirst($cat) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Destinations Grid -->
    <div class="grid-3">
        <?php foreach ($destinations as $dest): ?>
            <div class="card filterable-card" data-category="<?= strtolower($dest['category']) ?>">
                <div class="card-img-wrapper">
                    <img src="<?= htmlspecialchars($dest['image_url']) ?>" alt="<?= htmlspecialchars($dest['name']) ?>" class="card-img">
                    <span class="card-badge badge-amber"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($dest['category']) ?></span>
                    <div class="card-rating">
                        <i class="fa-solid fa-star"></i>
                        <span><?= number_format($dest['rating'], 2) ?></span>
                    </div>
                </div>

                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($dest['name']) ?>, <?= htmlspecialchars($dest['country']) ?></h3>
                    <p style="font-size: 0.82rem; color: var(--text-dim); margin-bottom: 0.6rem;">
                        <i class="fa-solid fa-compass"></i> Region: <?= htmlspecialchars($dest['region']) ?> &bull; 
                        <i class="fa-solid fa-sun"></i> Best: <?= htmlspecialchars($dest['best_season']) ?>
                    </p>
                    <p class="card-desc"><?= htmlspecialchars($dest['description']) ?></p>

                    <div style="margin-bottom: 1rem;">
                        <span class="badge badge-cyan"><?= htmlspecialchars($dest['tags']) ?></span>
                    </div>

                    <div class="card-meta">
                        <span class="card-price">
                            <?= format_currency($dest['avg_cost_per_day']) ?> <span>/ estimated day</span>
                        </span>
                        <button class="btn btn-primary btn-sm" onclick="planTripForDestination(<?= $dest['id'] ?>, '<?= addslashes($dest['name']) ?>')">
                            <i class="fa-solid fa-plane-departure"></i> Plan Trip
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
