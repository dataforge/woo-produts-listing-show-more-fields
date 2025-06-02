<?php
/*
Plugin Name: Woo Products Listing Show More Fields
Description: Adds a custom column to the WooCommerce product list table in the admin to display variation SKUs.
Version: 1.0.0
Author: Dataforge
Text Domain: woo-produts-listing-show-more-fields
*/

// Add custom column header to product list table in admin
function woo_produts_listing_show_more_fields_column_header($columns) {
    $columns['product_variations_sku'] = 'Variation SKUs';
    return $columns;
}
add_filter('manage_edit-product_columns', 'woo_produts_listing_show_more_fields_column_header', 20);

// Add custom column content to product list table in admin
function woo_produts_listing_show_more_fields_column_content($column, $product_id) {
    if ($column === 'product_variations_sku') {
        $product = wc_get_product($product_id);

        if ($product && $product->is_type('variable')) {
            $variation_skus = array();
            $variations = $product->get_children();

            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $variation_skus[] = $variation->get_sku();
                }
            }

            if (!empty($variation_skus)) {
                echo implode(', ', $variation_skus);
            } else {
                echo 'N/A';
            }
        } else {
            echo 'N/A';
        }
    }
}
add_action('manage_product_posts_custom_column', 'woo_produts_listing_show_more_fields_column_content', 20, 2);
