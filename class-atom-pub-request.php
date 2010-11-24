<?php
class AtomPubRequest {

    public static $param_page = "pg";
    public static $param_parent = "parent";
    public static $param_post_type = "pt";
    public static $param_id = "id";
    public static $param_request_type = "atompub";
    public static $request_type_service = "service";
    public static $request_type_list = "list";
    public static $request_type_children = "children";
    public static $request_type_post = "post";
    public static $query_parameter_keys = array("atompub", "include_content", "pg", "parent", "pt", "id");

    private $query_parameters = array();

    function AtomPubRequest($query_parameters) {
        $this->query_parameters = $query_parameters;
    }

    public function page() {
        return $this->get_query_parameter_as_positive(self::$param_page);
    }

    public function parent() {
        return $this->get_query_parameter_as_positive(self::$param_parent);
    }

    public function include_content() {
        return $this->is_query_parameter_empty("include_content");
    }

    public function id() {
        return $this->get_query_parameter_as_positive(self::$param_id);
    }

    public function request_type() {
        return $this->get_query_parameter_nonempty_string(self::$param_request_type);
    }

    /**
     * @return null|PostType
     */
    public function post_type() {
        return PostType::from_query($this->query_parameters[AtomPubRequest::$param_post_type]);
    }

    /**
     * @param string $name
     * @return string
     */
    private function get_query_parameter($name) {
        return $this->query_parameters[$name];
    }

    /**
     * @param  $name
     * @param null $default
     * @return int|null
     */
    private function get_query_parameter_as_int($name, $default = NULL) {
        $val = $this->query_parameters[$name];

        if (!isset($val)) {
            return $default;
        }

        return (int) $val;
    }

    /**
     * @param  $name
     * @param null $default
     * @return int|null
     */
    private function get_query_parameter_as_positive($name, $default = NULL) {
        $val = $this->query_parameters[$name];

        if (!isset($val)) {
            return $default;
        }

        $i = (int) $val;

        if ($i < 1) {
            return $default;
        }

        return $i;
    }

    private function is_query_parameter_empty($name) {
        $val = $this->query_parameters[$name];
        return !isset($val) || $val == "";
    }

    private function get_query_parameter_nonempty_string($name) {
        $val = $this->query_parameters[$name];
        return (isset($val) && strlen($val) > 0) ? $val : NULL;
    }
}

?>
