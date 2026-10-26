<?php
/**
 * AVR Web Consulting - Complete FAQ System
 * 
 * Manages JSON-based FAQ dictionaries, server-side rendering, schema generation,
 * interactive search hub, and automatic repair of missing FAQ answers across all website pages.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns the absolute directory path where FAQ JSON dictionaries reside.
 *
 * @return string Absolute path to data/faqs/
 */
function avr_get_faq_data_dir() {
    return AVR_THEME_DIR . '/data/faqs';
}

/**
 * Resolves the appropriate FAQ dictionary slug for the given or current page.
 *
 * @param string $slug Optional slug to resolve.
 * @return string Clean resolved slug identifier.
 */
function avr_resolve_faq_slug($slug = '') {
    if (!empty($slug)) {
        return sanitize_title(trim($slug, '/'));
    }

    if (is_front_page() || is_home()) {
        return 'home';
    }

    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $post_name = $post->post_name;
            
            // Check if page has ancestors for hierarchical match (e.g. seo-services/local-seo)
            if ($post->post_parent) {
                $ancestors = get_post_ancestors($post->ID);
                $ancestor_names = array();
                foreach (array_reverse($ancestors) as $aid) {
                    $ancestor_names[] = get_post_field('post_name', $aid);
                }
                $hierarchical = implode('-', $ancestor_names) . '-' . $post_name;
                $data_dir = avr_get_faq_data_dir();
                
                // If hierarchical alias exists on disk, prefer it
                if (file_exists($data_dir . '/' . $hierarchical . '.json')) {
                    return $hierarchical;
                }
            }
            
            return $post_name;
        }
    }

    // Fallback: check REQUEST_URI
    if (!empty($_SERVER['REQUEST_URI'])) {
        $path = trim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
        if (empty($path)) {
            return 'home';
        }
        $clean = sanitize_title(str_replace('/', '-', $path));
        return $clean;
    }

    return 'home';
}

/**
 * Loads the FAQ dictionary for a specific page slug.
 *
 * @param string $slug Page slug or identifier.
 * @return array List of FAQ items, each with 'id', 'question', 'answer'.
 */
function avr_get_page_faqs($slug = '') {
    static $cache = array();

    $resolved_slug = avr_resolve_faq_slug($slug);

    if (isset($cache[$resolved_slug])) {
        return $cache[$resolved_slug];
    }

    $data_dir = avr_get_faq_data_dir();
    $candidates = array();

    // 1. Direct resolved slug
    $candidates[] = $data_dir . '/' . $resolved_slug . '.json';

    // 2. Short name if slug has dashes (e.g., blog-aeo-vs-geo-vs-llmo -> aeo-vs-geo-vs-llmo)
    if (strpos($resolved_slug, '-') !== false) {
        $parts = explode('-', $resolved_slug);
        $short = end($parts);
        $candidates[] = $data_dir . '/' . $short . '.json';
    }

    // 3. Web development alias
    if ($resolved_slug === 'web-development' || $resolved_slug === 'web-design-development') {
        $candidates[] = $data_dir . '/web-design-development.json';
        $candidates[] = $data_dir . '/web-development.json';
    }

    $faq_file = null;
    foreach ($candidates as $cand) {
        // Path traversal defense
        $real = realpath($cand);
        if ($real && strpos($real, realpath($data_dir)) === 0 && file_exists($real)) {
            $faq_file = $real;
            break;
        }
    }

    // If still not found, check registry index.json
    if (!$faq_file) {
        $index_path = $data_dir . '/index.json';
        if (file_exists($index_path)) {
            $index_data = json_decode(file_get_contents($index_path), true);
            if (is_array($index_data)) {
                if (isset($index_data[$resolved_slug]['file'])) {
                    $target = $data_dir . '/' . $index_data[$resolved_slug]['file'];
                    if (file_exists($target)) {
                        $faq_file = $target;
                    }
                }
            }
        }
    }

    $faqs = array();

    if ($faq_file && file_exists($faq_file)) {
        $content = file_get_contents($faq_file);
        $data = json_decode($content, true);

        if (is_array($data)) {
            // Handle associative dictionary: {"id_1": {"id": "...", "question": "...", "answer": "..."}}
            // or indexed array: [{"id": "...", "question": "...", "answer": "..."}]
            foreach ($data as $key => $item) {
                if (is_array($item) && !empty($item['question']) && !empty($item['answer'])) {
                    $faqs[] = array(
                        'id'       => !empty($item['id']) ? sanitize_key($item['id']) : sanitize_key($key),
                        'question' => trim(wp_strip_all_tags($item['question'])),
                        'answer'   => trim($item['answer']),
                    );
                }
            }
        }
    }

    // Fallback: If not found in page-specific JSON, search in all-faqs.json
    if (empty($faqs)) {
        $all = avr_get_all_faqs();
        foreach ($all as $item) {
            if (!empty($item['pageSlug']) && ($item['pageSlug'] === $resolved_slug || sanitize_title($item['pageSlug']) === $resolved_slug)) {
                $faqs[] = array(
                    'id'       => !empty($item['id']) ? sanitize_key($item['id']) : sanitize_key($item['pageSlug'] . '_' . count($faqs)),
                    'question' => trim(wp_strip_all_tags($item['question'])),
                    'answer'   => trim($item['answer']),
                );
            }
        }
    }

    $cache[$resolved_slug] = $faqs;
    return $faqs;
}

/**
 * Loads all FAQs across the entire website from master all-faqs.json.
 *
 * @return array List of all FAQ items.
 */
function avr_get_all_faqs() {
    static $master = null;

    if ($master !== null) {
        return $master;
    }

    $master_file = avr_get_faq_data_dir() . '/all-faqs.json';
    if (!file_exists($master_file)) {
        $master = array();
        return $master;
    }

    $content = file_get_contents($master_file);
    $data = json_decode($content, true);

    $master = is_array($data) ? $data : array();
    return $master;
}

/**
 * Renders an accessible, SEO-friendly FAQ accordion section.
 *
 * @param string $slug Page slug. If omitted, current page is auto-resolved.
 * @param string $title Heading title. Defaults to 'Frequently Asked Questions'.
 * @param string $subtitle Optional subtitle / intro text.
 * @param array  $args Custom display arguments.
 * @return string HTML markup.
 */
function avr_render_faqs($slug = '', $title = '', $subtitle = '', $args = array()) {
    $resolved_slug = avr_resolve_faq_slug($slug);
    $faqs = avr_get_page_faqs($resolved_slug);

    if (empty($faqs)) {
        return '';
    }

    $heading = !empty($title) ? esc_html($title) : __('Frequently Asked Questions', 'avr-web-consulting');
    $section_id = !empty($args['id']) ? sanitize_html_class($args['id']) : 'faqs';
    $container_class = !empty($args['class']) ? esc_attr($args['class']) : 'py-12 scroll-mt-28';

    ob_start();
    ?>
    <section id="<?php echo esc_attr($section_id); ?>" class="wp-block-group avr-faq-section <?php echo esc_attr($container_class); ?>" aria-label="<?php echo esc_attr($heading); ?>">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold lg:text-3xl font-display text-foreground mb-3"><?php echo $heading; ?></h2>
            <?php if (!empty($subtitle)) : ?>
                <p class="text-base text-muted-foreground mb-6"><?php echo esc_html($subtitle); ?></p>
            <?php else : ?>
                <p class="text-sm text-muted-foreground mb-6"><?php esc_html_e('Click any question to view its detailed answer.', 'avr-web-consulting'); ?></p>
            <?php endif; ?>

            <div class="rounded-2xl border border-border bg-card px-6 py-2 divide-y divide-border avr-faq-accordion" data-faq-slug="<?php echo esc_attr($resolved_slug); ?>">
                <?php foreach ($faqs as $index => $faq) : 
                    $faq_id = !empty($faq['id']) ? esc_attr($faq['id']) : 'faq-' . esc_attr($resolved_slug) . '-' . $index;
                    $panel_id = 'faq-panel-' . $faq_id;
                    $btn_id = 'faq-btn-' . $faq_id;
                ?>
                    <details class="wp-block-details avr-faq-item py-4 group" data-faq-id="<?php echo esc_attr($faq_id); ?>">
                        <summary class="flex items-center justify-between cursor-pointer font-display text-base font-semibold text-foreground list-none select-none hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg py-1" id="<?php echo esc_attr($btn_id); ?>" aria-controls="<?php echo esc_attr($panel_id); ?>">
                            <span class="avr-faq-question pr-4"><?php echo esc_html($faq['question']); ?></span>
                            <svg class="avr-faq-icon h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </summary>
                        <div id="<?php echo esc_attr($panel_id); ?>" class="mt-3 text-base text-foreground/85 leading-relaxed pl-1 avr-faq-answer border-t border-border/40 pt-3" role="region" aria-labelledby="<?php echo esc_attr($btn_id); ?>">
                            <p><?php echo esc_html($faq['answer']); ?></p>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Renders the dedicated interactive FAQ Hub (/faqs/ page).
 * Includes instant live search, category filter pills, count indicators, and accordion list.
 *
 * @param array $args Display options.
 * @return string HTML markup.
 */
function avr_render_faq_hub($args = array()) {
    $all_faqs = avr_get_all_faqs();
    $total_count = count($all_faqs);

    // Group categories with counts
    $categories = array();
    foreach ($all_faqs as $item) {
        $cat = !empty($item['category']) ? $item['category'] : 'General';
        if (!isset($categories[$cat])) {
            $categories[$cat] = 0;
        }
        $categories[$cat]++;
    }
    arsort($categories);

    // Initial slice to keep DOM fast
    $initial_limit = 80;
    $initial_items = array_slice($all_faqs, 0, $initial_limit);

    ob_start();
    ?>
    <div class="avr-faq-hub-wrapper max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14" id="avr-faq-hub">
        <!-- Breadcrumb -->
        <nav aria-label="<?php esc_attr_e('Breadcrumbs', 'avr-web-consulting'); ?>" class="mb-6">
            <ol class="flex items-center space-x-2 text-sm text-muted-foreground">
                <li><a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-foreground"><?php esc_html_e('Home', 'avr-web-consulting'); ?></a></li>
                <li><span>/</span></li>
                <li class="text-foreground font-medium"><?php esc_html_e('FAQs', 'avr-web-consulting'); ?></li>
            </ol>
        </nav>

        <!-- Hero Section -->
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start mb-12">
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-primary">
                    <?php esc_html_e('Answer Hub', 'avr-web-consulting'); ?>
                </p>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight font-display text-foreground">
                    <?php echo esc_html(sprintf(__('%d+ answers on SEO, AI search and digital marketing', 'avr-web-consulting'), $total_count)); ?>
                </h1>
                <p class="mt-4 text-lg text-muted-foreground">
                    <?php echo esc_html(sprintf(__('Searchable hub of %d verified answers on SEO, AI SEO, AEO, GEO, LLMO, paid ads, content, web design, automation, industries and pricing from AVR Web Consulting.', 'avr-web-consulting'), $total_count)); ?>
                </p>
                <div class="mt-6 p-5 rounded-2xl border border-primary/20 bg-primary/5 text-foreground leading-relaxed">
                    <p class="text-base">
                        <?php echo esc_html(sprintf(__('This hub holds %d authentic answers. Search any question and get an immediate answer plus a direct link to the page that explains it in depth.', 'avr-web-consulting'), $total_count)); ?>
                    </p>
                </div>
            </div>
            <div class="relative rounded-3xl overflow-hidden border border-border shadow-sm aspect-video lg:aspect-square flex items-center justify-center bg-card">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/team-video-call-meeting.webp'); ?>" 
                     alt="<?php esc_attr_e('Searchable SEO and AI SEO FAQs', 'avr-web-consulting'); ?>" 
                     loading="eager" 
                     class="w-full h-full object-cover" />
            </div>
        </div>

        <!-- Stat Band -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10 text-center">
            <div class="p-4 rounded-2xl border border-border bg-card">
                <div class="text-3xl font-bold font-display text-foreground" id="stat-total-faqs"><?php echo esc_html($total_count); ?></div>
                <div class="text-xs uppercase tracking-wider text-muted-foreground mt-1"><?php esc_html_e('Total answers', 'avr-web-consulting'); ?></div>
            </div>
            <div class="p-4 rounded-2xl border border-border bg-card">
                <div class="text-3xl font-bold font-display text-foreground"><?php echo esc_html(count($categories)); ?></div>
                <div class="text-xs uppercase tracking-wider text-muted-foreground mt-1"><?php esc_html_e('Topics', 'avr-web-consulting'); ?></div>
            </div>
            <div class="p-4 rounded-2xl border border-border bg-card">
                <div class="text-3xl font-bold font-display text-foreground"><?php esc_html_e('Instant', 'avr-web-consulting'); ?></div>
                <div class="text-xs uppercase tracking-wider text-muted-foreground mt-1"><?php esc_html_e('Search speed', 'avr-web-consulting'); ?></div>
            </div>
            <div class="p-4 rounded-2xl border border-border bg-card">
                <div class="text-3xl font-bold font-display text-foreground"><?php esc_html_e('Monthly', 'avr-web-consulting'); ?></div>
                <div class="text-xs uppercase tracking-wider text-muted-foreground mt-1"><?php esc_html_e('Updated', 'avr-web-consulting'); ?></div>
            </div>
        </div>

        <!-- Search & Filter Controls -->
        <div class="rounded-3xl border border-border bg-card p-5 lg:p-6 mb-8 shadow-sm">
            <label for="faq-hub-search" class="sr-only"><?php esc_html_e('Search all frequently asked questions', 'avr-web-consulting'); ?></label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
                <input id="faq-hub-search" 
                       type="search" 
                       placeholder="<?php esc_attr_e('Search e.g. \'AI Overviews\', \'pricing\', \'local SEO\', \'n8n\', \'dental SEO\'...', 'avr-web-consulting'); ?>" 
                       class="w-full h-12 rounded-full border border-border bg-background pl-12 pr-4 text-base text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all" />
            </div>

            <!-- Category Pills -->
            <div class="mt-4 flex flex-wrap gap-2" id="faq-category-filters" role="radiogroup" aria-label="<?php esc_attr_e('FAQ Categories', 'avr-web-consulting'); ?>">
                <button type="button" 
                        class="faq-cat-filter-btn rounded-full px-4 py-1.5 text-xs font-semibold border transition-all duration-150 bg-primary text-primary-foreground border-primary" 
                        data-category="All" 
                        aria-pressed="true">
                    <?php echo esc_html(sprintf(__('All (%d)', 'avr-web-consulting'), $total_count)); ?>
                </button>
                <?php foreach ($categories as $cat_name => $count) : ?>
                    <button type="button" 
                            class="faq-cat-filter-btn rounded-full px-4 py-1.5 text-xs font-semibold border border-border bg-background text-foreground/80 hover:bg-muted hover:text-foreground transition-all duration-150" 
                            data-category="<?php echo esc_attr($cat_name); ?>" 
                            aria-pressed="false">
                        <?php echo esc_html($cat_name); ?> (<?php echo esc_html($count); ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Live Status Count -->
        <p id="faq-hub-status" class="mb-6 text-sm text-muted-foreground" role="status" aria-live="polite">
            <?php echo esc_html(sprintf(__('Showing %d answers', 'avr-web-consulting'), min($initial_limit, $total_count))); ?>
        </p>

        <!-- Empty State (Hidden by default) -->
        <div id="faq-hub-empty" class="hidden my-8 rounded-3xl border border-dashed border-border p-10 text-center">
            <h3 class="font-display text-lg font-semibold text-foreground"><?php esc_html_e('No answer matches that search', 'avr-web-consulting'); ?></h3>
            <p class="mt-2 text-muted-foreground text-sm">
                <?php esc_html_e('Try a broader search term, or', 'avr-web-consulting'); ?>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="text-primary underline ml-1 font-medium"><?php esc_html_e('ask us directly', 'avr-web-consulting'); ?></a>
                — <?php esc_html_e('we reply within one business day.', 'avr-web-consulting'); ?>
            </p>
        </div>

        <!-- FAQ Items List -->
        <div id="faq-hub-accordion" class="rounded-3xl border border-border bg-card px-6 py-2 divide-y divide-border">
            <?php foreach ($initial_items as $index => $item) : 
                $item_id = !empty($item['id']) ? esc_attr($item['id']) : 'hub-faq-' . $index;
                $panel_id = 'hub-panel-' . $item_id;
                $btn_id = 'hub-btn-' . $item_id;
                $source_link = !empty($item['permalink']) ? esc_url(home_url($item['permalink'])) : '#';
                $source_title = !empty($item['pageTitle']) ? esc_html($item['pageTitle']) : '';
            ?>
                <details class="wp-block-details avr-faq-item py-4 group" data-faq-id="<?php echo esc_attr($item_id); ?>" data-category="<?php echo esc_attr(!empty($item['category']) ? $item['category'] : 'General'); ?>">
                    <summary class="flex items-center justify-between cursor-pointer font-display text-base font-semibold text-foreground list-none select-none hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg py-1" id="<?php echo esc_attr($btn_id); ?>" aria-controls="<?php echo esc_attr($panel_id); ?>">
                        <span class="avr-faq-question pr-4"><?php echo esc_html($item['question']); ?></span>
                        <svg class="avr-faq-icon h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </summary>
                    <div id="<?php echo esc_attr($panel_id); ?>" class="mt-3 text-base text-foreground/85 leading-relaxed pl-1 avr-faq-answer border-t border-border/40 pt-3" role="region" aria-labelledby="<?php echo esc_attr($btn_id); ?>">
                        <p><?php echo esc_html($item['answer']); ?></p>
                        <?php if (!empty($source_title)) : ?>
                            <p class="mt-3 text-sm">
                                <span class="text-muted-foreground"><?php esc_html_e('More on this: ', 'avr-web-consulting'); ?></span>
                                <a href="<?php echo $source_link; ?>" class="text-primary underline font-medium hover:text-primary/80 transition-colors">
                                    <?php echo $source_title; ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <!-- Load More Button -->
        <?php if ($total_count > $initial_limit) : ?>
            <div class="mt-8 text-center" id="faq-load-more-wrapper">
                <button type="button" id="faq-load-more-btn" class="rounded-full px-6 py-2.5 border border-border bg-background text-sm font-semibold text-foreground hover:bg-muted hover:border-foreground/30 transition-all">
                    <?php echo esc_html(sprintf(__('Show all %d answers', 'avr-web-consulting'), $total_count)); ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Embedded Master FAQ JSON for Ultra-Fast Instant Client-Side Search -->
        <script type="application/json" id="avr-master-faq-data">
            <?php echo wp_json_encode($all_faqs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
        </script>

        <!-- CTA Band -->
        <div class="mt-16 rounded-3xl border border-border bg-card p-8 lg:p-10 text-center">
            <h3 class="text-2xl font-bold font-display text-foreground mb-3">
                <?php esc_html_e('Still have a question?', 'avr-web-consulting'); ?>
            </h3>
            <p class="text-muted-foreground max-w-xl mx-auto mb-6">
                <?php esc_html_e('Send it over. We answer real questions with real specifics, not sales copy — usually within one business day.', 'avr-web-consulting'); ?>
            </p>
            <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="inline-flex items-center justify-center rounded-full bg-primary px-8 py-3 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-all">
                <?php esc_html_e('Contact Our Specialists', 'avr-web-consulting'); ?>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Filter the_content to automatically repair missing/empty FAQ answers.
 * If page content contains collapsed/empty Radix containers or empty FAQ accordion regions,
 * this filter matches the question and injects the authentic answer from the JSON dictionary.
 *
 * @param string $content Post content HTML.
 * @return string Content with all FAQ answers restored.
 */
function avr_filter_faq_content($content) {
    if (empty($content) || !is_singular()) {
        return $content;
    }

    $current_slug = avr_resolve_faq_slug();
    $page_faqs = avr_get_page_faqs($current_slug);

    if (empty($page_faqs)) {
        return $content;
    }

    // Build lookup map: normalized question -> answer
    $lookup = array();
    foreach ($page_faqs as $item) {
        $clean_q = mb_strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', wp_strip_all_tags($item['question']))));
        if (!empty($clean_q)) {
            $lookup[$clean_q] = $item['answer'];
        }
    }

    // Pattern 1: Empty Radix accordion region divs: <div role="region" ...></div> with empty content
    // e.g. <button ...>Question</button></h3><div data-state="closed" id="radix-_R_..." hidden="" role="region" ...></div>
    $pattern = '/(<button[^>]*>([\s\S]*?)<\/button>(?:[\s\S]*?)<div[^>]*role="region"[^>]*>)\s*(<\/div>)/i';
    
    $repaired = preg_replace_callback($pattern, function($matches) use ($lookup) {
        $btn_html = $matches[2];
        $clean_q = mb_strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', wp_strip_all_tags($btn_html))));

        // Find match in lookup or partial match
        $answer = '';
        if (isset($lookup[$clean_q])) {
            $answer = $lookup[$clean_q];
        } else {
            foreach ($lookup as $q_key => $ans_text) {
                if (strpos($clean_q, $q_key) !== false || strpos($q_key, $clean_q) !== false) {
                    $answer = $ans_text;
                    break;
                }
            }
        }

        if (!empty($answer)) {
            $answer_html = '<div class="mt-3 text-base text-foreground/85 leading-relaxed pl-1 avr-faq-answer border-t border-border/40 pt-3"><p>' . esc_html($answer) . '</p></div>';
            return $matches[1] . $answer_html . $matches[3];
        }

        return $matches[0];
    }, $content);

    return $repaired;
}
add_filter('the_content', 'avr_filter_faq_content', 15);

/**
 * Outputs FAQPage JSON-LD Structured Data in wp_head for pages containing FAQs.
 */
function avr_faq_schema_output() {
    if (!is_singular()) {
        return;
    }

    // Skip if Yoast or RankMath is handling schema
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')) {
        return;
    }

    $current_slug = avr_resolve_faq_slug();
    $faqs = avr_get_page_faqs($current_slug);

    if (empty($faqs)) {
        return;
    }

    // Limit to 20 for JSON-LD to avoid bloat
    $schema_faqs = array_slice($faqs, 0, 20);
    $main_entity = array();

    foreach ($schema_faqs as $item) {
        $main_entity[] = array(
            '@type'          => 'Question',
            'name'           => $item['question'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => $item['answer'],
            ),
        );
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $main_entity,
    );

    echo "\n<!-- AVR FAQPage Schema -->\n";
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
add_action('wp_head', 'avr_faq_schema_output', 25);

/**
 * Register Shortcodes for manual or block embedding.
 */
add_shortcode('avr_faqs', function($atts) {
    $a = shortcode_atts(array(
        'slug'     => '',
        'title'    => '',
        'subtitle' => '',
    ), $atts);

    return avr_render_faqs($a['slug'], $a['title'], $a['subtitle']);
});

add_shortcode('avr_faq_hub', function($atts) {
    return avr_render_faq_hub();
});
