<?php
class UrlGenerator {
    function UrlGenerator($base_url, $blog_id) {
        $this->base_url = $base_url;
        $this->blog_id = $blog_id;
    }

    function list_url($page_index, PostType $post_type) {
        return $this->base_url . "/?" .
                AtomPubRequest::$param_request_type . "=" . AtomPubRequest::$request_type_list . "&" .
                AtomPubRequest::$param_post_type . "=" . $post_type . "&" .
                AtomPubRequest::$param_page . "=" . $page_index;
    }

    function list_iri(PostType $post_type) {
        return "urn:$this->blog_id:list:$post_type";
    }

    function post_url($post_id, PostType $post_type, ContentType $content_type) {
        global $ContentType_ATOM, $ContentType_HTML;

        $url = $this->base_url . "/?" .
                AtomPubRequest::$param_request_type . "=" . AtomPubRequest::$request_type_post .
                "&" . AtomPubRequest::$param_post_type . "=" . $post_type .
                "&id=$post_id";

        switch ($content_type) {
            case $ContentType_HTML:
                $url .= "&contentType=html";
            case $ContentType_ATOM:
                break;
            default:
                wp_die("Unknown content type: " . $content_type);
        }

        return $url;
    }

    function post_iri(PostType $post_type, $post_id) {
        return "urn:$this->blog_id:post:$post_type:$post_id";
    }

    public function child_posts($post_id, PostType $post_type, ContentType $content_type) {
        $page = AtomPubRequest::$param_page;
        $parent = AtomPubRequest::$param_parent;
        $post_type_key = AtomPubRequest::$param_post_type;

        return $this->base_url . "/?atompub=posts&{$page}=1&{$post_type_key}={$post_type}&${parent}={$post_id}&contentType={$content_type}";
    }

    public function child_posts_iri(PostType $post_type, $post_id) {
        return "urn:$this->blog_id:list:$post_type:$post_id";
    }
}

?>
