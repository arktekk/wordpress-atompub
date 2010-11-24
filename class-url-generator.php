<?php
class UrlGenerator {
    function UrlGenerator($base_url, $blog_id) {
        $this->base_url = $base_url;
        $this->blog_id = $blog_id;
    }

    function list_url($page_index, PostType $post_type) {
        return $this->base_url . "/?" .
                AtomPubRequest::$param_request_type . "=" . AtomPubRequest::$request_type_list . "&" .
                AtomPubRequest::$param_post_type . "=$post_type&" .
                AtomPubRequest::$param_page . "=$page_index";
    }

    function list_iri(PostType $post_type) {
        return "urn:$this->blog_id:" . AtomPubRequest::$request_type_list . ":$post_type";
    }

    public function child_posts_of($parent_id, $page_index, PostType $post_type) {
        return $this->base_url . "/?".
                AtomPubRequest::$param_request_type . "=" . AtomPubRequest::$request_type_children . "&" .
                AtomPubRequest::$param_page . "={$page_index}&" .
                AtomPubRequest::$param_post_type . "={$post_type}&" .
                AtomPubRequest::$param_parent . "={$parent_id}";
    }

    public function child_posts_iri(PostType $post_type, $post_id) {
        return "urn:$this->blog_id:" . AtomPubRequest::$request_type_children  . ":$post_type:$post_id";
    }

    function post_url($post_id, PostType $post_type, ContentType $content_type) {
        return $this->base_url . "/?" .
                AtomPubRequest::$param_request_type . "=" . AtomPubRequest::$request_type_post . "&" .
                AtomPubRequest::$param_post_type . "=$post_type&"  .
                "contentType={$content_type}&".
                "id=$post_id";
    }

    function post_iri(PostType $post_type, $post_id) {
        return "urn:$this->blog_id:post:$post_type:$post_id";
    }
}

?>
