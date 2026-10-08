<?php
require_once __DIR__ . '/header.php';

$db = get_db_connection();
$user_id = get_current_user_id();

// Fetch all trips for dropdown filter / expense logging
$stmt_trips = $db->prepare("SELECT id, title FROM trips WHERE user_id = ? ORDER BY title ASC");
$stmt_trips->execute([$user_id]);
$trips_list = $stmt_trips->fetchAll();

// Selected trip filter
$selected_trip = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

// Query expenses
$sql = "
    SELECT e.*, t.title as trip_title 
    FROM expenses e 
    JOIN trips t ON e.trip_id = t.id 
    WHERE t.user_id = ?
";
$params = [$user_id];

if ($selected_trip > 0) {
    $sql .= " AND e.trip_id = ?";
    $params[] = $selected_trip;
}

$sql .= " ORDER BY e.expense_date DESC, e.id DESC";

$stmt_exp = $db->prepare($sql);
$stmt_exp->execute($params);
$all_expenses = $stmt_exp->fetchAll();

// Calculate category totals
$total_spent = 0;
$cat_totals = [
    'Food & Dining' => 0,
    'Transportation' => 0,
    'Lodging' => 0,
    'Activities & Sightseeing' => 0,
    'Shopping' => 0,
    'Miscellaneous' => 0
];

foreach ($all_expenses as $exp) {
    $total_spent += $exp['amount'];
    if (isset($cat_totals[$exp['category']])) {
        $cat_totals[$exp['category']] += $exp['amount'];
    } else {
        $cat_totals['Miscellaneous'] += $exp['amount'];
    }
}
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-wallet" style="color: var(--primary);"></i> Travel Expense Tracker</h1>
            <p class="page-subtitle">Track, analyze, and manage your travel expenditures across all your journeys.</p>
        </div>
        <button class="btn btn-primary" data-modal-target="global-add-expense-modal">
            <i class="fa-solid fa-plus"></i> Log New Expense
        </button>
    </div>

    <!-- Category Progress Bar Card -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 2rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-md);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="color: var(--text-muted); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">Total Expenses Recorded</span>
                <h2 style="font-size: 2.4rem; color: var(--primary); font-weight: 800; font-family: 'Outfit', sans-serif; margin-top: 0.2rem;">
                    <?= format_currency($total_spent) ?>
                </h2>
            </div>

            <!-- Trip Filter Form -->
            <form method="GET" action="expenses.php" style="display: flex; gap: 0.75rem; align-items: center;">
                <select name="trip_id" class="form-control" onchange="this.form.submit()" style="width: 220px;">
                    <option value="0">All Trips Filter</option>
                    <?php foreach ($trips_list as $tr): ?>
                        <option value="<?= $tr['id'] ?>" <?= ($selected_trip === $tr['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tr['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selected_trip > 0): ?>
                    <a href="expenses.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem;">
            <?php foreach ($cat_totals as $cat_name => $cat_amt): 
                $pct = ($total_spent > 0) ? round(($cat_amt / $total_spent) * 100) : 0;
            ?>
                <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                    <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500; margin-bottom: 0.3rem;"><?= $cat_name ?></div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--text-main);"><?= format_currency($cat_amt) ?></div>
                    <div class="progress-bar-bg" style="height: 5px; margin-top: 0.5rem;">
                        <div class="progress-bar-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Expenses History Table -->
    <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.5rem; overflow-x: auto;">
        <h3 style="font-size: 1.25rem; margin-bottom: 1rem;"><i class="fa-solid fa-list" style="color: var(--primary);"></i> Expense History Logs</h3>

        <?php if (empty($all_expenses)): ?>
            <div style="text-align: center; padding: 3rem 0; color: var(--text-muted);">
                <i class="fa-solid fa-receipt" style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-dim);"></i>
                <p>No expenses found for this selection.</p>
            </div>
        <?php else: ?>
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Trip</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Payment Method</th>
                        <th>Amount</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_expenses as $exp): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($exp['expense_date'])) ?></td>
                            <td><strong style="color: var(--primary);"><?= htmlspecialchars($exp['trip_title']) ?></strong></td>
                            <td><?= htmlspecialchars($exp['description']) ?></td>
                            <td><span class="badge badge-cyan"><?= htmlspecialchars($exp['category']) ?></span></td>
                            <td><?= htmlspecialchars($exp['payment_method']) ?></td>
                            <td style="font-weight: 700; color: var(--text-main);"><?= format_currency($exp['amount']) ?></td>
                            <td style="text-align: right;">
                                <form action="api.php" method="POST" style="display: inline;" onsubmit="return confirm('Delete this expense log?');">
                                    <input type="hidden" name="action" value="delete_expense">
                                    <input type="hidden" name="expense_id" value="<?= $exp['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.6rem;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Global Add Expense -->
<div class="modal-overlay" id="global-add-expense-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fa-solid fa-receipt"></i> Log Travel Expense</h3>
            <button class="modal-close" data-modal-close>&times;</button>
        </div>
        <form action="api.php" method="POST">
            <input type="hidden" name="action" value="add_expense">

            <div class="form-group">
                <label class="form-label">Select Trip</label>
                <select name="trip_id" class="form-control" required>
                    <?php foreach ($trips_list as $tr): ?>
                        <option value="<?= $tr['id'] ?>" <?= ($selected_trip === $tr['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tr['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Expense Description</label>
                <input type="text" name="description" class="form-control" placeholder="e.g. Train ticket to Kyoto" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Amount ($)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" placeholder="60.00" required>
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
