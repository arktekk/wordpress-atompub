<?php
$post_type_post = new PostType("abc", "post", "Post");
$post_type_page = new PostType("xyz", "page", "Page");

class PostType {
    private $id = null;
    private $wordpress_id = null;
    private $title = null;

    function PostType($id, $wordpress_id, $title) {
        $this->id = $id;
        $this->wordpress_id = $wordpress_id;
        $this->title = $title;
    }

    function __toString() {
        return $this->id;
    }

    function wordpress_id() {
        return $this->wordpress_id;
    }

    /**
     * @return string
     */
    function title() {
        return $this->title;
    }

    static function from_query($s) {
        global $post_type_post, $post_type_page;

        switch($s) {
            case $post_type_post->id: return $post_type_post;
            case $post_type_page->id: return $post_type_page;
            default: return NULL;
        }
    }

    static function from_wordpress($s) {
        global $post_type_post, $post_type_page;

        switch($s) {
            case $post_type_post->wordpress_id(): return $post_type_post;
            case $post_type_page->wordpress_id: return $post_type_page;
            default: return NULL;
        }
    }
}

class AtomPubRequest {

    public static $param_page = "pg";
    public static $param_parent = "parent";
    public static $param_post_type = "pt";
    public static $query_parameter_keys = array("atompub", "include_content", "pg", "parent", "pt");

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
}

?>
