<?php

class AtomPubService {

    function AtomPubService(UrlGenerator $url_generator) {
        $this->url_generator = $url_generator;
    }

    /**
     * @param  $blog_name
     * @return AtomPubResponse
     */
    function to_response($blog_name) {
        global $ATOM_NS, $ATOMPUB_NS;
        global $SERVICE_CONTENT_TYPE, $ATOM_CONTENT_TYPE;
        global $post_type_post, $post_type_page;

        $posts_url = esc_url($this->url_generator->list_url(1, $post_type_post));
        $pages_url = esc_url($this->url_generator->list_url(1, $post_type_page));
        $xml = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<service xmlns="$ATOMPUB_NS" xmlns:atom="$ATOM_NS">
  <workspace>
    <atom:title>$blog_name Workspace</atom:title>
    <collection href="{$posts_url}">
      <atom:title>$blog_name Posts</atom:title>
      <accept>$ATOM_CONTENT_TYPE;type=entry</accept>
    </collection>
    <collection href="{$pages_url}">
      <atom:title>$blog_name Pages</atom:title>
      <accept>$ATOM_CONTENT_TYPE;type=entry</accept>
    </collection>
  </workspace>
</service>

EOD;
        $response = new AtomPubResponse();
        return $response->
                set_header("Content-Type: " . $SERVICE_CONTENT_TYPE)->
                with_body($xml);
    }
}

?>
