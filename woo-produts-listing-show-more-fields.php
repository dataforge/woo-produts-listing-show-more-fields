<?php
/*
Plugin Name: Woo Products Listing Show More Fields
Plugin URI:        https://github.com/dataforge/woo-produts-listing-show-more-fields
Description: Adds a custom column to the WooCommerce product list table in the admin to display various fields.
Version: 1.11
Author: Dataforge
GitHub Plugin URI: https://github.com/dataforge/woo-produts-listing-show-more-fields
Text Domain: woo-produts-listing-show-more-fields
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add settings submenu under WooCommerce
 */
function woo_produts_listing_show_more_fields_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Woo Products Listing Show More Fields', // Page title
        'Woo Products Listing Show More Fields', // Menu title
        'manage_woocommerce',
        'woo-produts-listing-show-more-fields',
        'woo_produts_listing_show_more_fields_settings_page'
    );
}
add_action('admin_menu', 'woo_produts_listing_show_more_fields_admin_menu');

/**
 * Settings page callback
 */
function woo_produts_listing_show_more_fields_settings_page() {
    // Available fields
    $fields = array(
        'sku'   => __('SKU', 'woo-produts-listing-show-more-fields'),
        'price' => __('Price', 'woo-produts-listing-show-more-fields'),
        'stock' => __('Stock', 'woo-produts-listing-show-more-fields'),
        'type'  => __('Product Type', 'woo-produts-listing-show-more-fields'),
    );
    $selected = get_option('woo_produts_listing_show_more_fields_fields', array('sku'));

    // Handle save settings
    if (isset($_POST['woo_produts_listing_show_more_fields_save'])) {
        check_admin_referer('woo_produts_listing_show_more_fields_save');
        $selected = isset($_POST['fields']) ? array_map('sanitize_text_field', $_POST['fields']) : array();
        update_option('woo_produts_listing_show_more_fields_fields', $selected);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'woo-produts-listing-show-more-fields') . '</p></div>';
    }

    // Handle "Check for Plugin Updates" button
    if (isset($_POST['woo_produts_listing_show_more_fields_check_update']) && check_admin_referer('woo_produts_listing_show_more_fields_check_update_nonce', 'woo_produts_listing_show_more_fields_check_update_nonce')) {
        // Simulate the cron event for plugin update check
        do_action('wp_update_plugins');
        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }
        // Remove the update_plugins transient to force a check
        delete_site_transient('update_plugins');
        // Call the update check directly as well
        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }
        // Get update info
        $plugin_file = plugin_basename(__FILE__);
        $update_plugins = get_site_transient('update_plugins');
        $update_msg = '';
        if (isset($update_plugins->response) && isset($update_plugins->response[$plugin_file])) {
            $new_version = $update_plugins->response[$plugin_file]->new_version;
            $update_msg = '<div class="updated"><p>' . esc_html__('Update available: version ', 'woo-produts-listing-show-more-fields') . esc_html($new_version) . '.</p></div>';
        } else {
            $update_msg = '<div class="updated"><p>' . esc_html__('No update available for this plugin.', 'woo-produts-listing-show-more-fields') . '</p></div>';
        }
        echo $update_msg;
    }
    ?>
    <div class="wrap">
        <h1>Woo Products Listing Show More Fields</h1>
        <form method="post">
            <?php wp_nonce_field('woo_produts_listing_show_more_fields_save'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Select fields to display in product list:', 'woo-produts-listing-show-more-fields'); ?></th>
                    <td>
                        <?php foreach ($fields as $key => $label): ?>
                            <label>
                                <input type="checkbox" name="fields[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $selected)); ?> />
                                <?php echo esc_html($label); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Save Settings', 'woo-produts-listing-show-more-fields'), 'primary', 'woo_produts_listing_show_more_fields_save'); ?>
        </form>
        <hr>
        <form method="post" style="margin-top:2em;">
            <?php wp_nonce_field('woo_produts_listing_show_more_fields_check_update_nonce', 'woo_produts_listing_show_more_fields_check_update_nonce'); ?>
            <input type="hidden" name="woo_produts_listing_show_more_fields_check_update" value="1">
            <?php submit_button('Check for Plugin Updates', 'secondary'); ?>
        </form>
    </div>
    <?php
}


/**
 * Add variant sub-row under variable products in the product list
 */
add_action('manage_product_posts_custom_column', function($column, $product_id) {
    if ($column !== 'name') return;
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) return;

    $variations = $product->get_children();
    if (empty($variations)) return;

    // Output a visible div with variant data, to be moved by JS
    echo '<div class="woo-plsmf-variant-row" data-product-id="' . esc_attr($product_id) . '">';
    echo '<table class="woo-plsmf-variant-table" style="width:100%; background:#f9f9f9; border:1px solid #e5e5e5; margin:8px 0; border-radius:4px; font-size:13px;">';
    // Header row
    echo '<thead><tr>';
    echo '<th>' . esc_html__('Image', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Name', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('SKU', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Stock', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Price', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Categories', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Tags', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Brands', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Featured', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '<th>' . esc_html__('Date', 'woo-produts-listing-show-more-fields') . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($variations as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) continue;
        // Image
        $image_id = $variation->get_image_id();
        $image_html = $image_id ? wp_get_attachment_image($image_id, array(32,32)) : '';
        // Name
        $name = $variation->get_name();
        // SKU
        $sku = $variation->get_sku();
        // Stock
        $stock = $variation->is_in_stock() ? ($variation->get_stock_quantity() !== null ? $variation->get_stock_quantity() : __('In stock', 'woo-produts-listing-show-more-fields')) : __('Out of stock', 'woo-produts-listing-show-more-fields');
        // Price
        $price = $variation->get_price_html() ? $variation->get_price_html() : '<span style="color:#a00;">No price set</span>';
        // Categories
        $categories = get_the_term_list($variation_id, 'product_cat', '', ', ', '') ?: '';
        // Tags
        $tags = get_the_term_list($variation_id, 'product_tag', '', ', ', '') ?: '';
        // Brands (if using WooCommerce Brands)
        $brands = function_exists('get_the_term_list') ? get_the_term_list($variation_id, 'product_brand', '', ', ', '') : '';
        // Featured
        $featured = $variation->get_meta('_featured') === 'yes' ? __('Yes', 'woo-produts-listing-show-more-fields') : __('No', 'woo-produts-listing-show-more-fields');
        // Date
        $date = get_post_field('post_date', $variation_id);

        echo '<tr>';
        echo '<td>' . $image_html . '</td>';
        echo '<td>' . esc_html($name) . '</td>';
        echo '<td>' . esc_html($sku) . '</td>';
        echo '<td>' . esc_html($stock) . '</td>';
        echo '<td>' . $price . '</td>';
        echo '<td>' . $categories . '</td>';
        echo '<td>' . $tags . '</td>';
        echo '<td>' . $brands . '</td>';
        echo '<td>' . $featured . '</td>';
        echo '<td>' . esc_html($date) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}, 100, 2);

/**
 * Add admin CSS and JS for variant sub-rows
 */
function woo_produts_listing_show_more_fields_admin_css() {
    $screen = get_current_screen();
    if (isset($screen->id) && $screen->id === 'edit-product') {
        echo '<style>
            .woo-plsmf-variant-row-tr td {
                background: #f9f9f9 !important;
                border-top: none !important;
                padding: 0 !important;
            }
            .woo-plsmf-variant-grid {
                overflow-x: auto;
                display: grid !important;
                grid-template-columns: repeat(9, auto);
                gap: 4px;
                background: #f9f9f9;
                border: 1px solid #e5e5e5;
                padding: 8px;
                border-radius: 4px;
            }
            .woo-plsmf-variant-grid > div {
                padding: 2px 6px;
                border-bottom: 1px solid #eee;
                font-size: 13px;
                white-space: nowrap;
            }
            .woo-plsmf-variant-grid > div:nth-child(-n+9) {
                font-weight: bold;
                background: #f1f1f1;
                border-bottom: 2px solid #ddd;
            }
        </style>';
        // Add JS to move the variant row after the parent row
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var rows = document.querySelectorAll(".woo-plsmf-variant-row");
            rows.forEach(function(row) {
                var productId = row.getAttribute("data-product-id");
                var parentTr = document.querySelector("tr[id=\'post-" + productId + "\']");
                if (parentTr) {
                    var colCount = parentTr.children.length;
                    var newTr = document.createElement("tr");
                    newTr.className = "woo-plsmf-variant-row-tr";
                    var td = document.createElement("td");
                    td.colSpan = colCount;
                    td.appendChild(row);
                    row.style.display = "block";
                    newTr.appendChild(td);
                    parentTr.parentNode.insertBefore(newTr, parentTr.nextSibling);
                }
            });
        });
        </script>';
    }
}
add_action('admin_head', 'woo_produts_listing_show_more_fields_admin_css');
