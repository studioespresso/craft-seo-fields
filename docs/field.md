# Field

## 1. Your field
The plugin assumes that the handle of the `SEO Fields` field will be simply: __`seo`__. If it can't find that field, it won't output anything.

If you use a different handle for your field, simply copy [this file](src/config.php) to the `config` directory of your project and change the fieldhandle to your own. 

## 2. Templating
To put SEO Fields on your html, just add `{% hook 'seo-fields' %}` within the `<head>` tag in your layout. 
 If you want more control over what the plugin output, you can include your own snippet instead of the templat hook. Have a look at the [default template](src/templates/_meta.twig) to get you started.
