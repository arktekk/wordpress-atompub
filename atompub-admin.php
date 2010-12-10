<?php
// add the admin options page
add_action('admin_menu', 'plugin_admin_add_page');
// add the admin settings and such
add_action('admin_init', 'plugin_admin_init');

function plugin_admin_add_page() {
    add_options_page('Custom Plugin Page', 'AtomPub', 'manage_options', 'atompub', 'atompub_options_page');
}

// display the admin options page
function atompub_options_page() {
    ?>
<div>
    <h2>AtomPub Settings</h2>

    <form action="options.php" method="post">
        <?php settings_fields('atompub_options'); ?>
        <?php do_settings_sections('plugin'); ?>

        <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>"/>
    </form>
</div>

<?php

}

function plugin_admin_init() {
    register_setting('atompub_options', 'atompub_options', 'atompub_options_validate');
    add_settings_section('plugin_pubsubhubbub', 'Pubsubhubbub Settings', 'plugin_section_text', 'plugin');
    add_settings_field('plugin_hub', 'Hub URL', 'plugin_setting_hub_url', 'plugin', 'plugin_pubsubhubbub');
}

function plugin_section_text() {
    // echo '<p>Main description of this section here.</p>';
}

function plugin_setting_hub_url() {
    $id = AtomPubOptions::get_options()->hubs()->id();
    $hub_urls = AtomPubOptions::get_options()->hubs()->urls();

    echo "<textarea id='plugin_hub' cols='100' rows='10' name='atompub_options[$id]'>";
    foreach($hub_urls as $url) {
        echo $url . "\r\n";
    }
    echo "</textarea><br/>";
    echo "<i>One URL per line</i>";
}

function atompub_options_validate($input) {

    $options = AtomPubOptions::get_options();

    error_log("atompub_options_validate, input=" . print_r($input, true));

    try_update($input, $options->hubs()->id(), $options->hubs());

    $new_options = $options->to_options();
    error_log("new_options=" . print_r($new_options, true));
    return $new_options;
}

function try_update($input, $key, AtomPubOption $option) {
    $new_value = $input[$key];
    list($valid, $error) = $option->try_update($new_value);
    if(!$valid) {
        add_settings_error('plugin_hub', 'plugin_hub', $error);
    }
}

?>
