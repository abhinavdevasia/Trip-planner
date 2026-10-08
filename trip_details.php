<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$user_id = get_current_user_id();
$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch trip details
$stmt = $db->prepare("
    SELECT t.*, d.image_url as dest_image
    FROM trips t
    LEFT JOIN destinations d ON t.destination_id = d.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$trip_id, $user_id]);
$trip = $stmt->fetch();

if (!$trip) {
    echo "<div class='container' style='padding: 5rem 0; text-align: center;'><h2>Trip Not Found</h2><p><a href='trips.php' class='btn btn-primary'>Back to Trips</a></p></div>";
    require_once __DIR__ . '/footer.php';
    exit;
}

// Fetch itinerary items grouped by day
$stmt_itin = $db->prepare("SELECT * FROM itinerary_items WHERE trip_id = ? ORDER BY day_number ASC, activity_time ASC");
$stmt_itin->execute([$trip_id]);
$itinerary_items = $stmt_itin->fetchAll();

// Group itinerary by day
$itinerary_by_day = [];
foreach ($itinerary_items as $item) {
    $itinerary_by_day[$item['day_number']][] = $item;
}

// Fetch expenses for this trip
$stmt_exp = $db->prepare("SELECT * FROM expenses WHERE trip_id = ? ORDER BY expense_date DESC, id DESC");
$stmt_exp->execute([$trip_id]);
$expenses = $stmt_exp->fetchAll();

// Calculate total expenses and breakdown by category
$total_spent = 0;
$expenses_by_cat = [
    'Food & Dining' => 0,
    'Transportation' => 0,
    'Lodging' => 0,
    'Activities & Sightseeing' => 0,
    'Shopping' => 0,
    'Miscellaneous' => 0
];

foreach ($expenses as $exp) {
    $total_spent += $exp['amount'];
    if (isset($expenses_by_cat[$exp['category']])) {
        $expenses_by_cat[$exp['category']] += $exp['amount'];
    } else {
        $expenses_by_cat['Miscellaneous'] += $exp['amount'];
    }
}

$remaining_budget = $trip['total_budget'] - $total_spent;
$budget_pct = ($trip['total_budget'] > 0) ? min(100, round(($total_spent / $trip['total_budget']) * 100)) : 0;
$img_url = $trip['dest_image'] ?: 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=800';
?>

<div class="container">
    <!-- Trip Banner Header -->
    <div style="position: relative; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 2.5rem; border: 1px solid var(--border-highlight); box-shadow: var(--shadow-md);">
        <img src="<?= htmlspecialchars($img_url) ?>" alt="Banner" style="width: 100%; height: 260px; object-fit: cover; opacity: 0.35;">
        <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(11,15,25,0.4) 0%, rgba(11,15,25,0.95) 100%); padding: 2rem; display: flex; flex-direction: column; justify-content: flex-end;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span class="badge badge-cyan" style="margin-bottom: 0.5rem; display: inline-block; font-size: 0.85rem;">
                        <i class="fa-solid fa-plane"></i> <?= htmlspecialchars($trip['status']) ?> Trip
                    </span>
                    <h1 style="font-size: 2.5rem; margin-bottom: 0.2rem;"><?= htmlspecialchars($trip['title']) ?></h1>
                    <p style="color: var(--text-muted); font-size: 1.05rem;">
                        <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?= htmlspecialchars($trip['location']) ?> &bull; 
                        <i class="fa-solid fa-calendar"></i> <?= date('M d, Y', strtotime($trip['start_date'])) ?> to <?= date('M d, Y', strtotime($trip['end_date'])) ?>
                    </p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button class="btn btn-secondary btn-sm" data-modal-target="add-itinerary-modal">
                        <i class="fa-solid fa-calendar-plus"></i> Add Activity
                    </button>
                    <button class="btn btn-primary btn-sm" data-modal-target="add-expense-modal">
                        <i class="fa-solid fa-plus-minus"></i> Log Expense
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Health Overview Cards -->
    <div class="stats-grid" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon cyan">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= format_currency($trip['total_budget']) ?></div>
                <div class="stat-label">Total Allocated Budget</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?= ($budget_pct > 90) ? 'coral' : 'emerald' ?>">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?= format_currency($total_spent) ?></div>
                <div class="stat-label">Total Spent So Far (<?= $budget_pct ?>%)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?= ($remaining_budget < 0) ? 'coral' : 'indigo' ?>">
                <i class="fa-solid fa-piggy-bank"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value" style="color: <?= ($remaining_budget < 0) ? 'var(--danger)' : 'var(--success)' ?>;">
                    <?= format_currency($remaining_budget) ?>
                </div>
                <div class="stat-label"><?= ($remaining_budget < 0) ? 'Budget Deficit' : 'Remaining Balance' ?></div>
            </div>
        </div>
    </div>

    <!-- Workspace Main Grid: Itinerary (Left) & Expenses (Right) -->
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem;">
        
        <!-- ================= ITINERARY PLANNER ================= -->
        <div>
            <div class="section-header">
                <div>
                    <h2 class="section-title"><i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Day-by-Day Itinerary</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Organize schedule, time slots, and activity notes</p>
                </div>
                <button class="btn btn-primary btn-sm" data-modal-target="add-itinerary-modal">
                    <i class="fa-solid fa-plus"></i> Add Item
                </button>
            </div>

            <?php if (empty($itinerary_by_day)): ?>
                <div style="background: var(--bg-surface); border: 1px dashed var(--border-color); border-radius: var(--radius-md); padding: 3rem; text-align: center;">
                    <i class="fa-solid fa-calendar-day" style="font-size: 2.5rem; color: var(--text-dim); margin-bottom: 1rem;"></i>
                    <h3>No Itinerary Activities Added</h3>
                    <p style="color: var(--text-muted); margin-bottom: 1.2rem;">Build your daily schedule step-by-step.</p>
                    <button class="btn btn-primary btn-sm" data-modal-target="add-itinerary-modal">
                        <i class="fa-solid fa-plus"></i> Add First Activity
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($itinerary_by_day as $day_num => $items): ?>
                    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color); margin-bottom: 1rem;">
                            <h3 style="font-size: 1.2rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-sun"></i> Day <?= $day_num ?> 
                                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 400;">(<?= date('M d, Y', strtotime($items[0]['activity_date'])) ?>)</span>
                            </h3>
                            <span class="badge badge-cyan"><?= count($items) ?> Activities</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($items as $item): 
                                $is_done = $item['completed'] == 1;
                            ?>
                                <div style="display: flex; align-items: flex-start; gap: 1rem; padding: 0.85rem; background: rgba(255,255,255,0.02); border-radius: var(--radius-sm); border-left: 3px solid var(--primary);">
                                    <input type="checkbox" classclose class="itinerary-toggle" data-item-id="<?= $item['id'] ?>" <?= $is_done ? 'checked' : '' ?> style="width: 20px; height: 20px; margin-top: 3px; cursor: pointer; accent-color: var(--primary);">
                                    
                                    <div style="flex-grow: 1;">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <h4 id="item-title-<?= $item['id'] ?>" style="font-size: 1.05rem; <?= $is_done ? 'text-decoration: line-through; opacity: 0.6;' : '' ?>">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </h4>
                                            <span style="font-size: 0.85rem; font-weight: 600; color: var(--accent);">
                                                <?= date('g:i A', strtotime($item['activity_time'])) ?>
                                            </span>
                                        </div>

                                        <?php if ($item['description']): ?>
                                            <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 0.2rem;"><?= htmlspecialchars($item['description']) ?></p>
                                        <?php endif; ?>

                                        <div style="display: flex; gap: 1rem; font-size: 0.82rem; color: var(--text-dim); margin-top: 0.5rem; flex-wrap: wrap;">
                                            <?php if ($item['location']): ?>
                                                <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['location']) ?></span>
                                            <?php endif; ?>
                                            <span><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($item['category']) ?></span>
                                            <?php if ($item['estimated_cost'] > 0): ?>
                                                <span style="color: var(--success);"><i class="fa-solid fa-coins"></i> Est: <?= format_currency($item['estimated_cost']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <form action="api.php" method="POST" style="margin-left: auto;">
                                        <input type="hidden" name="action" value="delete_itinerary">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
                                        <button type="submit" style="background: none; border: none; color: var(--text-dim); cursor: pointer;" title="Delete Activity">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ================= EXPENSE TRACKER ================= -->
        <div>
            <div class="section-header">
                <div>
                    <h2 class="section-title"><i class="fa-solid fa-pie-chart" style="color: var(--success);"></i> Expense Tracker</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Category breakdown & spending logs</p>
                </div>
                <button class="btn btn-primary btn-sm" data-modal-target="add-expense-modal">
                    <i class="fa-solid fa-plus"></i> Add Expense
                </button>
            </div>

            <!-- Category Spending Meters -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; font-weight: 600;">Spending Breakdown</h3>
                
                <?php foreach ($expenses_by_cat as $cat_name => $cat_amt): 
                    $cat_pct = ($total_spent > 0) ? round(($cat_amt / $total_spent) * 100) : 0;
                ?>
                    <div style="margin-bottom: 0.85rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                            <span><?= $cat_name ?></span>
                            <span style="font-weight: 600;"><?= format_currency($cat_amt) ?> (<?= $cat_pct ?>%)</span>
                        </div>
                        <div class="progress-bar-bg" style="height: 6px;">
                            <div class="progress-bar-fill" style="width: <?= $cat_pct ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Logged Expenses Table -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem; font-weight: 600;">Expense History</h3>

                <?php if (empty($expenses)): ?>
                    <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 1.5rem 0;">No expenses logged for this trip yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($expenses as $exp): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.92rem;"><?= htmlspecialchars($exp['description']) ?></div>
                                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($exp['category']) ?> &bull; <?= date('M d', strtotime($exp['expense_date'])) ?> (<?= htmlspecialchars($exp['payment_method']) ?>)
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-weight: 700; color: var(--primary); font-size: 1rem;"><?= format_currency($exp['amount']) ?></span>
                                    <form action="api.php" method="POST">
                                        <input type="hidden" name="action" value="delete_expense">
                                        <input type="hidden" name="expense_id" value="<?= $exp['id'] ?>">
                                        <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
                                        <button type="submit" style="background: none; border: none; color: var(--text-dim); cursor: pointer;" title="Delete Expense">
                                            <i class="fa-solid fa-trash-can" style="font-size: 0.85rem;"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Modal: Add Itinerary Activity -->
<div class="modal-overlay" id="add-itinerary-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-plus"></i> Add Itinerary Activity</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="api.php" method="POST">
            <input type="hidden" name="action" value="add_itinerary">
            <input type="hidden" name="trip_id" value="<?= $trip_id ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Day Number</label>
                    <input type="number" name="day_number" class="form-control" value="1" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="activity_date" class="form-control" value="<?= $trip['start_date'] ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Time</label>
                    <input type="time" name="activity_time" class="form-control" value="09:00">
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control">
                        <option value="Sightseeing">Sightseeing</option>
                        <option value="Dining">Dining</option>
                        <option value="Transport">Transport</option>
                        <option value="Activity">Activity</option>
                        <option value="Accommodation">Accommodation</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Activity Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Visit Eiffel Tower Observation Deck" required>
            </div>

            <div class="form-group">
                <label class="form-label">Location / Address</label>
                <input type="text" name="location" class="form-control" placeholder="e.g. Champ de Mars, Paris">
            </div>

            <div class="form-group">
                <label class="form-label">Estimated Cost ($)</label>
                <input type="number" step="0.01" name="estimated_cost" class="form-control" placeholder="0.00" value="0.00">
            </div>

            <div class="form-group">
                <label class="form-label">Description & Notes</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Ticket details, booking ref, tips..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Save Activity</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Expense -->
<div class="modal-overlay" id="add-expense-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-receipt"></i> Log Travel Expense</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="api.php" method="POST">
            <input type="hidden" name="action" value="add_expense">
            <input type="hidden" name="trip_id" value="<?= $trip_id ?>">

            <div class="form-group">
                <label class="form-label">Expense Description</label>
                <input type="text" name="description" class="form-control" placeholder="e.g. Lunch at Local Bistro" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Amount ($)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="45.50" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control" required>
                        <option value="Food & Dining">Food & Dining</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Lodging">Lodging</option>
                        <option value="Activities & Sightseeing">Activities & Sightseeing</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Miscellaneous">Miscellaneous</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Card">Card</option>
                        <option value="Cash">Cash</option>
                        <option value="Mobile Pay">Mobile Pay</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Save Expense</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
