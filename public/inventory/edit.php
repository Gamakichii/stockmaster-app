<?php
// public/inventory/edit.php - CORRECTED VERSION
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure logged in
$user_id = get_user_id();

// --- Get Item ID and Initial Fetch ---
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$item = null; // Initialize item data as null
$error_message = '';
$page_title = 'Edit Item - StockMaster'; // Default title

// Redirect immediately if ID is invalid or missing
if (!$item_id) {
    header('Location: index.php?status=error'); // Redirect back to list page
    exit;
}

// Fetch the current item details, ensuring it belongs to the logged-in user
try {
    $stmt_fetch = $pdo->prepare("SELECT * FROM inventory_items WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt_fetch->execute([':id' => $item_id, ':user_id' => $user_id]);
    $item = $stmt_fetch->fetch(); // Fetch the item data into $item array

    if (!$item) {
        // Item not found OR doesn't belong to the user - redirect
        header('Location: index.php?status=notfound');
        exit;
    }
    // Update page title now that we have the item name
    $page_title = 'Edit: ' . escape($item['name']) . ' - StockMaster';

} catch (PDOException $e) {
     error_log("Edit Item Fetch Error (User: {$user_id}, Item: {$item_id}): " . $e->getMessage());
     $error_message = 'Failed to load item data. Please try again.';
     $item = null; // Ensure item is null if fetch failed, form won't display
}


// --- Handle Form Submission (Update Logic) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure item was loaded successfully before processing POST
    if ($item) {
        // Sanitize and Validate Inputs from POST request
        $name = trim($_POST['name'] ?? '');
        $quantity_input = $_POST['quantity'] ?? '';
        $price_input = $_POST['price'] ?? '';
        $category = trim($_POST['category'] ?? '');

        // Store old values before potential update (useful for logging changes)
        $old_values = ['name' => $item['name'], 'quantity' => $item['quantity'], 'price' => $item['price'], 'category' => $item['category']];

        // Preserve raw inputs for form repopulation in case of validation errors
        $quantity_form_value = $quantity_input;
        $price_form_value = $price_input;

        // --- Validation (Similar to create.php) ---
        if (empty($name)) {
            $error_message = 'Item name is required.';
        } elseif (!is_numeric($quantity_input) || (int)$quantity_input < 0 || floor($quantity_input) != $quantity_input) {
            $error_message = 'Quantity must be a whole non-negative number.';
        } elseif (!is_numeric($price_input) || (float)$price_input < 0) {
            $error_message = 'Price must be a non-negative number.';
        } elseif (mb_strlen($name) > 100) {
             $error_message = 'Item name cannot exceed 100 characters.';
        } elseif (mb_strlen($category) > 50) {
             $error_message = 'Category name cannot exceed 50 characters.';
        } else {
            // --- Validation Passed - Prepare for DB Update ---
            $quantity = (int)$quantity_input;
            $price = round((float)$price_input, 2);
            $category_value = !empty($category) ? $category : null; // Prepare category for DB (allow NULL)

            // Check if anything actually changed to avoid unnecessary DB write/log
            $no_changes = (
                $name === $item['name'] &&
                $quantity === (int)$item['quantity'] &&
                abs($price - (float)$item['price']) < 0.001 && // Tolerance check for float
                $category_value === $item['category'] // Compare potentially null category
            );

            if ($no_changes) {
                 $error_message = "No changes were detected."; // Provide feedback
            } else {
                // --- Changes detected, attempt DB Update ---
                try {
                    $sql = "UPDATE inventory_items
                            SET name = :name, quantity = :quantity, price = :price, category = :category, updated_at = NOW()
                            WHERE id = :id AND user_id = :user_id";

                    $stmt_update = $pdo->prepare($sql);
                    $stmt_update->execute([
                        ':name' => $name,
                        ':quantity' => $quantity,
                        ':price' => $price,
                        ':category' => $category_value,
                        ':id' => $item_id,
                        ':user_id' => $user_id
                    ]);

                    if ($stmt_update->rowCount() > 0) {
                        $new_values = ['name' => $name, 'quantity' => $quantity, 'price' => $price, 'category' => $category_value];
                        log_action('update_item', ['item_id' => $item_id, 'old' => $old_values, 'new' => $new_values]);
                        header('Location: index.php?status=updated'); // Redirect on success
                        exit;
                    } else {
                        $error_message = 'Update query ran but did not affect any rows. No changes saved.';
                        log_action('update_item_failed', ['item_id' => $item_id, 'reason' => 'No rows affected by UPDATE']);
                    }
                } catch (PDOException $e) {
                    error_log("Update Item DB Error (User: {$user_id}, Item: {$item_id}): " . $e->getMessage());
                    $error_message = 'Failed to update item due to a database error. Please try again.';
                } // End try/catch for DB update
            } // End else (changes detected)
        } // End else (validation passed)

        // --- Repopulate $item array with submitted values if validation failed or DB error ---
        if ($error_message) { // Repopulate only if there was an error
            $item['name'] = $name;
            $item['quantity'] = $quantity_form_value; // Use raw input for form
            $item['price'] = $price_form_value;       // Use raw input for form
            $item['category'] = $category;           // Use raw input for form
        }
    } // End check if $item was loaded before processing POST
} // End if POST request


// --- Render the Page ---
require_once __DIR__ . '/../../includes/header.php'; // Render header
?>

<?php // --- Breadcrumbs --- ?>
<nav aria-label="breadcrumb" class="mb-6 text-sm text-gray-500">
  <ol class="list-none p-0 inline-flex flex-wrap">
    <li class="flex items-center">
      <a href="/dashboard.php" class="hover:text-blue-600">Dashboard</a>
      <span class="mx-2">/</span>
    </li>
    <li class="flex items-center">
      <a href="/inventory/index.php" class="hover:text-blue-600">Inventory</a>
      <span class="mx-2">/</span>
    </li>
    <li class="flex items-center text-gray-700" aria-current="page">
      Edit Item <?php echo ($item && empty($error_message)) ? ': ' . escape($item['name']) : ''; // Show name only if item loaded and no *fetch* error occurred ?>
    </li>
  </ol>
</nav>

<h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo escape($page_title); ?></h1>

<div class="max-w-lg mx-auto bg-white p-8 border border-gray-300 rounded-lg shadow-lg">

    <?php // Display Error Message (Could be fetch error or validation/update error) ?>
    <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
             <p class="font-bold">Error</p>
            <p><?php echo escape($error_message); ?></p>
        </div>
    <?php endif; ?>

    <?php // --- Display Form only if item was loaded successfully initially --- ?>
    <?php if ($item): ?>
    <form action="edit.php?id=<?php echo $item_id; ?>" method="POST" novalidate>
        <?php // Item Name ?>
        <div class="mb-4">
            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Item Name <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" required maxlength="100" value="<?php echo escape($item['name'] ?? ''); ?>"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && empty(trim($_POST['name'] ?? ''))) echo 'border-red-500'; ?>">
        </div>
        <?php // Quantity ?>
        <div class="mb-4">
            <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity <span class="text-red-500">*</span></label>
            <input type="number" id="quantity" name="quantity" required value="<?php echo escape($item['quantity'] ?? '0'); ?>" min="0" step="1" aria-describedby="quantityHelp"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && (!is_numeric($_POST['quantity'] ?? '') || (int)($_POST['quantity'] ?? -1) < 0)) echo 'border-red-500'; ?>">
             <p id="quantityHelp" class="text-xs text-gray-500 mt-1">Whole numbers only (0 or more).</p>
        </div>
        <?php // Price ?>
        <div class="mb-4">
            <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price (per item) <span class="text-red-500">*</span></label>
            <input type="number" id="price" name="price" required value="<?php echo escape(number_format((float)($item['price'] ?? 0.00), 2, '.', '')); // Format for input ?>" min="0" step="0.01" aria-describedby="priceHelp"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && (!is_numeric($_POST['price'] ?? '') || (float)($_POST['price'] ?? -1) < 0)) echo 'border-red-500'; ?>">
             <p id="priceHelp" class="text-xs text-gray-500 mt-1">Format like 1.99 or 10.00.</p>
        </div>
         <?php // Category ?>
         <div class="mb-6">
            <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category</label>
            <input type="text" id="category" name="category" maxlength="50" value="<?php echo escape($item['category'] ?? ''); ?>" aria-describedby="categoryHelp"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && mb_strlen(trim($_POST['category'] ?? '')) > 50) echo 'border-red-500'; ?>">
             <p id="categoryHelp" class="text-xs text-gray-500 mt-1">Optional, max 50 characters.</p>
        </div>

        <?php // Form Actions ?>
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <button type="submit"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex-grow sm:flex-grow-0 transition duration-150 ease-in-out">
                Save Changes
            </button>
            <a href="index.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800 border border-gray-300 px-4 py-2 rounded hover:bg-gray-100 transition duration-150 ease-in-out">
                Cancel
            </a>
        </div>
    </form>
    <?php elseif (!$error_message): // If item is null and there was no initial DB fetch error message, it means item wasn't found / permission denied from initial load ?>
         <p class="text-center text-yellow-700 italic">Item not found or you do not have permission to edit it.</p>
         <div class="text-center mt-4">
             <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to Inventory List</a>
        </div>
    <?php endif; // End if ($item) ?>

    <?php // Show back link only if there *was* a fetch error message and therefore $item is null ?>
     <?php if ($error_message && !$item): ?>
         <div class="text-center mt-4">
             <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to Inventory List</a>
        </div>
    <?php endif; ?>
</div> <?php // End form container ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; // Render footer ?>
