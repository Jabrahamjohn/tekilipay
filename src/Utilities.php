<?php

/**
 * @package Lipa Na Mpesa for WooCommerce
 * @subpackage WooCommerce Mpesa Gateway
 * @author AJ & JOSH
 * @since 0.18.01
 */

namespace Osen\Woocommerce;

class Utilities
{
    public function __construct()
    {
        add_action('manage_shop_order_posts_custom_column', array($this, 'wc_mpesa_order_list_column_content'), 10, 1);
        add_filter('woocommerce_email_attachments', array($this, 'woocommerce_emails_attach_downloadables'), 10, 3);
        add_filter('manage_edit-shop_order_columns', array($this, 'wc_mpesa_order_column'), 100);
        add_filter('woocommerce_account_orders_columns', array($this, 'add_transaction_id_column'), 10, 1);
        add_action('woocommerce_my_account_my_orders_column_receipt', array($this, 'add_transaction_id_column_row'));
        add_action('woocommerce_order_details_after_order_table_items', array($this, 'show_transaction_id'), 10, 1);
    }

    /**
     * @param \WC_Order $order
     */
    public function show_transaction_id($order)
    {
        if ($order->get_payment_method() === 'mpesa') {
            echo '<tfoot>
                <tr>
                    <th scope="row">' . __('Transaction ID', 'woocommerce') . ':</th>
                    <td><span class="woocommerce-Price-amount amount">' . $order->get_transaction_id() . '</td>
                </tr>
                <tr>
                    <th scope="row">' . __('Paying Phone', 'woocommerce') . ':</th>
                    <td>' . $order->get_meta('mpesa_phone', 'woocommerce') . '</td>
                </tr>
            </tfoot>';
        }
    }

    /**
     * Add a custom column before "actions" last column
     *
     * @param array $columns
     */
    public function add_transaction_id_column($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $name) {
            $new_columns[$key] = $name;

            // add transaction ID after order total column
            if ('order-total' === $key) {
                $new_columns['receipt'] = __('Transaction ID', 'woocommerce');
            }
        }

        return $new_columns;
    }

    /**
     * @param \WC_Order $order
     */
    public function add_transaction_id_column_row($order)
    {
        // Example with a custom field
        if ($value = $order->get_transaction_id()) {
            echo esc_html($value);
        }
    }

    /**
     * Add a custom column before "actions" last column
     *
     * @param array $columns
     */
    public function wc_mpesa_order_column($columns)
    {
        $ordered_columns = array();

        foreach ($columns as $key => $column) {
            $ordered_columns[$key] = $column;

            if ('order_date' === $key) {
                $ordered_columns['transaction_id'] = __('Receipt', 'woocommerce');
            }
        }

        return $ordered_columns;
    }

    /**
     * Column
     *
     * @param string $column
     */
    public function wc_mpesa_order_list_column_content($column)
    {
        global $post;
        $the_order = wc_get_order($post->ID);

        if ('transaction_id' === $column && $the_order->get_payment_method() === 'mpesa') {
            echo $the_order->get_transaction_id() ?? 'N/A';
        }
    }

    public function woocommerce_emails_attach_downloadables($attachments, $status, \WC_Order $order)
    {
        if (is_object($order) || isset($status) || !empty($order)) {
            if (method_exists($order, 'has_downloadable_item')) {
                if ($order->has_downloadable_item()) {

                    $allowed_statuses = array('customer_invoice', 'customer_completed_order');
                    if (isset($status) && in_array($status, $allowed_statuses)) {
                        foreach ($order->get_items() as $item) {
                            foreach ($order->get_item_downloads($item) as $download) {
                                $attachments[] = str_replace(content_url(), WP_CONTENT_DIR, $download['file']);
                            }
                        }
                    }
                }
            }
        }

        return $attachments;
    }
}
