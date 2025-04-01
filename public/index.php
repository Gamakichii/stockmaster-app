<?php
// public/index.php
$page_title = 'StockMaster - Simplify Your Inventory Management'; // Specific title
require_once __DIR__ . '/../includes/header.php'; // Include the updated header
?>

<main> <?php // Wrap content in main tag ?>

    <section class="bg-white py-16 md:py-24 px-4">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-800 mb-6 leading-tight">
                    Simplify Your Inventory Management
                </h1>
                <p class="text-lg text-gray-600 mb-8">
                    Streamline your stock control with our powerful AWS-powered
                    inventory management system.
                </p>
                <a href="<?php echo is_logged_in() ? '/dashboard.php' : '/register.php'; ?>"
                   class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-md text-lg inline-block transition duration-150 ease-in-out">
                    Get Started Free
                </a>
            </div>
            <div class="bg-gray-800 h-64 md:h-80 rounded-lg shadow-lg flex items-center justify-center">
                <span class="text-gray-400 text-xl italic">Dashboard Preview</span>
                <?php /* Replace with actual image:
                <img src="/path/to/dashboard-preview.jpg" alt="Dashboard Preview" class="rounded-lg object-cover w-full h-full">
                */ ?>
            </div>
        </div>
    </section>

    <section id="features" class="bg-gray-50 py-16 md:py-24 px-4">
        <div class="container mx-auto">
            <h2 class="text-3xl md:text-4xl font-semibold text-center text-gray-800 mb-12">
                Key Features
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-lg shadow-md border border-gray-200 text-center hover:shadow-xl transition-shadow duration-200">
                    <div class="mb-4 inline-block">
                        <?php // Placeholder for Icon - Replace with SVG or Font Icon ?>
                        <svg class="w-12 h-12 text-blue-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Real-time Analytics</h3>
                    <p class="text-gray-600">
                        Track your inventory metrics in real-time with powerful analytics tools.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-md border border-gray-200 text-center hover:shadow-xl transition-shadow duration-200">
                    <div class="mb-4 inline-block">
                         <?php // Placeholder for Icon ?>
                         <svg class="w-12 h-12 text-blue-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path></svg> <?php // Cloud Icon ?>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">AWS S3 Storage</h3>
                    <p class="text-gray-600">
                        Secure and scalable cloud storage for your inventory data.
                    </p>
                </div>

                <div class="bg-white p-8 rounded-lg shadow-md border border-gray-200 text-center hover:shadow-xl transition-shadow duration-200">
                     <div class="mb-4 inline-block">
                         <?php // Placeholder for Icon ?>
                         <svg class="w-12 h-12 text-blue-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> <?php // Mobile Icon ?>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-3">Mobile Ready</h3>
                    <p class="text-gray-600">
                        Access your inventory system from any device, anywhere.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php // You can re-add the Pricing/Contact sections here if needed, styled appropriately ?>
    <section id="pricing" class="py-16 md:py-24 px-4">
         <div class="container mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-semibold text-gray-800 mb-6">Pricing</h2>
            <p class="text-lg text-gray-600 mb-8">Simple plans for businesses of all sizes. (Details coming soon)</p>
            <a href="/register.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-md text-lg inline-block transition duration-150 ease-in-out">Sign Up</a>
         </div>
    </section>

    <section id="contact" class="bg-gray-50 py-16 md:py-24 px-4">
         <div class="container mx-auto text-center max-w-2xl">
             <h2 class="text-3xl md:text-4xl font-semibold text-gray-800 mb-6">Get In Touch</h2>
             <p class="text-lg text-gray-600 mb-8">Questions? Feedback? We'd love to hear from you. Contact us at support@stockmaster.example.com.</p>
             <?php /* Basic Contact Form Placeholder
             <form action="#" method="POST">
                 <div class="mb-4"><input type="text" placeholder="Your Name" class="w-full p-3 border rounded"></div>
                 <div class="mb-4"><input type="email" placeholder="Your Email" class="w-full p-3 border rounded"></div>
                 <div class="mb-4"><textarea placeholder="Your Message" rows="4" class="w-full p-3 border rounded"></textarea></div>
                 <button type="submit" class="bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-md text-lg inline-block transition duration-150 ease-in-out">Send Message</button>
             </form>
             */ ?>
        </div>
    </section>


</main> <?php // End main tag ?>


<?php require_once __DIR__ . '/../includes/footer.php'; // Include the updated footer ?>
