<?php
class AtomPubFeed {

    static function createChildrenFeed(UrlGenerator $url_generator, PostType $post_type, $parent_id, $page_index, $page_count, $page_size) {
        $feed_id = $url_generator->child_posts_iri($post_type, $parent_id);
        $feed_title = "Children of #$parent_id";
        return new AtomPubFeed($url_generator, $post_type, $feed_id, $feed_title, $page_index, $page_count, $page_size);
    }

    static function createListFeed(UrlGenerator $url_generator, PostType $post_type, $page_index, $page_count, $page_size) {
        $feed_id = $url_generator->list_iri($post_type);
        $feed_title = "$post_type->title()";
        return new AtomPubFeed($url_generator, $post_type, $feed_id, $feed_title, $page_index, $page_count, $page_size);
    }

    public static function createEntryFeed(UrlGenerator $url_generator, $post_type, $post_id) {
        $feed_id = $url_generator->post_iri($post_type, $post_id);
        $feed_title = "Entry #$post_id";
        return new AtomPubFeed($url_generator, $post_type, $feed_id, $feed_title, 1, 1, 1);
    }

    /**
     * @param UrlGenerator $url_generator
     * @param PostType $post_type
     * @param int $feed_id
     * @param int $feed_title
     * @param int $page_index
     * @param int $page_count
     * @param int $page_size
     * @return void
     */
    private function AtomPubFeed(UrlGenerator $url_generator, PostType $post_type, $feed_id, $feed_title, $page_index, $page_count, $page_size) {
        global $ATOM_NS, $ATOMPUB_NS, $OPENSEARCH_NS;
        global $ATOM_CONTENT_TYPE;

        $this->url_generator = $url_generator;

        $last_page = $page_count;
        $next_page = (($page_index + 1) > $last_page) ? NULL : $page_index + 1;
        $prev_page = ($page_index - 1) < 1 ? NULL : $page_index - 1;

        $entry_count = $page_count * $page_size;
        $first_entry_index = (($page_index - 1) * $page_size) + 1;

        $xml = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<feed
  xmlns="$ATOM_NS"
  xmlns:app="$ATOMPUB_NS"
  xmlns:opensearch="$OPENSEARCH_NS">
  <id>{$feed_id}</id>
  <title>{$feed_title}</title>
  <opensearch:totalResults>{$entry_count}</opensearch:totalResults>
  <opensearch:startIndex>{$first_entry_index}</opensearch:startIndex>
  <opensearch:itemsPerPage>{$page_size}</opensearch:itemsPerPage>

EOD;
        $xml .= rest_to_line("  <link rel='first' type='application/atom+xml' href='" . esc_url($this->url_generator->list_url(1, $post_type)) . "'/>");
        if (isset($next_page)) {
            $xml .= rest_to_line("  <link rel='next' type='application/atom+xml' href='" . esc_url($this->url_generator->list_url($next_page, $post_type)) . "'/>");
        }
        if (isset($prev_page)) {
            $xml .= rest_to_line("  <link rel='prev' type='application/atom+xml' href='" . esc_url($this->url_generator->list_url($prev_page, $post_type)) . "'/>");
        }
        $xml .= rest_to_line("  <link rel='last' type='application/atom+xml' href='" . esc_url($this->url_generator->list_url($last_page, $post_type)) . "'/>");

        $this->response = new AtomPubResponse();
        $this->response->
                set_header("Content-Type: " . $ATOM_CONTENT_TYPE)->
                add_body($xml);
    }

    function add_post($post, $author, $categories, $include_content = true) {
        global $ATOM_CONTENT_TYPE;
        global $ContentType_ATOM, $ContentType_HTML;

        // Work around http://core.trac.wordpress.org/ticket/15041
        $id = $post->guid;

        if (!strpos($id, "/?p=")) {
            $id .= "?p={$post->ID}";
        }

        $xml .= rest_to_line("  <entry>");
        $xml .= rest_to_line("    <id>{$id}</id>");

        list($content_type, $content) = self::encode_string($post->post_title);
        $xml .= rest_to_line("    <title type='$content_type'>$content</title>");

        $xml .= rest_to_line("    <published>" . atompub_to_date($post->post_date) . "</published>");
        $xml .= rest_to_line("    <updated>" . atompub_to_date($post->post_modified) . "</updated>");
        $xml .= rest_to_line("    <author>");
        $xml .= rest_to_line("      <name>" . $author->display_name . "</name>");
        $xml .= rest_to_line("    </author>");

        foreach ((array) $categories as $category) {
            // This scheme should be transferrable from installation to installation but now it just points to
            // the current installation
            $xml .= rest_to_line("    <category scheme='" . get_bloginfo('url') . "' term='$category->name'/>");
        }

        $post_type = PostType::from_wordpress($post->post_type);
        $xml .= rest_to_line("    <link rel='self' type='application/atom+xml' href='" . esc_url($this->url_generator->post_url($post->ID, $post_type, $ContentType_ATOM)) . "'/>");
        $xml .= rest_to_line("    <link rel='parent' type='application/atom+xml' href='" . esc_url($this->url_generator->post_url($post->post_parent, $post_type, $ContentType_ATOM)) . "'/>");
        $xml .= rest_to_line("    <app:collection href='" . esc_url($this->url_generator->child_posts($post->ID, $post_type, $ContentType_ATOM)) . "'>");
        $xml .= rest_to_line("      <title>Child pages</title>");
        $xml .= rest_to_line("      <app:accept>$ATOM_CONTENT_TYPE;type=entry</app:accept>");
        $xml .= rest_to_line("    </app:collection>");

        if ($post->post_status == 'draft') {
            $xml .= rest_to_line("    <app:control><app:draft>no</app:draft></app:control>");
        }
        else {
            $xml .= rest_to_line("    <app:control><app:draft>yes</app:draft></app:control>");
        }
        $xml .= rest_to_line("    <app:edited>" . atompub_to_date($post->post_modified) . "</app:edited>");

        if ($include_content) {
            $content = apply_filters('the_content', $post->post_content);
            $content = str_replace(']]>', ']]&gt;', $content);
            $content = apply_filters('the_content_feed', $content, "atom");
            list($content_type, $content) = self::encode_string($content);
            $xml .= rest_to_line("    <content type='$content_type'>$content</content>");
        }
        else {
            $xml .= rest_to_line("    <content type='xhtml' src='" . esc_url($this->url_generator->post_url($post->ID, $post_type, $ContentType_HTML)) . "'/>");
        }
        $xml .= rest_to_line("  </entry>");

        $this->response->add_body($xml);
    }

    /**
     * @return AtomPubResponse
     */
    function to_response() {
        return $this->response->add_body(rest_to_line("</feed>"));
    }

    static function encode_string($data) {
        return array('html', htmlentities($data, ENT_COMPAT, "UTF-8"));
    }
}

?>
