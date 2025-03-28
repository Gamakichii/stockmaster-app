<?php
// includes/header.php
// db.php starts session, loads .env, connects DB, and includes functions.php
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StockMaster - Efficient Inventory Management System">

    <title><?php echo escape($page_title ?? 'StockMaster'); // Default title ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Basic smooth scroll */
        html { scroll-behavior: smooth; }
        /* Hide elements managed by Alpine initially to prevent flash */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

    <nav class="bg-blue-600 p-4 text-white shadow-md sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <div class="container mx-auto flex justify-between items-center">

            <a href="<?php echo is_logged_in() ? '/dashboard.php' : '/index.php'; ?>" class="text-xl font-bold hover:text-blue-200 transition duration-150 ease-in-out">
                StockMaster
            </a>

            <div class="hidden md:flex items-center space-x-4">
                <?php if (is_logged_in()): ?>
                    <a href="/dashboard.php" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out">Dashboard</a>
                    <a href="/inventory/index.php" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out">Inventory</a>
                    <?php if (is_admin()): ?>
                        <a href="/admin/index.php" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out">Admin Panel</a>
                    <?php endif; ?>
                    <span class="px-3 py-2 text-blue-100">Hi, <?php echo escape($_SESSION['username'] ?? 'User'); ?>!</span>
                    <a href="/logout.php" class="px-3 py-2 bg-red-500 hover:bg-red-600 rounded ml-2 transition duration-150 ease-in-out">Logout</a>
                <?php else: ?>
                    <a href="/index.php#pricing" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out">Pricing</a>
                    <a href="/index.php#testimonials" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out">Testimonials</a>
                    <a href="/index.php#contact" class="px-3 py-2 hover:bg-blue-700 rounded transition duration-150 ease-in-out">Contact</a>
                    <a href="/login.php" class="ml-4 px-4 py-2 bg-green-500 hover:bg-green-600 rounded font-semibold transition duration-150 ease-in-out">Login</a>
                <?php endif; ?>
            </div>

            <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-white focus:outline-none" aria-label="Toggle mobile menu">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

        </div> <?php // End container ?>

        <div x-show="mobileMenuOpen"
             x-cloak
             @click.away="mobileMenuOpen = false" <?php // Close when clicking outside ?>
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 transform -translate-y-1"
             x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 transform -translate-y-1"
             class="md:hidden absolute top-full left-0 right-0 bg-blue-700 shadow-lg py-2 z-40" <?php // Position below nav ?>
        >
            <?php if (is_logged_in()): ?>
                <a href="/dashboard.php" class="block px-4 py-3 text-white hover:bg-blue-800 transition duration-150 ease-in-out">Dashboard</a>
                <a href="/inventory/index.php" class="block px-4 py-3 text-white hover:bg-blue-800 transition duration-150 ease-in-out">Inventory</a>
                <?php if (is_admin()): ?>
                    <a href="/admin/index.php" class="block px-4 py-3 text-white hover:bg-blue-800 transition duration-150 ease-in-out">Admin Panel</a>
                <?php endif; ?>
                <div class="border-t border-blue-600 my-2"></div>
                <span class="block px-4 py-2 text-blue-100 italic">Hi, <?php echo escape($_SESSION['username'] ?? 'User'); ?>!</span>
                <a href="/logout.php" class="block px-4 py-3 text-white bg-red-500 hover:bg-red-600 transition duration-150 ease-in-out">Logout</a>
            <?php else: ?>
                <a href="/index.php#pricing" @click="mobileMenuOpen = false" class="block px-4 py-3 text-white hover:bg-blue-800 transition duration-150 ease-in-out">Pricing</a>
                <a href="/index.php#testimonials" @click="mobileMenuOpen = false" class="block px-4 py-3 text-white hover:bg-blue-800 transition duration-150 ease-in-out">Testimonials</a>
                <a href="/index.php#contact" @click="mobileMenuOpen = false" class="block px-4 py-3 text-white hover:bg-blue-800 transition duration-150 ease-in-out">Contact</a>
                <div class="border-t border-blue-600 my-2"></div>
                <a href="/login.php" class="block px-4 py-3 text-white bg-green-500 hover:bg-green-600 transition duration-150 ease-in-out">Login</a>
            <?php endif; ?>
        </div> <?php // End mobile menu dropdown ?>

    </nav>

    <?php // Add padding-top if using fixed header that overlaps content (not needed for sticky) ?>
    <main class="container mx-auto p-4 mt-4">