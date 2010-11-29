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

    static function to_xml($href, $rel, $type = NULL) {
        $type = isset($type) ? "type='$type'" : "";
        return "<link rel='$rel' $type href='" . esc_url($href) . "'/>";
    }

    function __construct($href, $rel, $type) {
        $this->href = $href;
        $this->rel = $rel;
        $this->type = $type;
    }

    function __toString() {
        return self::to_xml($this->href, $this->rel, $this->type);
    }
}

abstract class AtomPubFeed {

    static function createChildrenFeed(UrlGenerator $url_generator, PostType $post_type, $post_id, $page_index, $page_count, $page_size) {
        return new ChildrenAtomPubFeed($url_generator, $post_type, $post_id, $page_index, $page_count, $page_size);
    }

    static function createListFeed(UrlGenerator $url_generator, PostType $post_type, $page_index, $page_count, $page_size) {
        return new ListAtomPubFeed($url_generator, $post_type, $page_index, $page_count, $page_size);
    }

    public static function createEntryFeed(UrlGenerator $url_generator, $post_type, $post_id) {
        return new EntryAtomPubFeed($url_generator, $post_type, $post_id);
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
        $xml .= rest_to_line("    <title type='$content_type'><![CDATA[$content]]></title>");

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

        $url = $this->url_generator->post_url($post->ID, $post_type, $ContentType_ATOM);
        $link = AtomPubLink::to_xml($url, "self", $ATOM_CONTENT_TYPE);
        $xml .= rest_to_line("    <!-- self=$url -->");
        $xml .= rest_to_line("    " . $link);

        $url = $this->url_generator->post_url($post_parent, $post_type, $ContentType_ATOM);
        $link = AtomPubLink::to_xml($url, "parent", $ATOM_CONTENT_TYPE);
        $xml .= rest_to_line("    <!-- parent=$url -->");
        $xml .= rest_to_line("    " . $link);

//        $url = $this->url_generator->child_posts_of($post->ID, 1, $post_type);
//        $link = AtomPubLink::to_xml($url, "urn:wordpress:children", $ATOM_CONTENT_TYPE);
//        $xml .= rest_to_line("    <!-- urn:wordpress:children=$url -->");
//        $xml .= rest_to_line("    " . $link);

        $xml .= rest_to_line("    <app:collection href='" . esc_url($this->url_generator->child_posts_of($post->ID, 1, $post_type)) . "'>");
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
            $xml .= rest_to_line("    <content type='$content_type'><![CDATA[$content]]></content>");
        }
        else {
            $xml .= rest_to_line("    <content src='" . esc_url($this->url_generator->post_url($post->ID, $post_type, $ContentType_HTML)) . "'/>");
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

class ListAtomPubFeed extends AtomPubFeed {
    function __construct(UrlGenerator $url_generator, PostType $post_type, $page_index, $page_count, $page_size) {
        global $ATOM_CONTENT_TYPE;

        parent::__construct($url_generator);

        $options = get_option('atompub_options');

        $feed_id = $url_generator->list_iri($post_type);
        $feed_title = "{$post_type}s List";

        $this->start_feed($feed_id, $feed_title);

        $search_result = OpenSearchSearchResults::fromWordpressResults($page_index, $page_count, $page_size);

        $this->response->add_body(rest_to_line($search_result->to_xml()));

        $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->list_url(1, $post_type), "first", $ATOM_CONTENT_TYPE)));
        if (($page_index + 1) > $page_count) {
            $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->list_url($page_index + 1, $post_type), "next", $ATOM_CONTENT_TYPE)));
        }
        if (($page_index - 1) < 1) {
            $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->list_url($page_index - 1, $post_type), "prev", $ATOM_CONTENT_TYPE)));
        }
        $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->list_url($page_count, $post_type), "last", $ATOM_CONTENT_TYPE)));

        $hub = $options["hub"];
        if(isset($hub)) {
            $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($hub, "hub")));
        }
    }
}

class ChildrenAtomPubFeed extends AtomPubFeed {
    function __construct(UrlGenerator $url_generator, PostType $post_type, $post_id, $page_index, $page_count, $page_size) {
        global $ATOM_CONTENT_TYPE;

        parent::__construct($url_generator);

        $feed_id = $url_generator->child_posts_iri($post_type, $post_id);
        $feed_title = "Children of #$post_id";

        $this->start_feed($feed_id, $feed_title);

        $search_result = OpenSearchSearchResults::fromWordpressResults($page_index, $page_count, $page_size);

        $this->response->add_body(rest_to_line($search_result->to_xml()));

        $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->child_posts_of($post_id, 1, $post_type), "first", $ATOM_CONTENT_TYPE)));
        if (($page_index + 1) > $page_count) {
            $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->child_posts_of($post_id, $page_index + 1, $post_type), "next", $ATOM_CONTENT_TYPE)));
        }
        if (($page_index - 1) < 1) {
            $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->child_posts_of($post_id, $page_index - 1, $post_type), "prev", $ATOM_CONTENT_TYPE)));
        }
        $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->child_posts_of($post_id, $page_count, $post_type), "last", $ATOM_CONTENT_TYPE)));
    }
}

class EntryAtomPubFeed extends AtomPubFeed {
    function __construct(UrlGenerator $url_generator, PostType $post_type, $post_id) {
        global $ATOM_CONTENT_TYPE;
        global $ContentType_ATOM;

        parent::__construct($url_generator);

        $feed_id = $url_generator->post_iri($post_type, $post_id);
        $feed_title = "Entry #$post_id";

        $this->start_feed($feed_id, $feed_title);

        $this->response->add_body(rest_to_line("  " . AtomPubLink::to_xml($this->url_generator->post_url($post_id, $post_type, $ContentType_ATOM), "self", $ATOM_CONTENT_TYPE)));
    }
}

?>
