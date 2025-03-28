<?php
// public/index.php
$page_title = 'Welcome - StockMaster'; // UPDATED title
require_once __DIR__ . '/../includes/header.php'; // Loads DB, functions, session, header HTML
?>

<section class="bg-white p-10 rounded-lg shadow-lg text-center mb-8 border border-gray-200">
    <h1 class="text-4xl font-bold text-gray-800 mb-4 sm:text-5xl">Inventory Management Made Simple</h1>
    <p class="text-gray-600 text-lg mb-6 max-w-2xl mx-auto"> <?php // Limit width of paragraph ?>
        Track your stock efficiently with StockMaster. Clean, modern, and accessible anywhere via AWS Cloud Computing power.
    </p>
    <a href="<?php echo is_logged_in() ? '/dashboard.php' : '/register.php'; ?>"
       class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-8 rounded-lg text-lg inline-block transition duration-300 ease-in-out transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
        Get Started Now
    </a>
</section>

<section id="pricing" class="my-12 p-8 bg-white rounded-lg shadow border border-gray-200">
    <h2 class="text-3xl font-semibold mb-6 text-center text-gray-700">Pricing</h2>
    <p class="text-gray-600 text-center">Flexible pricing plans available soon. Contact us for enterprise solutions.</p>
    </section>

<section id="testimonials" class="my-12 p-8 bg-gray-50 rounded-lg shadow border border-gray-200"> <?php // Different background ?>
    <h2 class="text-3xl font-semibold mb-6 text-center text-gray-700">What Users Say</h2>
    <blockquote class="mt-4 border-l-4 border-blue-500 pl-6 italic text-gray-700 max-w-xl mx-auto">
        "StockMaster streamlined our entire inventory process, saving us hours every week!"
        <cite class="block text-right not-italic text-gray-500 mt-2">- A Happy User, Mabalacat</cite> <?php // Context ?>
    </blockquote>
    </section>

<section id="contact" class="my-12 p-8 bg-white rounded-lg shadow border border-gray-200">
    <h2 class="text-3xl font-semibold mb-6 text-center text-gray-700">Contact Us</h2>
    <p class="text-gray-600 text-center max-w-lg mx-auto">Have questions or need support? Get in touch!</p>
    <p class="mt-6 text-center font-medium">Email: <a href="mailto:support@stockmaster.example.com" class="text-blue-600 hover:underline">support@stockmaster.example.com</a></p>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>