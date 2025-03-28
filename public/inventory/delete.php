<?php
// public/inventory/delete.php
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure user is logged in
$user_id = get_user_id();

// --- Security Check: Only allow POST method for delete actions ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If accessed via GET or other methods, it's invalid. Redirect away.
    header('Location: index.php?status=error'); // Redirect with a generic error
    exit;
}

// --- Validate Input: Get item ID from POST data ---
$item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
$item_name_for_log = trim($_POST['item_name'] ?? 'Unknown'); // Get name passed from form for logging

// Redirect if item ID is missing or invalid
if (!$item_id) {
    header('Location: index.php?status=error');
    exit;
}

// --- Attempt Deletion ---
try {
    // Prepare statement to delete the item, crucially including user_id check for ownership
    $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = :id AND user_id = :user_id");

    // Bind parameters
    $stmt->bindParam(':id', $item_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    // Execute the deletion
    $stmt->execute();

    // Check if any row was actually deleted (rowCount() > 0 indicates success)
    if ($stmt->rowCount() > 0) {
        // Log the successful deletion including the item name passed from the form
        log_action('delete_item', ['item_id' => $item_id, 'name' => $item_name_for_log]);
        // Redirect back to the inventory list with a success status
        header('Location: index.php?status=deleted');
    } else {
        // No rows deleted - this means the item either didn't exist or didn't belong to this user
        log_action('delete_item_failed', ['item_id' => $item_id, 'reason' => 'Item not found or permission denied during delete attempt']);
        // Redirect with a 'not found' status
        header('Location: index.php?status=notfound');
    }

} catch (PDOException $e) {
    // Log any database error that occurred during the deletion attempt
    error_log("Delete Item DB Error (User: {$user_id}, Item: {$item_id}): " . $e->getMessage());
    // Redirect with a generic error status
    header('Location: index.php?status=error');
}

// Ensure script terminates after potential redirects
exit;
?>