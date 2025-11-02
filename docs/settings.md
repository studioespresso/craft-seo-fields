---
title: Plugin settings - SEO Fields
prev: false
next: false
head:
  - - meta
    - name: description
      content: hello
  - - meta
    - name: keywords
      content: super duper SEO
---
 
# Plugin settings

The following options can be set by copying [this file](https://github.com/studioespresso/craft-seo-fields/blob/develop/src/config.php) to your project's `config` directory, and renaming it to `seo-fields.php`.

## pluginLabel
How the plugin is labelled in the CP's sidebar, defaults to ``SEO``

## titleSeperator
Defaults to ``-``

## robotsPerSite
Whether robots.txt should be set per site, defautls to ``false``

## sitemapPerSite <Badge type="danger" text="Deprecated" />
Since 5.1.0, this setting is deprecated. Sitemaps are now always generated per site.

## fieldHandle
This is the handle your SEO Fields - field has, defaults to ``seo`` but make sure to change this setting should your field be named differently.

## notFoundLimit
The maximum number of "not found" or 404 items to track, defaults to 10000. 

## schemaOptions
Additional options to be listed in the Schema options per section.
This take an array of :
- the class of the Schema object (base on [spatie/schema-org](https://github.com/spatie/schema-org))
- the label you want it be listed as

For example:

````php
    "schemaOptions" => [
        get_class(\Spatie\SchemaOrg\Schema::recipe()) => 'Recipe'
    ],
````

## logicallySeperatedSiteGroups <Badge type="tip" text="^4.1.3" />
Depending on your Craft CMS setup, you may have multiple site groups that are logically separated from each other. For example, you may have a site group for your main website and another site group for a secondary brand.

If this is the case, you can enable the `logicallySeperatedSiteGroups` setting. This will ensure that alternate links will only be generated for sites within the same site group.


