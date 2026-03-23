/**
 * ============================================================
 *  Universal Automatic Text Contrast System
 *  File: /assets/js/auto-contrast.js
 * ============================================================
 *  Automatically adjusts text color (Light/Dark) based on
 *  background brightness of sections and components.
 *
 *  Luminance formula (ITU-R BT.601):
 *    brightness = 0.299·R + 0.587·G + 0.114·B
 *    brightness > 186  →  dark text  (.dark-text)
 *    brightness ≤ 186  →  light text (.light-text)
 * ============================================================
 */

(function () {
    'use strict';

    // Check if the system is enabled via global config
    if (window.siteConfig && window.siteConfig.autoContrast === false) {
        console.log("Auto Contrast System: Disabled via Admin Panel.");
        return;
    }

    const CONFIG = {
        threshold: 186,
        // Selectors for sections/containers that should be checked
        targetSelectors: [
            'section',
            'header',
            'footer',
            '.navbar',
            '.bottom-nav',
            '.hero-slider',
            '.hero-slide',
            '.carousel-item',
            '.card',
            '.product-card',
            '.banner',
            '.hero-section',
            '.bg-image',
            '.banner-section',
            '[data-auto-contrast]'
        ].join(', '),
        // Selectors for specific interactives
        btnSelector: [
            '.btn',
            'button:not(.navbar-toggler)',
            'input[type="submit"]',
            'input[type="button"]'
        ].join(', '),
        // Classes to ignore
        skipClasses: ['no-contrast', 'navbar-toggler', 'btn-close']
    };

    // Cache for background image brightness results
    const imgBrightnessCache = new Map();

    /**
     * Parse rgb(a) string to {r, g, b}
     */
    function parseRGB(colorStr) {
        if (!colorStr || colorStr === 'transparent' || colorStr === 'rgba(0, 0, 0, 0)') return null;
        const m = colorStr.match(/rgba?\(\s*(\d+),\s*(\d+),\s*(\d+)/);
        if (!m) return null;
        return { r: +m[1], g: +m[2], b: +m[3] };
    }

    /**
     * Calculate brightness using luminance formula
     */
    function calculateBrightness(rgb) {
        return 0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b;
    }

    /**
     * Sample an image brightness using canvas
     */
    async function sampleImageBrightness(url) {
        if (imgBrightnessCache.has(url)) return imgBrightnessCache.get(url);

        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.src = url;
            img.onload = function () {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                // Use a very small canvas for sampling (performance)
                canvas.width = 10;
                canvas.height = 10;
                ctx.drawImage(img, 0, 0, 10, 10);
                
                try {
                    const data = ctx.getImageData(0, 0, 10, 10).data;
                    let r = 0, g = 0, b = 0;
                    for (let i = 0; i < data.length; i += 4) {
                        r += data[i];
                        g += data[i + 1];
                        b += data[i + 2];
                    }
                    const count = data.length / 4;
                    const brightness = calculateBrightness({ r: r / count, g: g / count, b: b / count });
                    imgBrightnessCache.set(url, brightness);
                    resolve(brightness);
                } catch (e) {
                    console.warn("Auto Contrast: Canvas tainted by cross-origin image.", url);
                    resolve(null); // Fallback to color detection
                }
            };
            img.onerror = () => resolve(null);
        });
    }

    /**
     * Determine effective background brightness of an element
     */
    async function getEffectiveBrightness(el) {
        const computed = window.getComputedStyle(el);
        
        // 1. Check for Background Image first
        const bgImg = computed.backgroundImage;
        if (bgImg && bgImg !== 'none' && bgImg.includes('url')) {
            const urlMatch = bgImg.match(/url\(["']?([^"']+)["']?\)/);
            if (urlMatch) {
                const imgBrightness = await sampleImageBrightness(urlMatch[1]);
                if (imgBrightness !== null) return imgBrightness;
            }
        }

        // 2. Check for Overlay (Common in hero sections)
        // Some systems use a pseudo-element with background color
        // This is hard to detect universally, so we might need to check if the element has 
        // a specific overlay class or just rely on the parent/computed bg.

        // 3. Check for Background Color
        let rgb = parseRGB(computed.backgroundColor);
        if (rgb) return calculateBrightness(rgb);

        // 4. Fallback: Check parent background if transparent
        let parent = el.parentElement;
        while (parent && parent !== document.body.parentElement) {
            const pStyle = window.getComputedStyle(parent);
            const pRgb = parseRGB(pStyle.backgroundColor);
            if (pRgb) return calculateBrightness(pRgb);
            parent = parent.parentElement;
        }

        // Default to white background brightness
        return 255;
    }

    /**
     * Apply contrast class to an element
     */
    async function applyToElement(el) {
        // Skip ignored classes
        if (CONFIG.skipClasses.some(cls => el.classList.contains(cls))) return;

        // Use cache/attribute to avoid redundant runs
        if (el.dataset.acDone === "true") return;

        const brightness = await getEffectiveBrightness(el);
        const isDark = brightness < CONFIG.threshold;

        // Apply classes
        el.classList.remove('light-text', 'dark-text');
        el.classList.add(isDark ? 'light-text' : 'dark-text');

        // Special handling for Navbar (Bootstrap compatibility)
        if (el.classList.contains('navbar')) {
            el.classList.remove('navbar-light', 'navbar-dark');
            el.classList.add(isDark ? 'navbar-dark' : 'navbar-light');
        }
        
        // Mark as processed
        el.dataset.acDone = "true";
    }

    /**
     * Special handling for buttons (always processed)
     * Falls back to walking up the DOM when the button's own background
     * is transparent (e.g. when it is set via a CSS custom property like
     * var(--btn-color) that hasn't fully resolved at DOMContentLoaded).
     */
    function applyToButton(btn) {
        if (CONFIG.skipClasses.some(cls => btn.classList.contains(cls))) return;

        const style = window.getComputedStyle(btn);
        let rgb = parseRGB(style.backgroundColor);

        // Fallback: walk up the DOM to find the first opaque background.
        // This handles buttons whose bg is set via a CSS variable that
        // resolves to transparent during the initial synchronous pass.
        if (!rgb) {
            let ancestor = btn.parentElement;
            while (ancestor && ancestor !== document.documentElement) {
                const aStyle = window.getComputedStyle(ancestor);
                const aRgb = parseRGB(aStyle.backgroundColor);
                if (aRgb) { rgb = aRgb; break; }
                ancestor = ancestor.parentElement;
            }
        }

        // Still nothing — assume a white/light surface so we get dark text.
        if (!rgb) rgb = { r: 255, g: 255, b: 255 };

        const brightness = calculateBrightness(rgb);
        btn.classList.remove('light-text', 'dark-text');
        btn.classList.add(brightness < CONFIG.threshold ? 'light-text' : 'dark-text');
    }

    /**
     * Main Scan Function
     */
    function scan() {
        // Process Containers
        document.querySelectorAll(CONFIG.targetSelectors).forEach(applyToElement);
        
        // Process Buttons
        document.querySelectorAll(CONFIG.btnSelector).forEach(applyToButton);
    }

    /**
     * Initialize the system
     */
    function init() {
        scan();

        // MutationObserver to handle dynamically added content
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mut => {
                mut.addedNodes.forEach(node => {
                    if (node.nodeType !== 1) return;
                    
                    if (node.matches(CONFIG.targetSelectors)) applyToElement(node);
                    if (node.matches(CONFIG.btnSelector)) applyToButton(node);
                    
                    node.querySelectorAll(CONFIG.targetSelectors).forEach(applyToElement);
                    node.querySelectorAll(CONFIG.btnSelector).forEach(applyToButton);
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Re-scan on window resize or theme change if applicable
        window.addEventListener('resize', debounce(scan, 250));
        window.addEventListener('themeColorChanged', () => {
             // Clear processed markers for re-scan
             document.querySelectorAll('[data-ac-done]').forEach(el => delete el.dataset.acDone);
             scan();
        });
    }

    /**
     * Helper: Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Start
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Secondary scan on full window load to catch delayed images/overlays
    // AND buttons whose CSS-variable backgrounds resolve after DOMContentLoaded.
    window.addEventListener('load', () => {
        // First pass: re-scan everything (catches late CSS variable resolution)
        setTimeout(scan, 100);
        // Second pass: re-scan buttons specifically after a longer delay to
        // handle any theme variables injected by ThemeService that may take
        // a little longer to cascade through Bootstrap's stylesheet.
        setTimeout(() => {
            document.querySelectorAll(CONFIG.btnSelector).forEach(applyToButton);
        }, 600);
    });

    // Public API
    window.UniversalContrast = {
        scan: scan,
        reprocess: () => {
            document.querySelectorAll('[data-ac-done]').forEach(el => delete el.dataset.acDone);
            scan();
        }
    };

})();
