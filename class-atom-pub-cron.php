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
        Pubsubhubbub::notify_hubs();
    }
}
?>
