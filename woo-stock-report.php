<?php

/**
 * Plugin Name: Stock Report for WooCommerce
 * Description: See stock on a per-variation level on the admin dashboard Products page without having to click into the product.
 * Version: 1.0.0
 * License: MIT
 * Author: Dado Agency
 * Author URI: https://dadoagency.com
 * Requires PHP: 5.3.0
 * WC requires at least: 3.2
 * WC tested up to: 4.3
 */

// Just exit if directly accessed
if (!defined('ABSPATH')) exit;

// Just exit if WooCommerce isn't installed and enabled
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) exit;


// Unset the default stock column and add our own
add_filter('manage_edit-product_columns', 'srfwc_customise_columns');
function srfwc_customise_columns($columns) {
  $new_columns = (is_array($columns)) ? $columns : array();

  // Remove the default stock column
  unset($new_columns['is_in_stock']);

  // Add our two new stock columns.
  // We slice the columns so we can stick our variation
  // columns in a convenient position (near where the default Stock column sits)
  return array_slice($new_columns, 0, 3, true)
    + array('var_stock' => 'Variation Stock')
    + array('var_stock_summary' => 'Stock')
    + array_slice($new_columns, 3, NULL, true);
}

// Populate the new stock columns
add_action('manage_posts_custom_column', 'srfwc_populate_variation_stock');
function srfwc_populate_variation_stock($column_name) {
  // TODO: Make this configurable
  $LOW_STOCK_THRESHOLD = 4;

  // Populate the 'var_stock' column
  if ($column_name == 'var_stock') {
    $productId = get_the_ID();
    $product   = wc_get_product($productId);

    if ($product->get_type() == 'variable') {
      foreach ($product->get_children() as $variation_id) {
        $variation = new WC_Product_Variation($variation_id);

        $variation_name = implode(", ", str_replace('-', ' ', $variation->get_attributes()));

        $stock = $variation->get_stock_quantity();

        if ($stock < $LOW_STOCK_THRESHOLD and $stock > 0) {
          // Low stock
          echo srfwc_get_stock_detail_html('orange', $variation_name . ': ' . $stock . ' in stock');
        } elseif ($stock == 0) {
          // Out of stock
          echo srfwc_get_stock_detail_html('red', $variation_name . ': out of stock');
        } elseif ($stock < 0) {
          // On backorder
          echo srfwc_get_stock_detail_html('purple', $variation_name . ': ' . abs( $stock ) . ' on backorder');
        } else {
          // In stock
          echo srfwc_get_stock_detail_html('green', $variation_name . ': ' . $stock . ' in stock');
        }
      }
    }

    // TODO: Make sure this works for grouped, bundled, etc products
    else {
      $stock = $product->get_stock_quantity();
      if ($stock < $LOW_STOCK_THRESHOLD and $stock > 0) {
        // Low stock
        echo srfwc_get_stock_detail_html('orange', $stock . ' in stock');
      } elseif ($stock == 0) {
        // Out of stock
        echo srfwc_get_stock_detail_html('red', 'Out of stock');
      } elseif ($stock < 0) {
        // On backorder
        echo srfwc_get_stock_detail_html('purple', abs( $stock ) . ' on backorder');
      } else {
        // In stock
        echo srfwc_get_stock_detail_html('green', $stock . ' in stock');
      }
    }
  }

  // Populate the 'var_stock_summary' column
  if ($column_name == 'var_stock_summary') {
    $productId = get_the_ID();
    $product   = wc_get_product($productId);

    if ($product->get_type() == 'variable') {
      $number_low_stock    = 0;
      $number_out_of_stock = 0;
      $number_backorder    = 0;

      // Count the number of low stock and out of stock variations
      foreach ($product->get_children() as $variation_id) {
        $variation = new WC_Product_Variation($variation_id);
        $variation_stock = $variation->get_stock_quantity();

        if ($variation_stock < $LOW_STOCK_THRESHOLD and $variation_stock > 0) {
          $number_low_stock += 1;
        } elseif ($variation_stock == 0) {
          $number_out_of_stock += 1;
        } elseif ($variation_stock < 0) {
          $number_backorder += 1;
        }
      }

      // Stock summary priorities:
      // If there are any products on backorder, that is the most important thing to display, so we choose to display that.
      // If there are no products on backorder, but there are products out of stock, then we display that.
      // If there are neither backorder nor out of stock, but there are products low in stock, then we display that.
      // Otherwise, everything must be in stock.

      if ($number_backorder > 0) {
        echo srfwc_get_stock_summary_html('purple', 'On backorder | ' . $number_backorder . ($number_backorder == 1 ? ' variation' : ' variations'));
      } elseif ($number_out_of_stock > 0) {
        echo srfwc_get_stock_summary_html('red', 'Out of stock | ' . $number_out_of_stock . ($number_out_of_stock == 1 ? ' variation' : ' variations'));
      } elseif ($number_low_stock > 0) {
        echo srfwc_get_stock_summary_html('orange', 'Low stock | ' . $number_low_stock . ($number_low_stock == 1 ? ' variation' : ' variations'));
      } else {
        echo srfwc_get_stock_summary_html('green', 'In stock | all variations');
      }
    }
    
    // TODO: Make sure this works for grouped, bundled, etc products
    else {
      $stock = $product->get_stock_quantity();
      if ($stock > 0 and $stock < $LOW_STOCK_THRESHOLD) {
        echo srfwc_get_stock_summary_html('orange', 'Low stock');
      } elseif ($stock == 0) {
        echo srfwc_get_stock_summary_html('red', 'Out of stock');
      } elseif ($stock < 0) {
        echo srfwc_get_stock_summary_html('purple', 'On backorder');
      } else {
        echo srfwc_get_stock_summary_html('green', 'In stock');
      }
    }
  }
}

function srfwc_get_stock_detail_html($colour, $message) {
  return '<div style="display:flex; flex-wrap:nowrap; align-items:center;"><div style="border-radius:50px; width:10px; height:10px; background-color:' . $colour . '; margin-right:5px;"></div>' . $message . '</div>';
}

function srfwc_get_stock_summary_html($colour, $message) {
  return '<div style="color:' . $colour . ';">' . $message . '</div>';
}
