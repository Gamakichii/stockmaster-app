<?php
// public/admin/index.php
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure user is logged in first

// --- Authorization Check: Only allow Admins ---
if (!is_admin()) {
     // Option 1: Redirect non-admins back to dashboard with a message
     // header('Location: /dashboard.php?status=unauthorized');
     // exit;

     // Option 2: Show an explicit "Access Denied" page
     $page_title = 'Access Denied';
     require_once __DIR__ . '/../../includes/header.php';
     echo '<div class="flex items-center justify-center min-h-[calc(100vh-200px)]">'; // Center vertically
     echo '<div class="max-w-md w-full mx-auto mt-10 bg-white p-8 border border-red-300 rounded-lg shadow-lg text-center">';
     echo '<h1 class="text-2xl font-bold text-red-700 mb-4">Access Denied</h1>';
     echo '<p class="text-red-600 mb-6">You do not have the necessary permissions to access the Admin Panel.</p>';
     echo '<div><a href="/dashboard.php" class="text-blue-600 hover:underline">&larr; Go Back to Dashboard</a></div>';
     echo '</div>';
     echo '</div>';
     require_once __DIR__ . '/../../includes/footer.php';
     exit; // Stop script execution for non-admins
}

// --- Admin Only Content Below ---
$page_title = 'Admin Panel - StockMaster'; // UPDATED title
require_once __DIR__ . '/../../includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">StockMaster Admin Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-3">User Management</h2>
        <p class="text-gray-600 mb-4 text-sm">Add, edit, or remove user accounts and manage their roles.</p>
        <a href="/admin/users.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded transition duration-150 ease-in-out">Manage Users</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-3">System Settings</h2>
        <p class="text-gray-600 mb-4 text-sm">Configure application-wide settings (functionality to be implemented).</p>
        <span class="inline-block bg-gray-400 text-white font-medium py-2 px-4 rounded cursor-not-allowed" aria-disabled="true" title="Feature not yet available">Configure Settings</span>
    </div>

     <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-3">Audit Logs</h2>
        <p class="text-gray-600 mb-4 text-sm">View a trail of important user actions and system events.</p>
        <a href="/admin/audit_log.php" class="inline-block bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded transition duration-150 ease-in-out">View Logs</a>
    </div>

    <?php /* Example: Inventory Overview Card
    <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200">
        <h2 class="text-xl font-semibold text-gray-700 mb-3">Global Inventory Overview</h2>
        <p class="text-gray-600 mb-4 text-sm">View combined inventory stats across all users (functionality TBD).</p>
        <span class="inline-block bg-gray-400 text-white font-medium py-2 px-4 rounded cursor-not-allowed" aria-disabled="true" title="Feature not yet available">View Overview</span>
    </div>
    */ ?>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>