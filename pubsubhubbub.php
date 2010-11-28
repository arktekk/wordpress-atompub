<?php

class Pubsubhubbub {
    function __construct($hub) {
        $this->hub = $hub;
    }

    function notify_hub_of_feed($feed_url) {
        error_log("notify_hub_of_feed");
        error_log("hub =$this->hub");
        error_log("feed=$feed_url");
        trigger_error("feed=$feed_url", E_USER_WARNING);

        $body = "hub.mode=publish&hub.url=" . urlencode("$feed_url");

        $response = wp_remote_post($this->hub, array("body" => $body));
        $message = wp_remote_retrieve_response_message($response);
        $code = wp_remote_retrieve_response_code($response);

        if (is_wp_error($response)) {
            trigger_error("Error notifying hub. hub=$this->hub, feed=$feed_url, result=$code $message", E_USER_ERROR);
        } else {
            trigger_error("Successfully notified hub. hub=$this->hub, feed=$feed_url, result=$code $message", E_USER_ERROR);
//            error_log("Response: $code $message");
//            error_log(print_r(wp_remote_retrieve_headers($response), true));
//            error_log("Body:");
//            error_log(wp_remote_retrieve_body($response));
        }
    }
}

?>
