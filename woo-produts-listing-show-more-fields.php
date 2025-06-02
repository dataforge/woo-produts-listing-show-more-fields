<?php
/*
Plugin Name: Woo Products Listing Show More Fields
Plugin URI:        https://github.com/dataforge/woo-produts-listing-show-more-fields
Description: Adds a custom column to the WooCommerce product list table in the admin to display variation SKUs.
Version: 1.0.0
Author: Dataforge
GitHub Plugin URI: https://github.com/dataforge/woo-produts-listing-show-more-fields
Text Domain: woo-produts-listing-show-more-fields
*/

/**
 * Add settings submenu under WooCommerce
 */
function woo_produts_listing_show_more_fields_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Product List Extra Fields', 'woo-produts-listing-show-more-fields'),
        __('Product List Extra Fields', 'woo-produts-listing-show-more-fields'),
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

    if (isset($_POST['woo_produts_listing_show_more_fields_save'])) {
        check_admin_referer('woo_produts_listing_show_more_fields_save');
        $selected = isset($_POST['fields']) ? array_map('sanitize_text_field', $_POST['fields']) : array();
        update_option('woo_produts_listing_show_more_fields_fields', $selected);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'woo-produts-listing-show-more-fields') . '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Product List Extra Fields Settings', 'woo-produts-listing-show-more-fields'); ?></h1>
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
    </div>
    <?php
}

// Add custom column header to product list table in admin
function woo_produts_listing_show_more_fields_column_header($columns) {
    $fields = get_option('woo_produts_listing_show_more_fields_fields', array('sku'));
    foreach ($fields as $field) {
        switch ($field) {
            case 'sku':
                $columns['woo_plsmf_sku'] = __('SKU', 'woo-produts-listing-show-more-fields');
                break;
            case 'price':
                $columns['woo_plsmf_price'] = __('Price', 'woo-produts-listing-show-more-fields');
                break;
            case 'stock':
                $columns['woo_plsmf_stock'] = __('Stock', 'woo-produts-listing-show-more-fields');
                break;
            case 'type':
                $columns['woo_plsmf_type'] = __('Product Type', 'woo-produts-listing-show-more-fields');
                break;
        }
    }
    return $columns;
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
                $variation_skus = array();
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $variation_skus[] = $variation->get_sku();
                    }
                }
                if (!empty($variation_skus)) {
                    $sku_list = implode(', ', $variation_skus);
                    $display = mb_strimwidth($sku_list, 0, 50, '...');
                    echo '<span title="' . esc_attr($sku_list) . '">' . esc_html($display) . '</span>';
                } else {
                    echo 'N/A';
                }
            } else {
                $sku = $product->get_sku();
                echo $sku ? esc_html($sku) : 'N/A';
            }
            break;
        case 'woo_plsmf_price':
            echo $product->get_price_html() ? $product->get_price_html() : 'N/A';
            break;
        case 'woo_plsmf_stock':
            if ($product->is_in_stock()) {
                echo esc_html($product->get_stock_quantity() !== null ? $product->get_stock_quantity() : __('In stock', 'woo-produts-listing-show-more-fields'));
            } else {
                echo esc_html__('Out of stock', 'woo-produts-listing-show-more-fields');
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
