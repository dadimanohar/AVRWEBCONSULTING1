<?php
/**
 * AVR Web Consulting - Theme Functions & Definitions
 * Block Theme with full Gutenberg block patterns, site editor support, and non-destructive page importer.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AVR_THEME_VERSION', '2.0.0');
define('AVR_THEME_DIR', get_template_directory());
define('AVR_THEME_URI', get_template_directory_uri());

// Include supporting modules
require_once AVR_THEME_DIR . '/inc/redirects.php';
require_once AVR_THEME_DIR . '/inc/seo-schema.php';
require_once AVR_THEME_DIR . '/inc/class-avr-importer.php';
require_once AVR_THEME_DIR . '/inc/faqs.php';

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function avr_theme_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title.
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support('post-thumbnails');

    // Add support for Block Styles.
    add_theme_support('wp-block-styles');

    // Enqueue editor styles.
    add_theme_support('editor-styles');
    add_editor_style('assets/css/theme.css');

    // Add support for full and wide align images.
    add_theme_support('align-wide');

    // Add support for responsive embedded content.
    add_theme_support('responsive-embeds');

    // Add support for custom logo.
    add_theme_support('custom-logo', array(
        'height'      => 48,
        'width'       => 48,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // HTML5 markup support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Register navigation menus (for classic/block menu fallback)
    register_nav_menus(array(
        'primary' => __('Primary Navigation', 'avr-web-consulting'),
        'footer'  => __('Footer Navigation', 'avr-web-consulting'),
    ));
}
add_action('after_setup_theme', 'avr_theme_setup');

/**
 * Enqueue scripts and styles properly with Google Fonts and non-render-blocking delivery.
 */
function avr_theme_scripts() {
    // Google Fonts (Manrope: 400, 500, 600, 700 & Sora: 500, 600, 700 with display=swap)
    wp_enqueue_style(
        'avr-google-fonts',
        'https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap',
        array(),
        null
    );

    // Main Theme Compiled Stylesheet (loaded asynchronously via style_loader_tag)
    wp_enqueue_style(
        'avr-theme-css',
        AVR_THEME_URI . '/assets/css/theme.css',
        array('avr-google-fonts'),
        AVR_THEME_VERSION
    );

    // Root style.css metadata and resets
    wp_enqueue_style(
        'avr-style',
        get_stylesheet_uri(),
        array('avr-theme-css'),
        AVR_THEME_VERSION
    );

    // Production Vanilla JavaScript (Mobile navigation, FAQ details, Video deferral)
    wp_enqueue_script(
        'avr-theme-js',
        AVR_THEME_URI . '/assets/js/theme.js',
        array(),
        AVR_THEME_VERSION,
        true
    );

    // Localize theme script for AJAX inquiry submissions
    wp_localize_script('avr-theme-js', 'avr_theme_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('avr_inquiry_nonce'),
        'home_url' => home_url('/'),
    ));
}
add_action('wp_enqueue_scripts', 'avr_theme_scripts');

/**
 * Preload Critical Fonts (preconnect to Google Fonts) and Hero LCP Poster in <head>
 */
function avr_preload_critical_performance_assets() {
    ?>
    <!-- Preconnect to Google Fonts with crossOrigin -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap">
    <?php if (is_front_page()) : ?>
    <!-- Preload High-Priority Hero LCP Poster Image -->
    <link rel="preload" as="image" href="<?php echo esc_url(AVR_THEME_URI . '/assets/images/home-hero-poster.webp'); ?>" type="image/webp" fetchpriority="high">
    <?php endif; ?>
    <!-- Inlined Critical CSS for Instant First Contentful Paint -->
    <style id="avr-critical-css">
      :root {
        --radius: .875rem;
        --background: oklch(100% 0 0);
        --foreground: oklch(19% .014 250);
        --primary: oklch(53% .14 234.5);
        --primary-foreground: oklch(100% 0 0);
        --ink: oklch(18.5% .012 254);
        --ink-foreground: oklch(98.5% .003 240);
        --border: oklch(91.8% .01 235);
        --font-display: "Sora", system-ui, -apple-system, "Segoe UI", sans-serif;
        --font-sans: "Manrope", system-ui, -apple-system, "Segoe UI", sans-serif;
      }
      html { scroll-behavior: smooth; -webkit-text-size-adjust: 100%; box-sizing: border-box; }
      *, *::before, *::after { box-sizing: inherit; }
      body { margin: 0; font-family: var(--font-sans); background: var(--background); color: var(--foreground); -webkit-font-smoothing: antialiased; }
      h1, h2, h3, h4, h5, h6, .font-display { font-family: var(--font-display); }
      .container-page { width: 100%; max-width: 80rem; margin-left: auto; margin-right: auto; padding-left: 1rem; padding-right: 1rem; }
      @media (min-width: 640px) { .container-page { padding-left: 1.5rem; padding-right: 1.5rem; } }
      @media (min-width: 1024px) { .container-page { padding-left: 2rem; padding-right: 2rem; } }
      header.sticky { position: sticky; top: 0; z-index: 50; width: 100%; border-bottom: 1px solid var(--border); background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(8px); }
    </style>
    <?php
}
add_action('wp_head', 'avr_preload_critical_performance_assets', 1);

/**
 * Make Theme Stylesheets Non-Render-Blocking (Async Preload with noscript Fallback)
 */
function avr_defer_render_blocking_css($html, $handle, $href, $media) {
    if (is_admin()) {
        return $html;
    }

    if (in_array($handle, array('avr-theme-css', 'avr-style', 'avr-theme-style-css', 'avr-main-style-css', 'avr-google-fonts', 'avr-fonts-css'), true)) {
        $tag = '<link rel="preload" id="' . esc_attr($handle) . '-preload" href="' . esc_url($href) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\';this.media=\'all\';">' . "\n";
        $tag .= '<noscript><link rel="stylesheet" href="' . esc_url($href) . '" media="all"></noscript>' . "\n";
        return $tag;
    }

    return $html;
}
add_filter('style_loader_tag', 'avr_defer_render_blocking_css', 10, 4);

/**
 * Optimize Third-Party Scripts: Fix Tawk.to CORS error and defer analytics
 */
function avr_optimize_third_party_scripts($tag, $handle, $src) {
    if (is_admin()) {
        return $tag;
    }

    // 1. Fix Tawk.to CORS error: remove invalid crossorigin attribute
    if (strpos($src, 'tawk.to') !== false || strpos($tag, 'tawk.to') !== false) {
        $tag = str_replace(array("crossorigin='*'", 'crossorigin="*"', 'crossorigin'), '', $tag);
    }

    // 2. Defer MonsterInsights / GTM / Analytics to prevent blocking main thread
    if (strpos($handle, 'monsterinsights') !== false || strpos($src, 'frontend-gtag') !== false || strpos($src, 'googletagmanager.com') !== false) {
        if (strpos($tag, 'defer') === false && strpos($tag, 'async') === false) {
            $tag = str_replace('<script ', '<script defer ', $tag);
        }
    }

    return $tag;
}
add_filter('script_loader_tag', 'avr_optimize_third_party_scripts', 999, 3);

/**
 * Send High-Performance Security and Caching Headers
 */
function avr_send_security_and_performance_headers() {
    if (is_admin()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cross-Origin-Opener-Policy: same-origin-allow-popups');

    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}
add_action('send_headers', 'avr_send_security_and_performance_headers');

/**
 * Auto-Optimize Content Images: Rewrite old image paths to WebP with dimensions and map /images/ to theme assets
 */
function avr_optimize_content_images($content) {
    if (empty($content)) {
        return $content;
    }

    // Rewrite /images/... to theme assets/images/... if referenced as /images/
    $content = preg_replace(
        '/(["\'])\/images\/([a-zA-Z0-9_\.-]+)(["\'])/i',
        '$1' . AVR_THEME_URI . '/assets/images/$2$3',
        $content
    );

    // Rewrite image1.jpg to image1.webp with explicit width and height
    $content = preg_replace(
        '/<img([^>]+)src=(["\'][^"\']*image1\.jpg["\'])([^>]*)>/i',
        '<img$1src="' . esc_url(AVR_THEME_URI . '/assets/images/image1.webp') . '" width="728" height="408" loading="lazy" decoding="async"$3>',
        $content
    );

    // Rewrite image2.jpeg to image2.webp with explicit width and height
    $content = preg_replace(
        '/<img([^>]+)src=(["\'][^"\']*image2\.jpeg["\'])([^>]*)>/i',
        '<img$1src="' . esc_url(AVR_THEME_URI . '/assets/images/image2.webp') . '" width="640" height="445" loading="lazy" decoding="async"$3>',
        $content
    );

    return $content;
}
add_filter('the_content', 'avr_optimize_content_images', 20);

/**
 * Register URL Rewrite Rule for direct /images/... requests
 */
function avr_register_image_rewrites() {
    add_rewrite_rule('^images/(.+)$', 'wp-content/themes/avr-web-consulting/assets/images/$1', 'top');
}
add_action('init', 'avr_register_image_rewrites');

/**
 * Register Block Pattern Categories
 */
function avr_register_block_pattern_categories() {
    $categories = array(
        'avr-heroes'   => array('label' => __('AVR - Hero Sections', 'avr-web-consulting')),
        'avr-services' => array('label' => __('AVR - Services & Cards', 'avr-web-consulting')),
        'avr-content'  => array('label' => __('AVR - Content Sections', 'avr-web-consulting')),
        'avr-faqs'     => array('label' => __('AVR - FAQ Accordions', 'avr-web-consulting')),
        'avr-cta'      => array('label' => __('AVR - Call to Action', 'avr-web-consulting')),
    );

    foreach ($categories as $slug => $properties) {
        if (!WP_Block_Pattern_Categories_Registry::get_instance()->is_registered($slug)) {
            register_block_pattern_category($slug, $properties);
        }
    }
}
add_action('init', 'avr_register_block_pattern_categories');

/**
 * Handle Consultation & Inquiry Form AJAX Submissions
 */
function avr_handle_inquiry_submission() {
    check_ajax_referer('avr_inquiry_nonce', 'nonce');

    $name    = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $email   = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $phone   = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

    if (empty($email) && empty($phone)) {
        wp_send_json_error(array('message' => 'Please provide an email or phone number.'));
        exit;
    }

    // Prepare notification email
    $to      = get_option('admin_email', 'info@avrwebconsulting.com');
    $subject = 'New Consultation Inquiry from ' . ($name ? $name : 'Website Visitor');
    $body    = "Name: $name\nEmail: $email\nPhone: $phone\nService: $service\n\nMessage:\n$message\n";
    $headers = array('Content-Type: text/plain; charset=UTF-8', "Reply-To: $email");

    @wp_mail($to, $subject, $body, $headers);

    wp_send_json_success(array(
        'message' => 'Thank you! Your inquiry has been sent successfully. Our digital marketing team will reach out within 24 hours.'
    ));
}
add_action('wp_ajax_avr_submit_inquiry', 'avr_handle_inquiry_submission');
add_action('wp_ajax_nopriv_avr_submit_inquiry', 'avr_handle_inquiry_submission');
