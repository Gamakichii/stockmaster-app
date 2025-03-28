<?php // includes/footer.php ?>


        </main> <?php // End main container ?>


    <footer class="bg-gray-800 text-white text-center p-6 mt-16 border-t-4 border-blue-500"> <?php // Increased padding/margin ?>
        <div class="container mx-auto">

            <?php
                // Use current server year dynamically for copyright
                $currentYear = date('Y');
                // Get server timezone setting
                $timezone = date_default_timezone_get();
            ?>

            <p class="mb-2">Copyright &copy; <?php echo $currentYear; ?> StockMaster. All rights reserved.</p>

            <p class="text-xs text-gray-400 mt-1">
                Location Context: Mabalacat, Central Luzon, Philippines.
                Server Timezone: <?php echo escape($timezone); ?>.
                Current Time: <?php echo date('Y-m-d H:i:s'); // Show server time ?>
            </p>

            <?php
            /* // Optional: Development/Debug Info - Remove or comment out for Production
            // Check if APP_ENV is set in the .env file and is not 'production'
            if (($_ENV['APP_ENV'] ?? 'production') !== 'production') {
                echo '<p class="text-xs text-yellow-400 mt-3 p-2 bg-gray-700 rounded inline-block">Mode: ' . escape($_ENV['APP_ENV']) . '</p>';
            }
            */
            ?>

        </div> <?php // End container ?>
    </footer>

    </body>
</html>