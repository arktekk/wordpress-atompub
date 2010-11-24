<?php
// These should be a part of the ContentType class as "xmlNamespace" and "mimeType"
$ATOM_CONTENT_TYPE = 'application/atom+xml';
$CATEGORIES_CONTENT_TYPE = 'application/atomcat+xml';
$SERVICE_CONTENT_TYPE = 'application/atomsvc+xml';

$ATOM_NS = 'http://www.w3.org/2005/Atom';
$ATOMPUB_NS = 'http://www.w3.org/2007/app';
$OPENSEARCH_NS = 'http://a9.com/-/spec/opensearch/1.1/';

/**
 * Appends a newline to the end of the string.
 */
function rest_to_line($line) {
    return $line . "\n";
}

function atompub_to_date($time) {
    return date('Y-m-d\TH:i:s\Z', strtotime($time));
}

$ContentType_HTML = new ContentType("html");
$ContentType_ATOM = new ContentType("atom");

$post_type_post = new PostType("abc", "post", "Post");
$post_type_page = new PostType("xyz", "page", "Page");

class ContentType {
    function ContentType($name) {
        $this->name = $name;
    }

    function __toString() {
        return $this->name;
    }
}

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

        switch ($s) {
            case $post_type_post->id: return $post_type_post;
            case $post_type_page->id: return $post_type_page;
            default: return NULL;
        }
    }

    static function from_wordpress($s) {
        global $post_type_post, $post_type_page;

        switch ($s) {
            case $post_type_post->wordpress_id: return $post_type_post;
            case $post_type_page->wordpress_id: return $post_type_page;
            default: return NULL;
        }
    }
}
?>
