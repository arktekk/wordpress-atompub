<?php
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

class ContentType {
    function ContentType($name) {
        $this->name = $name;
    }

    function __toString() {
        return $this->name;
    }
}

$ContentType_HTML = new ContentType("html");
$ContentType_ATOM = new ContentType("atom");
?>
