<?php
// public/inventory/edit.php
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure logged in
$user_id = get_user_id();

// --- Get Item ID and Initial Fetch ---
$item_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$item = null; // Initialize item data as null
$error_message = '';
$page_title = 'Edit Item - StockMaster'; // Default title (UPDATED)

// Redirect immediately if ID is invalid or missing
if (!$item_id) {
    header('Location: index.php?status=error');
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
    $page_title = 'Edit: ' . escape($item['name']) . ' - StockMaster'; // UPDATED title

} catch (PDOException $e) {
     error_log("Edit Item Fetch Error (User: {$user_id}, Item: {$item_id}): " . $e->getMessage());
     $error_message = 'Failed to load item data. Please try again.';
     // Do not exit here; allow the page to render with the error message and prevent form display
     $item = null; // Ensure item is null if fetch failed
}


// --- Handle Form Submission (Update Logic) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $item) { // Proceed only if item was loaded successfully AND form submitted via POST
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
            // Use == for float comparison due to potential precision issues, or use a tolerance check
            abs($price - (float)$item['price']) < 0.001 &&
            $category_value === $item['category'] // Compare potentially null category
        );

        if ($no_changes) {
             // Set an info message or simply redirect back without updating
             // Using error_message might be confusing, maybe a different variable?
             $error_message = "No changes were detected.";
             // Or redirect: header('Location: index.php?status=nochange'); exit;
        } else {
            // --- Changes detected, attempt DB Update ---
            try {
                // Prepare UPDATE statement, ensuring ownership again in WHERE clause
                $sql = "UPDATE inventory_items
                        SET name = :name, quantity = :quantity, price = :price, category = :category, updated_at = NOW()
                        WHERE id = :id AND user_id = :user_id"; // Crucial ownership check

                $stmt_update = $pdo->prepare($sql);

                // Execute the update
                $stmt_update->execute([
                    ':name' => $name,
                    ':quantity' => $quantity,
                    ':price' => $price,
                    ':category' => $category_value,
                    ':id' => $item_id,
                    ':user_id' => $user_id // Ensure user owns the item being updated
                ]);

                // Check if the update actually affected a row (optional but good)
                if ($stmt_update->rowCount() > 0) {
                    // Log the successful update action
                    $new_values = ['name' => $name, 'quantity' => $quantity, 'price' => $price, 'category' => $category_value];
                    log_action('update_item', ['item_id' => $item_id, 'old' => $old_values, 'new' => $new_values]);

                    // Redirect on successful update
                    header('Location: index.php?status=updated');
                    exit;
                } else {
                    // This might happen if the item was deleted between fetch and update,
                    // or if rowCount isn't reliable on all drivers/dbs for UPDATE
                    $error_message = 'Update did not affect any rows. Item might have been deleted or no changes were made.';
                    // Log this possibility
                    log_action('update_item_failed', ['item_id' => $item_id, 'reason' => 'No rows affected by UPDATE']);
                }

            } catch (PDOException $e) {
                error_log("Update Item DB Error (User: {$user_id}, Item: {$item_id}): " . $e->getMessage());
                $error_message = 'Failed to update item due to a database error. Please try again.';
            }
        }
    }

    // --- Repopulate $item array with submitted values if validation failed or DB error ---
    // This ensures the form shows the user's attempted changes, not the original values
    if ($error_message && $item) { // Only repopulate if there's an error AND the item was initially loaded
        $item['name'] = $name;
        $item['quantity'] = $quantity_form_value; // Use raw input for form
        $item['price'] = $price_form_value;       // Use raw input for form
        $item['category'] = $category;           // Use raw input for form
    }
}

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
      Edit Item <?php // Show item name if loaded, otherwise just 'Edit Item'
          echo ($item && !$error_message) ? ': ' . escape($item['name']) : '';
      ?>
    </li>
  </ol>
</nav>

<h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo escape($page_title); ?></h1>

<div class="max-w-lg mx-auto bg-white p-8 border border-gray-300 rounded-lg shadow-lg">

    <?php // Display Error Message ?>
    <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
             <p class="font-bold">Error</p>
            <p><?php echo escape($error_message); ?></p>
        </div>
    <?php endif; ?>

    <?php // Only display the form if the item was successfully loaded initially ?>
    <?php if ($item): ?>
    <form action="edit.php?id=<?php echo <span class="math-inline">item\_id; ?\>" method\="POST" novalidate\>
<?php // Item Name ?\>
<div class\="mb\-4"\>
<label for\="name" class\="block text\-gray\-700 text\-sm font\-bold mb\-2"\>Item Name <span class\="text\-red\-500"\>\*</span\></label\>
<input type\="text" id\="name" name\="name" required maxlength\="100" value\="<?php echo escape\(</span>item['name'] ?? ''); ?>"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <?php // Quantity ?>
        <div class="mb-4">
            <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity <span class="text-red-500">*</span></label>
            <input type="number" id="quantity" name="quantity" required value="<?php echo escape($item['quantity'] ?? '0'); ?>" min="0" step="1" aria-describedby="quantityHelp"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
             <p id="quantityHelp" class="text-xs text-gray-500 mt-1">Whole numbers only (0 or more).</p>
        </div>
        <?php // Price ?>
        <div class="mb-4">
            <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price (per item) <span class="text-red-500">*</span></label>
            <input type="number" id="price" name="price" required value="<?php echo escape(number_format((float)($item['price'] ?? 0.00), 2, '.', '')); // Format for input ?>" min="0" step="0.01" aria-describedby="priceHelp"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
             <p id="priceHelp" class="text-xs text-gray-500 mt-1">Format like 1.99 or 10.00.</p>
        </div>
         <?php // Category ?>
         <div class="mb-6">
            <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category</label>
            <input type="text" id="category" name="category" maxlength="50" value="<?php echo escape($item['category'] ?? ''); ?>" aria-describedby="categoryHelp"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
    <?php elseif (!$error_message): // If item is null and there was no DB fetch error, it means the item wasn't found for the user initially ?>
         <p class="text-center text-yellow-700 italic">Item not found or you do not have permission to edit it.</p>
         <div class="text-center mt-4">
             <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to Inventory List</a>
        </div>
    <?php endif; // End if ($item) ?>

    <?php // Show back link if there was a specific fetch error message ?>
     <?php if ($error_message && !$item): ?>
         <div class="text-center mt-4">
             <a href="index.php" class="text-blue-600 hover:underline">&larr; Back to Inventory List</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; // Render footer ?>