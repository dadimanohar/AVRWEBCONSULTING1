<?php
/**
 * AVR Web Consulting - Index Template
 * Fallback template for WordPress hierarchy.
 *
 * @package AVR_Web_Consulting
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (is_front_page()) {
    include AVR_THEME_DIR . '/front-page.php';
} elseif (have_posts()) {
    while (have_posts()) {
        the_post();
        ?>
        <main class="flex-1 py-12 lg:py-16">
            <div class="container-page max-w-4xl">
                <h1 class="text-3xl font-bold leading-tight lg:text-5xl text-foreground mb-6 font-display"><?php the_title(); ?></h1>
                <div class="prose prose-lg max-w-none prose-headings:font-display prose-headings:font-bold prose-headings:text-foreground prose-a:text-primary">
                    <?php the_content(); ?>
                </div>
            </div>
        </main>
        <?php
    }
} else {
    // If no post exists in database, render canonical page by URL slug
    $rendered = false;
    if (function_exists('avr_render_canonical_page')) {
        $rendered = avr_render_canonical_page();
    }
    if (!$rendered) {
        ?>
        <main class="flex-1 flex items-center justify-center py-20 lg:py-32 bg-background px-4">
            <div class="max-w-md text-center mx-auto">
                <h1 class="text-7xl font-bold font-display text-foreground tracking-tight">404</h1>
                <h2 class="mt-4 text-2xl font-semibold font-display text-foreground">Page not found</h2>
                <p class="mt-3 text-base text-muted-foreground leading-relaxed">The page you're looking for doesn't exist, has been moved, or the URL was mistyped.</p>
                <div class="mt-8 flex justify-center gap-3">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-semibold cursor-pointer transition-colors bg-primary text-primary-foreground shadow hover:bg-primary/90 h-10 px-8 rounded-full">Return to Homepage</a>
                </div>
            </div>
        </main>
        <?php
    }
}

get_footer();
