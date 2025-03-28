<?php
// public/dashboard.php
require_once __DIR__ . '/../includes/db.php'; // Loads functions, starts session, connects DB

require_login(); // Redirects to login if user is not authenticated

$user_id = get_user_id();
$username = $_SESSION['username'] ?? 'User'; // Get username from session

// Initialize variables for dashboard data
$total_items = 0;
$total_quantity = 0;
$recent_items = [];
$dashboard_error = ''; // To store any errors during data fetching

// Fetch dashboard data within a try-catch block for error handling
try {
    // Count distinct item types for the logged-in user
    $stmt_items = $pdo->prepare("SELECT COUNT(id) FROM inventory_items WHERE user_id = :user_id");
    $stmt_items->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_items->execute();
    $total_items = (int)$stmt_items->fetchColumn(); // Ensure integer type

    // Sum total stock quantity for the logged-in user
    $stmt_qty = $pdo->prepare("SELECT SUM(quantity) FROM inventory_items WHERE user_id = :user_id");
    $stmt_qty->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_qty->execute();
    // Use null coalescing operator for safety if user has no items (SUM returns NULL)
    $total_quantity = (int)($stmt_qty->fetchColumn() ?? 0);

    // Get 5 most recently updated items for the logged-in user
    $stmt_recent = $pdo->prepare("SELECT id, name, quantity, category, updated_at
                                   FROM inventory_items
                                   WHERE user_id = :user_id
                                   ORDER BY updated_at DESC
                                   LIMIT 5");
    $stmt_recent->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_recent->execute();
    $recent_items = $stmt_recent->fetchAll(); // Fetch all results (up to 5)

} catch (PDOException $e) {
    // Log the specific error for debugging
    error_log("Dashboard Data Fetch Error (User: {$user_id}): " . $e->getMessage());
    // Set a user-friendly error message
    $dashboard_error = "Could not load dashboard data. Please try again later.";
}

$page_title = 'Dashboard - StockMaster'; // UPDATED title
require_once __DIR__ . '/../includes/header.php'; // Render header
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Welcome to StockMaster, <?php echo escape($username); ?>!</h1>

<?php // Display error message if data fetching failed ?>
<?php if ($dashboard_error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p class="font-bold">Error</p>
        <p><?php echo escape($dashboard_error); ?></p>
    </div>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md text-center border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Item Types</h2>
        <p class="text-4xl font-bold text-blue-600"><?php echo escape($total_items); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Stock Quantity</h2>
        <p class="text-4xl font-bold text-green-600"><?php echo escape($total_quantity); ?></p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Your Role</h2>
        <p class="text-2xl font-bold text-purple-600 capitalize"><?php echo escape(get_user_role()); ?></p>
    </div>
</section>

<section class="bg-white p-6 rounded-lg shadow-md mb-8 border border-gray-200">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Recent Inventory Updates</h2>
    <?php if (!empty($recent_items)): ?>
        <div class="overflow-x-auto"> <?php // Add horizontal scroll on small screens ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($recent_items as $item): ?>
                    <li class="py-3 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                        <div class="mb-1 sm:mb-0"> <?php // Item details ?>
                            <span class="font-medium text-gray-900"><?php echo escape($item['name']); ?></span>
                            <span class="text-sm text-gray-600 ml-2">(Qty: <?php echo escape($item['quantity']); ?>)</span>
                            <?php if($item['category']): ?>
                                <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded ml-2 align-middle"><?php echo escape($item['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-gray-500 flex-shrink-0"> <?php // Timestamp ?>
                            Updated: <?php echo date('M d, Y H:i', strtotime($item['updated_at'])); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <p class="text-gray-500 italic">No recent inventory activity found for your account.</p>
    <?php endif; ?>
     <div class="mt-4 border-t pt-4"> <?php // Link to full inventory ?>
         <a href="/inventory/index.php" class="text-blue-600 hover:underline font-medium">View All Inventory &rarr;</a>
     </div>
</section>

<section class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Inventory Insights (Placeholder)</h2>
    <p class="text-gray-500 italic">Graphical charts displaying inventory trends could be added here using a JavaScript library like Chart.js or ApexCharts.</p>
    <?php /* Example Canvas for Chart.js
    <div class="mt-4" style="max-width: 600px; margin: auto;">
        <canvas id="inventoryChart"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Basic example chart setup - requires data to be passed from PHP
        const ctx = document.getElementById('inventoryChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar', // or 'line', 'pie'
                data: {
                    labels: ['Item A', 'Item B', 'Item C'], // Replace with actual item names/categories
                    datasets: [{
                        label: 'Stock Quantity',
                        data: [12, 19, 3], // Replace with actual quantities
                        borderWidth: 1,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)', // Example color
                        borderColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: { scales: { y: { beginAtZero: true } } }
            });
        }
    </script>
    */ ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; // Render footer ?>