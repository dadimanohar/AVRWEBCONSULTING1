<?php
/**
 * Title: AVR Accessible FAQ Accordion
 * Slug: avr/faq-accordion
 * Categories: avr-faqs
 * Description: Fully accessible, dynamic JSON-backed FAQ accordion with smooth toggle and keyboard support.
 */

// If running in dynamic template context, render page-specific FAQs from JSON dictionary
if (function_exists('avr_render_faqs')) {
    $rendered = avr_render_faqs();
    if (!empty($rendered)) {
        echo $rendered;
        return;
    }
}
?>
<!-- wp:group {"className":"py-12 scroll-mt-28","layout":{"type":"constrained"}} -->
<div id="faqs" class="wp-block-group py-12 scroll-mt-28">
  <!-- wp:heading {"level":2,"className":"text-2xl font-bold lg:text-3xl font-display text-foreground mb-6"} -->
  <h2 class="text-2xl font-bold lg:text-3xl font-display text-foreground mb-6">Frequently Asked Questions</h2>
  <!-- /wp:heading -->

  <!-- wp:shortcode -->
  [avr_faqs]
  <!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
