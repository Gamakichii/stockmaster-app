<?php
// public/inventory/index.php
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure user is logged in
$user_id = get_user_id();

// --- Configuration ---
$items_per_page = 15; // Items per page for pagination

// --- Input Handling & Validation ---
$search_term = trim($_GET['search'] ?? '');
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

// Sorting parameters - Validate against allowed columns
$allowed_sort_columns = ['name', 'quantity', 'price', 'category', 'updated_at'];
$sort_column = $_GET['sort'] ?? 'name'; // Default sort column
if (!in_array($sort_column, $allowed_sort_columns, true)) { // Use strict check
    $sort_column = 'name'; // Fallback to default if invalid column provided
}
// Validate sort direction
$sort_direction_input = strtolower($_GET['dir'] ?? 'asc');
$sort_direction = ($sort_direction_input === 'desc') ? 'DESC' : 'ASC'; // Default ASC

// --- Build Base Query and Parameters ---
$base_sql = "FROM inventory_items WHERE user_id = :user_id";
$params = [':user_id' => $user_id];
$where_clauses = []; // Array to hold WHERE conditions

// Add search condition if search term is provided
if (!empty($search_term)) {
    // Search across multiple relevant fields using OR
    $where_clauses[] = "(name LIKE :search OR category LIKE :search)"; // Add more fields if needed
    $params[':search'] = '%' . $search_term . '%'; // Use wildcard search
}

// Combine WHERE clauses if any exist
$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " AND " . implode(' AND ', $where_clauses); // Combine with AND
}

// --- Pagination: Count Total Items ---
$count_sql = "SELECT COUNT(id) " . $base_sql . $where_sql;
$total_items = 0;
$list_error = ''; // Initialize error variable

try {
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params); // Execute with search/filter parameters
    $total_items = (int)$stmt_count->fetchColumn();
} catch (PDOException $e) {
    error_log("Inventory Count Error (User: {$user_id}): " . $e->getMessage());
    $list_error = "Could not count inventory items. Please try again.";
    // If count fails, we can't proceed with pagination accurately
}

$total_pages = ($items_per_page > 0) ? ceil($total_items / $items_per_page) : 0;

// Adjust current page if it's out of bounds after calculation
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
} elseif ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $items_per_page;


// --- Fetch Items for Current Page ---
$items = []; // Initialize items array
if (empty($list_error)) { // Only fetch if count was successful (or no error occurred)
    $fetch_sql = "SELECT id, name, quantity, price, category, updated_at "
               . $base_sql . $where_sql
               . " ORDER BY " . $sort_column . " " . $sort_direction // Use validated sort parameters
               . " LIMIT :limit OFFSET :offset"; // Add pagination limits

    try {
        $stmt_fetch = $pdo->prepare($fetch_sql);
        // Bind parameters (including pagination limits)
        foreach ($params as $key => $val) {
            // Determine param type (example, adjust if needed)
            $param_type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt_fetch->bindValue($key, $val, $param_type);
        }
        $stmt_fetch->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt_fetch->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt_fetch->execute();
        $items = $stmt_fetch->fetchAll(); // Fetch all items for the current page
    } catch (PDOException $e) {
         error_log("Inventory List Fetch Error (User: {$user_id}, Page: {$current_page}): " . $e->getMessage());
         $list_error = "Could not fetch inventory items list. Please try again.";
         $items = []; // Ensure items is an empty array on error
    }
}

// --- Helper function for sort links ---
function sort_link($column, $text, $current_sort, $current_dir, $search_term) {
    $new_dir = ($current_sort === $column && $current_dir === 'ASC') ? 'desc' : 'asc';
    $arrow = '';
    if ($current_sort === $column) {
        $arrow = ($current_dir === 'ASC') ? ' &uarr;' : ' &darr;'; // Up/Down arrow
    }
    // Preserve search query and current page (optional, maybe reset page on sort?)
    $query_params = http_build_query(array_filter([ // array_filter removes empty values like search=''
        'sort' => $column,
        'dir' => $new_dir,
        'search' => $search_term,
        // 'page' => 1 // Uncomment to reset to page 1 when changing sort
    ]));
    return "<a href=\"?{$query_params}\" class=\"hover:text-blue-700 flex items-center\">{$text}{$arrow}</a>";
}

// --- Helper function for pagination links ---
function pagination_link($page, $text, $current_page, $total_pages, $search_term, $sort_column, $sort_direction) {
     if ($page < 1 || $page > $total_pages || $total_pages <= 1) return ''; // Don't link invalid pages or if only one page

     $query_params = http_build_query(array_filter([ // Preserve filters/sort
         'page' => $page,
         'search' => $search_term,
         'sort' => $sort_column,
         'dir' => $sort_direction
     ]));
     $base_class = 'px-3 py-1 border rounded transition duration-150 ease-in-out';
     $active_class = ($page == $current_page) ? 'bg-blue-500 text-white font-bold border-blue-500 cursor-default' : 'bg-white text-blue-600 border-gray-300 hover:bg-blue-50';
     $disabled_class = '';
     if (($page == $current_page - 1 && $current_page == 1) || ($page == $current_page + 1 && $current_page == $total_pages)) {
         $disabled_class = 'text-gray-400 border-gray-300 bg-gray-100 cursor-not-allowed';
         return "<span class=\"{$base_class} {$disabled_class}\">{$text}</span>"; // Non-clickable span for disabled prev/next
     }

     return "<a href=\"?{$query_params}\" class=\"{$base_class} {$active_class}\">{$text}</a>";
}

$page_title = 'Inventory - StockMaster'; // UPDATED title
require_once __DIR__ . '/../../includes/header.php'; // Render header
?>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <h1 class="text-3xl font-bold text-gray-800">Inventory Items</h1>
    <a href="create.php" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
        + Add New Item
    </a>
</div>

<div class="mb-4 bg-white p-4 rounded shadow border border-gray-200">
    <form action="index.php" method="GET" class="flex flex-col sm:flex-row gap-2 items-center">
         <input type="hidden" name="sort" value="<?php echo escape($sort_column); ?>">
         <input type="hidden" name="dir" value="<?php echo escape(strtoupper($sort_direction)); ?>">

        <label for="search" class="sr-only">Search Inventory</label>
        <input type="search" id="search" name="search" placeholder="Search Name or Category..."
               value="<?php echo escape($search_term); ?>"
               class="border rounded px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent flex-grow">
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded w-full sm:w-auto whitespace-nowrap transition duration-150 ease-in-out">
            Search
        </button>
         <?php if ($search_term): ?>
             <?php // Link to clear search, preserving sort order ?>
             <a href="index.php?sort=<?php echo escape($sort_column); ?>&dir=<?php echo escape(strtoupper($sort_direction)); ?>"
                class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-100 w-full sm:w-auto text-center whitespace-nowrap transition duration-150 ease-in-out">
                 Clear
             </a>
        <?php endif; ?>
    </form>
</div>

<?php // --- Display Status/Error Messages --- ?>
<?php if ($list_error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <p class="font-bold">Error</p>
        <p><?php echo escape($list_error); ?></p>
    </div>
<?php endif; ?>

<?php // Display status messages from redirects (e.g., after create/update/delete)
    $status = $_GET['status'] ?? '';
    $status_message = '';
    $status_type = 'info'; // 'info', 'success', 'warning', 'error'

    switch ($status) {
        case 'created': $status_message = 'Item added successfully!'; $status_type = 'success'; break;
        case 'updated': $status_message = 'Item updated successfully!'; $status_type = 'success'; break;
        case 'deleted': $status_message = 'Item deleted successfully!'; $status_type = 'success'; break; // Use a neutral/success style
        case 'notfound': $status_message = 'Item not found or access denied.'; $status_type = 'warning'; break;
        case 'error': $status_message = 'An error occurred. Please try again.'; $status_type = 'error'; break;
    }

    if ($status_message):
        $alert_classes = [
            'success' => 'bg-green-100 border-green-500 text-green-700',
            'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
            'error'   => 'bg-red-100 border-red-500 text-red-700',
            'info'    => 'bg-blue-100 border-blue-500 text-blue-700',
        ];
        $alert_class = $alert_classes[$status_type] ?? $alert_classes['info'];
?>
    <div class="<?php echo $alert_class; ?> border-l-4 p-4 mb-4" role="alert">
        <p><?php echo escape($status_message); ?></p>
    </div>
<?php endif; ?>

<?php // --- Inventory Table --- ?>
<div class="bg-white shadow-md rounded-lg overflow-x-auto border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <?php echo sort_link('name', 'Name', $sort_column, $sort_direction, $search_term); ?>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <?php echo sort_link('quantity', 'Quantity', $sort_column, $sort_direction, $search_term); ?>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                     <?php echo sort_link('price', 'Price', $sort_column, $sort_direction, $search_term); ?>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                     <?php echo sort_link('category', 'Category', $sort_column, $sort_direction, $search_term); ?>
                </th>
                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                     <?php echo sort_link('updated_at', 'Last Updated', $sort_column, $sort_direction, $search_term); ?>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($items) && empty($list_error)): // Show message only if no error occurred ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center italic">
                        <?php echo ($search_term) ? 'No items found matching your search.' : 'Your inventory is empty. Add some items!'; ?>
                    </td>
                </tr>
            <?php elseif (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo escape($item['name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"> <?php // Align quantity right ?>
                            <?php echo escape(number_format($item['quantity'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right"> <?php // Align price right ?>
                            $<?php echo escape(number_format($item['price'], 2)); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo escape($item['category'] ?? '-'); // Display '-' if category is null/empty ?>
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3"> <?php // Actions ?>
                            <a href="edit.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 transition duration-150 ease-in-out">Edit</a>
                            <?php // Delete Form - Use POST and JS confirm ?>
                            <form action="delete.php" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete the item \'<?php echo escape(addslashes($item['name'])); ?>\'? This action cannot be undone.');">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="item_name" value="<?php echo escape($item['name']); ?>"> <?php // Pass name for logging ?>
                                <button type="submit" class="text-red-600 hover:text-red-900 focus:outline-none transition duration-150 ease-in-out">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div> <?php // End table container ?>

<?php if ($total_pages > 1): ?>
    <nav class="mt-6 flex justify-center items-center space-x-1" aria-label="Pagination">
        <?php echo pagination_link($current_page - 1, '&laquo; Prev', $current_page, $total_pages, $search_term, $sort_column, $sort_direction); ?>

        <?php // Generate page number links (example: show current +/- 2 pages)
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);

            if ($start_page > 1) echo pagination_link(1, '1', $current_page, $total_pages, $search_term, $sort_column, $sort_direction) . ($start_page > 2 ? '<span class="px-2 py-1 text-gray-500">...</span>' : '');

            for ($i = $start_page; $i <= $end_page; $i++):
                echo pagination_link($i, $i, $current_page, $total_pages, $search_term, $sort_column, $sort_direction);
            endfor;

            if ($end_page < $total_pages) echo ($end_page < $total_pages - 1 ? '<span class="px-2 py-1 text-gray-500">...</span>' : '') . pagination_link($total_pages, $total_pages, $current_page, $total_pages, $search_term, $sort_column, $sort_direction);
        ?>

        <?php echo pagination_link($current_page + 1, 'Next &raquo;', $current_page, $total_pages, $search_term, $sort_column, $sort_direction); ?>
    </nav>
     <div class="mt-2 text-center text-sm text-gray-500">
        Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> (Total items: <?php echo $total_items; ?>)
    </div>
<?php elseif ($total_items > 0 && empty($list_error)): // Show total if only one page and no errors ?>
     <div class="mt-4 text-center text-sm text-gray-500">
        Total items: <?php echo $total_items; ?>
    </div>
<?php endif; ?>


<?php require_once __DIR__ . '/../../includes/footer.php'; // Render footer ?>