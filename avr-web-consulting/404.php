<?php
/**
 * AVR Web Consulting - 404 Page
 * Exact design from repository.
 *
 * @package AVR_Web_Consulting
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="flex-1 flex items-center justify-center py-20 lg:py-32 bg-background px-4">
  <div class="max-w-md text-center mx-auto">
    <h1 class="text-7xl font-bold font-display text-foreground tracking-tight">404</h1>
    <h2 class="mt-4 text-2xl font-semibold font-display text-foreground">Page not found</h2>
    <p class="mt-3 text-base text-muted-foreground leading-relaxed">The page you're looking for doesn't exist, has been moved, or the URL was mistyped.</p>
    <div class="mt-8 flex justify-center gap-3">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-semibold cursor-pointer transition-colors bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-8 rounded-full">Return to Homepage</a>
      <a href="<?php echo esc_url(home_url('/contact')); ?>" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-semibold border shadow-sm h-10 px-8 rounded-full border-border bg-transparent text-foreground hover:bg-accent hover:text-accent-foreground transition-colors">Contact Us</a>
    </div>
  </div>
</main>

<?php
get_footer();
