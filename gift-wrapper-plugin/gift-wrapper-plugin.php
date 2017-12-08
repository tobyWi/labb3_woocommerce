<?php
/**
 * Plugin Name: Gift Wrapper Plugin
 */

// Add gift wrapping option to checkout page.
add_action('woocommerce_after_order_notes', function() {
    echo '<div id="gift_wrap">';
    echo '<h3>Gift wrapping</h3>';

    woocommerce_form_field( 'gift_wrap_option', array(
        'type'          => 'checkbox',
        'class'         => array('form-row-wide'),
        'label'         => __('Gift wrap')
    ));

    woocommerce_form_field( 'gift_wrap_message', array(
        'type'          => 'text',
        'class'         => array('form-row-wide'),
        'label'         => __('Message to recipient'),
        'placeholder'   => __('Gift brought to you by *')
    ));

    echo '</div>';
});

// Validate the options.
add_action('woocommerce_checkout_process', function() {
    if (isset($_POST['gift_wrap_option']) && empty($_POST['gift_wrap_message'])) {
        wc_add_notice(__('You need to enter a gift wrap message.'), 'error');
    }
});

// Persist the options to the database.
add_action('woocommerce_checkout_update_order_meta', function($id) {
    if (isset($_POST['gift_wrap_option'])) {
        update_post_meta($id, 'gift_wrap_message', sanitize_text_field($_POST['gift_wrap_message']));
    }
});

// Show the gift wrapping options on the order page.
add_action('woocommerce_admin_order_data_after_billing_address', function() {
    echo "Gift wrap: ";
    echo get_post_meta(get_the_ID(), 'gift_wrap_option', true) ? 'Yes' : 'No';
    echo "<br/>Gift wrapping message: " . get_post_meta(get_the_id(), 'gift_wrap_message', true);
});
