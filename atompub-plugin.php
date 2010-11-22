<?php
/**
 * @package REST_Server
 * @version 1.0
 */
/*
Plugin Name: AtomPub Server
XXX Plugin URI: http://wordpress.org/extend/plugins/hello-dolly/
Description: AtomPub Server
Author: Event Systems AS
Version: 1.0
*/

require_once('class-atom-pub-feed.php');
require_once('class-atom-pub-request.php');
require_once('class-atom-pub-response.php');
require_once('class-atom-pub-server.php');
require_once('class-atom-pub-service.php');
require_once('class-url-generator.php');

// Hook into wordpress to handle atompub requests
add_action('parse_request', 'atompub_parse_request');
add_filter('query_vars', 'atompub_query_vars');

function atompub_parse_request($wp) {
    // only process requests with "my-plugin=ajax-handler"
    //    if (array_key_exists('atompub', $wp->query_vars) && $wp->query_vars['atompub'] == 'service') {
    if (array_key_exists('atompub', $wp->query_vars)) {
        $server = new AtomPubServer();
        $server->handle_request($wp->query_vars);
        // This doesn't feel quite right. There should be a better way to stop a request
        exit;
    }
}

function atompub_query_vars($vars) {
    foreach(AtomPubRequest::$query_parameter_keys as $key) {
        $vars[] = $key;
    }
    return $vars;
}

// Hook into wordpress to transform /atompub => /index.php?atompub=service
add_filter('rewrite_rules_array', 'atompub_insert_rewrite_rules');
add_filter('query_vars', 'atompub_insert_query_vars');
add_filter('init', 'atompub_flush_rules');

function atompub_flush_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}

function atompub_insert_rewrite_rules($rules) {
    $newrules = array();
    $newrules['(atompub)/(\d*)$'] = 'index.php?atompub&atompub_1=$matches[1]';
    return $newrules + $rules;
}

function atompub_insert_query_vars($vars) {
    array_push($vars, 'id');
    return $vars;
}

?>