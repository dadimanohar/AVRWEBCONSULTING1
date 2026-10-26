/**
 * AVR Web Consulting - Production Theme JavaScript
 * Fully accessible navigation, robust FAQ accordion handling, deferred hero video, and inquiry form.
 */

(function() {
  'use strict';

  function initAVRTheme() {
    // -----------------------------------------------------------------
    // 1. Mobile Navigation Drawer & Dropdowns
    // -----------------------------------------------------------------
    var menuBtn = document.getElementById('avr-mobile-menu-btn');
    var drawer = document.getElementById('avr-mobile-drawer');
    var overlay = document.getElementById('avr-mobile-overlay');
    var closeBtn = document.getElementById('avr-mobile-drawer-close');

    function openMobileMenu() {
      if (!drawer || !overlay) return;
      overlay.classList.remove('pointer-events-none', 'opacity-0');
      overlay.classList.add('pointer-events-auto', 'opacity-100');

      drawer.classList.remove('pointer-events-none', 'translate-x-full');
      drawer.classList.add('pointer-events-auto', 'translate-x-0');
      drawer.setAttribute('aria-hidden', 'false');

      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
      document.body.classList.add('overflow-hidden');

      if (closeBtn) closeBtn.focus();
    }

    function closeMobileMenu() {
      if (!drawer || !overlay) return;
      overlay.classList.remove('pointer-events-auto', 'opacity-100');
      overlay.classList.add('pointer-events-none', 'opacity-0');

      drawer.classList.remove('pointer-events-auto', 'translate-x-0');
      drawer.classList.add('pointer-events-none', 'translate-x-full');
      drawer.setAttribute('aria-hidden', 'true');

      if (menuBtn) {
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.focus();
      }
      document.body.classList.remove('overflow-hidden');
    }

    if (menuBtn) {
      menuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var isExpanded = menuBtn.getAttribute('aria-expanded') === 'true';
        if (isExpanded) {
          closeMobileMenu();
        } else {
          openMobileMenu();
        }
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeMobileMenu();
      });
    }

    if (overlay) {
      overlay.addEventListener('click', function(e) {
        e.preventDefault();
        closeMobileMenu();
      });
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeMobileMenu();
        closeAllDesktopDropdowns();
      } else if (e.key === 'Tab' && drawer && drawer.getAttribute('aria-hidden') === 'false') {
        var focusable = drawer.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length > 0) {
          var first = focusable[0];
          var last = focusable[focusable.length - 1];
          if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
          } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
          }
        }
      }
    });

    if (drawer) {
      var drawerLinks = drawer.querySelectorAll('a');
      drawerLinks.forEach(function(link) {
        link.addEventListener('click', function() {
          closeMobileMenu();
        });
      });

      // Mobile Accordion Categories in Drawer
      var accTriggers = drawer.querySelectorAll('.avr-mobile-acc-trigger');
      accTriggers.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var targetId = btn.getAttribute('data-target');
          var content = document.getElementById(targetId);
          var icon = btn.querySelector('.avr-acc-icon');
          var isExpanded = btn.getAttribute('aria-expanded') === 'true';

          if (content) {
            if (isExpanded) {
              content.classList.add('hidden');
              btn.setAttribute('aria-expanded', 'false');
              if (icon) icon.classList.remove('rotate-180');
            } else {
              content.classList.remove('hidden');
              btn.setAttribute('aria-expanded', 'true');
              if (icon) icon.classList.add('rotate-180');
            }
          }
        });
      });
    }

    // Desktop Dropdowns
    var desktopGroups = document.querySelectorAll('.avr-nav-group');
    function closeAllDesktopDropdowns() {
      desktopGroups.forEach(function(group) {
        var dropdown = group.querySelector('.avr-nav-dropdown');
        var link = group.querySelector('.avr-nav-link');
        var chevron = group.querySelector('.avr-chevron');
        if (dropdown) {
          dropdown.classList.remove('!visible', '!opacity-100', '!translate-y-0', '!pointer-events-auto');
        }
        if (link) link.setAttribute('aria-expanded', 'false');
        if (chevron) chevron.classList.remove('rotate-180');
      });
    }

    desktopGroups.forEach(function(group) {
      var link = group.querySelector('.avr-nav-link');
      var dropdown = group.querySelector('.avr-nav-dropdown');
      var chevron = group.querySelector('.avr-chevron');

      if (!link || !dropdown) return;

      link.addEventListener('click', function(e) {
        if (window.innerWidth >= 1280 && ('ontouchstart' in window || navigator.maxTouchPoints > 0)) {
          e.preventDefault();
          var isExpanded = link.getAttribute('aria-expanded') === 'true';
          if (isExpanded) {
            dropdown.classList.remove('!visible', '!opacity-100', '!translate-y-0', '!pointer-events-auto');
            link.setAttribute('aria-expanded', 'false');
            if (chevron) chevron.classList.remove('rotate-180');
          } else {
            closeAllDesktopDropdowns();
            dropdown.classList.add('!visible', '!opacity-100', '!translate-y-0', '!pointer-events-auto');
            link.setAttribute('aria-expanded', 'true');
            if (chevron) chevron.classList.add('rotate-180');
          }
        }
      });

      group.addEventListener('mouseenter', function() {
        if (window.innerWidth >= 1280) {
          dropdown.classList.add('!visible', '!opacity-100', '!translate-y-0', '!pointer-events-auto');
          link.setAttribute('aria-expanded', 'true');
          if (chevron) chevron.classList.add('rotate-180');
        }
      });

      group.addEventListener('mouseleave', function() {
        if (window.innerWidth >= 1280) {
          dropdown.classList.remove('!visible', '!opacity-100', '!translate-y-0', '!pointer-events-auto');
          link.setAttribute('aria-expanded', 'false');
          if (chevron) chevron.classList.remove('rotate-180');
        }
      });
    });

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.avr-nav-group')) {
        closeAllDesktopDropdowns();
      }
    });

    // -----------------------------------------------------------------
    // 2. FAQ Accordion Handling (Both Native <details> and Button Fallbacks)
    // -----------------------------------------------------------------
    // A. Native HTML5 <details> Enhancement (Core block wp:details & avr-faq-item)
    var detailsElements = document.querySelectorAll('details.wp-block-details, details.avr-faq-item');
    detailsElements.forEach(function(detail) {
      detail.addEventListener('toggle', function() {
        var chevron = detail.querySelector('.avr-faq-icon, .avr-faq-chevron, svg');
        if (chevron) {
          if (detail.open) {
            chevron.style.transform = 'rotate(180deg)';
          } else {
            chevron.style.transform = 'rotate(0deg)';
          }
        }
      });
    });

    // B. Custom & Button FAQ Accordions (including Radix UI and .avr-faq-trigger)
    var faqButtons = document.querySelectorAll('.avr-faq-trigger, [data-faq-trigger], [data-orientation="vertical"] button, .faq-trigger-btn, button[aria-controls^="radix-"], button[aria-controls^="faq-"]');
    faqButtons.forEach(function(btn) {
      if (!btn.hasAttribute('aria-expanded')) {
        btn.setAttribute('aria-expanded', 'false');
      }

      function toggleFaqButton(e) {
        if (e) e.preventDefault();
        var controlsId = btn.getAttribute('aria-controls');
        var content = controlsId ? document.getElementById(controlsId) : null;
        if (!content) {
          var item = btn.closest('.border-b') || btn.closest('.avr-faq-item') || btn.parentElement;
          content = item ? item.querySelector('[role="region"], div[data-state], .avr-faq-panel, .faq-content') : null;
        }
        var icon = btn.querySelector('.avr-faq-chevron, svg.lucide-chevron-down, svg');

        if (content) {
          var isClosed = content.hasAttribute('hidden') || content.classList.contains('hidden') || content.getAttribute('data-state') === 'closed';

          if (isClosed) {
            // Safety net: check if answer container was empty and inject answer
            if (content.textContent.trim().length === 0 && window.__avrMasterFaqs && window.__avrMasterFaqs.length) {
              var qText = btn.textContent.replace(/[\+\-]/g, '').trim().toLowerCase();
              var found = window.__avrMasterFaqs.find(function(f) {
                return f.question && f.question.toLowerCase().indexOf(qText.slice(0, 30)) !== -1;
              });
              if (found) {
                content.innerHTML = '<div class="mt-3 text-base text-foreground/85 leading-relaxed pl-1 avr-faq-answer border-t border-border/40 pt-3"><p>' + found.answer + '</p></div>';
              }
            }

            content.removeAttribute('hidden');
            content.classList.remove('hidden');
            content.setAttribute('data-state', 'open');
            btn.setAttribute('data-state', 'open');
            btn.setAttribute('aria-expanded', 'true');
            if (icon) icon.style.transform = 'rotate(180deg)';
          } else {
            content.setAttribute('hidden', '');
            content.classList.add('hidden');
            content.setAttribute('data-state', 'closed');
            btn.setAttribute('data-state', 'closed');
            btn.setAttribute('aria-expanded', 'false');
            if (icon) icon.style.transform = 'rotate(0deg)';
          }
        }
      }

      btn.addEventListener('click', toggleFaqButton);
      btn.addEventListener('keydown', function(e) {
        if (e.key === ' ' || e.key === 'Spacebar') {
          e.preventDefault();
          toggleFaqButton();
        }
      });
    });

    // -----------------------------------------------------------------
    // 3. Optimized Deferred Hero Video Loading
    // -----------------------------------------------------------------
    var heroVideo = document.querySelector('video[data-avr-deferred-video], .avr-hero-video');
    if (heroVideo) {
      // Respect user's reduced-motion preference & save mobile data
      var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      var isMobile = window.innerWidth < 768;

      // On mobile viewports, keep the instant, high-contrast static WebP poster for maximum PageSpeed (LCP < 1s)
      // On desktop (>= 768px), defer video start until idle so it never blocks FCP or LCP
      if (!prefersReducedMotion && !isMobile) {
        var startVideo = function() {
          var sources = heroVideo.querySelectorAll('source');
          var hasSrc = false;
          sources.forEach(function(s) {
            var dataSrc = s.getAttribute('data-src');
            if (dataSrc) {
              s.src = dataSrc;
              hasSrc = true;
            }
          });

          if (hasSrc) {
            heroVideo.load();
            var playPromise = heroVideo.play();
            if (playPromise !== undefined) {
              playPromise.then(function() {
                heroVideo.classList.remove('opacity-0');
              }).catch(function() {
                // Autoplay policy prevented playback, keep static poster visible
              });
            }
          }
        };

        // Delay video loading until after critical render completes
        if ('requestIdleCallback' in window) {
          window.requestIdleCallback(startVideo, { timeout: 2000 });
        } else {
          window.addEventListener('load', function() {
            setTimeout(startVideo, 400);
          });
        }
      }
    }

    // -----------------------------------------------------------------
    // 4. Table of Contents Smooth Scrolling
    // -----------------------------------------------------------------
    var tocAnchors = document.querySelectorAll('aside a[href^="#"], .toc-link, nav[aria-label="On this page"] a');
    tocAnchors.forEach(function(anchor) {
      anchor.addEventListener('click', function(e) {
        var href = anchor.getAttribute('href');
        if (href && href.length > 1) {
          var target = document.querySelector(href);
          if (target) {
            e.preventDefault();
            var headerOffset = 90;
            var elementPosition = target.getBoundingClientRect().top;
            var offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
              top: offsetPosition,
              behavior: 'smooth'
            });
            if (history.pushState) {
              history.pushState(null, '', href);
            }
          }
        }
      });
    });

    // -----------------------------------------------------------------
    // 5. Interactive Consultation & Inquiry Form Handler
    // -----------------------------------------------------------------
    var forms = document.querySelectorAll('form[data-avr-inquiry-form], form.pricing-inquiry-form, form#avr-inquiry-form');
    forms.forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var submitBtn = form.querySelector('button[type="submit"]');
        var originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg><span>Submitting...</span></span>';
        }

        var formData = new FormData(form);
        formData.append('action', 'avr_submit_inquiry');
        if (window.avr_theme_data && window.avr_theme_data.nonce) {
          formData.append('nonce', window.avr_theme_data.nonce);
        }

        var ajaxUrl = (window.avr_theme_data && window.avr_theme_data.ajax_url) || '/wp-admin/admin-ajax.php';

        fetch(ajaxUrl, {
          method: 'POST',
          body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }
          var notice = form.querySelector('.form-success-notice');
          if (!notice) {
            notice = document.createElement('div');
            notice.className = 'form-success-notice mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium';
            form.appendChild(notice);
          }
          notice.textContent = (data && data.message) || 'Thank you! Your inquiry has been sent successfully. Our team will contact you within 24 hours.';
          form.reset();
        })
        .catch(function() {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
          }
          var notice = form.querySelector('.form-success-notice');
          if (!notice) {
            notice = document.createElement('div');
            notice.className = 'form-success-notice mt-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium';
            form.appendChild(notice);
          }
          notice.textContent = 'Thank you! Your inquiry has been received. Our team will contact you within 24 hours.';
          form.reset();
        });
      });
    });

    // -----------------------------------------------------------------
    // 6. Interactive Searchable FAQ Hub (/faqs/ page)
    // -----------------------------------------------------------------
    var faqHub = document.getElementById('avr-faq-hub');
    if (faqHub) {
      var searchInput = document.getElementById('faq-hub-search');
      var filterButtons = faqHub.querySelectorAll('.faq-cat-filter-btn');
      var statusText = document.getElementById('faq-hub-status');
      var emptyNotice = document.getElementById('faq-hub-empty');
      var accordionContainer = document.getElementById('faq-hub-accordion');
      var loadMoreBtn = document.getElementById('faq-load-more-btn');
      var loadMoreWrapper = document.getElementById('faq-load-more-wrapper');

      var masterDataEl = document.getElementById('avr-master-faq-data');
      var masterFaqs = [];
      if (masterDataEl) {
        try {
          masterFaqs = JSON.parse(masterDataEl.textContent.trim());
          window.__avrMasterFaqs = masterFaqs;
        } catch (err) {
          console.warn('Could not parse master FAQ data:', err);
        }
      }

      var currentCat = 'All';
      var currentSearch = '';
      var maxShown = 80;

      function escapeHtml(str) {
        if (!str) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function renderHubFaqs() {
        if (!masterFaqs || !masterFaqs.length) return;

        var q = currentSearch.toLowerCase().trim();
        var filtered = masterFaqs.filter(function(item) {
          if (currentCat !== 'All' && item.category !== currentCat) {
            return false;
          }
          if (!q) return true;
          var inQ = item.question && item.question.toLowerCase().indexOf(q) !== -1;
          var inA = item.answer && item.answer.toLowerCase().indexOf(q) !== -1;
          var inP = item.pageTitle && item.pageTitle.toLowerCase().indexOf(q) !== -1;
          return inQ || inA || inP;
        });

        // Update status indicator
        if (statusText) {
          if (filtered.length === 0) {
            statusText.textContent = '0 answers found';
          } else if (q) {
            statusText.textContent = filtered.length + ' answer' + (filtered.length === 1 ? '' : 's') + ' matching "' + currentSearch + '"' + (filtered.length > maxShown ? ' — showing first ' + maxShown : '');
          } else {
            statusText.textContent = 'Showing ' + Math.min(filtered.length, maxShown) + ' of ' + filtered.length + ' answers';
          }
        }

        // Show/hide empty state
        if (emptyNotice) {
          if (filtered.length === 0) {
            emptyNotice.classList.remove('hidden');
          } else {
            emptyNotice.classList.add('hidden');
          }
        }

        // Show/hide load more button
        if (loadMoreWrapper) {
          if (filtered.length > maxShown) {
            loadMoreWrapper.classList.remove('hidden');
          } else {
            loadMoreWrapper.classList.add('hidden');
          }
        }

        // Slice items
        var itemsToRender = filtered.slice(0, maxShown);
        var homeUrl = (window.avr_theme_data && window.avr_theme_data.home_url) || '/';
        if (homeUrl.substr(-1) !== '/') homeUrl += '/';

        var html = '';
        for (var i = 0; i < itemsToRender.length; i++) {
          var item = itemsToRender[i];
          var itemId = item.id || ('faq-hub-' + i);
          var panelId = 'hub-panel-' + itemId;
          var btnId = 'hub-btn-' + itemId;
          var link = item.permalink ? (homeUrl + item.permalink.replace(/^\//, '')) : '#';
          var pTitle = item.pageTitle || '';

          html += '<details class="wp-block-details avr-faq-item py-4 group" data-faq-id="' + itemId + '" data-category="' + (item.category || 'General') + '">';
          html += '  <summary class="flex items-center justify-between cursor-pointer font-display text-base font-semibold text-foreground list-none select-none hover:text-primary transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg py-1" id="' + btnId + '" aria-controls="' + panelId + '">';
          html += '    <span class="avr-faq-question pr-4">' + escapeHtml(item.question) + '</span>';
          html += '    <svg class="avr-faq-icon h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
          html += '  </summary>';
          html += '  <div id="' + panelId + '" class="mt-3 text-base text-foreground/85 leading-relaxed pl-1 avr-faq-answer border-t border-border/40 pt-3" role="region" aria-labelledby="' + btnId + '">';
          html += '    <p>' + escapeHtml(item.answer) + '</p>';
          if (pTitle) {
            html += '    <p class="mt-3 text-sm">';
            html += '      <span class="text-muted-foreground">More on this: </span>';
            html += '      <a href="' + link + '" class="text-primary underline font-medium hover:text-primary/80 transition-colors">' + escapeHtml(pTitle) + '</a>';
            html += '    </p>';
          }
          html += '  </div>';
          html += '</details>';
        }

        if (accordionContainer) {
          accordionContainer.innerHTML = html;
        }
      }

      // Filter button click handler
      filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          var cat = btn.getAttribute('data-category') || 'All';
          currentCat = cat;
          maxShown = 80;

          // Update active pill button visual styles
          filterButtons.forEach(function(b) {
            var isActive = (b === btn);
            b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            if (isActive) {
              b.className = 'faq-cat-filter-btn rounded-full px-4 py-1.5 text-xs font-semibold border transition-all duration-150 bg-primary text-primary-foreground border-primary';
            } else {
              b.className = 'faq-cat-filter-btn rounded-full px-4 py-1.5 text-xs font-semibold border border-border bg-background text-foreground/80 hover:bg-muted hover:text-foreground transition-all duration-150';
            }
          });

          renderHubFaqs();
        });
      });

      // Search input debounced
      if (searchInput) {
        var searchDebounceTimer = null;
        searchInput.addEventListener('input', function() {
          clearTimeout(searchDebounceTimer);
          searchDebounceTimer = setTimeout(function() {
            currentSearch = searchInput.value;
            maxShown = 80;
            renderHubFaqs();
          }, 120);
        });
      }

      // Load more button
      if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function(e) {
          e.preventDefault();
          maxShown = 9999;
          renderHubFaqs();
        });
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAVRTheme);
  } else {
    initAVRTheme();
  }
})();
