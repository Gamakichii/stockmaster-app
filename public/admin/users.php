<?php
// public/admin/users.php
require_once __DIR__ . '/../../includes/db.php'; // Loads DB, functions, session

require_login(); // Ensure logged in

// --- Authorization Check ---
if (!is_admin()) {
     $page_title = 'Access Denied';
     require_once __DIR__ . '/../../includes/header.php';
     echo '<div class="flex items-center justify-center min-h-[calc(100vh-200px)]"><div class="max-w-md w-full mx-auto mt-10 bg-white p-8 border border-red-300 rounded-lg shadow-lg text-center"><h1 class="text-2xl font-bold text-red-700 mb-4">Access Denied</h1><p class="text-red-600 mb-6">You do not have permission to manage users.</p><div><a href="/dashboard.php" class="text-blue-600 hover:underline">&larr; Go Back to Dashboard</a></div></div></div>';
     require_once __DIR__ . '/../../includes/footer.php';
     exit;
}

// --- Logic for listing, adding, editing, deleting users would go here ---
// --- This is a placeholder ---

$users = [];
$user_error = '';
try {
    // Fetch all users (excluding passwords)
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("User Management Fetch Error: " . $e->getMessage());
    $user_error = "Could not load user list.";
}


$page_title = 'User Management - StockMaster'; // UPDATED title
require_once __DIR__ . '/../../includes/header.php';
?>

<?php // --- Breadcrumbs --- ?>
<nav aria-label="breadcrumb" class="mb-6 text-sm text-gray-500">
  <ol class="list-none p-0 inline-flex flex-wrap">
    <li class="flex items-center"><a href="/dashboard.php" class="hover:text-blue-600">Dashboard</a><span class="mx-2">/</span></li>
    <li class="flex items-center"><a href="/admin/index.php" class="hover:text-blue-600">Admin Panel</a><span class="mx-2">/</span></li>
    <li class="flex items-center text-gray-700" aria-current="page">User Management</li>
  </ol>
</nav>

<div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
    <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition duration-150 ease-in-out" onclick="alert('Add User functionality not yet implemented.');">
        + Add New User
    </button> <?php // Placeholder button ?>
</div>

<?php if ($user_error): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        <p class="font-bold">Error</p>
        <p><?php echo escape($user_error); ?></p>
    </div>
<?php endif; ?>

<div class="bg-white shadow-md rounded-lg overflow-x-auto border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($users) && empty($user_error)): ?>
                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">No users found.</td></tr>
            <?php elseif(!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['id']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo escape($user['username']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize"><?php echo escape($user['role']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-3">
                        <button onclick="alert('Edit User <?php echo $user['id']; ?> not implemented.');" class="text-indigo-600 hover:text-indigo-900 transition duration-150 ease-in-out">Edit</button>
                        <?php // Add delete form/button here - Ensure you don't delete the current admin! ?>
                        <?php if ($user['id'] !== get_user_id()): // Prevent self-delete ?>
                            <button onclick="if(confirm('Delete user <?php echo escape(addslashes($user['username'])); ?>?')) alert('Delete User <?php echo $user['id']; ?> not implemented.');" class="text-red-600 hover:text-red-900 transition duration-150 ease-in-out">Delete</button>
                        <?php else: ?>
                            <span class="text-gray-400 cursor-not-allowed">Delete</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>