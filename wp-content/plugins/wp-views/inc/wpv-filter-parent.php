<?php

if(is_admin()){
	add_action('init', 'wpv_filter_parent_init');
	
	function wpv_filter_parent_init() {
        global $pagenow;
        
        if($pagenow == 'post.php' || $pagenow == 'post-new.php'){
            add_action('wpv_add_filter_table_row', 'wpv_add_filter_parent_table_row', 1, 1);
            add_filter('wpv_add_filters', 'wpv_add_filter_parent', 1, 1);
        }
    }
    
    /**
     * Add a parent by parent filter
     * This gets added to the popup that shows the available filters.
     *
     */
    
    function wpv_add_filter_parent($filters) {
        $filters['post_parent'] = array('name' => 'Post parent',
										'type' => 'callback',
										'callback' => 'wpv_add_parent',
										'args' => array());
        return $filters;
    }

    /**
     * get the table row to add the the available filters
     *
     */
    
    function wpv_add_filter_parent_table_row($view_settings) {
        if (isset($view_settings['parent_mode'][0])) {
            global $view_settings_table_row;
            $td = wpv_get_table_row_ui_post_parent($view_settings_table_row, null, $view_settings);
        
            echo '<tr class="wpv_filter_row" id="wpv_filter_row_' . $view_settings_table_row . '">' . $td . '</tr>';
            
            $view_settings_table_row++;
        }
    }
    
    /**
     * get the table info for the parent.
     * This is called (via ajax) when we add a post parent filter
     * It's also called to display the existing post parent filter.
     *
     */
    
    function wpv_get_table_row_ui_post_parent($row, $selected, $view_settings = null) {
        
        if (isset($view_settings['parent_mode']) && is_array($view_settings['parent_mode'])) {
            $view_settings['parent_mode'] = $view_settings['parent_mode'][0];
        }
        if (isset($_POST['parent_mode'])) {
            // coming from the add filter button
            $defaults = array('parent_mode' => $_POST['parent_mode']);
            if (isset($_POST['parent_id'])) {
                $defaults['parent_id'] = $_POST['parent_id'];
            }
            
            $view_settings = wp_parse_args($view_settings, $defaults);
        }

        ob_start();
        wpv_add_parent(array('mode' => 'edit',
                             'view_settings' => $view_settings));
        $data = ob_get_clean();
        
        $td = '<td><img src="' . WPV_URL . '/res/img/delete.png" onclick="on_delete_wpv_filter(\'' . $row . '\')" style="cursor: pointer">';
        $td .= '<td class="wpv_td_filter">';
        $td .= "<div id=\"wpv-filter-parent-show\">\n";
        $td .= wpv_get_filter_parent_summary($view_settings);
        $td .= "</div>\n";
        $td .= "<div id=\"wpv-filter-parent-edit\" style='background:" . WPV_EDIT_BACKGROUND . ";display:none'>\n";

        $td .= '<fieldset>';
        $td .= '<legend><strong>' . __('Post parent', 'wpv-views') . ':</strong></legend>';
        $td .= '<div>' . $data . '</div>';
        $td .= '</fieldset>';
        ob_start();
        ?>
            <input class="button-primary" type="button" value="<?php echo __('OK', 'wpv-views'); ?>" name="<?php echo __('OK', 'wpv-views'); ?>" onclick="wpv_show_filter_parent_edit_ok()"/>
            <input class="button-secondary" type="button" value="<?php echo __('Cancel', 'wpv-views'); ?>" name="<?php echo __('Cancel', 'wpv-views'); ?>" onclick="wpv_show_filter_parent_edit_cancel()"/>
        <?php
        $td .= ob_get_clean();
        $td .= '</div></td>';
        
        return $td;
    }
    
    function wpv_get_filter_parent_summary($view_settings) {
        global $wpdb;
        
        ob_start();
        
        if ($view_settings['parent_mode'] == 'current_page') {
            _e('Select posts whose parent is the <strong>current page</strong>.', 'wpv-view');
        } else {
            if (isset($view_settings['parent_id']) && $view_settings['parent_id'] > 0) {
                $selected_title = $wpdb->get_var($wpdb->prepare("
                    SELECT post_title FROM {$wpdb->prefix}posts WHERE ID=%d", $view_settings['parent_id']));
            } else {
                $selected_title = 'None';
            }
            echo sprintf(__('Select posts whose parent is <strong>%s</strong>.', 'wpv-view'), $selected_title);
        }
        
        ?>
        <br />
        <input class="button-secondary" type="button" value="<?php echo __('Edit', 'wpv-views'); ?>" name="<?php echo __('Edit', 'wpv-views'); ?>" onclick="wpv_show_filter_parent_edit()"/>
        <?php
        
        $data = ob_get_clean();
        
        return $data;
        
    }
    
}

/**
 * Add the parent filter to the filter popup.
 *
 */

function wpv_add_parent($args) {
	
    global $wpdb;
    
    $edit = isset($args['mode']) && $args['mode'] == 'edit';
    
    $view_settings = isset($args['view_settings']) ? $args['view_settings'] : array();
    
    $defaults = array('parent_mode' => 'current_page',
                      'parent_id' => 0);
    $view_settings = wp_parse_args($view_settings, $defaults);

    wp_nonce_field('wpv_get_posts_select_nonce', 'wpv_get_posts_select_nonce');

	?>

	<div class="parent-div" style="margin-left: 20px;">

        <ul>
            <?php $radio_name = $edit ? '_wpv_settings[parent_mode][]' : 'parent_mode[]' ?>
            <li>
                <?php $checked = $view_settings['parent_mode'] == 'current_page' ? 'checked="checked"' : ''; ?>
                <label><input type="radio" name="<?php echo $radio_name; ?>" value="current_page" <?php echo $checked; ?>>&nbsp;<?php _e('Parent is the current page', 'wpv-views'); ?></label>
            </li>
            
            <li>
                <?php $checked = $view_settings['parent_mode'] == 'this_page' ? 'checked="checked"' : ''; ?>
                <label><input type="radio" name="<?php echo $radio_name; ?>" value="this_page" <?php echo $checked; ?>>&nbsp;<?php _e('Parent is:', 'wpv-views'); ?></label>
                
                <?php $select_id = $edit ? 'wpv_parent_post_type' : 'wpv_parent_post_type_add' ?>
                <select id="<?php echo $select_id; ?>">
                <?php
                    $hierarchical_post_types = get_post_types( array( 'hierarchical' => true ), 'objects');
                    if ($view_settings['parent_id'] == 0) {
                        $selected_type = 'page';
                    } else {
                        $selected_type = $wpdb->get_var($wpdb->prepare("
                                SELECT post_type FROM {$wpdb->prefix}posts WHERE ID=%d", $view_settings['parent_id']));
                        if (!$selected_type) {
                            $selected_type = 'page';
                        }
                    }
                    foreach ($hierarchical_post_types as $post_type) {
                        $selected = $selected_type == $post_type->name ? ' selected="selected"' : '';
                        echo '<option value="' . $post_type->name . '"' . $selected . '>' . $post_type->labels->singular_name . '</option>';
                    }
                    
                    
                    
                ?>
                </select>
                
                <?php $parent_select_name = $edit ? '_wpv_settings[parent_id]' : 'wpv_parent_id_add' ?>
                <?php wp_dropdown_pages(array('name'=>$parent_select_name, 'selected'=>$view_settings['parent_id'], 'post_type'=> $selected_type, 'show_option_none' => __('None', 'wpv-views'))); ?>

                <img id="wpv_update_parent" src="<?php echo WPV_URL; ?>/res/img/ajax-loader.gif" width="16" height="16" style="display:none" alt="loading" />
                
            </li>
        </ul>
        
	</div>
    
	<?php
    
    
}


function wpv_get_posts_select() {
    if (wp_verify_nonce($_POST['wpv_nonce'], 'wpv_get_posts_select_nonce')) {
        wp_dropdown_pages(array('name'=>'_wpv_settings[parent_id]', 'post_type' => $_POST['post_type'], 'show_option_none' => __('None', 'wpv-views')));
    }
    die();
}