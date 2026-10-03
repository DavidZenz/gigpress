<?php
/* Request-local menu ordering trace for the disposable compatibility runner. */

if (!defined('ABSPATH')) {
    exit;
}

$GLOBALS['gigpress_menu_trace'] = array(
    'callbacks' => array(),
    'row_creators' => array(),
    'active_callback' => null,
    'trace_is_request_local' => true,
);

function gigpress_menu_trace_slugs($items) {
    return array_values(array_map('strval', (array) $items));
}

function gigpress_menu_trace_callable_identity($callback) {
    if (is_string($callback)) {
        return $callback;
    }
    if (is_array($callback) && count($callback) === 2) {
        return (is_object($callback[0]) ? get_class($callback[0]) : (string) $callback[0]) . '::' . $callback[1];
    }
    return 'closure';
}

function gigpress_menu_trace_mark_row($slug) {
    $trace = &$GLOBALS['gigpress_menu_trace'];
    $active = $trace['active_callback'];
    $trace['row_creators'][] = array(
        'slug' => (string) $slug,
        'callback' => $active ? $active['identity'] : 'unknown',
        'priority' => $active ? $active['priority'] : null,
    );
}

function gigpress_menu_trace_install() {
    global $wp_filter;
    if (empty($wp_filter['menu_order']) || empty($wp_filter['menu_order']->callbacks)) {
        return;
    }
    foreach ($wp_filter['menu_order']->callbacks as $priority => &$callbacks) {
        foreach ($callbacks as $id => &$definition) {
            $callback = $definition['function'];
            $acceptedArgs = $definition['accepted_args'];
            $identity = gigpress_menu_trace_callable_identity($callback);
            $definition['function'] = function ($menuOrder) use ($callback, $acceptedArgs, $identity, $priority) {
                $trace = &$GLOBALS['gigpress_menu_trace'];
                $entry = array(
                    'identity' => $identity,
                    'priority' => (int) $priority,
                    'input' => gigpress_menu_trace_slugs($menuOrder),
                );
                $trace['active_callback'] = array('identity' => $identity, 'priority' => (int) $priority);
                $args = func_get_args();
                $result = call_user_func_array($callback, array_slice($args, 0, $acceptedArgs));
                $trace['active_callback'] = null;
                $entry['returned'] = gigpress_menu_trace_slugs($result);
                $trace['callbacks'][] = $entry;
                return $result;
            };
        }
        unset($definition);
    }
    unset($callbacks);
}

function gigpress_menu_trace_result($finalSlugs, $warnings, $plugin) {
    $trace = $GLOBALS['gigpress_menu_trace'];
    $callbacks = $trace['callbacks'];
    $input = !empty($callbacks) ? $callbacks[0]['input'] : array();
    $returned = !empty($callbacks) ? $callbacks[count($callbacks) - 1]['returned'] : $input;
    $created = array();
    foreach ($trace['row_creators'] as $row) {
        $created[] = $row['slug'];
    }
    return array(
        'runtime_label' => version_compare(PHP_VERSION, '8.3.0', '<') ? 'diagnostic-only' : 'supported',
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'gigpress' => $plugin,
        'input_order' => $input,
        'callbacks' => $callbacks,
        'row_creators' => $trace['row_creators'],
        'final_slugs' => gigpress_menu_trace_slugs($finalSlugs),
        'duplicate_slugs' => array_values(array_unique(array_diff_assoc($finalSlugs, array_unique($finalSlugs)))),
        'missing_from_input' => array_values(array_diff($created, $input)),
        'missing_from_returned_order' => array_values(array_diff($created, $returned)),
        'menu_warnings' => $warnings,
        'trace_is_request_local' => true,
    );
}

add_action('admin_menu', 'gigpress_menu_trace_install', PHP_INT_MAX);
