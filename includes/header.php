<?php
// includes/header.php - CORRECTED Navigation Logic
require_once __DIR__ . '/db.php'; // Loads DB, functions, session
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StockMaster - Simplify Your Inventory Management">
    <title><?php echo escape($page_title ?? 'StockMaster'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style> html { scroll-behavior: smooth; } [x-cloak] { display: none !important; } </style>
</head>
<body class="bg-white text-gray-800 font-sans"> <?php // Use bg-white for landing, maybe change based on page? ?>

    <nav class="bg-white shadow-sm sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="<?php echo is_logged_in() ? '/dashboard.php' : '/'; ?>" class="text-2xl font-bold text-gray-800">StockMaster</a>

            <div class="hidden md:flex items-center space-x-6">
                <?php // --- START Conditional Links --- ?>
                <?php if (is_logged_in()): ?>
                    <?php // --- Logged IN Links (Desktop) --- ?>
                    <a href="/dashboard.php" class="text-gray-600 hover:text-blue-600 font-medium">Dashboard</a>
                    <a href="/inventory/index.php" class="text-gray-600 hover:text-blue-600 font-medium">Inventory</a>
                    <a href="/phishing_report.php" class="text-gray-600 hover:text-blue-600 font-medium">Phishing Report</a>
                    <?php if (is_admin()): // Optional Admin link ?>
                         <a href="/admin/index.php" class="text-gray-600 hover:text-blue-600 font-medium">Admin</a>
                    <?php endif; ?>
                    <span class="text-sm text-gray-500 italic hidden lg:inline">Hi, <?php echo escape($_SESSION['username'] ?? 'User'); ?>!</span> <?php // Added username display ?>
                    <a href="/logout.php" class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out">Logout</a>
                <?php else: ?>
                    <?php // --- Logged OUT Links (Desktop) --- ?>
                    <a href="/#features" class="text-gray-600 hover:text-blue-600">Features</a> <?php // Changed to root path anchors ?>
                    <a href="/#pricing" class="text-gray-600 hover:text-blue-600">Pricing</a>
                    <a href="/#contact" class="text-gray-600 hover:text-blue-600">Contact</a>
                    <a href="/login.php" class="bg-gray-800 hover:bg-gray-700 text-white font-semibold px-4 py-2 rounded-md transition duration-150 ease-in-out">Login</a>
                <?php endif; ?>
                <?php // --- END Conditional Links --- ?>
            </div>

            <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-800 focus:outline-none" aria-label="Toggle mobile menu">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <div x-show="mobileMenuOpen" x-cloak
             @click.away="mobileMenuOpen = false"
             x-transition
             class="md:hidden absolute top-full left-0 right-0 bg-white shadow-lg py-2 border-t border-gray-200 z-40">

            <?php // --- START Conditional Mobile Links --- ?>
            <?php if (is_logged_in()): ?>
                <?php // --- Logged IN Links (Mobile) --- ?>
                <a href="/dashboard.php" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100 font-medium">Dashboard</a>
                <a href="/inventory/index.php" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100 font-medium">Inventory</a>
                <a href="/phishing_report.php" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100 font-medium">Phishing Report</a>
                 <?php if (is_admin()): ?>
                    <a href="/admin/index.php" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100 font-medium">Admin</a>
                <?php endif; ?>
                <div class="border-t border-gray-200 my-2"></div>
                <span class="block px-4 py-2 text-sm text-gray-500 italic">Hi, <?php echo escape($_SESSION['username'] ?? 'User'); ?>!</span>
                <a href="/logout.php" class="block px-4 py-3 text-red-600 font-medium hover:bg-gray-100">Logout</a>
            <?php else: ?>
                <?php // --- Logged OUT Links (Mobile) --- ?>
                <a href="/#features" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100">Features</a>
                <a href="/#pricing" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100">Pricing</a>
                <a href="/#contact" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 hover:bg-gray-100">Contact</a>
                <div class="border-t border-gray-200 my-2"></div>
                <a href="/login.php" @click="mobileMenuOpen = false" class="block px-4 py-3 text-gray-700 font-medium hover:bg-gray-100">Login</a>
            <?php endif; ?>
            <?php // --- END Conditional Mobile Links --- ?>
        </div>
    </nav>

    <main class="container mx-auto p-4 mt-4">
