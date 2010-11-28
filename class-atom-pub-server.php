<?php

require_once('utils.php');

class AtomPubServer {
    const page_size = 5;

    function AtomPubServer() {
        $this->app_base = site_url();
        $this->blog_id = get_option('blogname');
    }

    function url_generator() {
        static $url_generator;
        if (is_null($url_generator)) {
            $url_generator = new UrlGenerator($this->app_base, $this->blog_id);
        }
        return $url_generator;
    }

    function handle_request($query) {
        // TODO: check that the requested content type is available

        $method = $_SERVER['REQUEST_METHOD'];

        $request = new AtomPubRequest($query);

        switch($request->request_type()) {
            case AtomPubRequest::$request_type_service:
                if ($method == "GET") {
                    $this->get_service($request);
                }
                else {
                    $this->method_not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_list:
                if ($method == "GET") {
                    $this->get_list($request);
                }
                else {
                    $this->method_not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_children:
                if ($method == "GET") {
                    $this->get_children($request);
                }
                else {
                    $this->method_not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_post:
                if ($method == "GET") {
                    $this->get_post($request);
                }
                else {
                    $this->method_not_allowed("GET");
                }
                break;
            case AtomPubRequest::$request_type_notify_hubs:
                if ($method == "POST") {
                    if(!is_user_logged_in()) {
                        // This should ideally call unauthorized(), but as we don't
                        // have a way to authenticate over HTTP we have to resort to this
                        // non-RESTful way

                        $this->redirect(wp_login_url($_SERVER["REQUEST_URI"]));
                    }
                    if(!$this->current_user_can_notify_hubs()) {
                        $this->forbidden();
                    }

                    $this->notify_hubs($request);
                }
                else {
                    $this->method_not_allowed("POST");
                }
                break;
            default:
                $this->not_found();
        }
    }

    function current_user_can_notify_hubs() {
        return current_user_can('manage_options');
    }

    function get_service() {
        $service = new AtomPubService($this->url_generator());
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

        $feed = AtomPubFeed::createListFeed($this->url_generator(), $post_type, $current_page, $num_pages, self::page_size);
        AtomPubServer::query_to_feed($query, $feed, $request->include_content());
        $feed->to_response()->send();
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

        $feed = AtomPubFeed::createChildrenFeed($this->url_generator(), $post_type, $parent_id, $current_page, $num_pages, self::page_size);
        AtomPubServer::query_to_feed($query, $feed, $request->include_content());
        $feed->to_response()->send();
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
        $feed = AtomPubFeed::createEntryFeed($this->url_generator(), $post_type, $id);
        AtomPubServer::query_to_feed($query, $feed, $request->include_content());
        $feed->to_response()->send();
    }

    static function query_to_feed(WP_Query $query, AtomPubFeed $feed, $include_content) {
        if (!$query->have_posts()) {
            return;
        }

        while ($query->have_posts()) {
            $post = $query->next_post();
            $author = get_userdata($post->post_author);
            $categories = get_the_category($post->ID);
            $feed->add_post($post, $author, $categories, $include_content);
        }
    }

    function notify_hubs() {
        AtomPubCron::notify_hubs();
    }

    function redirect($url) {
        status_header('307');
        header("Location: $url");
        exit;
    }

    function bad_request($reason) {
        status_header('400');
        header("Content-Type: text/plain");
        echo $reason . "\n";
        exit;
    }

//    function unauthorized() {
//        status_header('401');
//        exit;
//    }

    function forbidden() {
        status_header('403');
        exit;
    }

    function not_found() {
        //        log_app('Status','404: Not Found');
        header('Content-Type: text/plain');
        status_header('404');
        exit;
    }

    function method_not_allowed($allow) {
        //        log_app('Status','405: Not Allowed');
        if(is_array($allow)) {
            header('Allow: ' . join(',', $allow));
        }
        else {
            header('Allow: ' . $allow);
        }
        status_header('405');
        exit;
    }
}

?>
