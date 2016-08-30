<?php
/**
 * Plugin Name: WP clutter remover
 * Description: Removes clutter from HTML head and pingback/trackback support.
 * Version: 1.3
 * Author: Aleksi
 * License: GPLv3
 */
if (!defined('ABSPATH')) {
    exit;
}

// X-Pingback -header
add_filter('wp_headers', function ($headers) {
    if (isset($headers['X-Pingback'])) {
        unset($headers['X-Pingback']);
    }

    return $headers;
});

// Trackback -rewrite rules
add_filter('rewrite_rules_array', function ($rules) {
    foreach ($rules as $rule => $rewrite) {
        if (preg_match('/trackback\/\?\$$/i', $rule)) {
            unset($rules[$rule]);
        }
    }

    return $rules;
});

// bloginfo('pingback_url')
add_filter('bloginfo_url', function ($output, $show) {
    if ($show == 'pingback_url') {
        $output = '';
    }

    return $output;
}, 10, 2);

// pingback hooks
add_filter('pre_update_default_ping_status', '__return_false');
add_filter('pre_option_default_ping_status', '__return_zero');
add_filter('pre_update_default_pingback_flag', '__return_false');
add_filter('pre_option_default_pingback_flag', '__return_zero');

// XMLRPC call
add_action('xmlrpc_call', function ($action) {
    if ('pingback.ping' == $action) {
        wp_die(__('403 Permission Denied'), __('Permission Denied'), ['response' => 403]);
    }
});

// Clean up tags from head
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'parent_post_rel_link', 10, 0);
remove_action('wp_head', 'start_post_rel_link', 10, 0);
remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
remove_action('template_redirect', 'wp_shortlink_header', 11);
add_filter('show_recent_comments_widget_style', '__return_false');

// Disable emojis
remove_action('admin_print_styles', 'print_emoji_styles');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji');
add_filter('tiny_mce_plugins', function ($plugins) {
    if (is_array($plugins)) {
        return array_diff($plugins, ['wpemoji']);
    } else {
        return [];
    }
});

// Disable REST API
add_filter('rest_enabled', '__return_false');
add_filter('rest_jsonp_enabled', '__return_false');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('template_redirect', 'rest_output_link_header', 11, 0);

// Remove some oEmbed features - they're slow!
add_filter('embed_oembed_discover', '__return_false');
remove_action('wp_head', 'wp_oembed_add_host_js');
remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
add_filter('tiny_mce_plugins', function($plugins) {
    return array_diff($plugins, array('wpembed'));
});

// Need to flush when rules are changed
register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
