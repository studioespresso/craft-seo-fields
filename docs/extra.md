---
title: Extra - SEO Fields
prev: false
next: false
---

# Extra

## Migrating from ether/seo
The plugin has a console command that will copy the content from an ``ether/seo`` field to a field from this plugin. 

**Step 1**: Install this plugin and create a field. Then assign to the field to the required sections (probably to each section that has one of the old fields).

**Step 2**: Run this console command:

```bash
# tell Craft to install the plugin
./craft seo-fields/migrate/ether
```

You can use pass the data on the old and new field like this:
```bash
# with parameters:
./craft seo-fields/migrate/ether --oldHandle=seo --newHandle=newSeo --siteId=1
```
If you ommit the ``siteId``, the command will loop through all sites in your install and will query all entries in each site.

### Title seperator
When you have entries that have a stray seperator symbol in title, we'll try to strip those before saving the new data.
You can optionally pass the seperator that is being using in the old plugin, then we'll split the title at the first occurence of that symbol and take anything before it as the title.
```bash
# with the titleSeperator parameter:
./craft seo-fields/migrate/ether --oldHandle=seo --newHandle=newSeo --titleSeperator="|"
```