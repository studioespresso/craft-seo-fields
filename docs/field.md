---
title: Field - SEO Fields
prev: false
next: false
---
# Field

## Your field
The plugin assumes that the handle of the `SEO Fields` field will be simply: __`seo`__. If it can't find that field, it won't output anything.

If you use a different handle for your field, simply copy [this file](src/config.php) to the `config` directory of your project and change the fieldhandle to your own. 

### Field settings
In the field settings you can enable or disable the following:
- Show the general tab
- Show the facebook tab
- Show the twitter tab

Under the advanced options you can:
- Enable the option to hide the site title
- Enable the option to hide the site title

## Templating
To put SEO Fields on your html, just add `{% hook 'seo-fields' %}` within the `<head>` tag in your layout. 
 If you want more control over what the plugin output, you can include your own snippet instead of the templat hook. Have a look at the [default template](src/templates/_meta.twig) to get you started.
