<form method="POST" action="">
    <input type="submit" name="clear_cart" value="Clear Cart" class="button button-danger"/>
</form>
<?php
// Function to clear the cart
function clear_cart_programmatically() {
    // Check if WooCommerce is active and session is initialized
    if (class_exists('WooCommerce') && WC()->cart) {
        if (isset($_POST['clear_cart'])) {
            WC()->cart->empty_cart(); // Clear the cart
            WC()->session->destroy_session(); // Clear session data including cookies
            echo '<div class="notice notice-success"><p>Cart has been cleared successfully!</p></div>';
        }
    }
}
clear_cart_programmatically();