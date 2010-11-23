<?php

class OpenSearchSearchResults {
    function __construct($total_results, $start_index, $items_per_page) {
        $this->total_results = $total_results;
        $this->start_index = $start_index;
        $this->items_per_page = $items_per_page;
    }

    static function fromWordpressResults($page_index, $page_count, $page_size) {
        return new OpenSearchSearchResults($page_count * $page_size, (($page_index - 1) * $page_size) + 1, $page_size);
    }

    function to_xml() {
        return "<opensearch:totalResults>{$this->total_results}</opensearch:totalResults>".
        "<opensearch:startIndex>{$this->start_index}</opensearch:startIndex>".
        "<opensearch:itemsPerPage>{$this->items_per_page}</opensearch:itemsPerPage>";
    }
}

class AtomPubLink {
    function __construct($href, $rel, $type) {
        $this->href = $href;
        $this->rel = $rel;
        $this->type = $type;
    }

    function to_xml() {
        return "<link rel='$this->rel' type='$this->type' href='" . esc_url($this->href) . "'/>";
    }
}

abstract class AtomPubFeed {

    static function createChildrenFeed(UrlGenerator $url_generator, PostType $post_type, $parent_id, $page_index, $page_count, $page_size) {
        $feed_id = $url_generator->child_posts_iri($post_type, $parent_id);
        $feed_title = "Children of #$parent_id";
        return new ListAtomPubFeed($url_generator, $post_type, $feed_id, $feed_title, $page_index, $page_count, $page_size);
    }

    static function createListFeed(UrlGenerator $url_generator, PostType $post_type, $page_index, $page_count, $page_size) {
        $feed_id = $url_generator->list_iri($post_type);
        $feed_title = "$post_type";
        return new ListAtomPubFeed($url_generator, $post_type, $feed_id, $feed_title, $page_index, $page_count, $page_size);
    }

    public static function createEntryFeed(UrlGenerator $url_generator, $post_type, $post_id) {
        $feed_id = $url_generator->post_iri($post_type, $post_id);
        $feed_title = "Entry #$post_id";
        return new AtomPubFeed($url_generator, $post_type, $feed_id, $feed_title, 1, 1, 1);
    }

    protected function __construct(UrlGenerator $url_generator) {
        global $ATOM_CONTENT_TYPE;

        $this->url_generator = $url_generator;
        $this->response = new AtomPubResponse();
        $this->response->set_header("Content-Type: " . $ATOM_CONTENT_TYPE);
    }

    protected function start_feed($feed_id, $feed_title) {
        global $ATOM_NS, $ATOMPUB_NS, $OPENSEARCH_NS;

        $xml = <<<EOD
<?xml version="1.0" encoding="utf-8"?>
<feed
  xmlns="$ATOM_NS"
  xmlns:app="$ATOMPUB_NS"
  xmlns:opensearch="$OPENSEARCH_NS">
  <id>{$feed_id}</id>
  <title>{$feed_title}</title>

EOD;
        $this->response->
                add_body($xml);
    }

    protected function create_feed_links(PostType $post_type, $page_index, $page_count, $page_size) {
        global $ATOM_CONTENT_TYPE;
        $last_page = $page_count;
        $next_page = (($page_index + 1) > $last_page) ? NULL : $page_index + 1;
        $prev_page = ($page_index - 1) < 1 ? NULL : $page_index - 1;

        $links = array();
        $links[] = new AtomPubLink($this->url_generator->list_url(1, $post_type), "first", $ATOM_CONTENT_TYPE);
        if (isset($next_page)) {
            $links[] = new AtomPubLink($this->url_generator->list_url($next_page, $post_type), "next", $ATOM_CONTENT_TYPE);
        }
        if (isset($prev_page)) {
            $links[] = new AtomPubLink($this->url_generator->list_url($prev_page, $post_type), "prev", $ATOM_CONTENT_TYPE);
        }
        $links[] = new AtomPubLink($this->url_generator->list_url($last_page, $post_type), "last", $ATOM_CONTENT_TYPE);

        return $links;
    }

    function add_post($post, $author, $categories, $include_content = true) {
        global $ATOM_CONTENT_TYPE;
        global $ContentType_ATOM, $ContentType_HTML;

        $guid = $post->guid;
        $post_title = $post->post_title;
        $post_parent = $post->post_parent;
        $post_date = $post->post_date;
        $post_modified = $post->post_modified;
        $post_content = $post->post_content;
        $post_status = $post->post_status;
        $author_display_name = $author->display_name;
        $post_type = PostType::from_wordpress($post->post_type);

        // Work around http://core.trac.wordpress.org/ticket/15041
        if (!strpos($guid, "/?p=")) {
            $guid .= "?p={$post->ID}";
        }

        $xml .= rest_to_line("  <entry>");
        $xml .= rest_to_line("    <id>{$guid}</id>");

        list($content_type, $content) = self::encode_string($post_title);
        $xml .= rest_to_line("    <title type='$content_type'>$content</title>");

        $xml .= rest_to_line("    <published>" . atompub_to_date($post_date) . "</published>");
        $xml .= rest_to_line("    <updated>" . atompub_to_date($post_modified) . "</updated>");
        $xml .= rest_to_line("    <author>");
        $xml .= rest_to_line("      <name>" . $author_display_name . "</name>");
        $xml .= rest_to_line("    </author>");

        foreach ((array) $categories as $category) {
            // This scheme should be transferrable from installation to installation but now it just points to
            // the current installation
            $xml .= rest_to_line("    <category scheme='" . get_bloginfo('url') . "' term='$category->name'/>");
        }

        $xml .= rest_to_line("    <!-- self=" . $this->url_generator->post_url($post->ID, $post_type, $ContentType_ATOM) . " -->");
        $xml .= rest_to_line("    <link rel='self' type='application/atom+xml' href='" . esc_url($this->url_generator->post_url($post->ID, $post_type, $ContentType_ATOM)) . "'/>");
        $xml .= rest_to_line("    <!-- parent=" . $this->url_generator->post_url($post_parent, $post_type, $ContentType_ATOM) . " -->");
        $xml .= rest_to_line("    <link rel='parent' type='application/atom+xml' href='" . esc_url($this->url_generator->post_url($post_parent, $post_type, $ContentType_ATOM)) . "'/>");
        $xml .= rest_to_line("    <!-- children=" . $this->url_generator->post_url($post_parent, $post_type, $ContentType_ATOM) . " -->");
        $xml .= rest_to_line("    <link rel='urn:wordpress:children' type='application/atom+xml' href='" . esc_url($this->url_generator->child_posts($post->ID, $post_type)) . "'/>");

        $xml .= rest_to_line("    <app:collection href='" . esc_url($this->url_generator->child_posts($post->ID, $post_type, $ContentType_ATOM)) . "'>");
        $xml .= rest_to_line("      <title>Child pages</title>");
        $xml .= rest_to_line("      <app:accept>$ATOM_CONTENT_TYPE;type=entry</app:accept>");
        $xml .= rest_to_line("    </app:collection>");

        if ($post_status == 'draft') {
            $xml .= rest_to_line("    <app:control><app:draft>no</app:draft></app:control>");
        }
        else {
            $xml .= rest_to_line("    <app:control><app:draft>yes</app:draft></app:control>");
        }
        $xml .= rest_to_line("    <app:edited>" . atompub_to_date($post_modified) . "</app:edited>");

        if ($include_content) {
            $content = apply_filters('the_content', $post_content);
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

class EntryAtomPubFeed extends AtomPubFeed {
    function add_feed_links() {
    }
}

class ListAtomPubFeed extends AtomPubFeed {
    function ListAtomPubFeed(UrlGenerator $url_generator, PostType $post_type, $feed_id, $feed_title, $page_index, $page_count, $page_size) {
        parent::__construct($url_generator);

        $links = $this->create_feed_links($post_type, $page_index, $page_count, $page_size);

        $this->start_feed($feed_id, $feed_title);

        $search_result = OpenSearchSearchResults::fromWordpressResults($page_index, $page_count, $page_size);

        $this->response->add_body(rest_to_line($search_result->to_xml()));

        foreach($links as $link) {
            $this->response->add_body(rest_to_line("  " . $link->to_xml()));
        }
    }
}

?>
