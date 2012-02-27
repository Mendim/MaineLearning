<?php

require WPV_PATH_EMBEDDED . '/inc/wpv.class.php';

class WP_Views_plugin extends WP_Views {
    
    function init() {
        add_filter( 'custom_menu_order', array($this, 'enable_custom_menu_order' ));
        add_filter( 'menu_order', array($this, 'custom_menu_order' ));

        global $wp_version;
        if (version_compare($wp_version, '3.3', '>=')) {
            add_action('admin_head-edit.php', array($this, 'admin_add_help'));
            add_action('admin_head-post.php', array($this, 'admin_add_help'));
            add_action('admin_head-post-new.php', array($this, 'admin_add_help'));
        }
        parent::init();        
    }

    function enable_custom_menu_order($menu_ord) {
        return true;
    }
    
    function custom_menu_order( $menu_ord ) {
        $types_index = array_search('wpcf', $menu_ord);
        $views_index = array_search('edit.php?post_type=view', $menu_ord);
        
        if ($types_index !== false && $views_index !== false) {
            // put the types menu above the views menu.
            unset($menu_ord[$types_index]);
            $menu_ord = array_values($menu_ord);
            array_splice($menu_ord, $views_index, 0, 'wpcf');
        }
        
        return $menu_ord;
    }
    
    function is_embedded() {
        return false;
    }
    
    function wpv_register_type_view() 
    {
      $labels = array(
        'name' => _x('Views', 'post type general name'),
        'singular_name' => _x('View', 'post type singular name'),
        'add_new' => _x('Add New View', 'book'),
        'add_new_item' => __('Add New View'),
        'edit_item' => __('Edit View'),
        'new_item' => __('New View'),
        'view_item' => __('View Views'),
        'search_items' => __('Search Views'),
        'not_found' =>  __('No views found'),
        'not_found_in_trash' => __('No views found in Trash'), 
        'parent_item_colon' => '',
        'menu_name' => 'Views'
    
      );
      $args = array(
        'labels' => $labels,
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true, 
        'show_in_menu' => true, 
        'query_var' => false,
        'rewrite' => false,
        'capability_type' => 'post',
        'can_export' => false,
        'has_archive' => false, 
        'hierarchical' => false,
        'menu_position' => null,
        'menu_icon' => WPV_URL .'/res/img/views-18.png',
        'supports' => array('title','editor','author')
      ); 
      register_post_type('view',$args);
    }

    function admin_menu(){

        // Add the view template menus.        
        add_submenu_page('edit.php?post_type=view', __('View Templates', 'wpv-views'), __('View Templates', 'wpv-views'), 'manage_options', 'edit.php?post_type=view-template');
        add_submenu_page('edit.php?post_type=view', __('New View Template', 'wpv-views'), __('New View Template', 'wpv-views'), 'manage_options', 'post-new.php?post_type=view-template');

        // add settings menu.        
        add_submenu_page('edit.php?post_type=view', __('Settings', 'wpv-views'), __('Settings', 'wpv-views'), 'manage_options', 'views-settings',
                    array($this, 'views_settings_admin'));
        
        // Add import export menu.
        if (function_exists('wpv_admin_menu_import_export')) {
            add_submenu_page('edit.php?post_type=view', __('Import/Export', 'wpv-views'), __('Import/Export', 'wpv-views'), 'manage_options', 'views-import-export',
                    'wpv_admin_menu_import_export');

        }
        
        add_submenu_page('edit.php?post_type=view', __('Views Subscription','wp-wiews'), __('Views Subscription','wp-wiews'), 'manage_options', WPV_FOLDER . '/menu/main.php', null, WPV_URL . '/res/img/icon16.png');
        
    }

    function settings_box_load(){
        add_meta_box('wpv_settings', '<img src="' . WPV_URL . '/res/img/icon16.png">&nbsp;&nbsp;' . __('View Query', 'wpv-views'), array($this, 'settings_box'), 'view', 'normal', 'high');    
        add_meta_box('wpv_layout', '<img src="' . WPV_URL . '/res/img/icon16.png">&nbsp;&nbsp;' . __('View Layout', 'wpv-views'), 'view_layout_box', 'view', 'normal', 'high');
        //add_meta_box('wpv_css', '<img src="' . WPV_URL . '/res/img/icon16.png">&nbsp;&nbsp;' . __('CSS for view', 'wpv-views'), array($this, 'css_box'), 'view', 'normal', 'high');    
        
        global $pagenow;
        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'view') {
            $this->include_admin_css();
        }
        if ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == WPV_FOLDER . '/menu/main.php') {
            $this->include_admin_css();
        }
        if ($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'wpv-import-theme') {
            $this->include_admin_css();
        }
}

    /**
     * Output the view query metabox on the view edit page.
     *
     */
    
    function settings_box($post){
        
        ?>
        <div id="wpv_view_query_controls" style="position: relative">
            <span id="wpv_view_query_controls_over" class="wpv_view_overlay" style="display:none">
                <p><strong><?php echo __('The view query settings will be copied from the original', 'wpv-views'); ?></strong></p>
            </span>
        <?php

        global $wp_version, $pagenow;        
        if (version_compare($wp_version, '3.2', '<')) {
            echo '<p style="color:red;"><strong>';
            _e('* Requires WordPress 3.2 or greater for best results.', 'wpv-views');
            echo '</strong></p>';
        }
        
        $this->include_admin_css();
        
        wp_nonce_field( 'wpv_get_table_row_ui_nonce', 'wpv_get_table_row_ui_nonce');

        ?>        
        <script type="text/javascript">
    
            var wpv_confirm_filter_change = '<?php _e("Are you sure you want to change the filter?\\n\\nIt appears that you made modifications to the filter.", 'wpv-views'); ?>';
            <?php if ($pagenow == 'post-new.php'): ?>
                jQuery(document).ready(function($){
                   wpv_add_initial_filter_shortcode(); 
                });
            <?php endif; ?>
            
            var wpv_save_button_text = '<?php _e("Save View", 'wpv-views'); ?>';
        </script>
        
        <?php
        
        global $WP_Views;
        $view_settings = $WP_Views->get_view_settings($post->ID);
//        $view_settings = (array)get_post_meta($post->ID, '_wpv_settings', true);
        ?>
        <table id="wpv_filter_table" class="widefat fixed">
            <thead>
                <tr>
                    <th width="20px"></th>
                    <th width="100%"><?php _e('Filter', 'wpv-views'); ?></th>
                </tr>
            </thead>
            
            <tbody>
                <tr id="wpv_filter_type_posts">
                    <?php wpv_filter_post_types_admin($view_settings); ?>
                </tr>
                
                <?php
                    global $view_settings_table_row;
                    $view_settings_table_row = 0;
                    do_action('wpv_add_filter_table_row', $view_settings);
                ?>
                
            </tbody>
        </table>

        <?php
        
        wpv_filter_add_filter_admin($view_settings);
        
        wpv_pagination_admin($view_settings);
        
        wpv_filter_meta_html_admin($view_settings);
        
        ?>
        </div>
        <?php
    }

    /**
     * save the view settings.
     * Called from a post_save action
     *
     */
    
    function save_view_settings($post_id){
        global $wpdb, $sitepress;
        
        list($post_type, $post_status) = $wpdb->get_row("SELECT post_type, post_status FROM {$wpdb->posts} WHERE ID = " . $post_id, ARRAY_N);
        
        if ($post_type == 'view') {
            
            if(isset($_POST['_wpv_settings'])){
                $_POST['_wpv_settings'] = apply_filters('wpv_view_settings_save', $_POST['_wpv_settings']);
                update_post_meta($post_id, '_wpv_settings', $_POST['_wpv_settings']);
            }
            save_view_layout_settings($post_id);
    
            
            if (isset($sitepress)) {
                if (isset($_POST['icl_trid'])) {
                    // save the post from the edit screen.
                    if (isset($_POST['wpv_duplicate_view'])) {
                        update_post_meta($post_id, '_wpv_view_sync', intval($_POST['wpv_duplicate_view']));
                    } else {
                        update_post_meta($post_id, '_wpv_view_sync', "0");
                    }
                    
                    $icl_trid = $_POST['icl_trid'];
                } else {
                    // get trid from database.
                    $icl_trid = $wpdb->get_var("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id={$post_id} AND element_type = 'post_$post_type'");
                }
                
                if (isset($_POST['wpv_duplicate_source_id'])) {
                    $source_id = $_POST['wpv_duplicate_source_id'];
                    $target_id = $post_id;
                } else {
                    // this is the source
                    $source_id = $post_id;
                    $target_id = null;
                }
                
                if ($icl_trid) {
                    $this->duplicate_view($source_id, $target_id, $icl_trid);
                }
            }
        }        
    }
    
    function duplicate_view($source_id, $target_id, $icl_trid) {
        
        global $wpdb;
        
        if ($target_id) {
            // we're saving a translation
            // see if we should copy from the original
            $duplicate = get_post_meta($target_id, '_wpv_view_sync', true);
            if ($duplicate === "") {
                // check the original state
                $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
            }
            if ($duplicate) {
                $view_settings = get_post_meta($source_id, '_wpv_settings', true);
                update_post_meta($target_id, '_wpv_settings', $view_settings);
                
                $view_layout_settings = get_post_meta($source_id, '_wpv_layout_settings', true);
                update_post_meta($target_id, '_wpv_layout_settings', $view_layout_settings);
            }
        } else {
            // We're saving the original
            // see if we should copy to translations.
            $translations = $wpdb->get_col("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = {$icl_trid}");
            
            foreach ($translations as $translation_id) {
                if ($translation_id != $source_id) {
                    $this->duplicate_view($source_id, $translation_id, $icl_trid);
                }
            }
        }
        
    }
 
	/**
	 * If the post has a view
	 * add an view edit link to post.
	 */
	
	function edit_post_link($link, $post_id) {

        if ($this->current_view) {		
			remove_filter('edit_post_link', array($this, 'edit_post_link'), 10, 2);
			
			ob_start();
			
			edit_post_link(__('Edit view', 'wpv-views'), '', '', $this->current_view);
			
			$link = $link . ' ' . ob_get_clean();
			
			add_filter('edit_post_link', array($this, 'edit_post_link'), 10, 2);
		}
		
		return $link;
	}

    function admin_add_help() {
        global $pagenow;
        $screen = get_current_screen();
        
        $help = $this->admin_plugin_help('', $screen->id, $screen);
        
        if ($help) {
            $screen->add_help_tab(array(
                                    'id' => 'views-help',
                                    'title' => __('Views', 'wpv-views'),
                                    'content' => $help,
                                    ));
        }
    }
    /**
    * Adds help on admin pages.
    * 
    * @param type $contextual_help
    * @param type $screen_id
    * @param type $screen
    * @return type 
    */
    function admin_plugin_help($contextual_help, $screen_id, $screen) {
        $help = '';
        switch ($screen_id) {
            case 'edit-view-template':
                $help = '<p>'.__("Create <strong>View Templates</strong> and attach them to content types to display content in complex ways. You can read more detail about View Templates on our website:",'wpv-views');
                $help .= '<br /><a href="http://wp-types.com/user-guides/view-templates/">http://wp-types.com/user-guides/view-templates/</a></p>';
                $help .= '<p>'.__("On this page you have the following options:", 'wpv-views').'</p>';
                $help .= '<ul><li>'.__("<strong>Add New</strong> – create a new View Template", 'wpv-views').'</li></ul>';
                $help .= '<p>'.__("Hover over the name of your View Template to get additional options:", 'wpv-views').'</p>';
                $help .= '<ul><li>'.__("<strong>Edit:</strong> Click to Edit the View Template", 'wpv-views').'</li>';
                $help .= '<li>'.__("<strong>Quick Edit:</strong> click to get quick editing options for the View Template, such as title, slug and date", 'wpv-views').'</li>';
                $help .= '<li>'.__("<strong>Trash:</strong> Move the View Template to Trash", 'wpv-views').'</li></ul>';
                $help .= '<p>'.sprintf(__("If you need additional help with View Templates you can visit our <a href='%s'>support forum</a>.", 'wpv-views'), WPV_SUPPORT_LINK).'</p>';
                break;
            
            case 'view-template':
                $help = '<p>'.__("Use this page to create and edit <strong>View Templates</strong>. For more information about View Templates visit the user guide on our website:", 'wpv-views');
		$help .= '<br /><a href="http://wp-types.com/user-guides/view-templates/">http://wp-types.com/user-guides/view-templates/</a></p>';
                $help .= '<p>'.__("To Create a View Template", 'wpv-views').'</p>';
                $help .= '<ol><li>'.__("Add a Title", 'wpv-views').'</li>';
                $help .= '<li>'.__("Add shortcodes to the body. You can find these by clicking on the “V” icon", 'wpv-views').'</li>';
                $help .= '<li>'.__("Use HTML mode to style your content (we recommend keeping your styles in style.css or another external stylesheet rather than including them inline)", 'wpv-views').'</li>';
                $help .= '</ol>';
                $help .= '<p>'.sprintf(__("If you need additional help with View Templates you can visit our <a href='%s'>support forum</a>.", 'wpv-views'), WPV_SUPPORT_LINK).'</p>';
                break;
            
            case 'edit-view':
                $help = '<p>'.__("Use <strong>Views</strong> to filter and display lists in complex and interesting ways. Read more about Views in our user guide:",'wpv-views');
                $help .= '<br /><a href="http://wp-types.com/user-guides/views/">http://wp-types.com/user-guides/views/</a></p>';
                $help .= '<p>'.__("This page gives you an overview of the Views you have created.", 'wpv-views').'</p>';
                $help .= '<p>'.__("It has the following options:", 'wpv-views').'</p>';
                $help .= '<ul><li>'.__("<strong>Add New</strong>: Add a New View", 'wpv-views').'</li></ul>';
                $help .= '<p>'.__("If you hover over a View's name you also have these options:", 'wpv-views').'</p>';
                $help .= '<ul><li>'.__("<strong>Edit</strong>: Click to edit the View<br />\n", 'wpv-views').'</li>';
                $help .= '<li>'.__("<strong>Quick Edit</strong>: click to get quick editing options for the View, such as title, slug and date", 'wpv-views').'</li>';
                $help .= '<li>'.__("<strong>Trash</strong>: Move the View to Trash", 'wpv-views').'</li></ul>';
                $help .= '<p>'.sprintf(__("If you need additional help with View Templates you can visit our <a href='%s'>support forum</a>.", 'wpv-views'), WPV_SUPPORT_LINK).'</p>';
                break;
            
            case 'view':
                $help = '<p>'.__("Use this page to create and edit your <strong>Views</strong>. You can read more about creating Views in our user guide:",'wpv-views');
                $help .= '<br /><a href="http://wp-types.com/user-guides/views/">http://wp-types.com/user-guides/views/</a></p>';
                $help .= '<p>'.__("To Create a View:", 'wpv-views').'</p>';
                $help .= '<ol><li>'.__("Add a Title for your View.", 'wpv-views').'</li>';
                $help .= '<li>'.__("Leave the shortcodes that are in your text area. These are for filtering and displaying your content.", 'wpv-views').'</li>';
                $help .= '<li>'.__("View Query &gt; Filter: Select how you would like your content to be filtered.", 'wpv-views').'</li>';
                $help .= '<li>'.__("View Query &gt; Pagination: Turn pagination on or off.", 'wpv-views').'</li>';
                $help .= '<li>'.__("View Query &gt; View/Edit HTML : fine tune the HTML for your query.", 'wpv-views').'</li>';
                $help .= '<li>'.__("View Layout: Choose your layout.", 'wpv-views').'</li>';
                $help .= '<li>'.__("View Layout &gt; View/Edit HTML: use addition CSS and HTML to control how your View is displayed.", 'wpv-views').'</li></ol>';
                $help .= '<p>'.sprintf(__("If you need additional help with View Templates you can visit our <a href='%s'>support forum</a>.", 'wpv-views'), WPV_SUPPORT_LINK).'</p>';
                break;
                
        }
        
        if ($help != '') {
            return $help;
        } else {
            return $contextual_help;
        }
    }
 
	// Add WPML sync options.
	
	function language_options() {
	
		global $sitepress, $post;
		
        if ($post->post_type == 'view') {
            list($translation, $source_id, $translated_id) = $sitepress->icl_get_metabox_states();
            
            echo '<br /><br /><strong>' . __('Views sync', 'wpv-views') . '</strong>';
        
            $checked = '';
            if ($translation) {
                if ($translated_id) {
                    $duplicate = get_post_meta($translated_id, '_wpv_view_sync', true);
                    if ($duplicate === "") {
                        // check the original state
                        $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
                    }
                } else {
                    // This is a new translation.
                    $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
                }
                
                if ($duplicate) {
                    $checked = ' checked="checked"';
                }
                echo '<br /><label><input class="wpv_duplicate_from_original" name="wpv_duplicate_view" type="checkbox" value="1" '.$checked . '/>' . __('Duplicate view from original', 'wpml-media') . '</label>';
                echo '<input name="wpv_duplicate_source_id" value="' . $source_id . '" type="hidden" />';
            } else {
    
                $duplicate = get_post_meta($source_id, '_wpv_view_sync', true);
                if ($duplicate) {
                    $checked = ' checked="checked"';
                }
                echo '<br /><label><input name="wpv_duplicate_view" type="checkbox" value="1" '.$checked . '/>' . __('Duplicate view to translations', 'wpv-views') . '</label>'; 
            }
        }
	}
 
    function views_settings_admin() {
        
        global $WPV_templates, $wpdb;
        
        $options = $this->get_options();
        
        $defaults = array('views_template_loop_blog' => '0');
        $options = wp_parse_args($options, $defaults);
        
        if (isset($_POST['submit']) && $_POST['submit'] == __('Save Changes', 'wpv-views') &&
                            wp_verify_nonce($_POST['wpv_view_templates'], 'wpv_view_templates')) {
            
            $options = $WPV_templates->submit($options);
            
            $this->save_options($options);
            
            ?>
                <div class="updated">
                    <p><?php _e("Settings Saved", 'wpv-views'); ?></p>
                </div>
            <?php
            
        }

        ?>
        
        <div class="wrap">
    
            <div id="icon-views" class="icon32"><br /></div>
            <h2><?php _e('Views Settings', 'wpv-views') ?></h2>
    
            <br />
            
            <form method="post" action="edit.php?post_type=view&page=views-settings">
                <input id="submit-top" class="button-primary" type="submit" value="<?php _e('Save Changes', 'wpv-views'); ?>" name="submit" />
                
                <?php $WPV_templates->admin_settings($options); ?>
                
                <?php wp_nonce_field('wpv_view_templates', 'wpv_view_templates'); ?>
                <p class="submit">
                    <input id="submit" class="button-primary" type="submit" value="<?php _e('Save Changes', 'wpv-views'); ?>" name="submit" />
                </p>

            </form>
            
            <?php
                // change the preview url when the selector changes.
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    jQuery('.views_template_select').change(function() {
                        
                        var taxonomy;
                        var link;
                        var loop = false;
                        if (jQuery(this).attr('name').substring(0, 20) == 'views_template_loop_') {
                            taxonomy = jQuery(this).attr('name').substring(20);
                            link = jQuery('#views_template_loop_preview_' + taxonomy).attr('href');
                            loop = true;
                        } else {
                            taxonomy = jQuery(this).attr('name').substring(19);
                            link = jQuery('#views_template_for_preview_' + taxonomy).attr('href');
                        }
                        
                        var newAdditionalURL = "";
                        var tempArray = link.split("?");
                        var baseURL = tempArray[0];
                        var aditionalURL = '';
                        if (tempArray.length == 2) {
                            aditionalURL = tempArray[1];
                        }
                        var temp = "";
                        if(aditionalURL) {
                            var tempArray = aditionalURL.split("&");
                            for ( var i in tempArray ){
                                if(tempArray[i].indexOf("view-template") == -1){
                                    newAdditionalURL += temp+tempArray[i];
                                    temp = "&";
                                    }
                                }
                        }
                        var rows_txt = temp+"view-template="+jQuery("#" + jQuery(this).attr('id') + ' option:selected').text();
                        var finalURL = baseURL+"?"+newAdditionalURL+rows_txt;
                        if (loop) {
                            jQuery('#views_template_loop_preview_' + taxonomy).attr('href', finalURL);
                        } else {
                            jQuery('#views_template_for_preview_' + taxonomy).attr('href', finalURL);
                            jQuery('#wpv_diff_template_' + taxonomy).hide();
                        }
                    });
                });
            </script>
            
        </div>

        
        <?php
    }
    
}