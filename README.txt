= TODOs =

o Use the XML writer API to instead of creating gigantic strings.

= URL Design =

Types:

page index: positive integer
page type: enum: abc (post), xyz (page)

== Service Document ==

/?atompub=service

Returns a application/atomsvc+xml resource

== List of feeds ==

* List feed
* All children of a specific page

== List feed ==

A list of all posts of a type (post or page)

/?atompub=list&page_type=<page type>&page=<page index>

Mime type: application/atom+xml
Atom id: /?atompub&page_type=<page type>

== Children of a specific page ==

/?atompub=list&page_type=<page type>&page=<page index>&parent=<post id>

Mime type: application/atom+xml
Atom id: /?atompub&page_type=<page type>
