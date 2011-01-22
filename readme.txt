=== AtomPub ===
Contributors: trygvis
Tags: comments, spam
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 1.0

== Description ==
This plugin allows you to access the Wordpress content over [AtomPub](http://tools.ietf.org/html/rfc5023) in a fairly generic way. It strives to be as standards compliant as possible. It can also act as a [Pubsubhubbub](http://code.google.com/p/pubsubhubbub/) publisher. While this plugin supports AtomPub it does currently not support publishing entries over AtomPub yet.

You can use it to turn your Wordpress installation into a CMS and use it as a backend for other applications that can handle AtomPub/Atom.

Note that the Wordpress Subversion repository only contain releases, development happens with [at github](http://github.com/arktekk/wordpress-atompub). Feel free to create tickets at the github repository if you find any bugs or want any improvements.

= Feed and Entry Structure =

The plugin exposes to collections that matches Wordpress' concepts: posts and pages. The feeds are paged and include [OpenSearch 1.1](http://www.opensearch.org/Specifications/OpenSearch/1.1) controls that a client can use to figure out how many entries that are in the feed.

= Extensions: OpenSearch 1.1 =

The feeds include the following OpenSearch elements: 

* totalResults
* startIndex
* itemsPerPage

= Extensions: Pubsubhubbub =


== Installation ==

To install simply upload the ZIP file through your administrative interface or unpack it under wp-content/plugins.

After installation and activation the AtomPub service document will be available under http://example.com/?atompub=service.

= Configuration =

After the plugin is installed an "AtomPub" entry will be available under "Settings" where you can configure the Pubsubhubbub hubs that Wordpress should ping when content is published.

== Changelog ==

= v1.0 - 2011-01-22 - First Release =

This was the first public release.

== Technical Details ==

This is a quick summary for developers, it is not necessary to understand if you're just using or installing the plugin.

= Internal types =

* page index: positive integer
* page type: enum: abc (post), xyz (page)

= TODOs =

o Use the XML writer API to instead of creating gigantic strings.

