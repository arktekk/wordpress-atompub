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

        $posts_url = $this->url_generator->list_url(1, $post_type_post);
        $posts_url_escaped = esc_url($posts_url);
        $pages_url = $this->url_generator->list_url(1, $post_type_page);
        $pages_url_escaped = esc_url($pages_url);
        $xml = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<service xmlns="$ATOMPUB_NS" xmlns:atom="$ATOM_NS">
  <workspace>
    <atom:title>$blog_name Workspace</atom:title>
    <!-- {$posts_url} -->
    <collection href="{$posts_url_escaped}">
      <atom:title>$blog_name Posts</atom:title>
      <accept>$ATOM_CONTENT_TYPE;type=entry</accept>
    </collection>
    <!-- {$pages_url} -->
    <collection href="{$pages_url_escaped}">
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
