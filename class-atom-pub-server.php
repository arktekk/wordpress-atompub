<?php

require_once('utils.php');

class AtomPubServer {
    const page_size = 5;

    function AtomPubServer() {
        $this->app_base = site_url();
    }

    function handle_request($query) {
        $method = $_SERVER['REQUEST_METHOD'];

        $request = new AtomPubRequest($query);

        $atompub = $query['atompub'];

        foreach ($query as $key => $value) {
            header("X-Debug: " . $key . "=" . $value);
        }

        if ($atompub == "service") {
            if ($method == "GET") {
                $this->get_service($request);
            }
            else {
                $this->not_allowed("GET");
            }
        }
        else if ($atompub == "posts") {
            if ($method == "GET") {
                $this->get_posts($request);
            }
            else {
                $this->not_allowed("GET");
            }
        } else if ($atompub == "pages") {
            if ($method == "GET") {
                $this->get_posts($request);
            }
            else {
                $this->not_allowed("GET");
            }
        }
        else {
            $this->not_found();
        }
    }

    function get_service() {
        $service = new AtomPubService(new UrlGenerator($this->app_base));
        $service->to_response(get_bloginfo('name'))->send();
    }

    function get_posts(AtomPubRequest $request) {
        $current_page = $request->page();
        if($current_page == NULL) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_page . "'.");
        }

        $post_type = $request->post_type();
        if(!isset($post_type)) {
            $this->bad_request("Invalid value for '" . AtomPubRequest::$param_post_type . "'.");
        }

        $args = array(
            'post_type' => $post_type->wordpress_id(),
            'posts_per_page' => self::page_size,
            'offset' => self::page_size * ($current_page - 1));

        $parent = $request->parent();
        if(isset($parent)) {
            $args["post_parent"] = $parent;
        }

        $query = new WP_Query($args);

        $num_pages = $query->max_num_pages;

        $feed = new AtomPubFeed(new UrlGenerator($this->app_base), $post_type, $current_page, $num_pages, self::page_size);

        while ($query->have_posts()) {
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
