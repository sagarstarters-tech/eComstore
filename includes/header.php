<?php
define('HEADER_INCLUDED', true);
ob_start();
include_once __DIR__ . '/session_setup.php';
include __DIR__ . '/db_connect.php';
require_once __DIR__ . '/maintenance.php';
checkMaintenanceMode();

// Calculate cart item count and total amount
$cart_count = 0;
$cart_total = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    if (count($product_ids) > 0) {
        $ids_str = implode(',', array_map('intval', $product_ids));
        $price_q = $conn->query("SELECT id, price FROM products WHERE id IN ($ids_str)");
        $prices = [];
        if ($price_q) {
            while ($row = $price_q->fetch_assoc()) {
                $prices[$row['id']] = $row['price'];
            }
        }
        foreach ($_SESSION['cart'] as $pid => $qty) {
            $cart_count += $qty;
            if (isset($prices[$pid])) {
                $cart_total += ($prices[$pid] * $qty);
            }
        }
    }
}

// Fallback: If profile_photo is not in session (from older logins), fetch it
if (isset($_SESSION['user_id']) && !isset($_SESSION['profile_photo'])) {
    $uid = intval($_SESSION['user_id']);
    $usr_q = $conn->query("SELECT profile_photo FROM users WHERE id=$uid");
    if ($usr_q && $usr_q->num_rows > 0) {
        $_SESSION['profile_photo'] = $usr_q->fetch_assoc()['profile_photo'] ?? '';
    } else {
        $_SESSION['profile_photo'] = '';
    }
}
require_once __DIR__ . '/SeoService.php';
$seoService = new SeoService($conn);

// Determine entity type and ID for SEO
$current_script = basename($_SERVER['SCRIPT_NAME']);
$entity_type = 'home';
$entity_id = 0;

if (isset($product['id'])) {
    $entity_type = 'product';
    $entity_id = intval($product['id']);
} elseif ($current_script === 'shop.php') {
    if (isset($_GET['category'])) {
        $entity_type = 'category';
        $entity_id = intval($_GET['category']);
    } elseif (isset($_GET['category_slug'])) {
        $entity_type = 'category';
        $category_slug = $conn->real_escape_string($_GET['category_slug']);
        $cat_q = $conn->query("SELECT id FROM categories WHERE slug='$category_slug'");
        if ($cat_q && $cat_q->num_rows > 0) {
            $entity_id = $cat_q->fetch_assoc()['id'];
        }
    } else {
        $entity_type = 'shop';
    }
} elseif ($current_script === 'page.php' && isset($_GET['slug'])) {
    $entity_type = 'page';
    $slug = $conn->real_escape_string($_GET['slug']);
    $page_q = $conn->query("SELECT id FROM pages WHERE slug='$slug'");
    if ($page_q && $page_q->num_rows > 0) {
        $entity_id = $page_q->fetch_assoc()['id'];
    }
}
// DEBUG: error_log("SEO Debug: Page=$current_page, Type=$entity_type, ID=$entity_id, SCRIPT=" . $_SERVER['SCRIPT_NAME']);

$seoData = $seoService->getPageSeo($entity_type, $entity_id, [
    'title' => $page_meta_title ?? null,
    'description' => $page_meta_description ?? null,
    'image' => $page_meta_image ?? null
]);

// Ensure social images are absolute URLs
function makeAbsoluteUrl($path) {
    if (empty($path)) return '';
    
    // TRAP: If it's already an absolute URL (starts with http), return as is
    if (strpos($path, 'http') === 0 && filter_var($path, FILTER_VALIDATE_URL)) {
        return $path;
    }
    
    // Determine protocol accurately
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $scheme = "https";
    }
    
    // Force HTTPS on production
    if (defined('APP_ENV') && APP_ENV === 'production') {
        $scheme = "https";
    }

    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = SITE_URL; // e.g., "" or "/store"
    
    // Remove leading slash from path for clean joining
    $cleanPath = ltrim($path, '/');
    
    // If path contains a slash (like "assets/images/file.jpg"), it's a relative path from app root
    if (strpos($cleanPath, '/') !== false) {
        $finalPath = $baseUrl . '/' . $cleanPath;
    } else {
        // Otherwise, it's a single filename. Assume it's in assets/images/
        $finalPath = $baseUrl . '/assets/images/' . $cleanPath;
    }
    
    // Clean up multiple slashes (e.g., //assets becomes /assets)
    // IMPORTANT: Don't let it break the protocol (http://)
    if (strpos($finalPath, '://') !== false) {
        $parts = explode('://', $finalPath, 2);
        $finalPath = $parts[0] . '://' . preg_replace('#/+#', '/', $parts[1]);
    } else {
        $finalPath = preg_replace('#/+#', '/', '/' . $finalPath);
    }
    
    return $scheme . "://" . $host . $finalPath;
}

$og_image_url = makeAbsoluteUrl($seoData['og_image']);
$twitter_image_url = makeAbsoluteUrl($seoData['twitter_image']);
// DEBUG: error_log("SEO Debug: OG Image=$og_image_url");

// Generate current canonical URL for og:url
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$current_url = $scheme . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seoData['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(trim($seoData['description'] ?? '')); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoData['keywords'] ?? ''); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($seoData['robots'] ?? 'index, follow'); ?>">
    <meta itemprop="image" content="<?php echo htmlspecialchars($og_image_url); ?>">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="<?php echo htmlspecialchars($seoData['og_type'] ?? 'website'); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars(trim($seoData['og_title'] ?? '')); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(trim($seoData['og_description'] ?? '')); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($seoData['og_title'] ?? 'Product Image'); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <?php if(!empty($seoData['site_name'])): ?>
    <meta property="og:site_name" content="<?php echo htmlspecialchars($seoData['site_name']); ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars(trim($seoData['twitter_title'] ?? '')); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(trim($seoData['twitter_description'] ?? '')); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($twitter_image_url); ?>">

    <?php if (!empty($seoData['favicon'])): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/<?php echo $seoData['favicon']; ?>">
    <?php endif; ?>

    <?php if (!empty($seoData['canonical'])): ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($seoData['canonical']); ?>">
    <?php endif; ?>

    <!-- PWA Settings -->
    <link rel="manifest" href="<?php echo SITE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#1e3c72">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/images/logo.jpg">

    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet"/>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <!-- Custom CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/style.css?v=1.1" rel="stylesheet">
    <!-- Theme Customizer CSS Variables -->
    <?php
    require_once __DIR__ . '/ThemeService.php';
    ThemeService::injectCSS($conn);
    ?>
    <!-- WhatsApp Widget CSS -->
    <link href="<?php echo SITE_URL; ?>/whatsapp-style.css" rel="stylesheet">
    <!-- Custom Animations CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/animations.css?v=1.1" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <?php
    require_once 'ScriptService.php';
    $scriptService = new ScriptService($conn);
    echo $scriptService->getHeaderScripts();
    ?>
    <!-- Auto Contrast Algorithm: ensures all button text is always readable -->
    <script>
        window.siteConfig = {
            autoContrast: <?php echo (isset($global_settings['auto_text_contrast']) && $global_settings['auto_text_contrast'] == '1') ? 'true' : 'false'; ?>
        };
    </script>
    <script src="<?php echo ASSETS_URL; ?>/js/auto-contrast.js" defer></script>
    
    <!-- Theme Selection Check Script for Immediate Load (prevents unstyled flash) -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-mdb-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <script src="<?php echo ASSETS_URL; ?>/js/theme-toggle.js?v=1.1" defer></script>

    <!-- Register Service Worker -->
    <script>
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('<?php echo SITE_URL; ?>/sw.js').then(registration => {
            console.log('SW registered: ', registration);
          }).catch(registrationError => {
            console.log('SW registration failed: ', registrationError);
          });
        });
      }
    </script>
    
    <?php if (!isset($_SESSION['user_id']) && isset($global_settings['google_login_enabled']) && $global_settings['google_login_enabled'] == '1' && isset($global_settings['google_one_tap_enabled']) && $global_settings['google_one_tap_enabled'] == '1' && !empty($global_settings['google_client_id'])): ?>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <div id="g_id_onload"
         data-client_id="<?php echo htmlspecialchars($global_settings['google_client_id']); ?>"
         data-login_uri="<?php 
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = defined('SITE_URL') ? SITE_URL : '';
            echo (strpos($baseUrl, 'http') === 0) ? rtrim($baseUrl, '/') . '/auth/google_callback.php' : ($protocol . '://' . $host . rtrim($baseUrl, '/') . '/auth/google_callback.php');
         ?>"
         data-auto_prompt="true">
    </div>
    <?php endif; ?>

</head>
<body class="body-fade-in">

<!-- Page Load Animation Overlay -->
<div id="page-loader">
    <div class="loader-spinner"></div>
</div>

<!-- Announcement Top Bar -->
<?php if (!empty($global_settings['header_announcement'])): ?>
<div class="bg-primary text-center py-2" style="font-size: 0.9rem; font-weight: 500;" data-auto-contrast="true">
    <?php echo htmlspecialchars($global_settings['header_announcement']); ?>
</div>
<?php endif; ?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light navbar-custom sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold montserrat primary-blue fs-3" href="<?php echo (defined('SITE_URL') && !empty(SITE_URL) && strpos(SITE_URL, 'http') !== 0) ? rtrim(SITE_URL, '/') . '/index.php' : (rtrim(SITE_URL, '/') ?: '') . '/index.php'; ?>">
        <?php if (!isset($global_settings['show_header_logo']) || $global_settings['show_header_logo'] == '1'): ?>
            <img src="<?php echo ASSETS_URL; ?>/images/<?php echo htmlspecialchars($global_settings['header_logo_image'] ?? 'logo.jpg'); ?>" alt="Logo" style="height: <?php echo htmlspecialchars($global_settings['header_logo_height'] ?? '40'); ?>px; width: auto; object-fit: contain;">
        <?php else: ?>
            <span>Sagar Starter's</span>
        <?php endif; ?>
    </a>
    
    <div class="d-flex align-items-center order-lg-3">
      <?php if(isset($global_settings['enable_header_search']) && $global_settings['enable_header_search'] == '1'): ?>
          <!-- Desktop Search Bar -->
          <form action="<?php echo SITE_URL; ?>/shop.php" method="GET" class="me-3 d-none d-lg-flex">
              <div class="input-group input-group-sm" style="width: 200px;">
                  <input type="text" name="search" class="form-control border-0 bg-light rounded-pill-start ps-3" placeholder="Search..." style="border-radius: 20px 0 0 20px;">
                  <button class="btn btn-light border-0 bg-light text-muted px-3" type="submit" style="border-radius: 0 20px 20px 0;">
                      <i class="fas fa-search"></i>
                  </button>
              </div>
          </form>
          <!-- Mobile Search Toggle Icon -->
          <a class="text-reset me-3 d-lg-none" href="#" data-mdb-toggle="collapse" data-mdb-target="#mobileSearchForm" aria-expanded="false">
              <i class="fas fa-search fs-5"></i>
          </a>
      <?php endif; ?>

      <div id="header-cart-container">
          <a class="text-reset me-3 position-relative d-flex align-items-center" href="<?php echo SITE_URL; ?>/cart.php">
            <div class="position-relative">
                <i class="fas fa-shopping-cart fs-5"></i>
                <?php if($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </div>
            <?php if($cart_count > 0): ?>
                <span class="ms-2 fw-bold small text-danger d-none d-sm-inline-block"><?php echo $global_currency . number_format($cart_total, 2); ?></span>
            <?php endif; ?>
          </a>
      </div>

      <!-- Theme Toggle Button -->
      <button id="themeToggleBtn" class="btn btn-link text-reset p-2 me-1 rounded-circle border-0 shadow-none" aria-label="Toggle theme">
          <i class="fas fa-moon fs-5" id="themeIcon"></i>
      </button>
      <script>
          // Immediate icon fix to prevent flicker
          (function() {
              var theme = localStorage.getItem('theme') || 'light';
              var icon = document.getElementById('themeIcon');
              if (icon) {
                  if (theme === 'dark') {
                      icon.classList.remove('fa-moon');
                      icon.classList.add('fa-sun');
                  } else {
                      icon.classList.remove('fa-sun');
                      icon.classList.add('fa-moon');
                  }
              }
          })();
      </script>

      <div id="header-auth-container">
          <?php if(isset($_SESSION['user_id'])): ?>
              <div class="dropdown me-2">
                  <a class="dropdown-toggle d-flex align-items-center hidden-arrow text-reset" href="#" id="navbarDropdownMenuLink" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                      <?php if(isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])): ?>
                          <img src="<?php echo ASSETS_URL; ?>/images/<?php echo htmlspecialchars($_SESSION['profile_photo']); ?>" alt="Profile" class="rounded-circle object-fit-cover" style="width: 32px; height: 32px; border: 2px solid #007aff;">
                      <?php else: ?>
                          <i class="fas fa-user-circle fs-4 primary-blue"></i>
                      <?php endif; ?>
                  </a>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                      <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/profile.php">My Profile</a></li>
                      <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/user/orders.php">My Orders</a></li>
                      <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                          <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/index.php">Admin Panel</a></li>
                      <?php endif; ?>
                      <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/includes/auth.php?action=logout">Logout</a></li>
                  </ul>
              </div>
          <?php else: ?>
              <a href="<?php echo SITE_URL; ?>/user/login.php" class="btn btn-outline-primary btn-custom btn-sm me-2 d-none d-sm-inline-block">Login</a>
              <a href="<?php echo SITE_URL; ?>/user/login.php" class="text-reset me-3 d-sm-none"><i class="fas fa-sign-in-alt fs-5"></i></a>
          <?php endif; ?>
      </div>

      <button class="navbar-toggler p-0 border-0" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarContent"
        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
        <i class="fas fa-bars fs-4"></i>
      </button>
    </div>
    
    <div class="collapse navbar-collapse order-lg-2" id="navbarContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php
        // Fetch top-level menus for Header
        $top_menu_q = $conn->query("SELECT * FROM menus WHERE parent_id IS NULL AND menu_location IN ('header', 'both1', 'both2') ORDER BY order_index ASC");
        if($top_menu_q && $top_menu_q->num_rows > 0) {
            while($tm = $top_menu_q->fetch_assoc()) {
                // Check if has children
                $tm_id = $tm['id'];
                $child_menu_q = $conn->query("SELECT * FROM menus WHERE parent_id = $tm_id ORDER BY order_index ASC");
                $has_children = ($child_menu_q && $child_menu_q->num_rows > 0);
                
                // Prefix relative URLs with SITE_URL (leave http/https URLs as-is)
                // Use a clean join to avoid protocol-relative or absolute switches if possible
                $tm_url = $tm['url'];
                if (strpos($tm_url, 'http') !== 0) {
                    $tm_url = rtrim(SITE_URL, '/') . '/' . ltrim($tm_url, '/');
                }

                if($has_children) {
                    echo '<li class="nav-item dropdown">';
                    echo '<a class="nav-link dropdown-toggle fw-bold" href="'.$tm_url.'" id="navbarDropdown'.$tm_id.'" role="button" data-mdb-toggle="dropdown" aria-expanded="false">';
                    echo htmlspecialchars($tm['name']);
                    echo '</a>';
                    echo '<ul class="dropdown-menu" aria-labelledby="navbarDropdown'.$tm_id.'">';
                    while($cm = $child_menu_q->fetch_assoc()) {
                        $cm_url = (strpos($cm['url'], 'http') === 0) ? $cm['url'] : SITE_URL . '/' . ltrim($cm['url'], '/');
                        echo '<li><a class="dropdown-item" href="'.$cm_url.'">'.htmlspecialchars($cm['name']).'</a></li>';
                    }
                    echo '</ul></li>';
                } else {
                    echo '<li class="nav-item">';
                    echo '<a class="nav-link fw-bold" href="'.$tm_url.'">'.htmlspecialchars($tm['name']).'</a>';
                    echo '</li>';
                }
            }
        }
        ?>
      </ul>
    </div>

    <?php if(isset($global_settings['enable_header_search']) && $global_settings['enable_header_search'] == '1'): ?>
    <!-- Mobile Search Form (Full-width Collapse) -->
    <div class="collapse d-lg-none w-100" id="mobileSearchForm">
        <div class="py-3 px-1 mt-2">
            <form action="<?php echo SITE_URL; ?>/shop.php" method="GET">
                <div class="input-group rounded-pill overflow-hidden bg-light shadow-sm">
                    <input type="text" name="search" class="form-control border-0 bg-transparent ps-4 py-2" placeholder="Search for products...">
                    <button class="btn btn-light border-0 bg-transparent text-primary px-3" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
  </div>
</nav>

<!-- Auth & Cart UI Synchronization -->
<script>
/**
 * Global function to synchronize the Header UI with the actual server session.
 * This solves the "Home Page showing logged-out" issue caused by server caching.
 */
function refreshUserState() {
    // Determine the correct path to the auth check utility
    let siteUrl = '<?php echo SITE_URL; ?>'.replace(/\/+$/, '');
    
    // Safety check: If siteUrl contains a dot but no protocol (e.g. "www.example.com"), 
    // it's likely an absolute domain. Browsers treat "www.xxx" as relative without protocol.
    if (siteUrl && siteUrl.includes('.') && !siteUrl.includes('://')) {
        siteUrl = (window.location.protocol || 'https:') + '//' + siteUrl;
    }
    
    let checkPath = '';
    if (siteUrl.indexOf('http') === 0) {
        checkPath = siteUrl.replace(/\/+$/, '') + '/includes/ajax_auth_check.php';
    } else {
        // Fallback to absolute path from root
        checkPath = (siteUrl.startsWith('/') ? siteUrl : '/' + siteUrl) + '/includes/ajax_auth_check.php';
        checkPath = checkPath.replace(/\/+/g, '/');
    }

    // Try primary path
    fetch(checkPath, { credentials: 'same-origin' })
        .then(response => {
            if (!response.ok) {
                // If the primary path fails (possible .env mismatch), try root relative
                return fetch('/includes/ajax_auth_check.php', { credentials: 'same-origin' });
            }
            return response;
        })
        .then(response => {
            if (!response.ok) throw new Error('Auth utility not found');
            return response.json();
        })
        .then(data => {
            const authContainer = document.getElementById('header-auth-container');
            const cartContainer = document.getElementById('header-cart-container');
            
            if (!authContainer || !cartContainer) return;

            // 1. Sync Cart UI
            const formattedTotal = new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(data.cart_total);

            let cartHtml = `
                <a class="text-reset me-3 position-relative d-flex align-items-center" href="${data.site_url}/cart.php">
                    <div class="position-relative">
                        <i class="fas fa-shopping-cart fs-5"></i>
                        ${data.cart_count > 0 ? `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">${data.cart_count}</span>` : ''}
                    </div>
                    ${data.cart_count > 0 ? `<span class="ms-2 fw-bold small text-danger d-none d-sm-inline-block">${data.global_currency}${formattedTotal}</span>` : ''}
                </a>
            `;
            cartContainer.innerHTML = cartHtml;

            // 2. Sync Auth UI
            let authHtml = '';
            if (data.logged_in) {
                // User is logged in
                authHtml = `
                    <div class="dropdown me-2">
                        <a class="dropdown-toggle d-flex align-items-center hidden-arrow text-reset" href="#" id="navbarDropdownMenuLink" role="button" data-mdb-toggle="dropdown" aria-expanded="false">
                            ${data.profile_photo ? 
                                `<img src="${data.assets_url}/images/${data.profile_photo}" alt="Profile" class="rounded-circle object-fit-cover" style="width: 32px; height: 32px; border: 2px solid #007aff;">` : 
                                '<i class="fas fa-user-circle fs-4 primary-blue"></i>'}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="${data.site_url}/user/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="${data.site_url}/user/orders.php">My Orders</a></li>
                            ${data.role === 'admin' ? `<li><a class="dropdown-item" href="${data.site_url}/admin/index.php">Admin Panel</a></li>` : ''}
                            <li><a class="dropdown-item" href="${data.site_url}/includes/auth.php?action=logout">Logout</a></li>
                        </ul>
                    </div>
                `;
            } else {
                // User is a guest
                authHtml = `
                    <a href="${data.site_url}/user/login.php" class="btn btn-outline-primary btn-custom btn-sm me-2 d-none d-sm-inline-block">Login</a>
                    <a href="${data.site_url}/user/login.php" class="text-reset me-3 d-sm-none"><i class="fas fa-sign-in-alt fs-5"></i></a>
                `;
            }
            authContainer.innerHTML = authHtml;

            // 3. Re-initialize MDB Components (Dropdowns) to ensure they work after HTML swap
            if (typeof mdb !== 'undefined' && mdb.Dropdown) {
                document.querySelectorAll('#header-auth-container [data-mdb-toggle="dropdown"]').forEach(el => {
                    new mdb.Dropdown(el);
                });
            }
        })
        .catch(err => {
            console.warn('UI Sync: Falling back to server-side state.');
        });
}

// Global initialization
document.addEventListener('DOMContentLoaded', refreshUserState);
</script>
