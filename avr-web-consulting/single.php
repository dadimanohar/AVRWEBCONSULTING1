<?php
/**
 * AVR Web Consulting - Single Post Template
 * Renders blog posts with exact repository design.
 *
 * @package AVR_Web_Consulting
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Try content-seeds first for blog posts
$rendered = false;
if (function_exists('avr_render_canonical_page')) {
    $slug = get_post_field('post_name', get_the_ID());
    $rendered = avr_render_canonical_page('/blog/' . $slug);
    if (!$rendered) {
        $rendered = avr_render_canonical_page($slug);
    }
}

if (!$rendered) {
    ?>
    <main class="flex-1 py-12 lg:py-16">
      <article class="container-page max-w-4xl">
        <?php while (have_posts()) { the_post(); ?>
          <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold font-display text-foreground leading-tight mb-4"><?php the_title(); ?></h1>
          <div class="flex items-center gap-4 text-sm text-muted-foreground pb-6 border-b border-border mb-8">
            <time datetime="<?php echo get_the_date('c'); ?>"><?php echo get_the_date(); ?></time>
            <span>•</span>
            <span class="font-medium text-foreground"><?php the_author(); ?></span>
          </div>
          <?php if (has_post_thumbnail()) { ?>
            <div class="rounded-3xl border border-border shadow-sm mb-10 overflow-hidden">
              <?php the_post_thumbnail('full', array('class' => 'w-full object-cover')); ?>
            </div>
          <?php } ?>
          <div class="prose prose-lg max-w-none prose-headings:font-display prose-headings:font-bold prose-headings:text-foreground prose-a:text-primary">
            <?php the_content(); ?>
          </div>
        <?php } ?>
      </article>
    </main>
    <?php
}

get_footer();
