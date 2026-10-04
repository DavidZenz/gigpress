<?php
/*
Plugin Name: GigPress Menu Conflict Fixture
Description: Controlled exact-key fixture used only by the disposable menu diagnostic.
*/

function gigpress_menu_conflict_late_add($menuOrder) {
    global $menu;
    $menu[] = array('', 'read', 'separator-gigpress', '', 'wp-menu-separator');
    if (function_exists('gigpress_menu_trace_mark_row')) {
        gigpress_menu_trace_mark_row('separator-gigpress');
    }
    /*
     * Model an incompatible order callback that drops every incoming key.
     * WordPress then compares this late row against another unranked row and
     * falls back to its earlier snapshot, which does not contain this slug.
     */
    return array();
}

function gigpress_menu_conflict_order_only($menuOrder) {
    return $menuOrder;
}

function gigpress_menu_conflict_mark_order() {
    $GLOBALS['gigpress_menu_order_conflict'] = true;
}

if ((getenv('COMPAT_CONFLICT_MODE') ?: '') === 'exact-key-late-add') {
    add_filter('custom_menu_order', '__return_true');
    add_filter('menu_order', 'gigpress_menu_conflict_late_add', 20);
} elseif ((getenv('COMPAT_CONFLICT_MODE') ?: '') === 'order-only') {
    $priority = (getenv('COMPAT_CONFLICT_POSITION') ?: '') === 'after' ? 20 : 5;
    add_action('admin_menu', 'gigpress_menu_conflict_mark_order', 1);
    add_filter('menu_order', 'gigpress_menu_conflict_order_only', $priority);
}
