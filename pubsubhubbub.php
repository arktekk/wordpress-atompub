<?php

class Pubsubhubbub {
    function __construct($hub) {
        $this->hub = $hub;
    }

    static function notify_hubs() {
        global $post_type_post, $post_type_page;

        $hubs = AtomPubOptions::get_options()->hubs();
        if(!$hubs->is_set()) {
            return;
        }

        $atom_pub_server = new AtomPubServer();
        $url_generator = $atom_pub_server->url_generator();

        foreach($hubs->urls() as $url) {
            $pubsubhubbub = new Pubsubhubbub($url);

            $pubsubhubbub->notify_hub_of_feed($url_generator->list_url(1, $post_type_post));
            $pubsubhubbub->notify_hub_of_feed($url_generator->list_url(1, $post_type_page));
        }
    }

    function notify_hub_of_feed($feed_url) {
//        error_log("notify_hub_of_feed");
//        error_log("hub =$this->hub");
//        error_log("feed=$feed_url");

        $body = "hub.mode=publish&hub.url=" . urlencode("$feed_url");
//        error_log($body);

        $response = wp_remote_post($this->hub, array("body" => $body));
        $message = wp_remote_retrieve_response_message($response);
        $code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            error_log("Error notifying hub. hub=$this->hub, feed=$feed_url, result=$code $message");
        } else {
            error_log("Successfully notified hub. hub=$this->hub, feed=$feed_url, result=$code $message");
        }
    }
}

?>
