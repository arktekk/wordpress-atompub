<?php
class UrlGenerator {
    function UrlGenerator($base_url) {
        $this->base_url = $base_url;
    }

    function collection($page, PostType $post_type) {
        return $this->base_url . "/?" .
                "atompub=posts&" .
                AtomPubRequest::$param_post_type . "=" . $post_type . "&" .
                AtomPubRequest::$param_page . "=" . $page;
    }

    function post($post_id, ContentType $content_type) {
        global $ContentType_ATOM, $ContentType_HTML;

        switch ($content_type) {
            case $ContentType_ATOM:
                return $this->base_url . "/?atompub=post&id=" . $post_id;
            case $ContentType_HTML:
                return $this->base_url . "/?atompub=post&id=" . $post_id . "&contentType=html";
            default:
                wp_die("Unknown content type: " . $content_type);
        }
    }

    public function child_posts($post_id, PostType $post_type, ContentType $content_type) {
        global $ContentType_ATOM, $ContentType_HTML;

        $page = AtomPubRequest::$param_page;
        $parent = AtomPubRequest::$param_parent;
        $post_type_key = AtomPubRequest::$param_post_type;

        return $this->base_url . "/?atompub=posts&{$page}=1&{$post_type_key}={$post_type}&${parent}={$post_id}&contentType={$content_type}";
    }
}

?>
