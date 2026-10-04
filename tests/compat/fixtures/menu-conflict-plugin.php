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
    return $menuOrder;
}

function gigpress_menu_conflict_order_only($menuOrder) {
    $GLOBALS['gigpress_menu_order_conflict'] = true;
    return $menuOrder;
}

if ((getenv('COMPAT_CONFLICT_MODE') ?: '') === 'exact-key-late-add') {
    add_filter('menu_order', 'gigpress_menu_conflict_late_add', 20);
} elseif ((getenv('COMPAT_CONFLICT_MODE') ?: '') === 'order-only') {
    add_filter('menu_order', 'gigpress_menu_conflict_order_only', 5);
}
