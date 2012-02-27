<?php

// check for an import
$wpv_theme_import = '';
$wpv_theme_import_xml = '';
if (file_exists(dirname(__FILE__) . '/settings.php')) {
    
    $wpv_theme_import = dirname(__FILE__) . '/settings.php';
    $wpv_theme_import_xml = dirname(__FILE__) . '/settings.xml';
}

if(defined('WPV_VERSION')) {
 
    // the plugin version is present.
    
    return; 
}

// THEME VERSION

define('WPV_VERSION', '0.9.3.1');
define('WPV_PATH', dirname(__FILE__));
define('WPV_PATH_EMBEDDED', dirname(__FILE__));
define('WPV_FOLDER', basename(WPV_PATH));
define('WPV_URL', get_stylesheet_directory_uri() . '/' . WPV_FOLDER);
define('WPV_URL_EMBEDDED', WPV_URL);

if (!defined('EDITOR_ADDON_RELPATH')) {
    define('EDITOR_ADDON_RELPATH', WPV_URL . '/common/visual-editor');
}

require WPV_PATH_EMBEDDED . '/inc/wpv-shortcodes.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-layout-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-meta-html-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-pagination-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-category-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-custom-field-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-order-by-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-post-types-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-search-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-status-embedded.php';
require WPV_PATH_EMBEDDED . '/inc/wpv-filter-parent-embedded.php';

require WPV_PATH_EMBEDDED . '/inc/wpv-widgets.php';

require WPV_PATH_EMBEDDED . '/inc/wpv.class.php';

require WPV_PATH_EMBEDDED . '/common/WPML/wpml-string-shortcode.php';

if (is_admin()) {
    require WPV_PATH_EMBEDDED . '/inc/wpv-import-export-embedded.php';
}

$WP_Views = new WP_Views();

require WPV_PATH . '/inc/views-templates/functions-templates.php';
require WPV_PATH . '/inc/views-templates/wpv-template.class.php';

$WPV_templates = new WPV_template();

function wpv_get_affiliate_url() {
    global $wpv_theme_import;
    
    $affiliate_url = '';
    if ($wpv_theme_import != '') {
		include $wpv_theme_import;
        
        if (isset($affiliate_id) && isset($affiliate_key)) {
            $affiliate_url = '?aid=' . $affiliate_id . '&affiliate_key=' . $affiliate_key;
        }
        
    }
    
    return $affiliate_url;

}
function wpv_promote_views_admin() {
    global $wpdb, $wpv_theme_import;
    
    $view_template_available = $wpdb->get_results("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type='view-template' AND post_status in ('publish')");
    $view_available = $wpdb->get_results("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type='view' AND post_status in ('publish')");
    
    $affiliate_url = wpv_get_affiliate_url();

    ?>
    
    <?php
        if (sizeof($view_available) > 0) {
            echo '<p>' . __('Views are lists and groups of content, like a listing or showcase page. On your theme the following Views are defined:', 'wpv-views') . "</p>\n";
            echo "<ul style='margin-left:20px;'>\n";
            foreach($view_available as $view) {
                echo "<li>" . $view->post_name . "</li>\n";
            }
            echo "</ul>\n";
        }
        if (sizeof($view_template_available) > 0) {
            echo '<p>' . __('View Templates are applied to pages to create different layouts. On your theme the following View Templates are defined:', 'wpv-views') . "</p>\n";
            echo "<ul style='margin-left:20px;'>\n";
            foreach($view_template_available as $template) {
                echo "<li>" . $template->post_name . "</li>\n";
            }
            echo "</ul>\n";
        }
    ?>
    <p><?php echo sprintf(__('If you want to edit these or create your own you can purchase the full version of <strong>Views</strong> from <a href="%s">%s</a>', 'wpv-views'),
                            'http://wp-types.com/' . $affiliate_url,
                            'http://wp-types.com/'); ?></p>
    
    <?php
    
}

if (!is_admin()) {
    wp_enqueue_script('jquery');
}
