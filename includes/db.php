<?php
// includes/db.php

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    // Consider secure session settings here (e.g., cookie_httponly)
    session_start();
}

// Load environment variables from .env file located in the project root
try {
    // Go up one level from 'includes' to the project root
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
     // Log error and provide a generic message for production
     error_log("Configuration Error: Cannot load .env file. " . $e->getMessage());
     // Avoid revealing detailed paths in production errors
     die("Application configuration error. Please contact support.");
}

// Define variables from environment variables
$db_host = $_ENV['DB_HOST'] ?? null;
$db_name = $_ENV['DB_NAME'] ?? null;
$db_user = $_ENV['DB_USER'] ?? null;
$db_pass = $_ENV['DB_PASS'] ?? null; // Sensitive!
$db_charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

// Validate essential configuration
if (!$db_host || !$db_name || !$db_user || !$db_pass) {
    error_log("Database configuration is incomplete. Check environment variables DB_HOST, DB_NAME, DB_USER, DB_PASS.");
    die("Database configuration error. Please contact support.");
}

$dsn = "mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=" . $db_charset;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements for security
];

try {
     // Establish PDO connection
     $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
     // Log the detailed error, show generic message to user
     error_log("Database Connection Error: " . $e->getMessage());
     die("Database connection failed. Please try again later or contact support.");
}

// --- Include functions AFTER establishing DB connection ---
// Or ensure functions load db.php if they need the $pdo global
require_once __DIR__ . '/functions.php';

?>
