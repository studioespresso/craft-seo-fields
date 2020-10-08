---
title: Templating - SEO Fields
prev: false
next: false
---

# Templating

## Template hook
To put SEO Fields on your html, just add `{% hook 'seo-fields' %}` within the `<head>` tag in your layout.

If you want more control over what the plugin output, you can include your own snippet instead of the templat hook. Have a look at the [default template](https://github.com/studioespresso/craft-seo-fields/blob/master/src/templates/_meta.twig) to get you started.

Note that the field looks for a field called `seo`. If it doesn't find that, it will not output anymore. 

[More information on that here.](field.html#field)

## Overwriting field values

Next to the site defaults and the field, you might also want to use text and images from another fields.
 
To do this, use the following twig functions:

````twig
{% do entry.seo.setMetaTitle('New title') %}
{% do entry.seo.setMetaDescription('custom meta description') %}
{% do entry.seo.setFacebookTitle('Facebook title') %}
{% do entry.seo.setFacebookDescription('custom description') %}
{% do entry.seo.setTwitterTitle('Twitter title') %}
{% do entry.seo.setTwitterDescription('Just for twitter') %}

// Overwriting the image fields can be done with an AssetQuery, an Asset itself, or with and Asset id
{% do entry.seo.setFacebookImage(entry.assetField) %}
{% do entry.seo.setTwitterImage(entry.assetField.one()) %}
````

This is a normal Twig template so the usual conditionals and operators work here. You can use these for the fields that need a text value. For the OG and Twitter images, you can pass an ``Asset`` to ``meta.getOgImage()``, eg: ```{% set ogImage = meta.getOgImage(entry.overviewImage.one()) %}```.

## Support for custom elements

Out of the box SEO Fields has support for:
 - Craft's Entries
 - Craft's Categories
 - Commerce [Products](https://plugins.craftcms.com/commerce) 
 - Solspce Calendar [Events](https://plugins.craftcms.com/calendar)
 
 *If you have a plugin on the store that has a custom element and you want SEO Fields to add support for it, feel free to [get in touch](mailto:jan@studioespresso.co)*
 
 If you have a custom element of your own and you want to add support for SEO Fields, pass the element's class to the following event:

```php
Event::on(SeoFields::class, SeoFields::EVENT_SEOFIELDS_REGISTER_ELEMENT,
    function (RegisterSeoElementEvent $event) use ($elements) {
        $event->elements = array_merge($event->elements,[
            Product::class,
        ]);
    }
);
```