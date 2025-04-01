<?php
// public/dashboard.php - MODIFIED with Charts
require_once __DIR__ . '/../includes/db.php'; // Loads functions, starts session, connects DB

require_login(); // Redirects to login if user is not authenticated

$user_id = get_user_id();
$username = $_SESSION['username'] ?? 'User'; // Get username from session

// --- Initialize variables ---
// Stats
$total_items = 0;
$total_quantity = 0;
$recent_items = [];
// Chart Data
$category_labels = [];
$category_data = [];
$top_item_labels = [];
$top_item_data = [];
// Error flag
$dashboard_error = '';

// --- Fetch ALL dashboard data (Stats and Chart Data) ---
try {
    // --- Basic Stats ---
    $stmt_items = $pdo->prepare("SELECT COUNT(id) FROM inventory_items WHERE user_id = :user_id");
    $stmt_items->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_items->execute();
    $total_items = (int)$stmt_items->fetchColumn();

    $stmt_qty = $pdo->prepare("SELECT SUM(quantity) FROM inventory_items WHERE user_id = :user_id");
    $stmt_qty->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_qty->execute();
    $total_quantity = (int)($stmt_qty->fetchColumn() ?? 0);

    $stmt_recent = $pdo->prepare("SELECT id, name, quantity, category, updated_at
                                   FROM inventory_items
                                   WHERE user_id = :user_id
                                   ORDER BY updated_at DESC
                                   LIMIT 5");
    $stmt_recent->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_recent->execute();
    $recent_items = $stmt_recent->fetchAll();

    // --- ★★★ Fetch Data for Category Pie Chart ★★★ ---
    // Group NULL categories as 'Uncategorized'
    $stmt_category = $pdo->prepare("
        SELECT COALESCE(category, 'Uncategorized') as category_name, SUM(quantity) as total_quantity
        FROM inventory_items
        WHERE user_id = :user_id AND quantity > 0
        GROUP BY category_name
        HAVING total_quantity > 0
        ORDER BY total_quantity DESC
    ");
    $stmt_category->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_category->execute();
    $category_results = $stmt_category->fetchAll();

    // Process category data for Chart.js
    foreach ($category_results as $row) {
        $category_labels[] = $row['category_name'];
        $category_data[] = (int)$row['total_quantity']; // Ensure it's an integer
    }

    // --- ★★★ Fetch Data for Top 5 Items Bar Chart ★★★ ---
    $stmt_top_items = $pdo->prepare("
        SELECT name, quantity
        FROM inventory_items
        WHERE user_id = :user_id AND quantity > 0
        ORDER BY quantity DESC
        LIMIT 5
    ");
    $stmt_top_items->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_top_items->execute();
    $top_items_results = $stmt_top_items->fetchAll();

    // Process top items data for Chart.js
    foreach ($top_items_results as $row) {
        $top_item_labels[] = $row['name'];
        $top_item_data[] = (int)$row['quantity']; // Ensure it's an integer
    }


} catch (PDOException $e) {
    error_log("Dashboard Data Fetch Error (User: {$user_id}): " . $e->getMessage());
    $dashboard_error = "Could not load some dashboard data. Please try again later.";
    // Avoid trying to render charts if data fetch failed partially
    $category_labels = $category_data = $top_item_labels = $top_item_data = [];
}

$page_title = 'Dashboard - StockMaster';
require_once __DIR__ . '/../includes/header.php'; // Render header (Ensure Chart.js is NOT loaded here)
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Welcome to StockMaster, <?php echo escape($username); ?>!</h1>

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

<section class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 text-center">Items by Category (Quantity)</h2>
        <?php if (!empty($category_labels) && !empty($category_data)): ?>
            <div class="relative mx-auto" style="max-height: 400px; max-width: 400px;"> <?php // Constrain size ?>
                <canvas id="categoryPieChart"></canvas>
            </div>
        <?php else: ?>
             <p class="text-gray-500 italic text-center">No category data available to display chart.</p>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 text-center">Top 5 Items by Quantity</h2>
         <?php if (!empty($top_item_labels) && !empty($top_item_data)): ?>
            <div class="relative" style="max-height: 400px;"> <?php // Constrain height ?>
                <canvas id="topItemsBarChart"></canvas>
            </div>
        <?php else: ?>
             <p class="text-gray-500 italic text-center">No item data available to display chart.</p>
        <?php endif; ?>
    </div>
</section>


<section class="bg-white p-6 rounded-lg shadow-md mb-8 border border-gray-200">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Recent Inventory Updates</h2>
    <?php if (!empty($recent_items)): ?>
        <div class="overflow-x-auto">
            <ul class="divide-y divide-gray-200">
                <?php foreach ($recent_items as $item): ?>
                    <li class="py-3 flex flex-col sm:flex-row justify-between items-start sm:items-center">
                        <div class="mb-1 sm:mb-0">
                            <span class="font-medium text-gray-900"><?php echo escape($item['name']); ?></span>
                            <span class="text-sm text-gray-600 ml-2">(Qty: <?php echo escape($item['quantity']); ?>)</span>
                            <?php if($item['category']): ?>
                                <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded ml-2 align-middle"><?php echo escape($item['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-sm text-gray-500 flex-shrink-0">
Updated: <?php
    try {
        $date = new DateTime($item['updated_at'], new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('Asia/Manila'));
        echo $date->format('M d, Y H:i');
    } catch (Exception $e) {
        echo 'Invalid Date';
    }
?>	
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else: ?>
        <p class="text-gray-500 italic">No recent inventory activity found for your account.</p>
    <?php endif; ?>
     <div class="mt-4 border-t pt-4">
         <a href="/inventory/index.php" class="text-blue-600 hover:underline font-medium">View All Inventory &rarr;</a>
     </div>
</section>

<?php // --- ★★★ Add Chart.js Library and Initialization Script ★★★ --- ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script> <?php // Chart.js CDN ?>

<script>
    // Wait for the DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', () => {

        // --- Data from PHP ---
        const categoryLabels = <?php echo json_encode($category_labels); ?>;
        const categoryData = <?php echo json_encode($category_data); ?>;
        const topItemLabels = <?php echo json_encode($top_item_labels); ?>;
        const topItemData = <?php echo json_encode($top_item_data); ?>;

        // --- Helper function for generating chart colors ---
        const generateColors = (numColors) => {
            const colors = [];
            // Basic HSL color generation loop - adjust saturation/lightness as needed
            for (let i = 0; i < numColors; i++) {
                const hue = (i * (360 / (numColors < 1 ? 1 : numColors))) % 360; // Distribute hues
                colors.push(`hsla(${hue}, 70%, 60%, 0.8)`); // Use hsla for slight transparency
            }
            return colors;
        };

        // --- Create Category Pie Chart ---
        const categoryCtx = document.getElementById('categoryPieChart');
        if (categoryCtx && categoryLabels.length > 0) {
            new Chart(categoryCtx, {
                type: 'pie', // Or 'doughnut'
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Quantity by Category',
                        data: categoryData,
                        backgroundColor: generateColors(categoryLabels.length),
                        borderWidth: 1,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allow chart to fill container based on style constraints
                    plugins: {
                        legend: {
                            position: 'top', // Or 'bottom', 'left', 'right'
                        },
                        title: {
                            display: false, // Title is already in the H2 tag
                            // text: 'Stock Quantity by Category'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += context.parsed;
                                        // Optionally calculate and add percentage
                                        // const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                        // const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) + '%' : '0%';
                                        // label += ` (${percentage})`;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } else if (categoryCtx) {
             // Optional: Hide canvas or show message if no data and element exists
             // categoryCtx.parentElement.innerHTML = '<p class="text-gray-500 italic text-center py-10">No category data for chart.</p>';
        }

        // --- Create Top Items Bar Chart ---
        const topItemsCtx = document.getElementById('topItemsBarChart');
        if (topItemsCtx && topItemLabels.length > 0) {
            new Chart(topItemsCtx, {
                type: 'bar',
                data: {
                    labels: topItemLabels,
                    datasets: [{
                        label: 'Quantity',
                        data: topItemData,
                        backgroundColor: generateColors(topItemLabels.length), // Reuse color generator
                        borderColor: generateColors(topItemLabels.length).map(c => c.replace('0.8','1')), // Solid border
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // Make it a horizontal bar chart if many items/long labels
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { // Note: For horizontal bar, x is the value axis
                            beginAtZero: true,
                             title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        y: { // And y is the category axis
                             title: {
                                display: true,
                                text: 'Item Name'
                            }
                        }
                    },
                    plugins: {
                         legend: {
                            display: false // Only one dataset, legend not very useful
                        },
                         title: {
                            display: false,
                            // text: 'Top 5 Items by Quantity'
                        }
                    }
                }
            });
        } else if (topItemsCtx) {
            // Optional: Hide canvas or show message if no data
             // topItemsCtx.parentElement.innerHTML = '<p class="text-gray-500 italic text-center py-10">No item data for chart.</p>';
        }

    }); // End DOMContentLoaded listener
</script>


<?php require_once __DIR__ . '/../includes/footer.php'; // Render footer ?>
