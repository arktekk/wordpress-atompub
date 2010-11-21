<?php
class AtomPubResponse {
    var $headers = array();
    var $body;

    /**
     * @param string $header
     * @return AtomPubResponse
     */
    function set_header($header) {
        $this->headers[] = $header;

        return $this;
    }

    /**
     * @param string $body
     * @return AtomPubResponse
     */
    function with_body($body) {
        $this->body = $body;

        return $this;
    }

    /**
     * @param string $body
     * @return AtomPubResponse
     */
    function add_body($body) {
        $this->body .= $body;

        return $this;
    }

    function send() {
        foreach ($this->headers as $header) {
            header($header);
        }

        echo $this->body;
    }
}

?>
