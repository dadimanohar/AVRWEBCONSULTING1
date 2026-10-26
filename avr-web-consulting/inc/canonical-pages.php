<?php
/**
 * AVR Web Consulting - Canonical Pages Renderer
 * Automatically renders repository pages for sub-services, industry playbooks,
 * location landing pages, and case studies when no custom editor content is present.
 *
 * @package AVR_Web_Consulting
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render canonical page by slug or URL path
 *
 * @param string $slug Page slug or path
 * @return bool True if page was rendered, false otherwise
 */
function avr_render_canonical_page($slug = '') {
    if (empty($slug)) {
        $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $slug = $request_uri;
    }

    // Normalize slug (strip leading/trailing slashes)
    $clean_slug = trim($slug, '/');
    $parts = explode('/', $clean_slug);
    $last_slug = end($parts);

    $seeds_file = AVR_THEME_DIR . '/inc/content-seeds.json';
    if (!file_exists($seeds_file)) {
        return false;
    }

    $seeds = json_decode(file_get_contents($seeds_file), true);
    if (!is_array($seeds)) {
        return false;
    }

    $match = null;

    // Search pass 1: Exact full slug or path
    foreach ($seeds as $item) {
        $full_path = !empty($item['parentSlug']) ? trim($item['parentSlug'], '/') . '/' . trim($item['slug'], '/') : trim($item['slug'], '/');
        if ($full_path === $clean_slug || trim($item['slug'], '/') === $clean_slug) {
            $match = $item;
            break;
        }
    }

    // Search pass 2: Match last slug part
    if (!$match) {
        foreach ($seeds as $item) {
            if (trim($item['slug'], '/') === $last_slug) {
                $match = $item;
                break;
            }
        }
    }

    if (!$match || empty($match['content'])) {
        return false;
    }

    $content = $match['content'];

    // Clean Gutenberg block comment tags to render pure HTML
    $clean_html = preg_replace('/<!--\s*\/?wp:[^>]*-->/s', '', $content);

    // Rewrite image paths to theme asset URLs
    $clean_html = preg_replace(
        '/(["\'])\/images\/([a-zA-Z0-9_\.-]+)(["\'])/i',
        '$1' . AVR_THEME_URI . '/assets/images/$2$3',
        $clean_html
    );
    $clean_html = preg_replace(
        '/(["\'])\/image1\.(webp|jpg)(["\'])/i',
        '$1' . AVR_THEME_URI . '/assets/images/image1.webp$3',
        $clean_html
    );
    $clean_html = preg_replace(
        '/(["\'])\/image2\.(webp|jpeg|jpg)(["\'])/i',
        '$1' . AVR_THEME_URI . '/assets/images/image2.webp$3',
        $clean_html
    );
    $clean_html = preg_replace(
        '/(["\'])\/home-hero-poster\.(webp|jpg)(["\'])/i',
        '$1' . AVR_THEME_URI . '/assets/images/home-hero-poster.webp$3',
        $clean_html
    );

    // Expand patterns or shortcodes
    if (strpos($clean_html, '[avr_faqs]') !== false && function_exists('avr_render_faqs')) {
        $faqs_html = avr_render_faqs();
        $clean_html = str_replace('[avr_faqs]', $faqs_html, $clean_html);
    }
    if (strpos($clean_html, '[avr_faq_hub]') !== false && function_exists('avr_faq_hub_shortcode')) {
        $faqs_hub_html = avr_faq_hub_shortcode();
        $clean_html = str_replace('[avr_faq_hub]', $faqs_hub_html, $clean_html);
    }

    echo '<main class="flex-1">' . PHP_EOL;
    echo $clean_html . PHP_EOL;
    echo '</main>' . PHP_EOL;

    return true;
}
