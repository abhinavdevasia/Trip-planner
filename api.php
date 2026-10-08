<?php
require_once __DIR__ . '/config.php';

$db = get_db_connection();
$user_id = get_current_user_id();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper to redirect back to referring page or specific URI
function redirect_back($fallback = 'index.php') {
    $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
    header("Location: $ref");
    exit;
}

switch ($action) {
    case 'create_trip':
        $title = sanitize($_POST['title'] ?? '');
        $destination_id = !empty($_POST['destination_id']) ? (int)$_POST['destination_id'] : null;
        $location = sanitize($_POST['location'] ?? '');
        $start_date = $_POST['start_date'] ?? date('Y-m-d');
        $end_date = $_POST['end_date'] ?? date('Y-m-d');
        $total_budget = (float)($_POST['total_budget'] ?? 0.00);
        $notes = sanitize($_POST['notes'] ?? '');

        if ($title && $location) {
            $stmt = $db->prepare("
                INSERT INTO trips (user_id, destination_id, title, location, start_date, end_date, total_budget, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Planned', ?)
            ");
            $stmt->execute([$user_id, $destination_id, $title, $location, $start_date, $end_date, $total_budget, $notes]);
            $new_trip_id = $db->lastInsertId();
            header("Location: trip_details.php?id=" . $new_trip_id);
            exit;
        }
        redirect_back('trips.php');
        break;

    case 'delete_trip':
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        if ($trip_id > 0) {
            $stmt = $db->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?");
            $stmt->execute([$trip_id, $user_id]);
        }
        redirect_back('trips.php');
        break;

    case 'add_itinerary':
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        $day_number = (int)($_POST['day_number'] ?? 1);
        $activity_date = $_POST['activity_date'] ?? date('Y-m-d');
        $activity_time = $_POST['activity_time'] ?? '09:00';
        $title = sanitize($_POST['title'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $category = sanitize($_POST['category'] ?? 'Sightseeing');
        $estimated_cost = (float)($_POST['estimated_cost'] ?? 0.00);
        $description = sanitize($_POST['description'] ?? '');

        if ($trip_id > 0 && $title) {
            $stmt = $db->prepare("
                INSERT INTO itinerary_items (trip_id, day_number, activity_date, activity_time, title, description, location, category, estimated_cost)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$trip_id, $day_number, $activity_date, $activity_time, $title, $description, $location, $category, $estimated_cost]);
        }
        redirect_back("trip_details.php?id=$trip_id");
        break;

    case 'toggle_itinerary':
        header('Content-Type: application/json');
        $item_id = (int)($_POST['item_id'] ?? 0);
        $completed = (int)($_POST['completed'] ?? 0);

        if ($item_id > 0) {
            $stmt = $db->prepare("UPDATE itinerary_items SET completed = ? WHERE id = ?");
            $stmt->execute([$completed, $item_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;

    case 'delete_itinerary':
        $item_id = (int)($_POST['item_id'] ?? 0);
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        if ($item_id > 0) {
            $stmt = $db->prepare("DELETE FROM itinerary_items WHERE id = ?");
            $stmt->execute([$item_id]);
        }
        redirect_back("trip_details.php?id=$trip_id");
        break;

    case 'add_expense':
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0.00);
        $category = sanitize($_POST['category'] ?? 'Miscellaneous');
        $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
        $payment_method = sanitize($_POST['payment_method'] ?? 'Card');

        if ($trip_id > 0 && $amount > 0 && $description) {
            $stmt = $db->prepare("
                INSERT INTO expenses (trip_id, category, amount, expense_date, description, payment_method)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$trip_id, $category, $amount, $expense_date, $description, $payment_method]);
        }
        redirect_back("trip_details.php?id=$trip_id");
        break;

    case 'delete_expense':
        $expense_id = (int)($_POST['expense_id'] ?? 0);
        $trip_id = (int)($_POST['trip_id'] ?? 0);
        if ($expense_id > 0) {
            $stmt = $db->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$expense_id]);
        }
        redirect_back($trip_id > 0 ? "trip_details.php?id=$trip_id" : "expenses.php");
        break;

    default:
        redirect_back('index.php');
        break;
}
