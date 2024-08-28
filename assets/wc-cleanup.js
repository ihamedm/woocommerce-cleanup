jQuery(document).ready(function($) {
    $('#wc_cleanup_form').on('submit', function(e) {
        e.preventDefault();

        if (!confirm('آیا مطمئن هستید که می‌خواهید موارد انتخاب شده را حذف کنید؟ این عملیات قابل بازگشت نیست.')) {
            return;
        }

        let cleanupItems = [];
        $('input[name="wc_cleanup_items[]"]:checked').each(function() {
            cleanupItems.push($(this).val());
        });

        cleanupNextItem(cleanupItems);
    });

    function cleanupNextItem(items) {
        if (items.length === 0) {
            alert('پاکسازی ووکامرس با موفقیت انجام شد.');
            return;
        }

        let item = items.shift();

        $.post(wc_cleanup_vars.ajax_url, {
            action: 'wc_cleanup_item',
            item: item,
            nonce: wc_cleanup_vars.nonce
        }, function(response) {
            if (response.success) {
                cleanupNextItem(items);
            } else {
                alert('خطا در پاکسازی: ' + response.data);
            }
        });
    }



    $('#publish-drafted-products').on('click', function(e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to publish 100 drafted products?')) {
            return;
        }

        $.ajax({
            url: wc_cleanup_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'publish_drafted_products',
                nonce: wc_cleanup_vars.nonce
            },
            success: function(response) {
                alert(response.data.message);
            },
            error: function(xhr, status, error) {
                alert('An error occurred: ' + error);
            }
        });
    });


});
