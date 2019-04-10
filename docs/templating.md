---
title: Templating - SEO Fields
prev: false
next: false
---

# Templating

## Template hook
To put SEO Fields on your html, just add `{% hook 'seo-fields' %}` within the `<head>` tag in your layout.

If you want more control over what the plugin output, you can include your own snippet instead of the templat hook. Have a look at the [default template](src/templates/_meta.twig) to get you started.

Note that the field looks for a field called `seo`. If it doesn't find that, it will not output anymore. 

[More information on that here.](/craft-seo-fields/field.html#field)

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