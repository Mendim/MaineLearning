<?php

// Set the default values to display in the View editor.
add_filter('wpv_view_settings', 'wpv_pager_defaults', 10, 2);
function wpv_pager_defaults($view_settings, $view_id=null) {
    $defaults = array(
        'posts_per_page' => 10,
        'pagination' => array(
            0 => 'disable',
            'mode' => 'paged',
            'preload_images' => 1,
            'cache_pages' => 1,
            'preload_pages' => 1,
            'spinner' => 'default',
            'spinner_image' => WPV_URL . '/res/img/ajax-loader.gif',
            'spinner_image_uploaded' => '',
            'callback_next' => '',
            'page_selector_control_type' => 'drop_down',
        ),
        'ajax_pagination' => array(
            0 => 'disable',
            'style' => 'fade',
        ),
        'rollover' => array(
            'posts_per_page' => 4,
            'speed' => 5,
            'effect' => 'fade',
            'preload_images' => 1,
            'include_page_selector' => 0,
        ),
    );
    $view_settings = wpv_parse_args_recursive($view_settings, $defaults);

    if ($view_settings['pagination']['spinner'] == 'uploaded') {
        $view_settings['pagination']['spinner_image'] = $view_settings['pagination']['spinner_image_uploaded'];
    }

    return $view_settings;
}

add_filter('wpv_view_settings_save', 'wpv_pager_defaults_save', 10, 1);
function wpv_pager_defaults_save($view_settings) {
    // we need to set 0 for the checkboxes that aren't checked and are missing for the $_POST.
    
    $defaults = array(
        'pagination' => array(
            'preload_images' => 0,
            'cache_pages' => 0,
            'preload_pages' => 0,
        ),
        'rollover' => array(
            'preload_images' => 0,
        ),
    );
    $view_settings = wpv_parse_args_recursive($view_settings, $defaults);

    return $view_settings;
}

/**
 * Views-Shortcode: wpv-pagination
 *
 * Description: Display the pagination controls that are within the shortcode.
 * The pagination controls will only be displayed if there are multiple
 * pages to display
 *
 * Parameters:
 * This has no parameters.
 *
 */

add_shortcode('wpv-pagination', 'wpv_pagination_shortcode');

function wpv_pagination_shortcode($atts, $value) {

    extract(
            shortcode_atts(array(), $atts)
    );

    global $WP_Views;
    $post_query = $WP_Views->get_query();

    if ($post_query && $post_query->max_num_pages > 1.0) {
        // output the pagination.
        return wpv_do_shortcode($value);
    } else {
        // only 1 page so we don't need any pagination controls.
        return '';
    }
}

/**
 * Views-Shortcode: wpv-pager-num-page
 *
 * Description: Display the maximum number of pages found by the Views Query.
 *
 * Parameters:
 * This has no parameters.
 *
 */
add_shortcode('wpv-pager-num-page', 'wpv_pager_num_page_shortcode');

function wpv_pager_num_page_shortcode($atts) {
    extract(
            shortcode_atts(array(), $atts)
    );

    global $WP_Views;
    $post_query = $WP_Views->get_query();

    return sprintf('%1.0f', $post_query->max_num_pages);
}

/**
 * Views-Shortcode: wpv-pager-prev-page
 *
 * Description: Display a "Previous" link to move to the previous page.
 * eg. [wpv-pager-prev-page]Previous[/wpv-pager-prev-page]
 *
 * Parameters:
 * This has no parameters.
 *
 */
add_shortcode('wpv-pager-prev-page', 'wpv_pager_prev_page_shortcode');

function wpv_pager_prev_page_shortcode($atts, $value) {
    extract(
            shortcode_atts(array(), $atts)
    );

    global $WP_Views;
    $post_query = $WP_Views->get_query();

    $page = intval($post_query->query_vars['paged']);

    if ($page > 1) {

        $page--;

        $value = wpv_do_shortcode($value);

        // TODO remove
//        return '<a href="#" onclick="return wpv_pager_click_' . $WP_Views->get_view_count() . '(\'' . $page. '\')">' . $value . '</a>';
        $view_settings = $WP_Views->get_view_settings();
        $ajax = $view_settings['ajax_pagination'][0] == 'enable' ? 'true' : 'false';
        $effect = isset($view_settings['ajax_pagination']['style']) ? $view_settings['ajax_pagination']['style'] : 'fade';
        $cache_pages = $view_settings['pagination']['cache_pages'];
        $preload_pages = $view_settings['pagination']['preload_pages'];
        $spinner = $view_settings['pagination']['spinner'];
        $spinner_image = $view_settings['pagination']['spinner_image'];
        $callback_next = $view_settings['pagination']['callback_next'];
        return '<a href="#" class="wpv-filter-previous-link" onclick="return wpv_pagination_replace_view(' . $WP_Views->get_view_count() . ',' . $page . ', ' . $ajax . ', \'' . $effect . '\', ' . $post_query->max_num_pages . ', ' . $cache_pages . ', ' . $preload_pages . ', \'' . $spinner . '\', \'' . $spinner_image . '\', \'' . $callback_next . '\', false)">' . $value . '</a>';
    } else {
        return '';
    }
}

/**
 * Views-Shortcode: wpv-pager-next-page
 *
 * Description: Display a "Next" link to move to the next page.
 * eg. [wpv-pager-next-page]Next[/wpv-pager-next-page]
 *
 * Parameters:
 * This has no parameters.
 *
 */
add_shortcode('wpv-pager-next-page', 'wpv_pager_next_page_shortcode');

function wpv_pager_next_page_shortcode($atts, $value) {
    extract(
            shortcode_atts(array(), $atts)
    );

    global $WP_Views;
    $post_query = $WP_Views->get_query();

    $page = intval($post_query->query_vars['paged']);

    if ($page < $post_query->max_num_pages) {

        $page++;

        $value = wpv_do_shortcode($value);

        // TODO remove
//        return '<a href="#" onclick="return wpv_pager_click_' . $WP_Views->get_view_count() . '(\'' . $page. '\')">' . $value . '</a>';
        $view_settings = $WP_Views->get_view_settings();
        $ajax = $view_settings['ajax_pagination'][0] == 'enable' ? 'true' : 'false';
        $effect = isset($view_settings['ajax_pagination']['style']) ? $view_settings['ajax_pagination']['style'] : 'fade';
        $cache_pages = $view_settings['pagination']['cache_pages'];
        $preload_pages = $view_settings['pagination']['preload_pages'];
        $spinner = $view_settings['pagination']['spinner'];
        $spinner_image = $view_settings['pagination']['spinner_image'];
        $callback_next = $view_settings['pagination']['callback_next'];
        return '<a href="#" class="wpv-filter-next-link" onclick="return wpv_pagination_replace_view(' . $WP_Views->get_view_count() . ',' . $page . ', ' . $ajax . ', \'' . $effect . '\',' . $post_query->max_num_pages . ', ' . $cache_pages . ', ' . $preload_pages . ', \'' . $spinner . '\', \'' . $spinner_image . '\', \'' . $callback_next . '\', false)">' . $value . '</a>';
    } else {
        return '';
    }
}

/**
 * Views-Shortcode: wpv-pager-current-page
 *
 * Description: Display the current page number. It can be displayed as a number
 * or as a drop-down list to select another page.
 *
 * Parameters:
 * 'style' => leave empty to display a number.
 * 'style' => 'drop_down' to display a selector to select another page.
 * 'stile' => 'link' to display a series of links to each page
 *
 */
add_shortcode('wpv-pager-current-page', 'wpv_pager_current_page_shortcode');

function wpv_pager_current_page_shortcode($atts) {
    extract(
            shortcode_atts(array(), $atts)
    );

    global $WP_Views;
    $post_query = $WP_Views->get_query();

    $page = intval($post_query->query_vars['paged']);

    if (isset($atts['style'])) {
        
        $view_settings = $WP_Views->get_view_settings();
        $cache_pages = $view_settings['pagination']['cache_pages'];
        $preload_pages = $view_settings['pagination']['preload_pages'];
        $spinner = $view_settings['pagination']['spinner'];
        $spinner_image = $view_settings['pagination']['spinner_image'];
        $callback_next = $view_settings['pagination']['callback_next'];
        
        if ($view_settings['pagination']['mode'] == 'paged') {
            $ajax = $view_settings['ajax_pagination'][0] == 'enable' ? 'true' : 'false';
            $effect = isset($view_settings['ajax_pagination']['style']) ? $view_settings['ajax_pagination']['style'] : 'fade';
        }
        
        if ($view_settings['pagination']['mode'] == 'rollover') {
            $ajax = 'true';
            $effect = $view_settings['rollover']['effect'];
            // convert rollover to slide effect if the user clicks on a page.
            
            if ($effect == 'slideleft' || $effect == 'slideright') {
                $effect = 'slideh';
            }
            if ($effect == 'slideup' || $effect == 'slidedown') {
                $effect = 'slidev';
            }
        }

        switch($atts['style']) {
            case 'drop_down':
                $out = '';
                $out .= '<select id="wpv-page-selector-' . $WP_Views->get_view_count() . '" onchange="wpv_pagination_replace_view(' . $WP_Views->get_view_count() . ', jQuery(this).val(), ' . $ajax . ', \'' . $effect . '\',' . $post_query->max_num_pages . ', ' . $cache_pages . ', ' . $preload_pages . ', \'' . $spinner . '\', \'' . $spinner_image . '\', \'' . $callback_next . '\', true);">' . "\n";
        
                $max_page = intval($post_query->max_num_pages);
                for ($i = 1; $i < $max_page + 1; $i++) {
                    $is_selected = $i == $page ? ' selected="selected"' : '';
                    $page_number = apply_filters('wpv_pagination_page_number', $i);
                    $out .= '<option value="' . $i . '" ' . $is_selected . '>' . $page_number . "</option>\n";
                }
                $out .= "</select>\n";
        
                return $out;
                    
            case 'link':
                $page_count = intval($post_query->max_num_pages);
                // output a series of links to each page.
                
                $out = '<div class="wpv_pagination_links">';
                $out .= '<ul class="wpv_pagination_dots" style="list-style-position:outside; margin: 0; list-style-type: none;">';
                
                for ($i = 1; $i < $page_count + 1; $i++) {
                    $page_title = sprintf(__('Page %s', 'wpv-views'), $i);
                    $page_title = apply_filters('wpv_pagination_page_title', $page_title, $i);
                    $page_number = apply_filters('wpv_pagination_page_number', $i);
                    $link = '<a title="' . $page_title . '" href="#" class="wpv-filter-previous-link" onclick="wpv_pagination_replace_view_links(' . $WP_Views->get_view_count() . ',' . $i . ', ' . $ajax . ', \'' . $effect . '\', ' . $page_count . ', ' . $cache_pages . ', ' . $preload_pages . ', \'' . $spinner . '\', \'' . $spinner_image . '\', \'' . $callback_next . '\', true); return false;">' . $page_number . '</a>';
                    $link_id = 'wpv-page-link-' . $WP_Views->get_view_count() . '-' . $i;
                    if ($i == $page) {
                        $out .= '<li style="list-style-position:outside; list-style-type: none; float: left; margin-right: 5px;" id="' . $link_id . '" class="wpv_page_current">' . $link . '</li>';
                    } else {
                        $out .= '<li style="list-style-position:outside; list-style-type: none; float: left; margin-right: 5px;" id="' . $link_id . '">' . $link . '</li>';
                    }
                }
                $out .= '</ul>';
                $out .= '</div>';
                $out .= '<br />';
                return $out;
                
                

        }
    } else {
        // show the page number.
        return sprintf('%d', $page);
    }
}

function wpv_pagination_js() {
    static $js_rendered = false;
    if ($js_rendered == false) {

        wp_nonce_field('wpv_get_page_nonce', 'wpv_get_page_nonce');

        ?>
        <script type="text/javascript">
        
            var wpv_admin_ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";

                        
        </script>
        <?php
        $js_rendered = true;
    }
}

function wpv_pagination_rollover_shortcode() {
    global $WP_Views;
    $post_query = $WP_Views->get_query();
    $view_settings = $WP_Views->get_view_settings();
    $view_settings['rollover']['count'] = $post_query->max_num_pages;
    wpv_pagination_rollover_add_slide($WP_Views->get_view_count(),
            $view_settings);
    add_action('wp_footer', 'wpv_pagination_rollover_js');
}

function wpv_pagination_rollover_add_slide($id, $settings = array()) {
    static $rollovers = array();
    if ($id == 'get') {
        return $rollovers;
    }
    $rollovers[$id] = $settings;
}

function wpv_pagination_rollover_js() {
    $rollovers = wpv_pagination_rollover_add_slide('get');
    if (!empty($rollovers)) {
        global $WP_Views;
        $out = '';
        wpv_pagination_js();

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function(){
        <?php
        foreach ($rollovers as $id => $rollover) {
            $out .= 'jQuery("#wpv-view-layout-' . $id . '").wpvRollover({id: ' . $id
                    . ', effect: "' . $rollover['rollover']['effect']
                    . '", speed: ' . $rollover['rollover']['speed']
                    . ', page: 1, count: ' . $rollover['rollover']['count']
                    . ', cache_pages:' . $rollover['pagination']['cache_pages']
                    . ', preload_pages:' . $rollover['pagination']['preload_pages']
                    . ', spinner:"' . $rollover['pagination']['spinner'] . '"'
                    . ', spinner_image:"' . $rollover['pagination']['spinner_image'] . '"'
                    . ', callback_next:"' . $rollover['pagination']['callback_next'] . '"'
                    . '});' . "\r\n";
        }
        echo $out;

        ?>
                });
                        
        </script>

        <?php
    }
}

// add a filter so we can set the correct language in WPML during pagination
add_filter('icl_current_language', 'wpv_ajax_pagination_lang');

function wpv_ajax_pagination_lang($lang) {
    if (isset($_POST['action']) && $_POST['action'] == 'wpv_get_page' && isset($_POST['lang'])) {
        $lang = $_POST['lang'];
    }

    return $lang;
}

// Gets the new page for a view.

function wpv_ajax_get_page() {
    if (wp_verify_nonce($_POST['wpv_nonce'], 'wpv_get_page_nonce')) {
        // Fix a problem with WPML using cookie language when DOING_AJAX is set.
        $cookie_lang = null;
        if (isset($_COOKIE['_icl_current_language']) && isset($_POST['lang'])) {
            $cookie_lang = $_COOKIE['_icl_current_language'];
            $_COOKIE['_icl_current_language'] = $_POST['lang'];
        }

        $post_id = $_POST['post_id'];

        $_GET['wpv_paged'] = $_POST['page'];
        $_GET['wpv_view_number'] = $_POST['view_number'];

        global $post, $authordata, $id;

        $post = get_post($post_id);
        $authordata = new WP_User($post->post_author);
        $id = $post->ID;

        echo wpv_do_shortcode($post->post_content);

        if ($cookie_lang) {
            // reset language cookie.
            $_COOKIE['_icl_current_language'] = $cookie_lang;
        }
    }
    die();
}

