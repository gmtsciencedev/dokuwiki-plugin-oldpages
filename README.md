# Dokuwiki oldpages plugin

This plugin is meant to track pages older than a certain age (we use for regulatory reason but one might use it for any purpose)

## Usage

Insert in a page:

`~~OLDPAGES|<root>|<age>~~`

Where <root> and <age> are place holders:

- <root> is the root folder where the search is performed, it is a wiki path like `playground:playground` (normally you'll put a namespace here not a single page),
- <age> is an number followed by d (days), m (months) or y (years) specifying the duration. 

For instance we use it like this:

`~~OLDPAGES|regulatory:smq|30m~~`

Which means look for page in the `regulatory:smq` namespace (or its subnamespaces) for pages older than 30 months. In our documentation system this is a bad thing so the default style is redish and alarming, but you can tune that by overriding the `oldpages-warning` CSS class in your template.

It is bilingual for now and support English and French.