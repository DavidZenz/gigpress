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

add_filter('menu_order', 'gigpress_menu_conflict_late_add', 20);
