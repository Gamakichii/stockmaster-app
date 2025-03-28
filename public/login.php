<?php
// public/login.php
require_once __DIR__ . '/../includes/db.php'; // Loads DB, functions, session

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /dashboard.php');
    exit;
}

$error_message = '';
$username_input = ''; // Preserve username input on failed login

// Handle POST request for login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $username_input = $username; // Store for repopulating form

    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            // Prepare statement to find user by username
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();

            // Verify user exists and password matches the hash
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct!

                // Regenerate session ID upon login for security (prevents session fixation)
                session_regenerate_id(true);

                // Store essential user info in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                // Log successful login action
                log_action('login_success');

                // Redirect to the dashboard (or intended page if stored)
                header('Location: /dashboard.php');
                exit;
            } else {
                // Invalid username or password
                log_action('login_failed', ['username' => $username]); // Log failed attempt
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            // Log database errors during login attempt
            error_log("Login DB Error: " . $e->getMessage());
            $error_message = 'An error occurred during login. Please try again later.';
        }
    }
}

$page_title = 'Login - StockMaster'; // UPDATED title
require_once __DIR__ . '/../includes/header.php'; // Render header AFTER potential redirects
?>

<div class="flex items-center justify-center min-h-[calc(100vh-200px)]"> <?php // Center form vertically ?>
    <div class="max-w-md w-full bg-white p-8 border border-gray-300 rounded-lg shadow-lg m-4"> <?php // Added shadow-lg ?>
        <h1 class="text-2xl font-bold text-center text-gray-700 mb-6">Login to StockMaster</h1>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"> <?php // Alert styling ?>
                <p class="font-bold">Error</p>
                <p><?php echo escape($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" novalidate>
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" id="username" name="username" required autofocus <?php // Autofocus username ?>
                       value="<?php echo escape($username_input); ?>"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && isset($_POST['username'])) echo 'border-red-500'; ?>">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php if($error_message && isset($_POST['password'])) echo 'border-red-500'; ?>">
            </div>
            <div class="flex items-center justify-between mb-4">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-150 ease-in-out">
                    Sign In
                </button>
                <a href="#" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    Forgot Password?
                </a>
            </div>
             <div class="text-center border-t pt-4 mt-4">
                 <p class="text-gray-600 text-sm">
                     Don't have an account?
                     <a href="/register.php" class="font-bold text-blue-500 hover:text-blue-800">
                         Register here
                     </a>
                 </p>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>