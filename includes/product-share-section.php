<?php
// product-share-section.php
// This file assumes $conn and $product are already available in the scope (from product.php)

$share_query = "SELECT * FROM product_share_settings WHERE id = 1";
$share_res = $conn->query($share_query);
$share_settings = $share_res && $share_res->num_rows > 0 ? $share_res->fetch_assoc() : [
    'whatsapp_status' => 1,
    'facebook_status' => 1,
    'telegram_status' => 1,
    'copylink_status' => 1,
    'section_title' => 'Share Product',
    'icon_style' => 'rounded'
];

// Determine if any share option is active
$any_active = $share_settings['whatsapp_status'] || $share_settings['facebook_status'] || $share_settings['telegram_status'] || $share_settings['copylink_status'];

if ($any_active): 
    $product_name = htmlspecialchars($product['name']);
    
    // Determine button class based on icon_style setting
    $btn_class = 'share-btn';
    if ($share_settings['icon_style'] === 'circle') {
        $btn_class .= ' share-btn-circle';
    } elseif ($share_settings['icon_style'] === 'rounded') {
        $btn_class .= ' share-btn-rounded';
    } elseif ($share_settings['icon_style'] === 'square') {
        $btn_class .= ' share-btn-square';
    }
?>
<!-- Product Share Section -->
<div class="product-share-wrapper mt-4 pt-3 border-top">
    <h6 class="fw-bold mb-3 text-secondary"><?php echo htmlspecialchars($share_settings['section_title']); ?></h6>
    <div class="d-flex align-items-center gap-2 flex-wrap" id="productShareContainer" data-title="<?php echo $product_name; ?>">
        
        <?php if ($share_settings['whatsapp_status']): ?>
            <button type="button" class="<?php echo $btn_class; ?> share-whatsapp" aria-label="Share on WhatsApp" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </button>
        <?php endif; ?>

        <?php if ($share_settings['facebook_status']): ?>
            <button type="button" class="<?php echo $btn_class; ?> share-facebook" aria-label="Share on Facebook" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </button>
        <?php endif; ?>

        <?php if ($share_settings['telegram_status']): ?>
            <button type="button" class="<?php echo $btn_class; ?> share-telegram" aria-label="Share on Telegram" title="Telegram">
                <i class="fab fa-telegram-plane"></i>
            </button>
        <?php endif; ?>

        <?php if ($share_settings['copylink_status']): ?>
            <div class="position-relative">
                <button type="button" class="<?php echo $btn_class; ?> share-copylink" aria-label="Copy Link" title="Copy Link">
                    <i class="fas fa-link"></i>
                </button>
                <div class="copy-tooltip d-none">Link Copied!</div>
            </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>
