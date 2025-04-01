<?php // includes/footer.php - Simplified Footer (No Social Icons) ?>

        <?php // Page specific content from other PHP files ends just before this ?>

    </main> <?php // Close the main content container started in header.php ?>


    <footer class="bg-gray-900 text-gray-400 py-10 px-4 mt-16">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="mb-6 md:mb-0">
                <h3 class="text-xl font-bold text-white mb-4">StockMaster</h3>
                <p class="text-sm mb-4 leading-relaxed">
                    Modern inventory management solution powered by AWS. Efficiently track and manage your stock.
                </p>
                 <?php // Social Media Icons Section REMOVED ?>
            </div>

            <?php // Column 2: Contact Info ?>
            <div>
                 <h4 class="font-semibold text-white mb-4 uppercase tracking-wider text-sm">Contact Us</h4>
                 <p class="text-sm mb-2">
                     Have questions or need support?
                 </p>
                 <p class="text-sm">
                    Email: <a href="mailto:support@stockmaster.example.com" class="hover:text-white underline">support@stockmaster.example.com</a>
                 </p>
                 <?php // Add Phone or Address here if desired ?>
                 </div>

        </div> <?php // End grid container ?>

        <div class="container mx-auto mt-10 pt-8 border-t border-gray-700 text-center text-sm">
             <?php
                 $currentYear = date('Y'); // Dynamic Year
                 $timezone = date_default_timezone_get();
             ?>
            &copy; <?php echo $currentYear; ?> StockMaster. All rights reserved.
            <span class="block mt-1 opacity-75">Mabalacat, Central Luzon, Philippines (Timezone: <?php echo escape($timezone); ?>)</span>
        </div>

    </footer> <?php // End footer section ?>

</body> <?php // Closing body tag ?>
</html> <?php // Closing html tag ?>
