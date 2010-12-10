<?php

/**
 * Singleton object representing the current options.
 */
class AtomPubOptions {
    private static $options_key = "atompub_options";
    private $hubs;

    private function __construct($options) {
        $this->hubs = new AtomPubOptionUrlList("hubs", "Hub URLs", $options["hubs"]);
    }

    /**
     * @return AtomPubOptionUrlList
     */
    function hubs() {
        return $this->hubs;
    }

    function to_options() {
        $options = array();
        $options[$this->hubs->id()] = $this->hubs->to_string();
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

class AtomPubOptionUrlList extends AtomPubOption {

    function __construct($id, $title, $values) {
        parent::__construct($id, $title, $values);
    }

    function urls() {
        $str = $this->to_string();
        if(!isset($str) || strlen($str) == 0) {
            return array();
        }
        return AtomPubOptionUrlList::split_string($str);
    }

    static function split_string($str) {
        $trimmed_urls = array();
        $urls = preg_split("/\\n/", $str, null, PREG_SPLIT_NO_EMPTY);
        foreach($urls as $url) {
            $url = trim($url);

            if(strlen($url) > 0) {
                $trimmed_urls[] = $url;
            }
        }

        return $trimmed_urls;
    }

    /**
     * @return array
     */
    function validate($new_value) {
        if(strlen($new_value) == 0) {
            return array(true, NULL);
        }

        foreach(AtomPubOptionUrlList::split_string($new_value) as $url) {
            $r = filter_var($url, FILTER_VALIDATE_URL);
            if($r == FALSE) {
                return array(false, $new_value, "Must be a list of valid URLs");
            }
        }

        return array(true, $new_value, "Must be a list of valid URLs");
    }
}
?>
