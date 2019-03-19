# SEO Fields for Craft CMS 3.1

Fields for your meta & OG data, sitemaps & robots.txt

![Screenshot](https://www.studioespresso.co/resources/seofields/banner.png)

## Requirements

This plugin requires Craft CMS 3.1.0 or later.

## Installation

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

## Features overview

SEO Fields lets you set default title & description for your site (or sites) and gives you a field to overwrite those defaults per entry.

Additionally, you can configure your robots.txt and your sitemaps as well.

## Field

### 1. Your field
The plugin assumes that the handle of the `SEO Fields` field will be simply: __`seo`__. If it can't find that field, it won't output anything.

If you use a different handle for your field, simply copy [this file](src/config.php) to the `config` directory of your project and change the fieldhandle to your own. 

### 2. Templating
To put SEO Fields on your html, just add `{% hook 'seo-fields' %}` within the `<head>` tag in your layout. 
 
 
 If you want more control over what the plugin output, you can include your own snippet instead of the templat hook. Have a look at the [default template](src/templates/_meta.twig) to get you started.

## Robots
When you install the plugin, it enabled the robots.txt by default with [this template](src/templates/_placeholder/_robots.twig). Based on the environment you've set in your site's ``.env`` file, it will only allow indexing on `live` or `production` environments and block indexing on all others.

You can modify the template with your own enviroments, conditions and settings in the CP.

## Sitemap.xml

---
Brought to you by [Studio Espresso](https://studioespresso.co)
