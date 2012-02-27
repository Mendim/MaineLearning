<?php
/**
 * Inits custom taxonomies.
 */
function wpcf_custom_taxonomies_init() {
    $custom_taxonomies = get_option('wpcf-custom-taxonomies', array());
    if (!empty($custom_taxonomies)) {
        foreach ($custom_taxonomies as $taxonomy => $data) {
            wpcf_custom_taxonomies_register($taxonomy, $data);
        }
    }
}

/**
 * Registers custom taxonomies.
 * 
 * @param type $post_type
 * @param type $data 
 */
function wpcf_custom_taxonomies_register($taxonomy, $data) {
    if (!empty($data['disabled'])) {
        return false;
    }
    // Set object types
    if (!empty($data['supports']) && is_array($data['supports'])) {
        $object_types = array_keys($data['supports']);
    } else {
        $object_types = array();
    }
    // Set labels
    if (!empty($data['labels'])) {
        if (!isset($data['labels']['name'])) {
            $data['labels']['name'] = $taxonomy;
        }
        if (!isset($data['labels']['singular_name'])) {
            $data['labels']['singular_name'] = $data['labels']['name'];
        }
        foreach ($data['labels'] as $label_key => $label) {
            switch ($label_key) {
                case 'parent_item':
                case 'parent_item_colon':
                case 'edit_item':
                case 'update_item':
                case 'add_new_item':
                case 'new_item_name':
                    $data['labels'][$label_key] = sprintf($label,
                            $data['labels']['singular_name']);
                    break;

                case 'search_items':
                case 'popular_items':
                case 'all_items':
                case 'separate_items_with_commas':
                case 'add_or_remove_items':
                case 'choose_from_most_used':
                case 'menu_name':
                    $data['labels'][$label_key] = sprintf($label,
                            $data['labels']['name']);
                    break;
            }
        }
    }
    $data['description'] = !empty($data['description']) ? htmlspecialchars(stripslashes($data['description']),
                    ENT_QUOTES) : '';
    $data['public'] = (empty($data['public']) || strval($data['public']) == 'hidden') ? false : true;
    $data['show_ui'] = (empty($data['show_ui']) || !$data['public']) ? false : true;
    $data['hierarchical'] = (empty($data['hierarchical']) || $data['hierarchical'] == 'flat')  ? false : true;
    $data['show_in_nav_menus'] = !empty($data['show_in_nav_menus']);
    if (empty($data['query_var_enabled'])) {
        $data['query_var'] = false;
    } else if (empty($data['query_var'])) {
        $data['query_var'] = true;
    }
    if (!empty($data['rewrite']['enabled'])) {
        $data['rewrite']['with_front'] = !empty($data['rewrite']['with_front']);
        $data['rewrite']['hierarchical'] = !empty($data['rewrite']['hierarchical']);
        // Make sure that rewrite/slug has a value
        if (!isset($data['rewrite']['slug']) || $data['rewrite']['slug'] == '') {
            $data['rewrite']['slug'] = $data['slug'];
        }
    } else {
        $data['rewrite'] = false;
    }
    // Force removing capabilities here
    unset($data['capabilities']);
    register_taxonomy($taxonomy, $object_types, $data);
}