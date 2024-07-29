<?php

// Create admin menu
add_action('admin_menu', 'wc_cleanup_admin_menu');
function wc_cleanup_admin_menu() {
    add_menu_page('دستیار تست انار', 'دستیار تست انار', 'manage_options', 'wc-cleanup', 'wc_cleanup_page', 'dashicons-trash', 80);
}

// Admin page content
function wc_cleanup_page() {
    ?>
    <div class="wrap">
        <h1>پاکسازی ووکامرس</h1>
        <p>گزینه‌های مورد نظر خود را برای حذف انتخاب کنید و دکمه زیر را برای انجام عملیات کلیک کنید. این عملیات غیرقابل بازگشت است.</p>
        <form method="post" action="">
            <?php wp_nonce_field('wc_cleanup_nonce', 'wc_cleanup_nonce_field'); ?>
            <input type="checkbox" name="wc_cleanup_items[]" value="products" checked> محصولات<br>
            <input type="checkbox" name="wc_cleanup_items[]" value="attributes" checked> ویژگی‌ها<br>
            <input type="checkbox" name="wc_cleanup_items[]" value="categories" checked> دسته‌بندی‌ها (دسته پیش‌فرض حذف نمی‌شود)<br>
            <input type="checkbox" name="wc_cleanup_items[]" value="media"> حذف همه رسانه‌ها<br><br>
            <input type="hidden" name="wc_cleanup_action" value="cleanup">
            <input type="submit" class="button button-primary" value="شروع پاکسازی" onclick="return confirm('آیا مطمئن هستید که می‌خواهید موارد انتخاب شده را حذف کنید؟ این عملیات قابل بازگشت نیست.');">
        </form>

        <br>
        <hr>
        <h1>انتشار همه محصولات پیش‌نویس</h1>
        <form method="post" action="">
            <?php wp_nonce_field('wc_publish_drafts_nonce', 'wc_publish_drafts_nonce_field'); ?>
            <input type="hidden" name="wc_publish_drafts_action" value="publish_drafts">
            <input type="submit" class="button button-secondary" value="انتشار همه محصولات پیش‌نویس" onclick="return confirm('آیا مطمئن هستید که می‌خواهید همه محصولات پیش‌نویس را منتشر کنید؟');">
        </form>
    </div>
    <?php
}

// Handle form submission
add_action('admin_init', 'wc_cleanup_handle_action');

function wc_cleanup_handle_action() {
    if (isset($_POST['wc_cleanup_action']) && $_POST['wc_cleanup_action'] === 'cleanup') {
        if (!isset($_POST['wc_cleanup_nonce_field']) || !wp_verify_nonce($_POST['wc_cleanup_nonce_field'], 'wc_cleanup_nonce')) {
            return;
        }
        if (isset($_POST['wc_cleanup_items']) && is_array($_POST['wc_cleanup_items'])) {
            $cleanup_items = $_POST['wc_cleanup_items'];
            wc_cleanup_run($cleanup_items);
        }
    }
}

function wc_cleanup_run($cleanup_items) {
    if (in_array('products', $cleanup_items)) {
        remove_all_products();
    }
    if (in_array('attributes', $cleanup_items)) {
        remove_all_product_attributes();
    }
    if (in_array('categories', $cleanup_items)) {
        remove_all_product_categories();
    }
    if (in_array('media', $cleanup_items)) {
        remove_all_media();
    }
    add_action('admin_notices', 'wc_cleanup_success_notice');
}

function wc_cleanup_success_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('پاکسازی ووکامرس با موفقیت انجام شد.', 'wc-cleanup'); ?></p>
    </div>
    <?php
}

// Function to remove all products
function remove_all_products() {
    $args = array(
        'post_type' => 'product',
        'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    $products = get_posts($args);
    foreach ($products as $product_id) {
        wp_delete_post($product_id, true);
    }
}

// Function to remove all product attributes
function remove_all_product_attributes() {
    global $wpdb;
    $attribute_taxonomies = wc_get_attribute_taxonomies();
    foreach ($attribute_taxonomies as $attribute) {
        wc_delete_attribute($attribute->attribute_id);
    }
    $attribute_taxonomies = $wpdb->get_col("SELECT DISTINCT taxonomy FROM {$wpdb->term_taxonomy} WHERE taxonomy LIKE 'pa_%'");
    foreach ($attribute_taxonomies as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }
}

// Function to remove all product categories, excluding the default category
function remove_all_product_categories() {
    $default_category_id = get_option('default_product_cat');
    $terms = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ));
    foreach ($terms as $term) {
        if ($term->term_id != $default_category_id) {
            wp_delete_term($term->term_id, 'product_cat');
        }
    }
}

// Function to remove all media
function remove_all_media() {
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    $media = get_posts($args);
    foreach ($media as $media_id) {
        wp_delete_attachment($media_id, true);
    }
}

// Add a button to the admin toolbar
add_action('admin_bar_menu', 'wc_cleanup_toolbar_button', 100);

function wc_cleanup_toolbar_button($wp_admin_bar) {
    if (current_user_can('manage_options')) {
        $args = array(
            'id'    => 'wc_cleanup_button',
            'title' => 'پاکسازی ووکامرس',
            'href'  => admin_url('admin.php?page=wc-cleanup'),
            'meta'  => array('class' => 'wc-cleanup-toolbar-button'),
        );
        $wp_admin_bar->add_node($args);
    }
}

// Handle form submission for publishing drafted products
add_action('admin_init', 'wc_publish_drafts_handle_action');

function wc_publish_drafts_handle_action() {
    if (isset($_POST['wc_publish_drafts_action']) && $_POST['wc_publish_drafts_action'] === 'publish_drafts') {
        if (!isset($_POST['wc_publish_drafts_nonce_field']) || !wp_verify_nonce($_POST['wc_publish_drafts_nonce_field'], 'wc_publish_drafts_nonce')) {
            return;
        }
        publish_all_drafted_products();
    }
}

function publish_all_drafted_products() {
    $args = array(
        'post_type' => 'product',
        'post_status' => 'draft',
        'posts_per_page' => -1,
        'fields' => 'ids',
    );
    $drafts = get_posts($args);
    foreach ($drafts as $product_id) {
        $product = array(
            'ID' => $product_id,
            'post_status' => 'publish',
        );
        wp_update_post($product);
    }
    add_action('admin_notices', 'wc_publish_drafts_success_notice');
}

function wc_publish_drafts_success_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('همه محصولات پیش‌نویس با موفقیت منتشر شدند.', 'wc-cleanup'); ?></p>
    </div>
    <?php
}
?>
