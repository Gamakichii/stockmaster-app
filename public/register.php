<?php
// public/register.php - CORRECTED VERSION
require_once __DIR__ . '/../includes/db.php'; // Loads DB, functions, session

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

// Initialize variables
$error_message = '';
$success_message = '';
$username = ''; // Keep input values on error

// Handle POST request for registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // $role = 'staff'; // Default role for new users

    // --- Validation ---
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        // Username validation: letters, numbers, underscore, 3-20 chars
        $error_message = 'Username must be 3-20 characters (letters, numbers, underscore only).';
    } elseif (strlen($password) < 8) {
        // Password length validation
        $error_message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        // Password confirmation check
        $error_message = 'Passwords do not match.';
    } else {
        // --- Validation Passed - Check if username already exists ---
        try {
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
            $stmtCheck->bindParam(':username', $username, PDO::PARAM_STR);
            $stmtCheck->execute();

            if ($stmtCheck->fetch()) {
                $error_message = 'Username is already taken. Please choose another.';
            } else {
                // --- Username available, proceed with insertion ---

                // Hash the password securely using default algorithm (bcrypt recommended)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Prepare insert statement
                $insertStmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, :role)");

                // Execute insertion with default role 'staff'
                $insertStmt->execute([
                    ':username' => $username,
                    ':password' => $hashed_password,
                    ':role' => 'staff' // Assign default role
                ]);

                $new_user_id = $pdo->lastInsertId(); // Get the ID of the newly created user

                // Log successful registration
                log_action('register_success', ['username' => $username, 'user_id' => $new_user_id]);

                // Set success message and clear username for security (don't clear password fields)
                $success_message = 'Registration successful! You can now <a href="login.php" class="font-bold text-blue-700 hover:underline">login</a>.';
                $username = ''; // Clear username input on success

            } // End else (username available)
        } catch (PDOException $e) {
            error_log("Registration DB Error: " . $e->getMessage());
            $error_message = 'An error occurred during registration. Please try again later.';
        } // End try/catch
    } // End else (validation passed)
} // End if POST request

$page_title = 'Register - StockMaster'; // UPDATED title
require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex items-center justify-center min-h-[calc(100vh-200px)]">
    <div class="max-w-md w-full bg-white p-8 border border-gray-300 rounded-lg shadow-lg m-4">
        <h1 class="text-2xl font-bold text-center text-gray-700 mb-6">Register for StockMaster</h1>

        <?php // Display error or success messages ?>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p class="font-bold">Error</p>
                <p><?php echo escape($error_message); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
             <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                 <p class="font-bold">Success</p>
                 <p><?php echo $success_message; // Allow the HTML link ?></p>
             </div>
        <?php endif; ?>

        <?php // Only show form if registration wasn't successful ?>
        <?php if (!$success_message): ?>
        <form action="register.php" method="POST" novalidate>
            <?php // This is the USERNAME input block ?>
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" id="username" name="username" required value="<?php echo escape($username); ?>"
                       maxlength="20" pattern="^[a-zA-Z0-9_]{3,20}$" aria-describedby="usernameHelp"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && isset($_POST['username'])) echo 'border-red-500'; ?>">
                <p id="usernameHelp" class="text-xs text-gray-500 mt-1">3-20 characters (letters, numbers, underscore).</p>
            </div>

            <?php // This is the PASSWORD input block that follows ?>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password" required minlength="8" aria-describedby="passwordHelp"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && (isset($_POST['password']) || isset($_POST['confirm_password']))) echo 'border-red-500'; ?>">
                <p id="passwordHelp" class="text-xs text-gray-500 mt-1">Minimum 8 characters.</p>
            </div>

            <?php // Confirm Password input ?>
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && (isset($_POST['password']) || isset($_POST['confirm_password']))) echo 'border-red-500'; ?>">
            </div>

            <?php // Submit button and Login link ?>
            <div class="flex items-center justify-between">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Register
                </button>
                 <a href="/login.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Already registered? Login
                </a>
            </div>
        </form>
        <?php endif; // End if !$success_message ?>
    </div> <?php // End form container div ?>
</div> <?php // End flex centering div ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>