---
title: General - SEO Fields
prev: false
next: false
---
# Usage

## 1.Setting defaults
After installing the plugin, you'll want to set defaults for your site.
You can set:
- a default site title (that will be added after the entry's title
- a title seperator to go between to entry title and the site title
- a default meta image.

<img src="./images/defaults.png">

If you have multiple sites in your install, you can specify different defaults for each site.

## 2. The field
Next up, you create an `SEO Fields` field and add it to your section's layout. More about the field and it's options can be found [here](field.html#field).

## 3. Rendering

Add `{% hook 'seo-fields' %}` to your layout.