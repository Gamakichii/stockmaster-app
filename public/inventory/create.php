<?php
// public/inventory/create.php - MODIFIED with Add Quantity on Duplicate
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure logged in
$user_id = get_user_id();

// --- Initialize variables ---
$error_message = '';
$info_message = ''; // For confirmation message
$name = '';
$quantity = 0;
$price = 0.00;
$category = '';

// Variables for confirmation state
$show_confirmation = false;
$existing_item_id = null;
$existing_quantity = null;
$quantity_to_add = 0;

// --- Handle POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Check if this is a confirmation submission to add quantity ---
    if (isset($_POST['action']) && $_POST['action'] === 'add_quantity') {
        $existing_item_id = filter_input(INPUT_POST, 'existing_item_id', FILTER_VALIDATE_INT);
        $quantity_to_add_input = $_POST['quantity_to_add'] ?? '';
        $item_name_for_log = trim($_POST['item_name'] ?? 'Unknown'); // Get name for logging

        // Validate quantity again
        if (!is_numeric($quantity_to_add_input) || (int)$quantity_to_add_input < 0 || floor($quantity_to_add_input) != $quantity_to_add_input) {
             $error_message = 'Invalid quantity provided for addition. Must be a whole non-negative number.';
             // Need to refetch item data to redisplay confirmation correctly - or handle state better
             // For simplicity here, we'll just show the error and they'd have to restart the add process
             $show_confirmation = false; // Exit confirmation mode on error
        } elseif (!$existing_item_id) {
             $error_message = 'Cannot add quantity. Item ID missing.';
             $show_confirmation = false;
        } else {
            $quantity_to_add = (int)$quantity_to_add_input;

            // Proceed with UPDATE
            try {
                $updateSql = "UPDATE inventory_items
                              SET quantity = quantity + :added_quantity, updated_at = NOW()
                              WHERE id = :id AND user_id = :user_id"; // Ensure ownership
                $stmtUpdate = $pdo->prepare($updateSql);
                $stmtUpdate->execute([
                    ':added_quantity' => $quantity_to_add,
                    ':id' => $existing_item_id,
                    ':user_id' => $user_id
                ]);

                if ($stmtUpdate->rowCount() > 0) {
                    log_action('add_quantity_to_item', ['item_id' => $existing_item_id, 'name' => $item_name_for_log, 'quantity_added' => $quantity_to_add]);
                    header('Location: index.php?status=quantity_added'); // New status message
                    exit;
                } else {
                    $error_message = 'Could not add quantity. Item may have been deleted or permission denied.';
                    log_action('add_quantity_failed', ['item_id' => $existing_item_id, 'reason' => 'No rows affected by UPDATE']);
                }

            } catch (PDOException $e) {
                error_log("Add Quantity DB Error (User: {$user_id}, Item: {$existing_item_id}): " . $e->getMessage());
                $error_message = 'Database error while trying to add quantity.';
            }
        }

    } else {
        // --- Process Initial 'Add New Item' Submission ---
        $name = trim($_POST['name'] ?? '');
        $quantity_input = $_POST['quantity'] ?? '';
        $price_input = $_POST['price'] ?? '';
        $category = trim($_POST['category'] ?? '');

        // Preserve raw inputs for form repopulation
        $quantity_form_value = $quantity_input;
        $price_form_value = $price_input;
        $quantity = $quantity_input; // Keep for form value if validation fails
        $price = $price_input;       // Keep for form value if validation fails

        // --- Basic Validation ---
        if (empty($name)) {
            $error_message = 'Item name is required.';
        } elseif (!is_numeric($quantity_input) || (int)$quantity_input < 0 || floor($quantity_input) != $quantity_input) {
            $error_message = 'Quantity must be a whole non-negative number (e.g., 0, 1, 10).';
        } elseif (!is_numeric($price_input) || (float)$price_input < 0) {
            $error_message = 'Price must be a non-negative number (e.g., 0.00, 1.99).';
        } elseif (mb_strlen($name) > 100) {
            $error_message = 'Item name cannot exceed 100 characters.';
        } elseif (mb_strlen($category) > 50) {
            $error_message = 'Category name cannot exceed 50 characters.';
        } else {
            // --- Basic Validation Passed - Check for Duplicate ---
            $quantity_to_add = (int)$quantity_input; // Store the quantity user intended to add
            $price = round((float)$price_input, 2); // Store validated price
            $category_value = !empty($category) ? $category : null; // Store category

            try {
                $checkSql = "SELECT id, quantity FROM inventory_items WHERE name = :name AND user_id = :user_id LIMIT 1";
                $stmtCheck = $pdo->prepare($checkSql);
                $stmtCheck->execute([':name' => $name, ':user_id' => $user_id]);
                $existing_item = $stmtCheck->fetch();

                if ($existing_item) {
                    // --- DUPLICATE FOUND: Prepare for Confirmation ---
                    $show_confirmation = true;
                    $existing_item_id = $existing_item['id'];
                    $existing_quantity = $existing_item['quantity'];
                    $info_message = "Item '" . escape($name) . "' already exists with quantity " . escape($existing_quantity) . ". Add the submitted quantity (" . escape($quantity_to_add) . ")?";
                    // Keep original form values for potential display in confirmation
                    $quantity = $quantity_to_add;

                } else {
                    // --- NO DUPLICATE: Proceed with Insertion ---
                    try {
                        $sql = "INSERT INTO inventory_items (user_id, name, quantity, price, category, created_at, updated_at)
                                VALUES (:user_id, :name, :quantity, :price, :category, NOW(), NOW())";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':user_id' => $user_id,
                            ':name' => $name,
                            ':quantity' => $quantity_to_add, // Insert the submitted quantity
                            ':price' => $price,
                            ':category' => $category_value
                        ]);
                        $new_item_id = $pdo->lastInsertId();
                        log_action('create_item', ['item_id' => $new_item_id, 'name' => $name, 'quantity' => $quantity_to_add, 'price' => $price, 'category' => $category_value]);
                        header('Location: index.php?status=created');
                        exit;
                    } catch (PDOException $e) {
                        error_log("Create Item DB Error (User: {$user_id}, Name: {$name}): " . $e->getMessage());
                        $error_message = 'Failed to add item due to a database error.';
                    } // End INSERT try/catch
                } // End else (no duplicate found)

            } catch (PDOException $e) { // Catch errors during DUPLICATE CHECK
                 error_log("Create Item Duplicate Check Error (User: {$user_id}, Name: {$name}): " . $e->getMessage());
                 $error_message = 'Failed to check for duplicate items.';
            } // End Duplicate Check try/catch

        } // End else (basic validation passed)

        // Repopulate if validation error occurred during initial submission
        if ($error_message && !$show_confirmation) {
             $quantity = $quantity_form_value;
             $price = $price_form_value;
        }
    } // End else (Initial 'Add New Item' Submission)
} // End if POST request


$page_title = 'Add New Item - StockMaster';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php // Breadcrumbs ?>
<nav aria-label="breadcrumb" class="mb-6 text-sm text-gray-500">
  <ol class="list-none p-0 inline-flex flex-wrap">
    <li class="flex items-center"><a href="/dashboard.php" class="hover:text-blue-600">Dashboard</a><span class="mx-2">/</span></li>
    <li class="flex items-center"><a href="/inventory/index.php" class="hover:text-blue-600">Inventory</a><span class="mx-2">/</span></li>
    <li class="flex items-center text-gray-700" aria-current="page">Add New Item</li>
  </ol>
</nav>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Add New Inventory Item</h1>

<div class="max-w-lg mx-auto bg-white p-8 border border-gray-300 rounded-lg shadow-lg">

    <?php // Display Error or Info Messages ?>
    <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
             <p class="font-bold">Error</p>
            <p><?php echo escape($error_message); ?></p>
        </div>
    <?php elseif ($info_message): ?>
         <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
             <p class="font-bold">Confirmation Required</p>
            <p><?php echo escape($info_message); ?></p> <?php // Info message for duplicate ?>
        </div>
    <?php endif; ?>


    <?php // --- Conditionally Render Form --- ?>

    <?php if ($show_confirmation): ?>
        <?php // --- Confirmation Form (Add Quantity) --- ?>
        <form action="create.php" method="POST" novalidate>
            <input type="hidden" name="action" value="add_quantity">
            <input type="hidden" name="existing_item_id" value="<?php echo escape($existing_item_id); ?>">
            <input type="hidden" name="item_name" value="<?php echo escape($name); ?>"> <?php // Pass name again for logging ?>

            <?php // Display Item Name (Readonly) ?>
            <div class="mb-4">
                <label for="name_display" class="block text-gray-700 text-sm font-bold mb-2">Item Name:</label>
                <input type="text" id="name_display" value="<?php echo escape($name); ?>" readonly disabled
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-500 bg-gray-100 leading-tight cursor-not-allowed">
            </div>

            <?php // Quantity to Add Input ?>
            <div class="mb-6">
                <label for="quantity_to_add" class="block text-gray-700 text-sm font-bold mb-2">Quantity to Add <span class="text-red-500">*</span></label>
                <input type="number" id="quantity_to_add" name="quantity_to_add" required value="<?php echo escape($quantity_to_add); ?>" min="0" step="1"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Confirm or change the quantity to add to the existing stock.</p>
            </div>

             <?php // Hidden/Disabled Price and Category ?>
             <p class="text-sm text-gray-600 mb-6 italic">Price and Category cannot be modified when adding to existing stock.</p>
             <?php /* Optionally show disabled fields:
             <div class="mb-4 opacity-50"> ... Price input with 'disabled' ... </div>
             <div class="mb-6 opacity-50"> ... Category input with 'disabled' ... </div>
             */ ?>

            <?php // Confirmation Actions ?>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <button type="submit"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex-grow sm:flex-grow-0 transition duration-150 ease-in-out">
                    Confirm Add Quantity
                </button>
                <a href="index.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800 border border-gray-300 px-4 py-2 rounded hover:bg-gray-100 transition duration-150 ease-in-out">
                    Cancel
                </a>
            </div>
        </form>

    <?php else: ?>
        <?php // --- Original Full "Add New Item" Form --- ?>
        <form action="create.php" method="POST" novalidate>
            <?php // Item Name ?>
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Item Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" required maxlength="100" value="<?php echo escape($name); ?>"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && empty(trim($_POST['name'] ?? ''))) echo 'border-red-500'; ?>">
            </div>
            <?php // Quantity ?>
            <div class="mb-4">
                <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity <span class="text-red-500">*</span></label>
                <input type="number" id="quantity" name="quantity" required value="<?php echo escape($quantity); ?>" min="0" step="1" aria-describedby="quantityHelp"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && (!is_numeric($_POST['quantity'] ?? '') || (int)($_POST['quantity'] ?? -1) < 0)) echo 'border-red-500'; ?>">
                 <p id="quantityHelp" class="text-xs text-gray-500 mt-1">Whole numbers only (0 or more).</p>
            </div>
            <?php // Price ?>
            <div class="mb-4">
                <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price (per item) <span class="text-red-500">*</span></label>
                <input type="number" id="price" name="price" required value="<?php echo escape($price); ?>" min="0" step="0.01" aria-describedby="priceHelp"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && (!is_numeric($_POST['price'] ?? '') || (float)($_POST['price'] ?? -1) < 0)) echo 'border-red-500'; ?>">
                 <p id="priceHelp" class="text-xs text-gray-500 mt-1">Format like 1.99 or 10.00.</p>
            </div>
             <?php // Category ?>
             <div class="mb-6">
                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category</label>
                <input type="text" id="category" name="category" maxlength="50" value="<?php echo escape($category); ?>" aria-describedby="categoryHelp"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && mb_strlen(trim($_POST['category'] ?? '')) > 50) echo 'border-red-500'; ?>">
                 <p id="categoryHelp" class="text-xs text-gray-500 mt-1">Optional, max 50 characters.</p>
            </div>

            <?php // Form Actions ?>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <button type="submit"
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline flex-grow sm:flex-grow-0 transition duration-150 ease-in-out">
                    Add Item
                </button>
                <a href="index.php" class="inline-block align-baseline font-bold text-sm text-gray-600 hover:text-gray-800 border border-gray-300 px-4 py-2 rounded hover:bg-gray-100 transition duration-150 ease-in-out">
                    Cancel
                </a>
            </div>
        </form>
    <?php endif; // End conditional form rendering ?>

</div> <?php // End form container div ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; // Render footer ?>
