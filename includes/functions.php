<?php
// includes/functions.php

// Ensure DB connection is attempted if needed (db.php includes this)
// require_once __DIR__ . '/db.php';

// Check if user is logged in
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

// Redirect if not logged in
function require_login(): void {
    if (!is_logged_in()) {
        // Optional: Store intended destination?
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /login.php'); // Adjust path if your app isn't at the root
        exit;
    }
}

// Get current user's ID
function get_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// Get current user's role
function get_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

// Check if the current user is an admin
function is_admin(): bool {
    return get_user_role() === 'admin';
}

// Basic function to sanitize output against XSS
function escape(?string $html): string {
    return htmlspecialchars($html ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Basic function to log actions (expand as needed)
function log_action(string $action, ?array $details = null): void {
    // Access the global PDO object defined in db.php
    global $pdo;

    // Avoid logging if PDO isn't initialized (e.g., during early config errors)
    if (!isset($pdo)) {
        error_log("Attempted to log action '$action' before PDO initialization.");
        return;
    }

    // Basic implementation - consider a more robust logging library for production
    // Requires an 'audit_logs' table (see SQL setup phase)
    try {
        $sql = "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            // Use null if user_id isn't set in session (e.g., failed login)
            ':user_id' => get_user_id(),
            ':action' => $action,
            // Store structured details as JSON
            ':details' => $details ? json_encode($details) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);
    } catch (PDOException $e) {
        // Log the logging failure itself, but don't stop application execution
        error_log("Audit Log Failed for action '$action': " . $e->getMessage());
    }
}
?>