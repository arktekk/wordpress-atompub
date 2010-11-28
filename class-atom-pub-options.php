<?php

/**
 * Singleton object representing the current options.
 */
class AtomPubOptions {
    private static $options_key = "atompub_options";
    private $hub;

    private function __construct($options) {
        $this->hub = new AtomPubOptionUrl("hub", "Hub URL", $options["hub"]);
    }

    /**
     * @return AtomPubOptionUrl
     */
    function hub() {
        return $this->hub;
    }

    function to_options() {
        $options = array();
        $options[$this->hub->id()] = $this->hub->to_string();
        return $options;
    }

    /**
     * @static
     * @return AtomPubOptions
     */
    static function get_options() {
        static $instance;
        if (is_null($instance)) {
            $instance = new AtomPubOptions(get_option(AtomPubOptions::$options_key));
        }
        return $instance;
    }
}

abstract class AtomPubOption {
    private $id;
    private $title;
    private $is_set;
    private $value;

    protected function __construct($id, $title, $value) {
        $this->id = $id;
        $this->title = $title;
        list($this->is_set, $v) = $this->validate($value);

        if($this->is_set) {
            $this->value = $v;
        }
    }

    /**
     * @return string
     */
    final function id() {
        return $this->id;
    }

    /**
     * @return string
     */
    final function title() {
        return $this->title;
    }

    /**
     * @return bool
     */
    final function is_set() {
        return $this->is_set;
    }

    /**
     * @return string
     */
    final function to_string() {
        return $this->is_set() ? $this->value : NULL;
    }

    /**
     * @abstract
     * @return boolean|string
     */
    function try_update($new_value) {
        list($valid, $value, $error) = $this->validate($new_value);

        if($valid) {
            $this->is_set = true;
            $this->value = $value;
            return array(true);
        }

        return array(false, $error);
    }

    function __toString() {
        throw new Exception("That's a no-no!");
    }

    /**
     * @abstract
     * @return array
     */
    protected abstract function validate($new_value);
}

class AtomPubOptionUrl extends AtomPubOption {

    function __construct($id, $title, $value) {
        parent::__construct($id, $title, $value);
    }

    /**
     * @return array
     */
    function validate($new_value) {
        error_log("AtomPubOptionUrl::validate, new_value=$new_value");

        if(strlen($new_value) == 0) {
            error_log("AtomPubOptionUrl::validate, empty string is ok.");
            return array(true, NULL);
        }

        $r = filter_var($new_value, FILTER_VALIDATE_URL);

        error_log("AtomPubOptionUrl::validate, valid:" . ($r != FALSE) . ", result=" . print_r($r, true));

        return array($r != FALSE, $new_value, "Must be a valid URL");
    }
}
?>
