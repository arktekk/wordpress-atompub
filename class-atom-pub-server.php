<?php

require_once('utils.php');

class AtomPubServer {
    const page_size = 5;

    function AtomPubServer() {
        $this->app_base = site_url();
        $this->blog_id = get_option('blogname');
    }

    function handle_request($query) {
        $method = $_SERVER['REQUEST_METHOD'];

        $request = new AtomPubRequest($query);

        switch($request->request_type()) {
            case AtomPubRequest::$request_type_service:
                if ($method == "GET") {
                    $this->get_service($request);
                }
                else {
                    $this->not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_list:
                if ($method == "GET") {
                    $this->get_list($request);
                }
                else {
                    $this->not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_children:
                if ($method == "GET") {
                    $this->get_children($request);
                }
                else {
                    $this->not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_post:
                if ($method == "GET") {
                    $this->get_post($request);
                }
                else {
                    $this->not_allowed("GET");
                }
                break;
            default:
                $this->not_found();
        }
    }

    function get_service() {
        $service = new AtomPubService(new UrlGenerator($this->app_base, $this->blog_id));
        $service->to_response(get_bloginfo('name'))->send();
    }

    function get_list(AtomPubRequest $request) {
        $current_page = $request->page();
        if ($current_page == NULL) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_page . "'.");
        }

        $post_type = $request->post_type();
        if (!isset($post_type)) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_post_type . "'.");
        }

        $args = array(
            'post_type' => $post_type->wordpress_id(),
            'posts_per_page' => self::page_size,
            'offset' => self::page_size * ($current_page - 1));

        $query = new WP_Query($args);

        $num_pages = $query->max_num_pages;

        $feed = AtomPubFeed::createListFeed(new UrlGenerator($this->app_base, $this->blog_id), $post_type, $current_page, $num_pages, self::page_size);

        while ($query->have_posts()) {
            $post = $query->next_post();
            $author = get_userdata($post->post_author);
            $categories = get_the_category($post->ID);
            $feed->add_post($post, $author, $categories, $request->include_content());
        }
        $feed->to_response($query, $categories)->send();
    }

    function get_children(AtomPubRequest $request) {
        $current_page = $request->page();
        if ($current_page == NULL) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_page . "'.");
        }

        $post_type = $request->post_type();
        if (!isset($post_type)) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_post_type . "'.");
        }

        $parent_id = $request->parent();
        if (!isset($parent_id)) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_parent . "'.");
        }

        $args = array(
            'post_type' => $post_type->wordpress_id(),
            'posts_per_page' => self::page_size,
            'post_parent' => $parent_id,
            'offset' => self::page_size * ($current_page - 1),
            'orderby' => 'title',
            'order' => 'asc');

        $query = new WP_Query($args);

        $num_pages = $query->max_num_pages;

        $feed = AtomPubFeed::createChildrenFeed(new UrlGenerator($this->app_base, $this->blog_id), $post_type, $parent_id, $current_page, $num_pages, self::page_size);

        while ($query->have_posts()) {
            $post = $query->next_post();
            $author = get_userdata($post->post_author);
            $categories = get_the_category($post->ID);
            $feed->add_post($post, $author, $categories, $request->include_content());
        }
        $feed->to_response($query, $categories)->send();
    }

    function get_post(AtomPubRequest $request) {
        $post_type = $request->post_type();
        if (!isset($post_type)) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_post_type . "'.");
        }

        $id = $request->id();
        if (!isset($id)) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_id . "'.");
        }

        $args = array(
            'post__in' => array($id),
            'post_type' => $post_type->wordpress_id());

        $query = new WP_Query($args);
        $feed = AtomPubFeed::createEntryFeed(new UrlGenerator($this->app_base, $this->blog_id), $post_type, $id);
        if ($query->have_posts()) {
            $post = $query->next_post();
            $author = get_userdata($post->post_author);
            $categories = get_the_category($post->ID);
            $feed->add_post($post, $author, $categories, $request->include_content());
        }
        $feed->to_response($query, $categories)->send();
    }

    function bad_request($reason) {
        status_header('400');
        header("Content-Type: text/plain");
        echo $reason . "\n";
        exit;
    }

    function not_allowed($allow) {
        //        log_app('Status','405: Not Allowed');
        header('Allow: ' . join(',', $allow));
        status_header('405');
        exit;
    }

    function not_found() {
        //        log_app('Status','404: Not Found');
        header('Content-Type: text/plain');
        status_header('404');
        exit;
    }
}

?>
