<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$user_id = get_current_user_id();

// Fetch trips with total spent
$stmt = $db->prepare("
    SELECT t.*, d.image_url as dest_image,
           (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE trip_id = t.id) as spent,
           (SELECT COUNT(*) FROM itinerary_items WHERE trip_id = t.id) as itinerary_count
    FROM trips t
    LEFT JOIN destinations d ON t.destination_id = d.id
    WHERE t.user_id = ?
    ORDER BY t.start_date DESC
");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-plane-departure" style="color: var(--primary);"></i> My Travel Adventures</h1>
            <p class="page-subtitle">Manage your ongoing, upcoming, and past travel itineraries and spending.</p>
        </div>
        <button class="btn btn-primary" data-modal-target="new-trip-modal">
            <i class="fa-solid fa-plus"></i> Create New Trip
        </button>
    </div>

    <!-- Filter Buttons -->
    <div class="filter-bar">
        <button class="filter-btn active" data-category="all">All Trips</button>
        <button class="filter-btn" data-category="active">Active</button>
        <button class="filter-btn" data-category="planned">Planned</button>
        <button class="filter-btn" data-category="completed">Completed</button>
    </div>

    <?php if (empty($trips)): ?>
        <div style="text-align: center; padding: 4rem; background: var(--bg-surface); border-radius: var(--radius-md); border: 1px dashed var(--border-color);">
            <i class="fa-solid fa-compass" style="font-size: 3rem; color: var(--text-dim); margin-bottom: 1rem;"></i>
            <h2>No trips logged yet</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Start creating your custom itinerary or choose from destination suggestions.</p>
            <button class="btn btn-primary" data-modal-target="new-trip-modal">
                <i class="fa-solid fa-plus"></i> Create Trip
            </button>
        </div>
    <?php else: ?>
        <div class="grid-3">
            <?php foreach ($trips as $trip): 
                $img = $trip['dest_image'] ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800';
                $pct = ($trip['total_budget'] > 0) ? min(100, round(($trip['spent'] / $trip['total_budget']) * 100)) : 0;
                
                $status_class = 'badge-cyan';
                if ($trip['status'] === 'Active') $status_class = 'badge-emerald';
                if ($trip['status'] === 'Completed') $status_class = 'badge-rose';
            ?>
                <div class="card filterable-card" data-category="<?= strtolower($trip['status']) ?>">
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
                        <p class="card-desc">
                            <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?= htmlspecialchars($trip['location']) ?><br>
                            <span style="font-size: 0.85rem; color: var(--text-dim);">
                                <i class="fa-solid fa-list-check"></i> <?= $trip['itinerary_count'] ?> Itinerary Activities
                            </span>
                        </p>

                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600;">
                                <span style="color: var(--text-muted);">Budget Used</span>
                                <span><?= format_currency($trip['spent']) ?> / <?= format_currency($trip['total_budget']) ?></span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill <?= ($pct > 90) ? 'danger' : (($pct > 75) ? 'warning' : '') ?>" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>

                        <div class="card-meta">
                            <form action="api.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this trip?');">
                                <input type="hidden" name="action" value="delete_trip">
                                <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Delete Trip">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                            <a href="trip_details.php?id=<?= $trip['id'] ?>" class="btn btn-primary btn-sm">
                                Open Workspace &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
