---
title: Schema.org markup - SEO Fields
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
 
# Schema.org markup

## Schema type per section

In the Schema.org section of the plugin, you can set a Schema type for each of the sections defined in your site.

The fields from the SEO field (meta title, meta description, facebook image) will be used to populated the Schema and it will be added to the page in ``JSON-LD`` format.

<img src="/img/schema_org.png" alt="">

## Custom markup & template overrides
In case you want to specify the schema type yourself, overwrite the values or set additional properties, you can do so by following these steps.
### 1) Disable to plugin from rendering the schema data.
This can be done with the following snippet:

````twig
{# make sure you add this line above any layout you're extending #}
{% do entry.setShouldRenderSchema(false) %}
{% extend 'layout.twig' %}
````

### 2) Add you own schema tag

You can create a new Schema object through the ``seoFields`` variable in your template.
````twig
{# @var schema \Spatie\SchemaOrg\Schema #}
{% set schema = seoFields.schema %}
````
When using PHPStorm, it's recommended that you use the [Symfony Plugin](https://plugins.jetbrains.com/plugin/7219-symfony-plugin), to get proper autocompletion on the created object
Doing that will make it easier to add a script like the example below:

````twig
{# @var schema \Spatie\SchemaOrg\Schema #}
{% set schema = craft.schema %}
{{ schema.organization
    .name("Studio Espresso")
    .email("info@studioespresso.co")
|raw }}

````


## Add your own types through settings

Out of the box, you can set a section to one of the following types:
- <a href="https://schema.org/WebPage" target="_blank">WebPage</a>
- <a href="https://schema.org/Article" target="_blank">Article</a>
- <a href="https://schema.org/CreativeWork" target="_blank">CreativeWork</a>
- <a href="https://schema.org/Review" target="_blank">Review</a>
- <a href="https://schema.org/Organisation" target="_blank">Organisation</a>
- <a href="https://schema.org/Recipe" target="_blank">Recipe</a>
- <a href="https://schema.org/Person" target="_blank">Person</a>

You can extend this list by setting the ``schemaOptions`` attribute in the ``seo-fields.php`` settings file. You can find the syntax [here]() 

