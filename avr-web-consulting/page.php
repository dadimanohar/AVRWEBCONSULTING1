<?php
/**
 * AVR Web Consulting - Generic Page Template (fallback)
 * Renders content-seeds pages with exact repository design.
 *
 * @package AVR_Web_Consulting
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Try to render the exact page from content-seeds using slug matching
$rendered = false;
if (function_exists('avr_render_canonical_page')) {
    $slug = get_post_field('post_name', get_the_ID());
    $parent = wp_get_post_parent_id(get_the_ID());
    if ($parent) {
        $parent_slug = get_post_field('post_name', $parent);
        $full_slug = $parent_slug . '/' . $slug;
        $rendered = avr_render_canonical_page($full_slug);
    }
    if (!$rendered) {
        $rendered = avr_render_canonical_page($slug);
    }
}

if (!$rendered) {
    // Standard WordPress content loop
    ?>
    <main class="flex-1 py-12 lg:py-16">
      <div class="container-page max-w-4xl">
        <?php
        while (have_posts()) {
            the_post();
            ?>
            <h1 class="text-3xl font-bold leading-tight lg:text-5xl text-foreground mb-6 font-display"><?php the_title(); ?></h1>
            <div class="prose prose-lg max-w-none prose-headings:font-display prose-headings:font-bold prose-headings:text-foreground prose-a:text-primary">
                <?php the_content(); ?>
            </div>
            <?php
        }
        ?>
      </div>
    </main>
    <?php
}

get_footer();
