---
title: General - SEO Fields
prev: false
next: false
---
# General

## 0. Requirements

This plugin requires Craft CMS 3.1.0 or later.

## 1. Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

```bash
# go to the project directory
cd /path/to/my-craft-project.dev

# tell Composer to install the plugin
composer require studioespresso/craft-seo-fields

# tell Craft to install the plugin
./craft install/plugin seo-fields
```

## 2. Defaults
After installing the plugin, you'll want to set defaults for your site.
You can set:
- a default site title (that will be added after the entry's title
- a title seperator to go between to entry title and the site title
- a default meta image.

<img src="./images/defaults.png" width="400">

If you have multiple sites in your install, you can specify different defaults for each site.

## 3. The field
Next up, you create an `SEO Fields` field and add it to your section's layout. More about the field and it's options can be found [here](field.html#field).

## 4. Rendering

Add `{% hook 'seo-fields' %}` to your layout.