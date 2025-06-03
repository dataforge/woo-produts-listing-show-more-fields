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

// Add custom column header to product list table in admin
function woo_produts_listing_show_more_fields_column_header($columns) {
    $fields = get_option('woo_produts_listing_show_more_fields_fields', array('sku'));
    $custom_columns = array();
    foreach ($fields as $field) {
        switch ($field) {
            case 'sku':
                $custom_columns['woo_plsmf_sku'] = __('SKU (Variants)', 'woo-produts-listing-show-more-fields');
                break;
            case 'price':
                $custom_columns['woo_plsmf_price'] = __('Price', 'woo-produts-listing-show-more-fields');
                break;
            case 'stock':
                $custom_columns['woo_plsmf_stock'] = __('Stock', 'woo-produts-listing-show-more-fields');
                break;
            case 'type':
                $custom_columns['woo_plsmf_type'] = __('Product Type', 'woo-produts-listing-show-more-fields');
                break;
        }
    }
    // Insert custom columns immediately after the checkbox column for visibility
    $new_columns = array();
    $inserted = false;
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if (!$inserted && $key === 'cb') {
            foreach ($custom_columns as $ckey => $cval) {
                $new_columns[$ckey] = $cval;
            }
            $inserted = true;
        }
    }
    // If 'cb' not found, append custom columns at the end
    if (!$inserted) {
        $new_columns = array_merge($columns, $custom_columns);
    }
    return $new_columns;
}
add_filter('manage_edit-product_columns', 'woo_produts_listing_show_more_fields_column_header', 20);

/**
 * Add custom column content to product list table in admin
 */
function woo_produts_listing_show_more_fields_column_content($column, $product_id) {
    $product = wc_get_product($product_id);
    if (!$product) {
        echo 'N/A';
        return;
    }

    switch ($column) {
        case 'woo_plsmf_sku':
            if ($product->is_type('variable')) {
                // Show parent SKU
                $parent_sku = $product->get_sku();
                echo $parent_sku ? '<strong>' . esc_html($parent_sku) . '</strong>' : '<span style="color:#a00;">No SKU set</span>';

                // Get all variations
                $variations = $product->get_children();
                if (!empty($variations)) {
                    // Output variant data as a CSS grid for proper formatting
                    echo '<div class="woo-plsmf-variant-grid" style="margin-top:8px; display:grid; grid-template-columns: repeat(9, auto); gap:4px; background:#f9f9f9; border:1px solid #e5e5e5; padding:8px; border-radius:4px;">';
                    // Header row
                    echo '<div style="font-weight:bold;">' . esc_html__('Image', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('SKU', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Stock', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Price', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Categories', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Tags', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Brands', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Featured', 'woo-produts-listing-show-more-fields') . '</div>';
                    echo '<div style="font-weight:bold;">' . esc_html__('Date', 'woo-produts-listing-show-more-fields') . '</div>';
                    foreach ($variations as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if (!$variation) continue;
                        // Image
                        $image_id = $variation->get_image_id();
                        $image_html = $image_id ? wp_get_attachment_image($image_id, array(32,32)) : '';
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

                        echo '<div>' . $image_html . '</div>';
                        echo '<div>' . esc_html($sku) . '</div>';
                        echo '<div>' . esc_html($stock) . '</div>';
                        echo '<div>' . $price . '</div>';
                        echo '<div>' . $categories . '</div>';
                        echo '<div>' . $tags . '</div>';
                        echo '<div>' . $brands . '</div>';
                        echo '<div>' . $featured . '</div>';
                        echo '<div>' . esc_html($date) . '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div><span style="color:#a00;">No variations found</span></div>';
                }
            } else {
                $sku = $product->get_sku();
                echo $sku ? esc_html($sku) : '<span style="color:#a00;">No SKU set</span>';
            }
            break;
        case 'woo_plsmf_price':
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                $prices = array();
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation && $variation->get_price() !== '') {
                        $prices[] = floatval($variation->get_price());
                    }
                }
                if (!empty($prices)) {
                    $min = min($prices);
                    $max = max($prices);
                    if ($min === $max) {
                        echo wc_price($min);
                    } else {
                        echo wc_price($min) . ' - ' . wc_price($max);
                    }
                } else {
                    echo '<span style="color:#a00;">No prices set for variations</span>';
                }
            } else {
                echo $product->get_price_html() ? $product->get_price_html() : '<span style="color:#a00;">No price set</span>';
            }
            break;
        case 'woo_plsmf_stock':
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                $total_stock = 0;
                $in_stock_count = 0;
                $out_stock_count = 0;
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        if ($variation->is_in_stock()) {
                            $in_stock_count++;
                            $qty = $variation->get_stock_quantity();
                            if ($qty !== null) {
                                $total_stock += $qty;
                            }
                        } else {
                            $out_stock_count++;
                        }
                    }
                }
                if ($in_stock_count && $out_stock_count) {
                    echo '<span style="color:#e67e22;">Mixed stock status</span>';
                } elseif ($in_stock_count) {
                    echo esc_html($total_stock) . ' (' . esc_html__('In stock', 'woo-produts-listing-show-more-fields') . ')';
                } elseif ($out_stock_count) {
                    echo esc_html__('Out of stock', 'woo-produts-listing-show-more-fields');
                } else {
                    echo '<span style="color:#a00;">No stock info for variations</span>';
                }
            } else {
                if ($product->is_in_stock()) {
                    echo esc_html($product->get_stock_quantity() !== null ? $product->get_stock_quantity() : __('In stock', 'woo-produts-listing-show-more-fields'));
                } else {
                    echo esc_html__('Out of stock', 'woo-produts-listing-show-more-fields');
                }
            }
            break;
        case 'woo_plsmf_type':
            echo esc_html(wc_get_product_type($product_id));
            break;
    }
}
add_action('manage_product_posts_custom_column', 'woo_produts_listing_show_more_fields_column_content', 20, 2);

// Add admin CSS to style the custom column for better table formatting
function woo_produts_listing_show_more_fields_admin_css() {
    $screen = get_current_screen();
    if (isset($screen->id) && $screen->id === 'edit-product') {
        echo '<style>
            .wp-list-table .column-product_variations_sku {
                max-width: 200px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        </style>';
    }
}
add_action('admin_head', 'woo_produts_listing_show_more_fields_admin_css');
