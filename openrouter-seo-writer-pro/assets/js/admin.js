jQuery(document).ready(function($) {
    // 1. Test Connection Logic
    $('#or-seo-test-connection').on('click', function() {
        const apiKey = $('input[name="or_seo_api_key"]').val();
        const $status = $('#or-seo-connection-status');
        const $select = $('#or_seo_default_model');

        $status.text('Testing...').css('color', 'blue');

        $.ajax({
            url: or_seo_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'test_openrouter_connection',
                api_key: apiKey,
                nonce: or_seo_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $status.text('Connection Successful!').css('color', 'green');
                    $select.empty();
                    response.data.forEach(function(model) {
                        $select.append(`<option value="${model}">${model}</option>`);
                    });
                } else {
                    $status.text('Error: ' + response.data).css('color', 'red');
                }
            },
            error: function() {
                $status.text('Request failed.').css('color', 'red');
            }
        });
    });

    // 2. Stage 1: Generate Outline
    $('#or-seo-generate-outline').on('click', function() {
        const keyword = $('#or_seo_primary_keyword').val();
        const $spinner = $('#or-seo-spinner');
        const $status = $('#or-seo-status-message');
        const $outlineContainer = $('#or-seo-outline-container');
        const $outlineTextarea = $('#or_seo_outline');

        if (!keyword) {
            alert('Please enter a primary keyword.');
            return;
        }

        $spinner.addClass('is-active');
        $status.text('Generating outline...');

        $.ajax({
            url: or_seo_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_outline',
                keyword: keyword,
                nonce: or_seo_vars.nonce
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                if (response.success) {
                    $status.text('Outline generated! Edit it below.');
                    $outlineTextarea.val(response.data);
                    $outlineContainer.show();
                } else {
                    $status.text('Error: ' + response.data);
                }
            }
        });
    });

    // 3. Stage 2: Generate Full Draft
    $('#or-seo-generate-draft').on('click', function() {
        const keyword = $('#or_seo_primary_keyword').val();
        const outline = $('#or_seo_outline').val();
        const postId = $('#post_ID').val();
        const $spinner = $('#or-seo-spinner');
        const $status = $('#or-seo-status-message');

        if (!outline) {
            alert('Outline is required.');
            return;
        }

        $spinner.addClass('is-active');
        $status.text('Drafting article (this may take a minute)...');

        $.ajax({
            url: or_seo_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_draft',
                keyword: keyword,
                outline: outline,
                post_id: postId,
                nonce: or_seo_vars.nonce
            },
            success: function(response) {
                $spinner.removeClass('is-active');
                if (response.success) {
                    $status.text('Article generated successfully!');

                    // Use Gutenberg API to insert content
                    if (window.wp && wp.data && wp.data.dispatch('core/editor')) {
                        wp.data.dispatch('core/editor').editPost({
                            content: response.data.clean_content
                        });
                        alert('Content inserted into the editor!');
                    } else {
                        // Fallback for classic editor (if any) or just alert
                        alert('Article generated. Please check SEO Meta fields.');
                    }
                } else {
                    $status.text('Error: ' + response.data);
                }
            }
        });
    });
});
