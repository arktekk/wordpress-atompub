<?php
add_action("atompub_pubsubhubbub_cron","atompub_pubsubhubbub_cron");

function atompub_pubsubhubbub_cron() {
    AtomPubCron::hourly();
}

class AtomPubCron {
    static function activate() {
        wp_clear_scheduled_hook("atompub_pubsubhubbub_cron");
        wp_schedule_event(time(), "hourly", "atompub_pubsubhubbub_cron");
    }    

    static function dectivate() {
        wp_clear_scheduled_hook("atompub_pubsubhubbub_callback");
        wp_clear_scheduled_hook("atompub_pubsubhubbub_cron");
    }

    static function hourly() {
        AtomPubCron::notify_hubs();
    }

    static function notify_hubs() {
        global $post_type_post, $post_type_page;

        $hub = AtomPubOptions::get_options()->hub();
        if(!$hub->is_set()) {
            return;
        }

        $atom_pub_server = new AtomPubServer();
        $url_generator = $atom_pub_server->url_generator();

        $pubsubhubbub = new Pubsubhubbub($hub->to_string());

        $pubsubhubbub->notify_hub_of_feed($url_generator->list_url(1, $post_type_post));
        $pubsubhubbub->notify_hub_of_feed($url_generator->list_url(1, $post_type_page));
    }
}
?>
