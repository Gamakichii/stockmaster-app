<?php
// public/admin/audit_log.php
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure logged in

// --- Authorization Check ---
if (!is_admin()) {
     // Show access denied message inline
     $page_title = 'Access Denied';
     require_once __DIR__ . '/../../includes/header.php';
     echo '<div class="flex items-center justify-center min-h-[calc(100vh-200px)]"><div class="max-w-md w-full mx-auto mt-10 bg-white p-8 border border-red-300 rounded-lg shadow-lg text-center"><h1 class="text-2xl font-bold text-red-700 mb-4">Access Denied</h1><p class="text-red-600 mb-6">You do not have permission to view audit logs.</p><div><a href="/dashboard.php" class="text-blue-600 hover:underline">&larr; Go Back to Dashboard</a></div></div></div>';
     require_once __DIR__ . '/../../includes/footer.php';
     exit;
}

// --- Configuration & Input ---
$logs_per_page = 30; // Number of log entries per page
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
// Add filters later (e.g., by user, action, date range)
// $filter_user = $_GET['user'] ?? '';
// $filter_action = $_GET['action'] ?? '';

// --- Build Query ---
$base_sql = "FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id"; // Join to get username
$params = [];
$where_clauses = [];

// Example Filter (add UI later)
// if (!empty($filter_user)) { $where_clauses[] = "u.username LIKE :user"; $params[':user'] = '%'.$filter_user.'%'; }
// if (!empty($filter_action)) { $where_clauses[] = "a.action = :action"; $params[':action'] = $filter_action; }

$where_sql = !empty($where_clauses) ? " WHERE " . implode(' AND ', $where_clauses) : '';

// --- Pagination: Count Total Logs ---
$count_sql = "SELECT COUNT(a.id) " . $base_sql . $where_sql;
$total_logs = 0;
$log_error = '';

try {
    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_logs = (int)$stmt_count->fetchColumn();
} catch (PDOException $e) {
    error_log("Audit Log Count Error: " . $e->getMessage());
    $log_error = "Could not count audit log entries.";
}

$total_pages = ($logs_per_page > 0) ? ceil($total_logs / $logs_per_page) : 0;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $logs_per_page;

// --- Fetch Logs for Current Page ---
$logs = [];
if (empty($log_error)) {
    $fetch_sql = "SELECT a.id, a.timestamp, a.user_id, a.action, a.details, a.ip_address, u.username "
               . $base_sql . $where_sql
               . " ORDER BY a.timestamp DESC " // Most recent first
               . " LIMIT :limit OFFSET :offset";

    try {
        $stmt_fetch = $pdo->prepare($fetch_sql);
        foreach ($params as $key => $val) {
            $stmt_fetch->bindValue($key, $val);
        }
        $stmt_fetch->bindValue(':limit', $logs_per_page, PDO::PARAM_INT);
        $stmt_fetch->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $logs = $stmt_fetch->fetchAll();
    } catch (PDOException $e) {
        error_log("Audit Log Fetch Error (Page: {$current_page}): " . $e->getMessage());
        $log_error = "Could not fetch audit logs.";
        $logs = [];
    }
}

$page_title = 'Audit Logs - StockMaster'; // UPDATED title
require_once __DIR__ . '/../../includes/header.php';
?>

<?php // --- Breadcrumbs --- ?>
<nav aria-label="breadcrumb" class="mb-6 text-sm text-gray-500">
  <ol class="list-none p-0 inline-flex flex-wrap">
    <li class="flex items-center"><a href="/dashboard.php" class="hover:text-blue-600">Dashboard</a><span class="mx-2">/</span></li>
    <li class="flex items-center"><a href="/admin/index.php" class="hover:text-blue-600">Admin Panel</a><span class="mx-2">/</span></li>
    <li class="flex items-center text-gray-700" aria-current="page">Audit Logs</li>
  </ol>
</nav>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Audit Logs</h1>

<?php if ($log_error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <p class="font-bold">Error</p>
        <p><?php echo escape($log_error); ?></p>
    </div>
<?php endif; ?>

<?php /* <div class="mb-4 bg-white p-4 rounded shadow border border-gray-200">
    <form method="GET" action="audit_log.php"> Filters... </form>
</div>
*/ ?>

<p class="mb-4 text-sm text-gray-600">
    Displaying <?php echo count($logs); ?> log entries
    (Page <?php echo $current_page; ?> of <?php echo max(1, $total_pages); ?>, Total: <?php echo $total_logs; ?>). Most recent first.
</p>

<div class="bg-white shadow-md rounded-lg overflow-x-auto border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($logs) && empty($log_error)): ?>
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">No audit logs found matching criteria.</td></tr>
            <?php elseif (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo escape($log['username'] ?? 'System/Unknown'); ?>
                            <span class="text-xs text-gray-400 ml-1">(ID: <?php echo $log['user_id'] ?? 'N/A'; ?>)</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium"><?php echo escape($log['action']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-lg"> <?php // Limit width ?>
                            <?php // Attempt to pretty-print JSON details
                                $details_text = $log['details'];
                                if ($details_text) {
                                    $details_array = json_decode($details_text, true);
                                    if (json_last_error() === JSON_ERROR_NONE) {
                                        // Use <pre> for better formatting of JSON output
                                        echo '<pre class="text-xs bg-gray-100 p-2 rounded overflow-x-auto whitespace-pre-wrap break-all">' . escape(json_encode($details_array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
                                    } else {
                                        // Show as plain text if not valid JSON or very long
                                        echo '<span class="text-xs break-all">' . escape(mb_strimwidth($details_text, 0, 200, '...')) . '</span>'; // Truncate long text
                                    }
                                } else { echo '-'; }
                            ?>
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo escape($log['ip_address'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php // --- Pagination for Logs ---
    // Basic pagination link generation (can be moved to functions.php)
    if ($total_pages > 1): ?>
    <nav class="mt-6 flex justify-center items-center space-x-1" aria-label="Audit Log Pagination">
        <?php // Previous Page Link
             $prev_page = $current_page - 1;
             $link_class = "px-3 py-1 border rounded transition duration-150 ease-in-out";
             if ($prev_page >= 1): ?>
                <a href="?page=<?php echo $prev_page; /* Add other filters */ ?>" class="<?php echo $link_class; ?> bg-white text-blue-600 border-gray-300 hover:bg-blue-50">&laquo; Prev</a>
             <?php else: ?>
                 <span class="<?php echo $link_class; ?> text-gray-400 border-gray-300 bg-gray-100 cursor-not-allowed">&laquo; Prev</span>
             <?php endif; ?>

        <?php // Page Number Links (simplified)
            for ($i = 1; $i <= $total_pages; $i++):
                $active_class = ($i == $current_page) ? 'bg-blue-500 text-white font-bold border-blue-500 cursor-default' : 'bg-white text-blue-600 border-gray-300 hover:bg-blue-50';
                // Basic logic to limit displayed page numbers could be added here
                if ($total_pages <= 10 || abs($i - $current_page) < 3 || $i == 1 || $i == $total_pages) {
                    echo "<a href='?page=$i' class='$link_class $active_class'>$i</a>";
                } elseif (abs($i - $current_page) === 3) {
                    echo "<span class='$link_class border-none text-gray-500'>...</span>";
                }
             endfor; ?>

        <?php // Next Page Link
            $next_page = $current_page + 1;
            if ($next_page <= $total_pages): ?>
                <a href="?page=<?php echo $next_page; /* Add other filters */ ?>" class="<?php echo $link_class; ?> bg-white text-blue-600 border-gray-300 hover:bg-blue-50">Next &raquo;</a>
             <?php else: ?>
                 <span class="<?php echo $link_class; ?> text-gray-400 border-gray-300 bg-gray-100 cursor-not-allowed">Next &raquo;</span>
             <?php endif; ?>
    </nav>
<?php endif; ?>


<?php
require_once __DIR__ . '/../../includes/footer.php';
?>