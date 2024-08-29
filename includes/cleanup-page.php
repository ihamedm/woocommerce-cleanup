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
        <form id="wc_cleanup_form" method="post" action="">
            <?php wp_nonce_field('wc_cleanup_nonce', 'wc_cleanup_nonce_field'); ?>
            <input type="checkbox" name="wc_cleanup_items[]" value="products" checked> محصولات<br>
            <input type="checkbox" name="wc_cleanup_items[]" value="attributes" checked> ویژگی‌ها<br>
            <input type="checkbox" name="wc_cleanup_items[]" value="categories" checked> دسته‌بندی‌ها (دسته پیش‌فرض حذف نمی‌شود)<br>
            <input type="checkbox" name="wc_cleanup_items[]" value="media" checked> حذف همه رسانه‌ها<br><br>
            <input type="hidden" name="wc_cleanup_action" value="cleanup">
            <input type="submit" class="button button-primary" value="شروع پاکسازی">
        </form>



        <hr>
            <button id="publish-drafted-products" class="button button-primary">۱۰۰ محصول پابلیش کن</button>


        <hr>
        <?php include_once 'clean-cart-session.php';?>
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


add_action('wp_ajax_wc_cleanup_item', 'wc_cleanup_item');
function wc_cleanup_item() {
    check_ajax_referer('wc_cleanup_nonce', 'nonce');

    $item = sanitize_text_field($_POST['item']);

    try {
        switch ($item) {
            case 'products':
                remove_all_products();
                break;
            case 'attributes':
                remove_all_product_attributes();
                break;
            case 'categories':
                remove_all_product_categories();
                break;
            case 'media':
                remove_all_media();
                break;
            default:
                throw new Exception('مورد نامعتبر است');
        }

        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }
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

function remove_all_options(){
    delete_option('awca_proceed_products');
    delete_option('awca_total_products');
    delete_option('awca_product_save_lock');
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

function my_plugin_publish_drafted_products() {
    check_ajax_referer('wc_cleanup_nonce', 'nonce');

    $args = array(
        'post_type' => 'product',
        'post_status' => 'draft',
        'posts_per_page' => 100
    );

    $drafts = get_posts($args);

    if (empty($drafts)) {
        wp_send_json_error(array('message' => 'No drafted products found.'));
    }

    foreach ($drafts as $draft) {
        wp_update_post(array(
            'ID' => $draft->ID,
            'post_status' => 'publish'
        ));
    }

    wp_send_json_success(array('message' => '50 drafted products have been published.'));
}
add_action('wp_ajax_publish_drafted_products', 'my_plugin_publish_drafted_products');


?>
